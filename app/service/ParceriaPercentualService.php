<?php
// app/service/ParceriaPercentualService.php
/**
 * Percentuais de cada participante de uma parceria de levantamento.
 *
 * Fixa: o percentual cadastrado.
 * Por tempo (dois marcos, por periodos - decisao de 13/11/2026): o tempo do processo (distribuicao -> levantamento)
 * fica dividido em 3 periodos e em cada um vale a divisao daquela coluna:
 *   percentual = a x antes + (p - a) x entre + (1 - p) x depois
 * a = fracao do tempo antes do 1o marco, p = fracao antes do 2o marco (meses completos), cada coluna soma 100%.
 * O nivel diz em qual marco o percentual da pessoa muda: fixo (nao muda: entre e depois = antes), nivel 1 (entre = depois),
 * nivel 2 (entre = antes), nivel 1 e 2 (entre informado).
 * Sem banco: recebe e devolve arrays.
 */
class ParceriaPercentualService
{
    /** Verdadeiro so para true, 1, 'S' ou 't' (o 'N' e o vazio sao falsos). */
    public static function marcado($valor): bool
    {
        return in_array($valor, [true, 1, 'S', 't'], true);
    }

    public static function mesesCompletos(string $de, string $ate): int
    {
        $d1 = new DateTime(substr($de, 0, 10));
        $d2 = new DateTime(substr($ate, 0, 10));
        if ($d2 <= $d1) { return 0; }
        $meses = ((int) $d2->format('Y') - (int) $d1->format('Y')) * 12 + ((int) $d2->format('n') - (int) $d1->format('n'));
        if ((int) $d2->format('j') < (int) $d1->format('j')) { $meses--; }
        return max(0, $meses);
    }

    public static function fracaoAntes(string $g, string $x, string $marco): float
    {
        $g = substr($g, 0, 10); $x = substr($x, 0, 10); $marco = substr($marco, 0, 10);
        if ($g >= $marco) { return 0.0; }
        if ($x <= $marco) { return 1.0; }
        $total = self::mesesCompletos($g, $x);
        if ($total === 0) { return ($x < $marco) ? 1.0 : 0.0; }
        return max(0.0, min(1.0, self::mesesCompletos($g, $marco) / $total));
    }

    /** "peso dos períodos: antes do 1º marco X%, entre os marcos Y%, depois do 2º marco Z%" (a e p em fracao). */
    public static function textoPesos(float $a, float $p): string
    {
        $f = function ($v) { return number_format($v * 100, 2, ',', '.') . '%'; };
        return 'peso dos períodos: antes do 1º marco ' . $f($a) . ', entre os marcos ' . $f(max(0, $p - $a)) . ', depois do 2º marco ' . $f(1 - $p);
    }

    /** Niveis da parceria por tempo. */
    public static function niveisTemporais(): array
    {
        return [ParceriaLevantamentoParticipante::NIVEL_FIXO, ParceriaLevantamentoParticipante::NIVEL_MARCO1,
                ParceriaLevantamentoParticipante::NIVEL_MARCO2, ParceriaLevantamentoParticipante::NIVEL_MARCO12];
    }

    /** Percentual "entre os marcos" pelo nivel: fixo e nivel 2 repetem o antes, nivel 1 repete o depois, nivel 1 e 2 e o informado. */
    public static function entre(array $p): float
    {
        switch ($p['nivel'] ?? '') {
            case ParceriaLevantamentoParticipante::NIVEL_MARCO1: return self::depois($p);
            case ParceriaLevantamentoParticipante::NIVEL_MARCO2:
            case ParceriaLevantamentoParticipante::NIVEL_FIXO: return (float) ($p['percentual_antes'] ?? 0);
            default: return (float) ($p['percentual_entre'] ?? 0);
        }
    }

    /** Percentual "depois do 2o marco": no fixo repete o antes; nos outros niveis e o informado. */
    public static function depois(array $p): float
    {
        return (float) ((($p['nivel'] ?? '') === ParceriaLevantamentoParticipante::NIVEL_FIXO) ? ($p['percentual_antes'] ?? 0) : ($p['percentual_depois'] ?? 0));
    }

