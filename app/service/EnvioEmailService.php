<?php
/**
 * E-mail pela tela do agendamento (09/11/2026): assinatura e anexos do envio.
 *
 * Sem Manual sai o que o template tem. Com Manual o usuario escolhe a assinatura so deste envio (a padrao do escritorio, a do
 * template, outro arquivo ou nenhuma) e tira ou acrescenta anexos; o template nao muda. A previa (Visualizar e mensagem
 * pronta) monta o mesmo corpo do envio, com o endereco relativo das imagens e sem criar nada.
 */
class EnvioEmailService
{
    const MODO_PADRAO   = 'PADRAO';
    const MODO_TEMPLATE = 'TEMPLATE';
    const MODO_ARQUIVO  = 'ARQUIVO';
    const MODO_NENHUMA  = 'NENHUMA';

    /** Opcoes do combo Assinatura: [valor => rotulo]. "Do template" so quando o template tem assinatura propria. */
    public static function opcoesAssinatura(?TemplateEscritorio $template): array
    {
        $opcoes = [self::MODO_PADRAO => 'Padrão do escritório'];

        if ($template && $template->assinatura_modo === TemplateEscritorio::ASSINATURA_OUTRA && $template->assinatura_id
            && ($assinatura = EmailAssinatura::find((int) $template->assinatura_id)))
        {
            $opcoes[self::MODO_TEMPLATE] = 'Do template: ' . $assinatura->nome_original;
        }

        $opcoes[self::MODO_ARQUIVO] = 'Outro arquivo (só neste envio)';
        $opcoes[self::MODO_NENHUMA] = 'Sem assinatura';

        return $opcoes;
    }

    /** Valor inicial do combo: a escolha do template. */
    public static function modoInicial(?TemplateEscritorio $template): string
    {
        if (!$template)
        {
            return self::MODO_PADRAO;
        }
        if ($template->assinatura_modo === TemplateEscritorio::ASSINATURA_NENHUMA)
        {
            return self::MODO_NENHUMA;
        }

        return isset(self::opcoesAssinatura($template)[self::MODO_TEMPLATE]) ? self::MODO_TEMPLATE : self::MODO_PADRAO;
    }

    /** Anexos do template: [id => "nome (tamanho)"] (texto ja escapado). */
    public static function anexosDoTemplate(?TemplateEscritorio $template): array
    {
        $itens = [];
        foreach ($template ? TemplateEmailService::anexosDoTemplate((int) $template->id) : [] as $id)
        {
            if ($anexo = EmailAnexo::find($id))
            {
                $itens[$id] = htmlspecialchars((string) $anexo->nome_original, ENT_QUOTES, 'UTF-8') . ' (' . EmailAnexoService::tamanhoLegivel((int) $anexo->tamanho) . ')';
            }
        }

        return $itens;
    }

    /**
     * Assinatura e anexos que saem. Arquivos novos do Manual viram registros aqui. Usa a transacao aberta.
     *
     * @return array ['assinatura_id' => ?int, 'anexo_ids' => int[]]
     */
    public static function escolha(Agendamento $agendamento, ?TemplateEscritorio $template, bool $manual, array $param): array
    {
        [$assinaturaId, $arquivo] = self::assinaturaDaEscolha($agendamento, $template, $manual, $param);

        if ($arquivo)
        {
            $assinaturaId = (int) EmailAssinaturaService::criarDeArquivo($arquivo[0], $arquivo[1])->id;
        }

        $anexos = self::anexosMantidos($template, $manual, $param);
        if ($manual)
        {
            foreach (EmailUploadCampo::arquivos($param['anexos_novos'] ?? null) as [$caminho, $nome])
            {
                $anexos[] = (int) EmailAnexoService::criarDeArquivo($caminho, $nome)->id;
            }
        }

        EmailAnexoService::validarTotal($anexos);

        return ['assinatura_id' => $assinaturaId, 'anexo_ids' => $anexos];
    }

