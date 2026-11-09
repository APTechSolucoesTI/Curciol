<?php
/**
 * Template de e-mail (09/11/2026): assinatura (padrao do escritorio, outra ou nenhuma), anexos e botoes da confirmacao,
 * e a previa do formulario. O TemplateEscritorioForm so chama daqui. Usa a transacao aberta.
 */
class TemplateEmailService
{
    const MODOS = [TemplateEscritorio::ASSINATURA_PADRAO, TemplateEscritorio::ASSINATURA_OUTRA, TemplateEscritorio::ASSINATURA_NENHUMA];

    /**
     * Grava assinatura, anexos e botoes do template ja salvo, a partir do que veio do formulario. Template de WhatsApp nao
     * guarda nada disso.
     */
    public static function salvarExtras(TemplateEscritorio $template, array $param): void
    {
        if ($template->tipo_template !== TemplateEscritorio::EMAIL)
        {
            $template->assinatura_modo = TemplateEscritorio::ASSINATURA_PADRAO;
            $template->assinatura_id = null;
            $template->botao_confirmar_texto = null;
            $template->botao_confirmar_cor = null;
            $template->botao_cancelar_texto = null;
            $template->botao_cancelar_cor = null;
            $template->store();
            TemplateEscritorioAnexo::where('template_escritorio_id', '=', (int) $template->id)->delete();
            return;
        }

        // ---------- assinatura ----------
        $modo = in_array($param['assinatura_modo'] ?? '', self::MODOS, true) ? $param['assinatura_modo'] : TemplateEscritorio::ASSINATURA_PADRAO;
        $template->assinatura_modo = $modo;

        if ($modo === TemplateEscritorio::ASSINATURA_OUTRA)
        {
            // o objeto do formulario nao traz assinatura_id: vale a gravada
            if (!$template->assinatura_id && $template->id && ($gravado = TemplateEscritorio::find((int) $template->id)))
            {
                $template->assinatura_id = $gravado->assinatura_id;
            }

            $arquivo = EmailUploadCampo::arquivo($param['assinatura_arquivo'] ?? null);

            if ($arquivo)
            {
                $template->assinatura_id = (int) EmailAssinaturaService::criarDeArquivo($arquivo[0], $arquivo[1])->id;
            }
            elseif (!$template->assinatura_id)
            {
                throw new Exception('Escolha o arquivo da assinatura (opção "Outra").');
            }
        }
        else
        {
            $template->assinatura_id = null;
        }

        // ---------- botoes (so contam na confirmacao, mas ficam guardados) ----------
        $texto = function ($valor, string $padrao) { $v = trim((string) $valor); return $v !== '' ? mb_substr($v, 0, 60) : $padrao; };
        $cor = function ($valor, string $padrao) { $v = trim((string) $valor); return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : $padrao; };
        $template->botao_confirmar_texto = $texto($param['botao_confirmar_texto'] ?? $template->botao_confirmar_texto, EmailMontagemService::CONFIRMAR_TEXTO);
        $template->botao_confirmar_cor = $cor($param['botao_confirmar_cor'] ?? $template->botao_confirmar_cor, EmailMontagemService::CONFIRMAR_COR);
        $template->botao_cancelar_texto = $texto($param['botao_cancelar_texto'] ?? $template->botao_cancelar_texto, EmailMontagemService::CANCELAR_TEXTO);
        $template->botao_cancelar_cor = $cor($param['botao_cancelar_cor'] ?? $template->botao_cancelar_cor, EmailMontagemService::CANCELAR_COR);
        $template->store();

        // ---------- anexos: os marcados continuam, os novos entram ----------
        $atuais = self::anexosDoTemplate((int) $template->id);

        if (array_key_exists('anexos_manter', $param))
        {
            $manter = array_map('intval', (array) $param['anexos_manter']);
            $manter = array_values(array_filter($atuais, function ($id) use ($manter) { return in_array($id, $manter, true); }));
        }
        else
        {
            $manter = $atuais;
        }

        foreach (EmailUploadCampo::arquivos($param['anexos_novos'] ?? null) as [$caminho, $nome])
        {
            $manter[] = (int) EmailAnexoService::criarDeArquivo($caminho, $nome)->id;
        }

        EmailAnexoService::validarTotal($manter);

        TemplateEscritorioAnexo::where('template_escritorio_id', '=', (int) $template->id)->delete();
        foreach ($manter as $ordem => $anexoId)
        {
            $ligacao = new TemplateEscritorioAnexo();
            $ligacao->template_escritorio_id = (int) $template->id;
            $ligacao->email_anexo_id = $anexoId;
            $ligacao->ordem = $ordem;
            $ligacao->store();
        }
    }

