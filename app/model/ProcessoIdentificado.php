<?php

/**
 * Processo com um rotulo legivel para combos e seletores.
 *
 * Os combos do sistema identificam o processo por {numero_cnj_numero}. Um
 * pre-processo nao tem numero, entao apareceria como uma linha em branco na
 * lista - impossivel de escolher.
 *
 * Esta subclasse existe em vez de um getter dentro de Processo porque
 * Processo e gerado pelo Mad Builder a partir do schema: uma regeneracao
 * apagaria o getter e os combos voltariam a ficar em branco, sem aviso.
 * Aqui o Builder nao mexe.
 *
 * Aponta para a mesma tabela (TABLENAME e herdado), entao nao ha consulta
 * nova nem duplicacao de dados - so uma forma diferente de ler o mesmo
 * registro.
 *
 * Uso: new TDBCombo('processo_id', 'escritorio', 'ProcessoIdentificado',
 *                   'id', '{identificacao}', ...)
 */
class ProcessoIdentificado extends Processo
{
    /**
     * Rotulo do registro nos tres estados possiveis:
     *
     *   convencional .... 0001234-56.2026.5.15.0001 (#150)
     *   pre-processo .... Reclamação trabalhista — João da Silva (#150)
     *   convertido ...... 0001234-56.2026.5.15.0001 — Reclamação trabalhista (#150)
     *
     * O id interno aparece sempre: e o unico identificador que existe em
     * qualquer um dos tres.
     */
    public function get_identificacao()
    {
        $numero    = trim((string) $this->numero_cnj_numero);
        $descricao = trim((string) $this->descricao_pre_processo);

        $partes = [];

        if ($numero !== '')
        {
            $partes[] = $numero;
        }

        if ($descricao !== '')
        {
            $partes[] = $descricao;
        }

        if (empty($partes))
        {
            $partes[] = 'sem número e sem descrição';
        }

        return implode(' — ', $partes) . " (#{$this->id})";
    }
}
