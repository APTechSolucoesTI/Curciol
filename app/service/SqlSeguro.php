<?php
/**
 * Valores da tela que entram em SQL montado a mao (subconsultas "(SELECT ...)" do TFilter, que o framework nao
 * parametriza). Seguranca 22/10/2026: os filtros das listagens concatenavam o valor digitado direto no SQL.
 * Para o mesmo valor valido o SQL gerado e o mesmo de antes; o que muda e que um valor fora do tipo nao altera a consulta.
 */
class SqlSeguro
{
    /** Id inteiro (combo/autocomplete). Valor nao numerico vira 0 (nenhum registro). */
    public static function id($v): int
    {
        if (is_array($v)) {
            $v = reset($v);
        }
        return is_numeric($v) ? (int) $v : 0;
    }

    /** Lista de ids para "IN (...)": "1,2,3"; vazia vira "0". */
    public static function ids($v): string
    {
        $ids = [];
        foreach ((array) $v as $x) {
            if (is_numeric($x)) {
                $ids[] = (int) $x;
            }
        }
        return $ids ? implode(',', array_unique($ids)) : '0';
    }

    /**
     * Texto para dentro de aspas simples (ex.: like '%...%'). Dobra a aspa simples, o escape do PostgreSQL com
     * standard_conforming_strings (padrao desde o 9.1). % e _ continuam como curinga do like, como antes.
     */
    public static function texto($v): string
    {
        return str_replace("'", "''", (string) (is_array($v) ? reset($v) : $v));
    }

    /** Data aaaa-mm-dd (ou dd/mm/aaaa, convertida). Invalida devolve null: o filtro e ignorado. */
    public static function data($v): ?string
    {
        $v = trim((string) (is_array($v) ? reset($v) : $v));
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})( \d{2}:\d{2}(:\d{2})?)?$/', $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $v;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return null;
    }
}