    /** O e-mail como sai para este agendamento: assunto e corpo com as variaveis trocadas, botoes, assinatura e anexos. */
    public static function previa(Agendamento $agendamento, ?TemplateEscritorio $template, bool $manual, string $titulo, string $texto, array $param): string
    {
        $avisos = [];

        try
        {
            [$assinaturaId, $arquivo] = self::assinaturaDaEscolha($agendamento, $template, $manual, $param);
        }
        catch (Exception $e)
        {
            [$assinaturaId, $arquivo] = [null, null];
            $avisos[] = $e->getMessage();
        }

        if ($arquivo)
        {
            $avisos[] = 'A assinatura do arquivo escolhido (' . $arquivo[1] . ') entra no fim do e-mail ao enviar.';
        }

        $assinatura = $assinaturaId ? EmailAssinatura::find($assinaturaId) : null;
        $corpo = EmailMontagemService::corpo(TemplateEscritorio::replace($agendamento, EmailMontagemService::comBotoes($texto, $template)), $assinatura, '');
        $html = EmailMontagemService::previa(TemplateEscritorio::replace($agendamento, $titulo), $corpo, self::anexosMantidos($template, $manual, $param));

        $novos = '';
        if ($manual)
        {
            foreach ((array) ($param['anexos_novos'] ?? []) as $item)
            {
                $nome = preg_replace('/^[0-9a-f]{13}_/', '', basename((string) $item));
                if (trim($nome) !== '' && strpos($nome, '{') === false && strpos($nome, '%7B') === false)
                {
                    $novos .= '<span style="display:inline-block;border:1px dashed #bbb;border-radius:4px;padding:3px 8px;margin:0 6px 6px 0;">'
                            . '<i class="fas fa-paperclip"></i> ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . ' <span style="color:#777">(novo)</span></span>';
                }
            }
        }
        if ($novos !== '')
        {
            $html = preg_replace('#</div>$#', '<div style="margin-top:6px;">' . $novos . '</div></div>', $html, 1);
        }

        $aviso = '';
        foreach ($avisos as $a)
        {
            $aviso .= '<div class="alert alert-warning" style="margin:0 0 8px;padding:6px 10px;">' . htmlspecialchars($a, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        return $aviso . $html;
    }

    // =================================================================
    // Apoio
    // =================================================================

    /** @return array [assinatura_id ja existente ou null, arquivo novo [caminho, nome] ou null] */
    private static function assinaturaDaEscolha(Agendamento $agendamento, ?TemplateEscritorio $template, bool $manual, array $param): array
    {
        $doEscritorio = EmailAssinaturaService::doEscritorio((int) $agendamento->agenda->escritorio_id);
        $doTemplate = $template && $template->assinatura_id ? (int) $template->assinatura_id : null;

        if (!$manual)
        {
            $modo = $template ? ($template->assinatura_modo ?: TemplateEscritorio::ASSINATURA_PADRAO) : TemplateEscritorio::ASSINATURA_PADRAO;
            return [EmailAssinaturaService::resolver($modo, $doTemplate, $doEscritorio), null];
        }

        switch ((string) ($param['assinatura_modo'] ?? self::modoInicial($template)))
        {
            case self::MODO_NENHUMA:
                return [null, null];
            case self::MODO_TEMPLATE:
                return [isset(self::opcoesAssinatura($template)[self::MODO_TEMPLATE]) ? $doTemplate : $doEscritorio, null];
            case self::MODO_ARQUIVO:
                $arquivo = EmailUploadCampo::arquivo($param['assinatura_arquivo'] ?? null);
                if (!$arquivo)
                {
                    throw new Exception('Escolha o arquivo da assinatura (opção "Outro arquivo").');
                }
                return [null, $arquivo];
            default:
                return [$doEscritorio, null];
        }
    }

    /** Anexos do template que continuam: sem Manual, todos; com Manual, so os marcados (nunca um de fora do template). */
    private static function anexosMantidos(?TemplateEscritorio $template, bool $manual, array $param): array
    {
        $doTemplate = $template ? TemplateEmailService::anexosDoTemplate((int) $template->id) : [];

        if (!$manual || (!array_key_exists('anexos_template', $param) && empty($param['anexos_template_enviado'])))
        {
            return $doTemplate;
        }

        $marcados = array_map('intval', (array) ($param['anexos_template'] ?? []));

        return array_values(array_filter($doTemplate, function ($id) use ($marcados) { return in_array($id, $marcados, true); }));
    }
}
