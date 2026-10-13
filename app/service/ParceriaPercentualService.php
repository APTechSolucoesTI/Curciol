<?php
// app/service/ParceriaPercentualService.php
/**
 * Percentuais de cada participante de uma parceria de levantamento.
 *
 * Fixa: o percentual cadastrado. Por tempo (dois marcos): reexpressao
 * exata das formulas do arquivo "Fluxo e Regras de Calculo":
 *   D = soma(nivel 1: a x %antes + b x %depois)        (marco 1)
 *   nivel 2 = (1 - D) x (p x %antes + q x %depois)     (marco 2)
 * com fracoes complementares (b = 1 - a, q = 1 - p), limitadas a [0, 1].
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

    public static function validar(string $tipo, array $participantes): void
    {
        if (empty($participantes)) { throw new Exception('Informe ao menos um participante.'); }

        foreach ($participantes as $p) {
            foreach (['percentual', 'percentual_antes', 'percentual_depois'] as $campo) {
                if (isset($p[$campo]) && (float) $p[$campo] < 0) { throw new Exception('Os percentuais não podem ser negativos.'); }
            }
            if ($tipo === ParceriaLevantamento::TIPO_TEMPORAL
                && !in_array($p['nivel'], [ParceriaLevantamentoParticipante::NIVEL_MARCO1, ParceriaLevantamentoParticipante::NIVEL_MARCO2], true)) {
                throw new Exception('Nível inválido na parceria por tempo (use nível 1 ou nível 2).');
            }
        }

        $pessoas = array_map(function ($p) { return (int) $p['pessoa_id']; }, $participantes);
        if (count($pessoas) !== count(array_unique($pessoas))) { throw new Exception('Há participante repetido na parceria.'); }

        $difs = array_filter($participantes, function ($p) { return self::marcado($p['recebe_diferenca'] ?? false); });
        if (count($difs) !== 1) { throw new Exception('Marque exatamente um participante para receber a diferença de centavos.'); }

        $soma = function (array $lista, string $campo) { return round(array_sum(array_map(function ($p) use ($campo) { return (float) ($p[$campo] ?? 0); }, $lista)), 6); };

        if ($tipo === ParceriaLevantamento::TIPO_FIXA) {
            if ($soma($participantes, 'percentual') != 100.0) {
                throw new Exception('Os percentuais da parceria devem somar 100%.');
            }
            return;
        }

        $nivel1 = array_filter($participantes, function ($p) { return $p['nivel'] === ParceriaLevantamentoParticipante::NIVEL_MARCO1; });
        $nivel2 = array_filter($participantes, function ($p) { return $p['nivel'] === ParceriaLevantamentoParticipante::NIVEL_MARCO2; });
        if (empty($nivel2)) { throw new Exception('A parceria por tempo precisa de participantes no nível 2.'); }
        if ($soma($nivel2, 'percentual_antes') != 100.0 || $soma($nivel2, 'percentual_depois') != 100.0) {
            throw new Exception('No nível 2, as colunas "antes" e "depois" devem somar 100% cada.');
        }
        if ($soma($nivel1, 'percentual_antes') > 100.0 || $soma($nivel1, 'percentual_depois') > 100.0) {
            throw new Exception('O nível 1 não pode passar de 100%.');
        }
    }

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
        $p = self::fracaoAntes($g, $x, $m2);

        $d = 0.0;
        foreach ($participantes as $q) {
            if ($q['nivel'] === ParceriaLevantamentoParticipante::NIVEL_MARCO1) {
                $d += ($a * (float) $q['percentual_antes'] + (1 - $a) * (float) $q['percentual_depois']) / 100;
            }
        }

        $saida = [];
        foreach ($participantes as $q) {
            $pct = ($q['nivel'] === ParceriaLevantamentoParticipante::NIVEL_MARCO1)
                ? ($a * (float) $q['percentual_antes'] + (1 - $a) * (float) $q['percentual_depois']) / 100
                : (1 - $d) * ($p * (float) $q['percentual_antes'] + (1 - $p) * (float) $q['percentual_depois']) / 100;
            $saida[] = ['pessoa_id' => (int) $q['pessoa_id'], 'percentual' => $pct, 'recebe_diferenca' => self::marcado($q['recebe_diferenca'] ?? false)];
        }

        return ['a' => $a, 'p' => $p, 'd' => $d, 'participantes' => $saida];
    }
}
