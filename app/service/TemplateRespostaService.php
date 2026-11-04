<?php
/**
 * Respostas do template de confirmacao por WhatsApp (04/11/2026): duas respostas definidas pelo usuario e, para cada uma, o
 * template de envio automatico, obrigatoriamente da categoria certa. O e-mail nao tem nada disso: o cliente responde pelos
 * links da mensagem. Quem identifica a resposta e envia e o ApchatRespostaService. Usa a transacao aberta.
 */
class TemplateRespostaService
{
    /** As duas respostas: a coluna do template enviado depois e a categoria que ele tem de ter. */
    const RAMOS = [
        'confirmado' => ['coluna' => 'template_confirmado_id', 'resposta' => 'respostas_confirmar', 'categoria' => TemplateEscritorio::CONFIRMADO],
        'cancelado'  => ['coluna' => 'template_cancelado_id', 'resposta' => 'respostas_cancelar', 'categoria' => TemplateEscritorio::CANCELAMENTO],
    ];

    public static function ehConfirmacaoWhatsapp(TemplateEscritorio $template): bool
    {
        return $template->categoria === TemplateEscritorio::CONFIRMACAO && $template->tipo_template === TemplateEscritorio::WHATSAPP;
    }

    /** Filtro das opcoes de template de resposta: WhatsApp, habilitado, da categoria certa, do escritorio do usuario. */
    public static function criteriaDoRamo(string $ramo): TCriteria
    {
        $criteria = new TCriteria();
        $criteria->add(new TFilter('tipo_template', '=', TemplateEscritorio::WHATSAPP));
        $criteria->add(new TFilter('categoria', '=', self::RAMOS[$ramo]['categoria']));
        $criteria->add(new TFilter('habilitado', '=', 'T'));
        $criteria->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '" . (int) TSession::getValue('userunitid') . "')"));

