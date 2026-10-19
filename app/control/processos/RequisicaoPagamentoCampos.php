<?php
/**
 * Pecas comuns das duas telas da requisicao de pagamento (janela e aba do processo):
 * - descontos do deposito no lancamento do pagamento: o valor do MLE passa a ser bruto - descontos
 *   (previdenciario + assistencial + IRRF; em branco conta como 0);
 * - trava do status Quitado (ver RequisicaoQuitacaoService).
 */
class RequisicaoPagamentoCampos
{
    const DESCONTOS = [
        'desconto_previdenciario' => 'Desconto previdenciário',
        'desconto_assistencial'   => 'Desconto assistencial',
        'desconto_irrf'           => 'Desconto IRRF',
    ];

    public static function nomeDesconto(string $campo, int $rpcId): string
    {
        return 'etapa3_' . $campo . '_' . $rpcId;
    }

    /** [TLabel, TNumeric] de cada desconto, prontos para o addFields. */
    public static function camposDescontos(int $rpcId): array
    {
        $r = [];
        foreach (self::DESCONTOS as $campo => $rotulo) {
            $f = new TNumeric(self::nomeDesconto($campo, $rpcId), 2, ',', '.', true);
            $f->setSize('100%');
            $f->setProperty('placeholder', '0,00');
            $r[] = [new TLabel($rotulo), $f];
        }
        return $r;
    }

    /** Valores dos campos: os do pagamento ($cliente com etapa3_desconto_*) ou em branco (novo pagamento). */
    public static function preencherDescontos(stdClass $data, int $rpcId, $cliente): void
    {
        foreach (array_keys(self::DESCONTOS) as $campo) {
            $data->{self::nomeDesconto($campo, $rpcId)} = $cliente ? ($cliente->{'etapa3_' . $campo} ?? null) : null;
        }
    }

    /** Descontos digitados: [campo => float|null] ('1.234,56' => 1234.56; vazio => null). */
    public static function descontosDoPost(array $param, int $rpcId): array
    {
        $r = [];
        foreach (array_keys(self::DESCONTOS) as $campo) {
            $v = trim((string) ($param[self::nomeDesconto($campo, $rpcId)] ?? ''));
            if ($v === '') {
                $r[$campo] = null;
                continue;
            }
            $t = str_replace(',', '.', str_replace('.', '', $v));
            if (!is_numeric($t)) {
                throw new Exception('Valor inválido em ' . mb_strtolower(self::DESCONTOS[$campo]) . ": '{$v}'.");
            }
            $r[$campo] = (float) $t;
        }
        return $r;
    }

