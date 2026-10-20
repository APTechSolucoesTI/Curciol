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

    /** Fecha a cortina atual e atualiza a lista de baixo (ex.: registro excluido). */
    public static function fecharCortinaEAtualizar(): void
    {
        TScript::create('window.curciolAtualizarListaAoFechar = true; Template.closeRightPanel();');
    }
}