    /** Ids dos anexos do template, na ordem. */
    public static function anexosDoTemplate(int $templateId): array
    {
        if ($templateId <= 0)
        {
            return [];
        }

        return array_map(function ($l) { return (int) $l->email_anexo_id; },
                         TemplateEscritorioAnexo::where('template_escritorio_id', '=', $templateId)->orderBy('ordem')->orderBy('id')->load() ?: []);
    }

    /**
     * Previa do formulario do template: assunto, texto com as variaveis destacadas, botoes (sem link de verdade), assinatura
     * que vale e anexos. Sem agendamento por tras, as variaveis ficam como estao. WhatsApp: vazio.
     */
    public static function previa(array $param): string
    {
        if (($param['tipo_template'] ?? '') !== TemplateEscritorio::EMAIL)
        {
            return '';
        }

        $gravado = !empty($param['id']) ? TemplateEscritorio::find((int) $param['id']) : null;

        $template = new TemplateEscritorio();
        $template->tipo_template = TemplateEscritorio::EMAIL;
        $template->categoria = (string) ($param['categoria'] ?? '');
        $template->template = (string) ($param['template'] ?? '');
        foreach (['botao_confirmar_texto', 'botao_confirmar_cor', 'botao_cancelar_texto', 'botao_cancelar_cor'] as $campo)
        {
            $template->$campo = $param[$campo] ?? null;
        }

        $avisos = [];
        if (EmailMontagemService::usaLinksAntigos($template))
        {
            $avisos[] = 'Este template usa os links no texto ({$link_confirmacao} / {$link_cancelamento}), então os botões não entram. '
                      . 'Para usar os botões, apague esses links do texto.';
        }

        $texto = self::destacarVariaveis((string) $template->template);
        $texto = EmailMontagemService::comBotoes($texto, $template);
        $texto = str_replace(['href="{$link_confirmacao}"', 'href="{$link_cancelamento}"'], 'href="#"', $texto);

        // assinatura que vale agora
        $modo = in_array($param['assinatura_modo'] ?? '', self::MODOS, true) ? $param['assinatura_modo'] : TemplateEscritorio::ASSINATURA_PADRAO;
        $escritorio = Escritorio::findByUnitId(TSession::getValue('userunitid'));
        $doEscritorio = $escritorio ? EmailAssinaturaService::doEscritorio((int) $escritorio->id) : null;
        $assinaturaId = EmailAssinaturaService::resolver($modo, $gravado ? ((int) $gravado->assinatura_id ?: null) : null, $doEscritorio);
        $novoArquivo = $modo === TemplateEscritorio::ASSINATURA_OUTRA && trim(is_string($param['assinatura_arquivo'] ?? null) ? $param['assinatura_arquivo'] : '') !== '';

        if ($novoArquivo)
        {
            $avisos[] = 'A assinatura nova aparece na prévia depois de salvar o template.';
        }
        elseif ($modo === TemplateEscritorio::ASSINATURA_OUTRA && !$assinaturaId)
        {
            $avisos[] = 'Escolha o arquivo da assinatura (opção "Outra").';
        }
        elseif ($modo === TemplateEscritorio::ASSINATURA_PADRAO && !$doEscritorio)
        {
            $avisos[] = 'O escritório ainda não tem assinatura: o e-mail sai sem. Configure em Escritório › Configurações › E-mail.';
        }

        $assinatura = !$novoArquivo && $assinaturaId ? EmailAssinatura::find($assinaturaId) : null;
        $corpo = EmailMontagemService::corpo($texto, $assinatura, '');

        // anexos: os marcados que ja estao no template e os que acabaram de ser escolhidos
        $mantidos = array_key_exists('anexos_manter', $param) ? array_map('intval', (array) $param['anexos_manter']) : self::anexosDoTemplate($gravado ? (int) $gravado->id : 0);
        $novos = '';
        foreach ((array) ($param['anexos_novos'] ?? []) as $item)
        {
            $nome = preg_replace('/^[0-9a-f]{13}_/', '', basename((string) $item));
            if (trim($nome) !== '' && strpos($nome, '{') === false && strpos($nome, '%7B') === false)
            {
                $novos .= '<span style="display:inline-block;border:1px dashed #bbb;border-radius:4px;padding:3px 8px;margin:0 6px 6px 0;">'
                        . '<i class="fas fa-paperclip"></i> ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . ' <span style="color:#777">(novo)</span></span>';
            }
        }

        $html = EmailMontagemService::previa((string) ($param['titulo'] ?? ''), $corpo, $mantidos);
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

    /** Destaca {$variavel} so no texto (nunca dentro de uma tag ou atributo). */
    private static function destacarVariaveis(string $html): string
    {
        $partes = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($partes as $i => $parte)
        {
            if ($parte === '' || $parte[0] === '<')
            {
                continue;
            }
            $partes[$i] = preg_replace('/\{\$(?!botoes\})[a-z_]+\}/i', '<span style="background:#fff3cd;border-radius:3px;padding:0 2px;">$0</span>', $parte);
        }

        return implode('', $partes);
    }
}
