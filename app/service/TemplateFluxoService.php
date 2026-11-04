<?php
/**
 * Fluxo da confirmacao por WhatsApp visto de ponta a ponta (04/11/2026): cada template de confirmacao guarda as respostas
 * validas e as mensagens que vem depois (TemplateEscritorio::template_confirmado_id, template_cancelado_id e
 * template_lembrete_id). Aqui so se le esse vinculo para mostrar na listagem e nos formularios, e se liga uma mensagem
 * criada a partir do fluxo. Quem executa o fluxo e o ApchatRespostaService. Usa a transacao aberta.
 */
class TemplateFluxoService
{
    /** Os pontos do fluxo que recebem uma mensagem. */
    const RAMOS = [
        'confirmado' => ['coluna' => 'template_confirmado_id', 'categoria' => TemplateEscritorio::CONFIRMADO],
        'cancelado'  => ['coluna' => 'template_cancelado_id', 'categoria' => TemplateEscritorio::CANCELAMENTO],
        'lembrete'   => ['coluna' => 'template_lembrete_id', 'categoria' => TemplateEscritorio::LEMBRETE],
    ];

    /** Os templates de confirmacao por WhatsApp do escritorio: cada um e um fluxo. */
    public static function fluxos(int $escritorioId): array
    {
        return TemplateEscritorio::where('escritorio_id', '=', $escritorioId)
                                 ->where('tipo_template', '=', TemplateEscritorio::WHATSAPP)
                                 ->where('categoria', '=', TemplateEscritorio::CONFIRMACAO)
                                 ->orderBy('id')
                                 ->load() ?: [];
    }

    /** O template de confirmacao, se ele for mesmo o inicio de um fluxo do escritorio. */
    public static function confirmacao(int $id, int $escritorioId): ?TemplateEscritorio
    {
        $template = TemplateEscritorio::find($id);

        return $template && (int) $template->escritorio_id === $escritorioId
            && $template->tipo_template === TemplateEscritorio::WHATSAPP && $template->categoria === TemplateEscritorio::CONFIRMACAO ? $template : null;
    }

    /** Mensagem nova criada a partir do fluxo: passa a ser a mensagem daquele ponto. */
    public static function ligar(int $confirmacaoId, string $ramo, TemplateEscritorio $mensagem): void
    {
        $confirmacao = self::confirmacao($confirmacaoId, (int) $mensagem->escritorio_id);

        if (!$confirmacao || !isset(self::RAMOS[$ramo]))
        {
            throw new Exception('Confirmação do fluxo não encontrada.');
        }

        $confirmacao->{self::RAMOS[$ramo]['coluna']} = $mensagem->id;
        $confirmacao->store();
    }