    public static function validar(string $tipo, array $participantes): void
    {
        if (empty($participantes)) { throw new Exception('Informe ao menos um participante.'); }

        foreach ($participantes as $p) {
            foreach (['percentual', 'percentual_antes', 'percentual_entre', 'percentual_depois'] as $campo) {
                if (isset($p[$campo]) && (float) $p[$campo] < 0) { throw new Exception('Os percentuais não podem ser negativos.'); }
            }
            if ($tipo === ParceriaLevantamento::TIPO_TEMPORAL && !in_array($p['nivel'] ?? null, self::niveisTemporais(), true)) {
                throw new Exception('Nível inválido na parceria por tempo (use fixo, nível 1, nível 2 ou nível 1 e 2).');
            }
        }

        $pessoas = array_map(function ($p) { return (int) $p['pessoa_id']; }, $participantes);
        if (count($pessoas) !== count(array_unique($pessoas))) { throw new Exception('Há participante repetido na parceria.'); }

        $difs = array_filter($participantes, function ($p) { return self::marcado($p['recebe_diferenca'] ?? false); });
        if (count($difs) !== 1) { throw new Exception('Marque exatamente um participante para receber a diferença de centavos.'); }

        $soma = function (callable $valor) use ($participantes) { return round(array_sum(array_map($valor, $participantes)), 6); };

        if ($tipo === ParceriaLevantamento::TIPO_FIXA) {
            if ($soma(function ($p) { return (float) ($p['percentual'] ?? 0); }) != 100.0) {
                throw new Exception('Os percentuais da parceria devem somar 100%.');
            }
            return;
        }

        $antes  = $soma(function ($p) { return (float) ($p['percentual_antes'] ?? 0); });
        $entre  = $soma(function ($p) { return self::entre($p); });
        $depois = $soma(function ($p) { return self::depois($p); });
        if ($antes != 100.0 || $entre != 100.0 || $depois != 100.0) {
            $f = function ($v) { return rtrim(rtrim(number_format($v, 4, ',', '.'), '0'), ',') . '%'; };
            throw new Exception('Cada período deve somar 100%: antes do 1º marco ' . $f($antes) . ', entre os marcos ' . $f($entre)
                . ', depois do 2º marco ' . $f($depois) . '.');
        }
    }

    /**
     * @return array ['a' => fracao antes do 1o marco, 'p' => fracao antes do 2o marco, 'd' => null (era o peso do nivel 1
     *               da cascata; fichas antigas guardam), 'participantes' => [['pessoa_id', 'percentual' (0-1), 'recebe_diferenca'], ...]]
     */
    public static function percentuais(string $tipo, array $participantes, ?string $marco1, ?string $marco2, ?string $g, ?string $x): array
    {
        self::validar($tipo, $participantes);

        if ($tipo === ParceriaLevantamento::TIPO_FIXA) {
            return ['a' => null, 'p' => null, 'd' => null, 'participantes' => array_map(function ($p) {
                return ['pessoa_id' => (int) $p['pessoa_id'], 'percentual' => (float) $p['percentual'] / 100, 'recebe_diferenca' => self::marcado($p['recebe_diferenca'] ?? false)];
            }, array_values($participantes))];
        }

        $m1 = substr((string) $marco1, 0, 10);
        $m2 = substr((string) $marco2, 0, 10);
        if ($m1 === '' || $m2 === '') {
            throw new Exception('Informe os dois marcos da parceria por tempo.');
        }
        if ($m1 > $m2) {
            throw new Exception('O primeiro marco deve ser anterior ao segundo.');
        }

        $g = substr((string) $g, 0, 10);
        $x = substr((string) $x, 0, 10);
        if ($g === '' || $x === '' || $g > $x) {
            throw new Exception('Informe a data de distribuição (anterior ou igual à data do levantamento) para a parceria por tempo.');
        }

        $a = self::fracaoAntes($g, $x, $m1);
        $p = max($a, self::fracaoAntes($g, $x, $m2));

        $saida = [];
        foreach (array_values($participantes) as $q) {
            $pct = ($a * (float) $q['percentual_antes'] + ($p - $a) * self::entre($q) + (1 - $p) * self::depois($q)) / 100;
            $saida[] = ['pessoa_id' => (int) $q['pessoa_id'], 'percentual' => $pct, 'recebe_diferenca' => self::marcado($q['recebe_diferenca'] ?? false)];
        }

        return ['a' => $a, 'p' => $p, 'd' => null, 'participantes' => $saida];
    }
}
