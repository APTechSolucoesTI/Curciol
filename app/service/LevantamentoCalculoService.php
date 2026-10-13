<?php
// app/service/LevantamentoCalculoService.php
/**
 * Calculo do financeiro de um levantamento judicial ("Fluxo e Regras de
 * Calculo", 01/10/2026). Sem banco: entra um array, sai um array.
 *
 * MLE do sistema = W da planilha (liquido apos descontos legais), logo
 * S = N - MLE. AA e arredondado a centavos e Z = Y - AA (Z + AA = Y sempre).
 * Repasses: arredondados a centavos; a diferenca vai para o participante
 * marcado "recebe a diferenca". Se a diferenca deixar algum repasse negativo,
 * ou a soma nao fechar em AD, o calculo para com erro (nada e descartado).
 */
class LevantamentoCalculoService
{
    public static function calcular(array $e): array
    {
        $n   = round((float) $e['bruto'], 2);
        $mle = round((float) $e['mle'], 2);
        $y   = round((float) $e['valor_levantado'], 2);
        $regra = (string) $e['regra'];

        if (empty($e['data_deposito'])) { throw new Exception('Informe a data do depósito.'); }
        if (!isset($e['pct_nf']) || $e['pct_nf'] === '') { throw new Exception('Informe o % de custo de NF (entre 0 e 100).'); }
        $nf = (float) $e['pct_nf'];

        $dep = substr((string) $e['data_deposito'], 0, 10);
        $lev = substr((string) ($e['data_levantamento'] ?? ''), 0, 10);

        if ($mle > $n) { throw new Exception('O valor do MLE não pode ser maior que o bruto depositado.'); }
        if ($mle <= 0) { throw new Exception('O pagamento não tem valor do MLE.'); }
        if ($y <= 0) { throw new Exception('Informe o valor levantado.'); }
        if ($lev === '' || $lev < $dep) {
            throw new Exception('A data do levantamento não pode ser anterior à data do depósito.');
        }
        if ($nf < 0 || $nf > 100) { throw new Exception('O % de custo de NF deve estar entre 0 e 100.'); }

        $s = $n - $mle;

        switch ($regra) {
            case BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS:
                $t = $e['pct_honorarios'];
                if ($t === null || $t === '' || (float) $t < 0 || (float) $t > 100) {
                    throw new Exception('Informe o % de honorários (entre 0 e 100).');
                }
                $u = $n * (float) $t / 100;
                break;
            case BeneficiarioCredito::REGRA_SOMENTE_HONORARIOS:
                $u = $n - $s;
                break;
            case BeneficiarioCredito::REGRA_SOMENTE_CLIENTE:
                $u = 0.0;
                break;
            default:
                throw new Exception('Beneficiário do crédito sem regra de cálculo válida.');
        }

        $v = $n - $s - $u;
        if ($v < -0.000001) { throw new Exception('Os honorários ultrapassam o valor do cliente (verifique o % de honorários e os descontos).'); }
        $w = $u + $v;

        $aa = round($y * $u / $w, 2);
        $z  = round($y - $aa, 2);
        $ac = round($aa * $nf / 100, 2);
        $ad = round($aa - $ac, 2);

        $repasses = [];
        if ($ad > 0) {
            if (empty($e['participantes'])) { throw new Exception('Escolha a parceria para dividir os honorários.'); }
            $soma = 0.0; $indiceDif = null;
            foreach (array_values($e['participantes']) as $i => $p) {
                $valor = round($ad * (float) $p['percentual'], 2);
                $repasses[] = ['pessoa_id' => (int) $p['pessoa_id'], 'percentual' => (float) $p['percentual'], 'valor' => $valor, 'ajuste_centavos' => 0.0];
                $soma += $valor;
                if (ParceriaPercentualService::marcado($p['recebe_diferenca'] ?? false)) { $indiceDif = $i; }
            }
            $diferenca = round($ad - $soma, 2);
            if ($diferenca != 0.0) {
                if ($indiceDif === null) { throw new Exception('A parceria não tem participante para receber a diferença de centavos.'); }
                $novo = round($repasses[$indiceDif]['valor'] + $diferenca, 2);
                if ($novo < 0) { throw new Exception('Não foi possível distribuir os centavos do repasse.'); }
                $repasses[$indiceDif]['valor'] = $novo;
                $repasses[$indiceDif]['ajuste_centavos'] = $diferenca;
            }
            foreach ($repasses as $r) {
                if ($r['valor'] < 0) { throw new Exception('Não foi possível distribuir os centavos do repasse.'); }
            }
            $repasses = array_values(array_filter($repasses, function ($r) { return $r['valor'] != 0; }));
            if (round(array_sum(array_column($repasses, 'valor')), 2) != $ad) {
                throw new Exception('Não foi possível distribuir os centavos do repasse.');
            }
        }

        return compact('s', 'u', 'v', 'w', 'aa', 'z', 'ac', 'ad') + ['repasses' => $repasses];
    }
}