    /** As respostas como foram escritas (uma por linha), sem repetir. */
    public static function respostas(?string $lista): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', (string) $lista)), 'strlen')));
    }

    /** Para o formulario: onde esta mensagem entra no fluxo (texto com as respostas e a confirmacao de cada vinculo). */
    public static function usadoEm(TemplateEscritorio $template): array
    {
        $usos = [];

        foreach (self::fluxos((int) $template->escritorio_id) as $fluxo)
        {
            foreach (array_keys(self::RAMOS) as $ramo)
            {
                if ((int) $fluxo->{self::RAMOS[$ramo]['coluna']} === (int) $template->id)
                {
                    $usos[] = self::fraseDoRamo($fluxo, $ramo);
                }
            }
        }

        return $usos;
    }

    /** "Enviada quando o cliente responde sim a CONFIRMACAO". HTML ja escapado. */
    public static function fraseDoRamo(TemplateEscritorio $fluxo, string $ramo): string
    {
        $nome = self::e($fluxo->descricao);

        if ($ramo === 'lembrete')
        {
            return "Lembrete que já vem escolhido na tela de envio depois de confirmar: <b>{$nome}</b>";
        }

        $lista = $ramo === 'confirmado' ? $fluxo->respostas_confirmar : $fluxo->respostas_cancelar;
        $respostas = implode(' / ', array_map([self::class, 'e'], self::respostas($lista))) ?: 'a resposta definida';

        return "Enviada quando o cliente responde <b>{$respostas}</b> à confirmação <b>{$nome}</b>";
    }

    /** Coluna da listagem: o que o template tem de vinculo com os outros. So texto: a linha inteira ja abre o formulario. */
    public static function vinculoDaLista(TemplateEscritorio $template): string
    {
        if ($template->categoria === TemplateEscritorio::CONFIRMACAO)
        {
            if ($template->tipo_template !== TemplateEscritorio::WHATSAPP)
            {
                return '<span style="color:#777">O cliente responde pelos links do e-mail</span>';
            }

            $linhas = [];
            foreach (['confirmado' => $template->respostas_confirmar, 'cancelado' => $template->respostas_cancelar] as $ramo => $lista)
            {
                $respostas = implode(' / ', array_map([self::class, 'e'], self::respostas($lista))) ?: 'sem resposta definida';
                $filho = self::mensagem($template, $ramo);
                $linhas[] = "<b>{$respostas}</b> → " . ($filho ? self::e($filho->descricao) : '<span style="color:#b26a00">sem mensagem</span>');
            }
            return implode('<br>', $linhas);
        }

        $usos = [];
        foreach (self::fluxos((int) $template->escritorio_id) as $fluxo)
        {
            foreach (['confirmado' => $fluxo->respostas_confirmar, 'cancelado' => $fluxo->respostas_cancelar] as $ramo => $lista)
            {
                if ((int) $fluxo->{self::RAMOS[$ramo]['coluna']} === (int) $template->id)
                {
                    $usos[] = 'Resposta ao <b>' . (implode(' / ', array_map([self::class, 'e'], self::respostas($lista))) ?: 'sem resposta definida') . '</b> de ' . self::e($fluxo->descricao);
                }
            }
            if ((int) $fluxo->template_lembrete_id === (int) $template->id)
            {
                $usos[] = 'Lembrete de ' . self::e($fluxo->descricao);
            }
        }

        return $usos ? implode('<br>', $usos) : '<span style="color:#777">Sem vínculo</span>';
    }

    /** Painel no topo da listagem: um quadro por confirmacao, com o caminho de cada resposta. */
    public static function painel(int $escritorioId): string
    {
        $quadros = '';
        foreach (self::fluxos($escritorioId) as $fluxo)
        {
            $quadros .= self::quadro($fluxo);
        }
        if ($quadros === '')
        {
            $quadros = '<div class="tf-fluxo">Nenhuma confirmação por WhatsApp cadastrada. Comece pelo <b>Novo fluxo</b>.</div>';
        }

        return '<style>
            .tf-painel { background:#fff; border:1px solid #ddd; border-radius:4px; margin-bottom:12px; padding:12px 14px; }
            .tf-topo { display:flex; flex-wrap:wrap; gap:8px; justify-content:space-between; align-items:flex-start; }
            .tf-topo p { margin:2px 0 0; color:#555; font-size:13px; max-width:860px; }
            .tf-fluxo { margin-top:12px; border-top:1px solid #eee; padding-top:10px; font-size:13px; }
            .tf-envio { margin-bottom:6px; }
            .tf-rolagem { overflow-x:auto; }
            .tf-tabela { width:100%; min-width:640px; border-collapse:collapse; }
            .tf-tabela th { padding:5px 8px; border-bottom:2px solid #ddd; color:#555; font-weight:600; text-align:left; }
            .tf-tabela td { padding:7px 8px; border-bottom:1px solid #eee; vertical-align:top; }
            .tf-resposta { display:inline-block; margin:0 3px 2px 0; padding:1px 8px; border:1px solid #bbb; border-radius:10px; background:#f7f7f7; }
            .tf-sim { color:#2e7d32; font-weight:600; }
            .tf-nao { color:#c62828; font-weight:600; }
            .tf-aviso { color:#b26a00; }
            .tf-nota { color:#777; font-size:12px; }
            .tf-lembrete { margin-top:7px; }
        </style>
        <div class="tf-painel">
            <div class="tf-topo">
                <div><b style="font-size:15px">Fluxo da confirmação por WhatsApp</b>
                <p>O sistema envia a confirmação, o cliente responde e, conforme a resposta, o agendamento muda e sai a mensagem seguinte. Clique em uma mensagem para editar. Vale a resposta inteira: uma frase com a palavra no meio não conta. Por e-mail não há respostas para definir: o cliente confirma ou cancela pelos links da mensagem.</p></div>
                ' . self::link('<i class="fas fa-plus" style="color:#4CAF50"></i> Novo fluxo', 'onShow', ['fluxo_novo' => 1], 'btn btn-sm btn-default') . '
            </div>
            ' . $quadros . '
        </div>';
    }

    private static function quadro(TemplateEscritorio $fluxo): string
    {
        $desabilitada = $fluxo->habilitado !== 'T' ? ' <span class="tf-aviso">(desabilitada: não aparece na tela de envio)</span>' : '';
        $editar = self::link('<i class="far fa-edit"></i> Editar respostas e mensagens', 'onEdit', ['key' => $fluxo->id]);
        $sensivel = $fluxo->ignorar_maiusculas === 'F' ? ' <span class="tf-nota">(respeita maiúsculas e minúsculas)</span>' : '';

        return '<div class="tf-fluxo">
            <div class="tf-envio">O sistema envia ao cliente: ' . self::linkDaMensagem($fluxo) . $desabilitada . ' &nbsp; ' . $editar . '</div>
            <div class="tf-rolagem"><table class="tf-tabela">
                <thead><tr><th>Se o cliente responder</th><th>O agendamento</th><th>E o sistema envia</th></tr></thead>
                <tbody>
                    <tr><td>' . self::fichas($fluxo->respostas_confirmar) . $sensivel . '</td><td class="tf-sim">Fica Confirmado</td><td>' . self::celulaMensagem($fluxo, 'confirmado') . '</td></tr>
                    <tr><td>' . self::fichas($fluxo->respostas_cancelar) . $sensivel . '</td><td><span class="tf-nao">Fica Cancelado</span><br><span class="tf-nota">com financeiro gerado, não cancela</span></td><td>' . self::celulaMensagem($fluxo, 'cancelado') . '</td></tr>
                    <tr><td>Qualquer outra resposta</td><td>Não muda</td><td>Nada: a equipe resolve no APChat</td></tr>
                </tbody>
            </table></div>
            <div class="tf-lembrete">' . self::linhaDoLembrete($fluxo) . '</div>
        </div>';
    }

    private static function fichas(?string $lista): string
    {
        $respostas = self::respostas($lista);

        if (!$respostas)
        {
            return '<span class="tf-aviso">Nenhuma resposta definida</span>';
        }

        return implode('', array_map(function ($r) { return '<span class="tf-resposta">' . self::e($r) . '</span>'; }, $respostas));
    }

    private static function celulaMensagem(TemplateEscritorio $fluxo, string $ramo): string
    {
        $filho = self::mensagem($fluxo, $ramo);

        if (!$filho)
        {
            return '<span class="tf-aviso">Nenhuma: só muda o agendamento.</span> ' . self::linkCriar($fluxo, $ramo, 'Criar mensagem');
        }

        return self::linkDaMensagem($filho) . ($filho->habilitado !== 'T' ? ' <span class="tf-aviso">(desabilitada: não é enviada)</span>' : '');
    }

    private static function linhaDoLembrete(TemplateEscritorio $fluxo): string
    {
        $lembrete = self::mensagem($fluxo, 'lembrete');

        if (!$lembrete)
        {
            return 'Depois de confirmado: <span class="tf-aviso">nenhum lembrete escolhido</span>; a tela de envio sugere o primeiro da categoria Lembrete. ' . self::linkCriar($fluxo, 'lembrete', 'Criar lembrete');
        }

        return 'Depois de confirmado: ' . self::linkDaMensagem($lembrete) . ' já vem escolhido na tela de envio. <span class="tf-nota">Só sai quando alguém envia ou agenda.</span>';
    }

    private static function mensagem(TemplateEscritorio $fluxo, string $ramo): ?TemplateEscritorio
    {
        $id = (int) $fluxo->{self::RAMOS[$ramo]['coluna']};

        return $id ? (TemplateEscritorio::find($id) ?: null) : null;
    }

    private static function linkDaMensagem(TemplateEscritorio $template): string
    {
        return self::link('<b>' . self::e($template->descricao) . '</b>', 'onEdit', ['key' => $template->id], '', trim(strip_tags((string) $template->template)));
    }

    private static function linkCriar(TemplateEscritorio $fluxo, string $ramo, string $texto): string
    {
        return self::link('<i class="fas fa-plus"></i> ' . $texto, 'onShow', ['fluxo_pai' => $fluxo->id, 'fluxo_ramo' => $ramo]);
    }

    private static function link(string $conteudo, string $metodo, array $param, string $classe = '', string $dica = ''): string
    {
        $url = 'index.php?' . http_build_query(['class' => 'TemplateEscritorioForm', 'method' => $metodo] + $param + ['register_state' => 'false']);
        $estilo = $classe !== '' ? "class='{$classe}'" : "style='color:#1a73e8; text-decoration:underline'";

        return "<a generator='adianti' {$estilo} href='" . self::e($url) . "' title='" . self::e($dica) . "'>{$conteudo}</a>";
    }

    private static function e($texto): string
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
    }
}
