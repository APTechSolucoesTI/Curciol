<?php
/**
 * Parceria de repasse no cadastro/edicao do agendamento (29/10/2026): fica junto dos procedimentos (onde esta a coluna
 * Parceiro, que continua so para o preco). Abaixo, a divisao por colaborador (so leitura): porcentagem da parceria e o
 * valor sobre o total dos procedimentos, recalculados na tela a cada mudanca (mesma regra de centavos do financeiro:
 * a diferenca fica com quem a parceria indica). O Gerar financeiro do agendamento usa esta parceria.
 */
class AgendamentoParceriaCampo
{
    /** Acrescenta a linha "Parceria de repasse" e a tabela de divisao ao formulario. Usa a transacao aberta ou abre a propria. */
    public static function adicionar(BootstrapFormBuilder $form, string $formName): TCombo
    {
        $parcerias = AgendamentoFinanceiroService::parceriasFixas();
        $dados = [];
        foreach (array_keys($parcerias) as $id) {
            $dados[$id] = array_map(function ($p) {
                return ['nome' => htmlspecialchars((string) $p['nome'], ENT_QUOTES, 'UTF-8'), 'percentual' => $p['percentual'], 'diferenca' => $p['diferenca']];
            }, AgendamentoFinanceiroService::participantes((int) $id));
        }

        $combo = new TCombo('parceria_levantamento_id');
        $combo->addItems($parcerias);
        $combo->enableSearch();
        $combo->setSize('100%');

        $alvo = 'agp_divisao_' . $formName;
        $div = new TElement('div');
        $div->id = $alvo;
        $div->class = 'agp-divisao';

        $row = $form->addFields([new TLabel('Parceria de repasse:', null, '14px', null, '100%'), $combo], [new TLabel('Divisão por colaborador:', null, '14px', null, '100%'), $div]);
        $row->layout = ['col-sm-4', 'col-sm-8'];

        $form->addContent([TScript::create(self::script($formName, $alvo, $dados), false)]);
        $form->addContent(['<style>
            .agp-divisao table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .agp-divisao th { background: #f3f5f6; font-weight: 600; text-align: left; padding: 4px 8px; border-bottom: 1px solid #d0d4da; }
            .agp-divisao td { padding: 4px 8px; border-bottom: 1px solid #eceef1; }
            .agp-divisao .agp-num { text-align: right; white-space: nowrap; }
            .agp-divisao tfoot td { font-weight: 600; border-bottom: 0; }
            .agp-divisao .agp-vazio { color: #6f7d92; padding: 6px 0; }
        </style>']);
        return $combo;
    }

    private static function script(string $formName, string $alvo, array $dados): string
    {
        $json = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        return <<<JS
(function () {
    var dados = {$json}, ultimo = null, timer = null;
    function num(v) {
        v = (v || '').toString().replace(/R\\$|\\s/g, '');
        if (v.indexOf(',') >= 0) { v = v.replace(/\\./g, '').replace(',', '.'); }
        var n = parseFloat(v); return isNaN(n) ? 0 : n;
    }
    function moeda(c) { return 'R$ ' + (c / 100).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function desenhar() {
        var el = document.getElementById('{$alvo}');
        if (!el) { clearInterval(timer); return; }
        var f = $('form[name="{$formName}"]');
        var pid = f.find('[name="parceria_levantamento_id"]').val() || '';
        var total = 0;
        f.find('[name="agendamento_procedimento_agendamento_valor_total[]"]').each(function () { total += num($(this).val()); });
        var base = Math.round(total * 100), assinatura = pid + '|' + base;
        if (assinatura === ultimo) { return; }
        ultimo = assinatura;
        var linhas = dados[pid];
        if (!pid || !linhas) { el.innerHTML = '<div class="agp-vazio">Escolha a parceria de repasse para ver quanto fica com cada colaborador.</div>'; return; }
        if (!linhas.length) { el.innerHTML = '<div class="agp-vazio">A parceria não tem participantes com percentual.</div>'; return; }
        var cent = linhas.map(function (l) { return Math.round(base * l.percentual / 100); });
        var receptor = -1;
        linhas.forEach(function (l, i) { if (l.diferenca) { receptor = i; } });
        if (receptor >= 0) { cent[receptor] += base - cent.reduce(function (a, b) { return a + b; }, 0); }
        var h = '<table><thead><tr><th>Colaborador</th><th class="agp-num">Porcentagem</th><th class="agp-num">Valor</th></tr></thead><tbody>';
        linhas.forEach(function (l, i) {
            h += '<tr><td>' + l.nome + '</td><td class="agp-num">' + l.percentual.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 4}) + '%</td><td class="agp-num">' + moeda(cent[i]) + '</td></tr>';
        });
        h += '</tbody><tfoot><tr><td>Total dos procedimentos</td><td></td><td class="agp-num">' + moeda(base) + '</td></tr></tfoot></table>';
        el.innerHTML = h;
    }
    timer = setInterval(desenhar, 700);
    setTimeout(desenhar, 100);
})();
JS;
    }
}
