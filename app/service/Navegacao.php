<?php
/**
 * Para onde a tela vai depois de uma acao (20/10/2026).
 *
 * Padrao: a acao feita numa cortina (detalhe da conta, formulario aberto por ela) reabre a cortina no registro
 * afetado, sem trocar a tela de baixo (lista, requisicao, processo...). A lista de baixo fica marcada como
 * desatualizada e se atualiza, mantendo os filtros, quando a ultima cortina fechar (curciol-cortina.js).
 */
class Navegacao
{
    /** A lista debaixo da cortina se atualiza (mantendo os filtros) quando a ultima cortina fechar. */
    public static function atualizarListaAoFechar(): void
    {
        TScript::create('window.curciolAtualizarListaAoFechar = true;');
    }

    /**
     * Abre (ou atualiza no lugar) o detalhe da conta na cortina. $fecharCamada: tela da cortina que deve sair antes
     * (ex.: o formulario que acabou de salvar), para o detalhe aparecer por cima. $atrasoMs: espera outra tela que
     * esta sendo atualizada na mesma resposta (para a conta ficar por cima dela).
     */
    public static function abrirConta(int $contaId, ?string $fecharCamada = null, int $atrasoMs = 0): void
    {
        if ($fecharCamada !== null) {
            TScript::create('$("#adianti_right_panel > [page_name=\'' . preg_replace('/[^A-Za-z0-9_]/', '', $fecharCamada) . '\']").remove();');
        }
        self::atualizarListaAoFechar();
        if ($atrasoMs > 0) {
            $url = 'index.php?class=ContaFormView&method=onShow&key=' . $contaId . '&id=' . $contaId . '&target_container=adianti_right_panel';
            TScript::create('setTimeout(function () { Adianti.waitMessage = false; __adianti_load_page(' . json_encode($url) . '); }, ' . $atrasoMs . ');');
            return;
        }
        AdiantiCoreApplication::loadPage('ContaFormView', 'onShow', ['key' => $contaId, 'id' => $contaId, 'target_container' => 'adianti_right_panel']);
    }

    /**
     * Depois de salvar/excluir num formulario: se ele esta numa cortina, ela fecha e a tela de baixo continua (se for
     * uma lista, se atualiza mantendo os filtros); se foi aberto como pagina, volta para a lista $lista (com os filtros).
     */
    public static function voltarParaLista(string $lista, string $formulario): void
    {
        $lista = preg_replace('/[^A-Za-z0-9_]/', '', $lista);
        $formulario = preg_replace('/[^A-Za-z0-9_]/', '', $formulario);
        TScript::create("(function () {
            var camada = $('#adianti_right_panel > [page_name=\"{$formulario}\"]');
            if (camada.length) {
                window.curciolAtualizarListaAoFechar = true;
                if (camada.is($('#adianti_right_panel > [page_name]').last())) { Template.closeRightPanel(); } else { camada.remove(); }
            } else {
                Adianti.waitMessage = false;
                __adianti_load_page('index.php?class={$lista}&method=onReload');
            }
        })();");
    }

    /**
     * Depois de salvar um cadastro na cortina: ela continua aberta no registro, recarregada do banco (abas e grades
     * com o que foi gravado), na mesma aba; a lista de baixo se atualiza quando a cortina fechar.
     */
    public static function continuarNoRegistro(string $formulario, int $id, array $param = []): void
    {
        // aberto no meio de outra tarefa (agenda, atendimento, conta, cadastro de cima): fecha e volta para ela (05/11/2026)
        if (!empty($param['fechar_ao_salvar']))
        {
            TScript::create('Template.closeRightPanel();');
            self::selecionarPessoaNaTelaDeBaixo($id, (string) ($param['retorno_pessoa'] ?? ''));
            return;
        }

        self::atualizarListaAoFechar();
        $abrir = ['key' => $id, 'id' => $id, 'target_container' => 'adianti_right_panel'];
        if (isset($param['current_tab']) && ctype_digit((string) $param['current_tab'])) {
            $abrir['current_tab'] = (int) $param['current_tab'];
        }
        AdiantiCoreApplication::loadPage(preg_replace('/[^A-Za-z0-9_]/', '', $formulario), 'onEdit', $abrir);
    }

    /** Cadastro criado pelo + da conta: volta selecionado no campo pessoa_id dela, sem mexer no resto. Chamar depois do commit. */
    private static function selecionarPessoaNaTelaDeBaixo(int $id, string $formulario): void
    {
        if (!preg_match('/^form_\w+$/', $formulario))
        {
            return;
        }
        TTransaction::open('escritorio');
        $pessoa = Pessoa::find($id);
        TTransaction::close();
        if (!$pessoa)
        {
            return;
        }
        $nome = json_encode((string) $pessoa->nome, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        TScript::create("(function () { var campo = $('form[name=\"{$formulario}\"]').last().find('[name=pessoa_id]');
            if (campo.length) { campo.find('option[value=\"{$id}\"]').remove(); campo.append(new Option({$nome}, '{$id}', true, true)).trigger('change'); } })();");
    }

    /** Cadastro aberto de dentro do agendamento: a cortina fecha e o agendamento que estava aberto se atualiza. */
    public static function voltarParaAgendamento(int $agendamentoId): void
    {
        TScript::create("Template.closeRightPanel();
            if ($('[page_name=AgendamentoFormView]').length) {
                Adianti.waitMessage = false;
                __adianti_load_page('index.php?class=AgendamentoFormView&method=onShow&key={$agendamentoId}&register_state=false');
            }");
    }

    /** Fecha a cortina atual e atualiza a lista de baixo (ex.: registro excluido). */
    public static function fecharCortinaEAtualizar(): void
    {
        TScript::create('window.curciolAtualizarListaAoFechar = true; Template.closeRightPanel();');
    }
}
