<?php
/**
 * Filtros das listagens que se somam (20/10/2026).
 *
 * As listagens tem ate tres lugares de filtro: o formulario da cortina (botao Filtros), o cabecalho das colunas e os
 * botoes rapidos (ex.: Vencidas, A vencer, Em aberto). Antes cada busca lia so o formulario que a disparou e apagava
 * os outros filtros. Agora a busca parte dos filtros ja aplicados (guardados na sessao em <Classe>_filter_data) e so
 * troca os campos do formulario que disparou a busca: o resto continua valendo.
 */
class FiltroLista
{
    /**
     * Filtros aplicados + campos do formulario que disparou a busca (inclusive os esvaziados, que deixam de filtrar)
     * + $param['__filtros_extra'] (botoes rapidos).
     */
    public static function acumular(string $classe, $dadosFormulario, array $param = []): stdClass
    {
        $dados = (array) (TSession::getValue($classe . '_filter_data') ?: []);
        foreach ((array) $dadosFormulario as $campo => $valor) {
            $dados[$campo] = $valor;
        }
        foreach ((array) ($param['__filtros_extra'] ?? []) as $campo => $valor) {
            $dados[$campo] = $valor;
        }
        return (object) $dados;
    }

    /**
     * Botao rapido liga/desliga: aplica os valores somando aos filtros atuais; se ja estavam aplicados, tira.
     * @return array valores para __filtros_extra
     */
    public static function alternar(string $classe, array $valores): array
    {
        $atual = (array) (TSession::getValue($classe . '_filter_data') ?: []);
        $ligado = true;
        foreach ($valores as $campo => $valor) {
            if ((string) ($atual[$campo] ?? '') !== (string) $valor) {
                $ligado = false;
            }
        }
        if ($ligado) {
            foreach ($valores as $campo => $valor) {
                $valores[$campo] = '';
            }
        }
        return $valores;
    }

    /**
     * Pessoas de um grupo, para o autocomplete dos filtros de nome (TDBUniqueSearch com chave = nome: o filtro continua
     * sendo pelo nome, so que escolhido numa lista).
     */
    public static function criterioGrupo(string $grupoId): TCriteria
    {
        $c = new TCriteria();
        $c->add(new TFilter('id', 'in', '(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = ' . (int) $grupoId . ')'));
        return $c;
    }

    /** Aba fechada e aberta de novo: a tela comeca sem filtros (chamado pelo engine.php com curciol_limpar_filtros=1). */
    public static function limparSessao(string $classe): void
    {
        $classe = preg_replace('/[^A-Za-z0-9_]/', '', $classe);
        foreach (['_filter_data', '_filters', '_filtros', '_colunas', '_parametros'] as $sufixo) {
            TSession::setValue($classe . $sufixo, null);
        }
    }

    /** O valor esta aplicado agora? (para destacar o botao rapido ativo) */
    public static function aplicado(string $classe, string $campo, string $valor): bool
    {
        $atual = (array) (TSession::getValue($classe . '_filter_data') ?: []);
        return (string) ($atual[$campo] ?? '') === $valor;
    }
}