    /** Pagamento ja gravado (bruto, MLE e descontos) ou null. */
    public static function pagamentoGravado(PDO $conn, $etapa3Id): ?array
    {
        if (empty($etapa3Id)) {
            return null;
        }
        $st = $conn->prepare('SELECT valor_bruto_depositado, valor_mle, desconto_previdenciario, desconto_assistencial, desconto_irrf FROM requisicao_pagamento_etapa3 WHERE id = ?');
        $st->execute([(int) $etapa3Id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * MLE e descontos a gravar.
     * - Com bruto: MLE = bruto - (previdenciario + assistencial + IRRF); desconto em branco = 0.
     * - Pagamento anterior a essa regra (sem descontos gravados) que continua sem descontos e com o mesmo bruto:
     *   mantem o MLE gravado.
     * - Sem bruto (pagamento ainda nao depositado, ou MLE informado antes do bruto): mantem o MLE informado.
     * @return array ['valor_mle' => ?float, 'desconto_previdenciario' => ?float, 'desconto_assistencial' => ?float, 'desconto_irrf' => ?float]
     */
    public static function calcularMle(?float $bruto, array $descontos, ?float $mleInformado, ?array $gravado): array
    {
        $semDescontos = array_fill_keys(array_keys(self::DESCONTOS), null);
        $vazios = true;
        foreach (array_keys(self::DESCONTOS) as $campo) {
            if (($descontos[$campo] ?? null) !== null) {
                $vazios = false;
            }
        }

        if ($bruto === null || round($bruto, 2) <= 0) {
            if (!$vazios) {
                throw new Exception('Informe o valor bruto depositado para lançar os descontos.');
            }
            return ['valor_mle' => $mleInformado] + $semDescontos;
        }

        if ($vazios && $gravado !== null && $gravado['valor_mle'] !== null
            && $gravado['desconto_previdenciario'] === null && $gravado['desconto_assistencial'] === null && $gravado['desconto_irrf'] === null
            && round((float) $gravado['valor_bruto_depositado'], 2) === round($bruto, 2)) {
            return ['valor_mle' => round((float) $gravado['valor_mle'], 2)] + $semDescontos;
        }

        $r = [];
        foreach (self::DESCONTOS as $campo => $rotulo) {
            $v = $descontos[$campo] ?? null;
            if ($v !== null && $v < 0) {
                throw new Exception('O ' . mb_strtolower($rotulo) . ' não pode ser negativo.');
            }
            $r[$campo] = round((float) $v, 2);
        }
        $soma = round(array_sum($r), 2);
        if ($soma > round($bruto, 2)) {
            throw new Exception('Os descontos (' . self::moeda($soma) . ') não podem passar do valor bruto depositado (' . self::moeda($bruto) . ').');
        }
        return ['valor_mle' => round($bruto - $soma, 2)] + $r;
    }

    /** Ao digitar bruto ou descontos, o MLE e recalculado (bruto - descontos) e fica so leitura enquanto houver bruto. */
    public static function scriptMle(string $formName, int $rpcId, string $campoBruto, string $campoMle): void
    {
        $descontos = array_map(function ($c) use ($rpcId) { return self::nomeDesconto($c, $rpcId); }, array_keys(self::DESCONTOS));
        $form = json_encode('#' . $formName);
        $bruto = json_encode($campoBruto);
        $mle = json_encode($campoMle);
        $lista = json_encode($descontos);
        $js = <<<JS
(function () {
    var sel = {$form}, bruto = {$bruto}, mle = {$mle}, descontos = {$lista}, ev = '.descMle{$rpcId}';
    function form() { return $(sel).last(); }
    function campo(n) { return form().find('[name="' + n + '"]').last(); }
    function num(v) {
        v = String(v || '').trim();
        if (v === '') { return null; }
        var n = parseFloat(v.split('.').join('').replace(',', '.'));
        return isNaN(n) ? null : n;
    }
    function travar() {
        var b = num(campo(bruto).val()), calculado = (b !== null && b > 0);
        campo(mle).prop('readonly', calculado).css('background-color', calculado ? '#f3f4f6' : '');
    }
    function calcular() {
        var b = num(campo(bruto).val());
        if (b !== null && b > 0) {
            var soma = 0;
            descontos.forEach(function (n) { soma += num(campo(n).val()) || 0; });
            var v = Math.round((b - soma) * 100) / 100;
            campo(mle).val(v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }
        travar();
    }
    var alvos = [bruto].concat(descontos).map(function (n) { return sel + ' [name="' + n + '"]'; }).join(', ');
    $(document).off(ev).on('input' + ev + ' keyup' + ev + ' change' + ev, alvos, calcular);
    setTimeout(travar, 300);
})();
JS;
        TScript::create($js);
    }

    /**
     * Status do cliente da requisicao: "Quitado" fica desabilitado no combo enquanto a condicao nao vale
     * (o motivo aparece debaixo do campo). Devolve o aviso (vazio quando Quitado pode ser escolhido).
     */
    public static function travaQuitado(string $formName, int $rpcId, $statusAtual): TElement
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $quitado = RequisicaoQuitacaoService::statusQuitadoId();
            $motivo = ($quitado === null || (int) $statusAtual === $quitado) ? null : RequisicaoQuitacaoService::motivo($rpcId);
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
        $aviso = new TElement('div');
        $aviso->class = 'req-status-dica';
        if ($motivo === null) {
            return $aviso;
        }

        $opcao = json_encode('#' . $formName . ' select[name="status_' . $rpcId . '"] option[value="' . $quitado . '"]');
        $combo = json_encode('#' . $formName . ' select[name="status_' . $rpcId . '"]');
        $titulo = json_encode('Disponível quando: ' . $motivo);
        TScript::create("(function () {
            function travar() { $({$opcao}).prop('disabled', true).attr('title', {$titulo}); }
            $(document).off('select2:opening.quitado{$rpcId}').on('select2:opening.quitado{$rpcId}', {$combo}, travar);
            setTimeout(travar, 200); setTimeout(travar, 800);
        })();");

        $aviso->style = 'font-size:11px;color:#8a6d3b;margin-top:3px;line-height:1.3';
        $aviso->add('Quitado indisponível: ' . htmlspecialchars($motivo));
        return $aviso;
    }

    private static function moeda(float $v): string
    {
        return 'R$ ' . number_format($v, 2, ',', '.');
    }
}