        return $criteria;
    }

    /** As opcoes do combo: [id => descricao]. */
    public static function itensDoRamo(string $ramo): array
    {
        $criteria = self::criteriaDoRamo($ramo);
        $criteria->setProperty('order', 'descricao');

        $itens = [];
        foreach (TemplateEscritorio::getObjects($criteria) ?: [] as $template)
        {
            $itens[$template->id] = $template->descricao;
        }

        return $itens;
    }

    /** Confirmacao por WhatsApp: exige as duas respostas e os dois templates. Qualquer outro template: nada disso fica gravado. */
    public static function validar(TemplateEscritorio $template): void
    {
        if (!self::ehConfirmacaoWhatsapp($template))
        {
            $template->respostas_confirmar = null;
            $template->respostas_cancelar = null;
            $template->ignorar_maiusculas = 'T';
            $template->template_confirmado_id = null;
            $template->template_cancelado_id = null;
            return;
        }

        $ignorar = $template->ignorar_maiusculas !== 'F';
        $comparaveis = [];

        foreach (self::RAMOS as $ramo)
        {
            $template->{$ramo['resposta']} = trim((string) $template->{$ramo['resposta']});
            $comparaveis[] = ApchatRespostaService::respostaComparavel($template->{$ramo['resposta']}, $ignorar);

            if (end($comparaveis) === '')
            {
                throw new Exception('Defina as duas respostas do cliente, com pelo menos uma letra ou número.');
            }
        }

        if ($comparaveis[0] === $comparaveis[1])
        {
            throw new Exception('As duas respostas são iguais' . ($ignorar ? ' quando maiúsculas, minúsculas e acentos são ignorados' : '') . '. Defina respostas diferentes.');
        }

        foreach (self::RAMOS as $ramo)
        {
            $resposta = $template->{$ramo['resposta']};
            $id = (int) $template->{$ramo['coluna']};

            if (!$id)
            {
                throw new Exception("Escolha o template enviado depois da resposta \"{$resposta}\".");
            }

            $enviado = TemplateEscritorio::find($id);

            if (!$enviado || (int) $enviado->escritorio_id !== (int) $template->escritorio_id
                || $enviado->tipo_template !== TemplateEscritorio::WHATSAPP || $enviado->categoria !== $ramo['categoria'] || $enviado->habilitado !== 'T')
            {
                throw new Exception("O template enviado depois da resposta \"{$resposta}\" tem de ser de WhatsApp, habilitado e da categoria " . TemplateEscritorio::CATEGORIAS[$ramo['categoria']] . '.');
            }
        }
    }

    /** O template e a resposta automatica de alguma confirmacao: nao pode sair da categoria, do WhatsApp nem ser desabilitado. */
    public static function exigirLivreParaMudar(TemplateEscritorio $gravado, TemplateEscritorio $novo): void
    {
        if ($novo->categoria !== $gravado->categoria || $novo->tipo_template !== $gravado->tipo_template || $novo->habilitado !== 'T')
        {
            self::exigirSemUso($gravado, 'mudar a categoria ou o tipo ou desabilitar');
        }
    }

    public static function exigirSemUso(TemplateEscritorio $template, string $acao = 'excluir'): void
    {
        foreach (self::confirmacoes((int) $template->escritorio_id) as $confirmacao)
        {
            foreach (self::RAMOS as $ramo)
            {
                if ((int) $confirmacao->{$ramo['coluna']} === (int) $template->id)
                {
                    throw new Exception("Este template é a resposta automática da confirmação \"{$confirmacao->descricao}\". Troque lá antes de {$acao}.");
                }
            }
        }
    }

    /** Para o formulario: de que confirmacao este template e resposta. HTML ja escapado. */
    public static function usadoEm(TemplateEscritorio $template): array
    {
        $usos = [];

        foreach (self::confirmacoes((int) $template->escritorio_id) as $confirmacao)
        {
            foreach (self::RAMOS as $ramo)
            {
                if ((int) $confirmacao->{$ramo['coluna']} === (int) $template->id)
                {
                    $usos[] = 'Enviado quando o cliente responde <b>' . self::e($confirmacao->{$ramo['resposta']}) . '</b> à confirmação <b>' . self::e($confirmacao->descricao) . '</b>';
                }
            }
        }

        return $usos;
    }

    /** Coluna da listagem. So texto: a linha inteira ja abre o formulario. */
    public static function colunaDaLista(TemplateEscritorio $template): string
    {
        if (self::ehConfirmacaoWhatsapp($template))
        {
            $linhas = [];
            foreach (self::RAMOS as $ramo)
            {
                $enviado = $template->{$ramo['coluna']} ? TemplateEscritorio::find((int) $template->{$ramo['coluna']}) : null;
                $linhas[] = '<b>' . self::e($template->{$ramo['resposta']}) . '</b> → ' . ($enviado ? self::e($enviado->descricao) : '<span style="color:#b26a00">sem template</span>');
            }
            return implode('<br>', $linhas);
        }

        $usos = [];
        foreach (self::confirmacoes((int) $template->escritorio_id) as $confirmacao)
        {
            foreach (self::RAMOS as $ramo)
            {
                if ((int) $confirmacao->{$ramo['coluna']} === (int) $template->id)
                {
                    $usos[] = 'Resposta ao <b>' . self::e($confirmacao->{$ramo['resposta']}) . '</b> de ' . self::e($confirmacao->descricao);
                }
            }
        }

        return implode('<br>', $usos);
    }

    private static function confirmacoes(int $escritorioId): array
    {
        return TemplateEscritorio::where('escritorio_id', '=', $escritorioId)
                                 ->where('tipo_template', '=', TemplateEscritorio::WHATSAPP)
                                 ->where('categoria', '=', TemplateEscritorio::CONFIRMACAO)
                                 ->orderBy('id')
                                 ->load() ?: [];
    }

    private static function e($texto): string
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
    }
}
