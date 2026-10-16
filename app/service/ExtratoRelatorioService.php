<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Relatorio do Extrato (PDF e Excel) no layout do relatorio de conta-caixa: por conta-caixa, cabecalho
 * (tipo, inicio, agencia/conta/banco), saldo anterior, movimentacoes, total movimentado e saldo posterior,
 * agora com todos os parametros da janela do Extrato (situacao, periodo, tipo, historico, valores, pessoa, processo).
 * Os saldos sao sempre da conta-caixa inteira (so compensados); as movimentacoes sao as que atendem aos filtros.
 */
class ExtratoRelatorioService
{
    const LIMITE = 20000;

    /** @return array ['filtros' => string, 'contas' => [...], 'total_entrada' => f, 'total_saida' => f, 'aviso' => ?string] */
    public static function dados(array $p): array
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $c = TTransaction::get();
            $crit = new TCriteria();
            foreach (ExtratoParametrosService::filtros($p) as $f) { $crit->add($f); }
            $col = ExtratoParametrosService::COLUNA_DATA[$p['periodo_tipo'] ?? 'compensacao'] ?? 'data_compensacao';
            $crit->setProperty('order', "conta_caixa_id, {$col}, id");
            $crit->setProperty('limit', self::LIMITE + 1);
            $movs = (new TRepository('Extrato'))->load($crit, false) ?: [];
            $aviso = null;
            if (count($movs) > self::LIMITE) {
                $movs = array_slice($movs, 0, self::LIMITE);
                $aviso = 'O relatório mostra as primeiras ' . number_format(self::LIMITE, 0, ',', '.') . ' movimentações. Refine os filtros.';
            }
            $tipos = $c->query('SELECT id, nome FROM tipo_extrato')->fetchAll(PDO::FETCH_KEY_PAIR);

            $porConta = [];
            foreach ($movs as $m) { $porConta[(int) $m->conta_caixa_id][] = $m; }
            if (!empty($p['conta_caixa_ids'])) { foreach ($p['conta_caixa_ids'] as $id) { $porConta += [(int) $id => []]; } }

            $contas = [];
            $te = 0.0; $ts = 0.0;
            foreach ($porConta as $ccId => $lista) {
                $cc = $c->query('SELECT cc.*, t.nome AS tipo_nome, b.nome AS banco_nome FROM conta_caixa cc LEFT JOIN tipo_conta_caixa t ON t.id = cc.tipo_conta_caixa_id
                                   LEFT JOIN banco b ON b.id = cc.banco_id WHERE cc.id = ' . (int) $ccId)->fetch(PDO::FETCH_OBJ);
                if (!$cc) { continue; }
                [$anterior, $posterior, $rotulos] = self::saldos($c, $cc, $p);
                $linhas = [];
                $e = 0.0; $s = 0.0;
                foreach ($lista as $m) {
                    $entrada = (float) $m->entrada_valor; $saida = (float) $m->saida_valor;
                    $e += $entrada; $s += $saida;
                    $linhas[] = [
                        'data' => self::br($m->{$col}),
                        'compensacao' => self::br($m->data_compensacao),
                        'tipo' => $tipos[(int) $m->tipo_extrato_id] ?? '',
                        'historico' => (string) $m->historico,
                        'entrada' => $entrada,
                        'saida' => $saida,
                        'compensado' => ($m->compensado === 'S'),
                    ];
                }
                $te += $e; $ts += $s;
                $contas[] = ['conta' => $cc, 'linhas' => $linhas, 'entrada' => round($e, 2), 'saida' => round($s, 2),
                             'saldo_anterior' => $anterior, 'saldo_posterior' => $posterior, 'rotulos' => $rotulos];
            }
            usort($contas, function ($a, $b) { return strcmp((string) $a['conta']->nome, (string) $b['conta']->nome); });
            $r = ['filtros' => ExtratoParametrosService::descrever($p), 'contas' => $contas, 'total_entrada' => round($te, 2), 'total_saida' => round($ts, 2),
                  'aviso' => $aviso, 'coluna_data' => ExtratoParametrosService::ROTULO_DATA[$p['periodo_tipo'] ?? 'compensacao'] ?? 'Compensação',
                  'filtrado' => (bool) array_filter([$p['tipo_extrato_ids'] ?? [], $p['historico'] ?? '', $p['entrada_valor'] ?? null, $p['saida_valor'] ?? null, $p['pessoa_id'] ?? null, $p['processo_id'] ?? null, $p['situacao'] ?? ''],
                        function ($v) { return $v !== null && $v !== '' && $v !== []; })];
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /**
     * Saldos da conta-caixa (so compensados): com periodo pela compensacao, anterior = ate o dia anterior ao inicio
     * e posterior = ate o fim; sem isso, o saldo compensado ate hoje.
     */
    private static function saldos(PDO $c, object $cc, array $p): array
    {
        $soma = function (?string $ate, bool $antes) use ($c, $cc) {
            $sql = "SELECT COALESCE(SUM(COALESCE(entrada_valor, 0) - COALESCE(saida_valor, 0)), 0) FROM extrato WHERE conta_caixa_id = ? AND compensado = 'S'";
            $par = [(int) $cc->id];
            if ($ate) { $sql .= $antes ? ' AND data_compensacao < ?' : ' AND data_compensacao <= ?'; $par[] = $ate; }
            $st = $c->prepare($sql); $st->execute($par);
            return round((float) $cc->saldo_inicial + (float) $st->fetchColumn(), 2);
        };
        if (($p['periodo_tipo'] ?? 'compensacao') === 'compensacao' && (!empty($p['data_de']) || !empty($p['data_ate']))) {
            return [!empty($p['data_de']) ? $soma($p['data_de'], true) : round((float) $cc->saldo_inicial, 2), $soma($p['data_ate'] ?: date('Y-m-d'), false), ['Saldo anterior', 'Saldo posterior']];
        }
        return [null, $soma(null, false), [null, 'Saldo compensado atual']];
    }

    /** @return array ['arquivo' => caminho em tmp/, 'nome' => nome para download] */
    public static function gerarPdf(array $p): array
    {
        $d = self::dados($p);
        $e = function ($t) { return htmlspecialchars(self::nfc($t), ENT_QUOTES, 'UTF-8'); };
        $m = function ($v) { return number_format((float) $v, 2, ',', '.'); };
        $corpo = '';
        foreach ($d['contas'] as $k) {
            $cc = $k['conta'];
            $corpo .= '<h3>Conta caixa: ' . $e($cc->nome) . '</h3><table class="cab"><tr><td>Tipo: ' . $e($cc->tipo_nome) . '</td><td>Data inicial da conta: ' . $e(self::br($cc->dt_inicial)) . '</td>'
                . ($cc->codigo_agencia || $cc->codigo_conta ? '<td>Agência: ' . $e($cc->codigo_agencia) . ' · Conta: ' . $e($cc->codigo_conta) . ($cc->banco_nome ? ' · Banco: ' . $e($cc->banco_nome) : '') . '</td>' : '<td></td>') . '</tr></table>';
            if ($k['rotulos'][0]) { $corpo .= '<div class="saldo">' . $e($k['rotulos'][0]) . ': <b>R$ ' . $m($k['saldo_anterior']) . '</b></div>'; }
            $corpo .= '<table class="t"><thead><tr><th style="width:10%">' . $e($d['coluna_data']) . '</th>' . ($d['coluna_data'] !== 'Compensação' ? '<th style="width:10%">Compensação</th>' : '')
                . '<th style="width:12%">Tipo</th><th>Histórico</th><th style="width:11%" class="v">Entrada</th><th style="width:11%" class="v">Saída</th></tr></thead><tbody>';
            foreach ($k['linhas'] as $l) {
                $corpo .= '<tr' . ($l['compensado'] ? '' : ' class="nc"') . '><td>' . $e($l['data']) . '</td>' . ($d['coluna_data'] !== 'Compensação' ? '<td>' . $e($l['compensacao'] ?: 'não comp.') . '</td>' : '')
                    . '<td>' . $e($l['tipo']) . '</td><td>' . $e($l['historico']) . '</td><td class="v">' . ($l['entrada'] ? $m($l['entrada']) : '') . '</td><td class="v">' . ($l['saida'] ? $m($l['saida']) : '') . '</td></tr>';
            }
            if (!$k['linhas']) { $corpo .= '<tr><td colspan="6">Nenhuma movimentação com os filtros.</td></tr>'; }
            $corpo .= '</tbody></table><div class="sub">Entradas: <b>R$ ' . $m($k['entrada']) . '</b> · Saídas: <b>R$ ' . $m($k['saida']) . '</b> · Movimentação: <b>R$ ' . $m($k['entrada'] - $k['saida']) . '</b></div>'
                . '<div class="saldo">' . $e($k['rotulos'][1]) . ': <b>R$ ' . $m($k['saldo_posterior']) . '</b></div>';
        }
        if ($corpo === '') { $corpo = '<p>Nenhuma movimentação encontrada com os filtros informados.</p>'; }
        $logo = is_file(LevantamentoPrestacaoContasService::CABECALHO_PADRAO) ? '<img src="' . LevantamentoPrestacaoContasService::CABECALHO_PADRAO . '" style="width:48%">' : '';
        $nota = ($d['filtrado'] ? '<div class="nota">Com filtros de situação, tipo, histórico, valor, pessoa ou processo, as movimentações listadas são só as que atendem aos filtros; os saldos são da conta-caixa inteira (compensados).</div>' : '')
            . ($d['aviso'] ? '<div class="nota">' . $e($d['aviso']) . '</div>' : '');

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            @page { margin: 26px 30px 40px 30px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1b1b1b; }
            h1 { font-size: 13pt; margin: 10px 0 2px 0; }
            h3 { font-size: 10pt; margin: 16px 0 2px 0; color: #122945; }
            .filtros, .nota { color: #555; margin-bottom: 6px; }
            table.cab { width: 100%; margin-bottom: 4px; } table.cab td { padding: 1px 0; }
            table.t { width: 100%; border-collapse: collapse; }
            table.t th { text-align: left; background: #005284; color: #fff; padding: 3px 4px; }
            table.t td { border-bottom: 1px solid #e3e3e3; padding: 3px 4px; vertical-align: top; }
            tr.nc td { color: #8a5a00; }
            .v { text-align: right; white-space: nowrap; }
            .sub { text-align: right; margin-top: 4px; }
            .saldo { font-size: 9.5pt; margin: 4px 0; }
            .geral { margin-top: 16px; padding-top: 6px; border-top: 2px solid #122945; text-align: right; font-size: 10pt; }
        </style></head><body>' . $logo
            . '<h1>Extrato</h1><div class="filtros">' . $e($d['filtros']) . ' · emitido em ' . date('d/m/Y H:i') . '</div>' . $nota . $corpo
            . (count($d['contas']) > 1 ? '<div class="geral">Total geral — entradas: <b>R$ ' . $m($d['total_entrada']) . '</b> · saídas: <b>R$ ' . $m($d['total_saida']) . '</b> · movimentação: <b>R$ ' . $m($d['total_entrada'] - $d['total_saida']) . '</b></div>' : '')
            . '</body></html>';

        $options = new Options();
        $options->set('dpi', '110');
        $options->setIsRemoteEnabled(false);
        $options->setChroot(getcwd());
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $arquivo = 'tmp/extrato_' . uniqid() . '.pdf';
        file_put_contents($arquivo, $dompdf->output());
        return ['arquivo' => $arquivo, 'nome' => 'Extrato_' . date('Ymd') . '.pdf'];
    }

    /** Excel no layout do relatorio sintetico de conta-caixa, uma secao por conta. */
    public static function gerarXls(array $p): array
    {
        $d = self::dados($p);
        $outraData = ($d['coluna_data'] !== 'Compensação');
        $n = $outraData ? 6 : 5;
        $t = new TTableWriterXLS($outraData ? [110, 110, 120, 420, 110, 110] : [130, 120, 420, 110, 110]);
        $t->addStyle('titleAll', 'Helvetica', '14', 'B', '#FFFFFF', '#122945');
        $t->addStyle('title', 'Helvetica', '10', 'B', '#FFFFFF', '#005284');
        $t->addStyle('data', 'Helvetica', '10', ' ', '#000000', '#FFFFFF');
        $t->addStyle('dataSaldo', 'Helvetica', '10', 'B', '#000000', '#FFFFFF');
        $t->addStyle('calcSaldo', 'Helvetica', '12', 'B', '#000000', '#FFFFFF');
        $num = function ($v) { return number_format((float) $v, 2, ',', '.'); };

        $t->addRow(); $t->addCell('Extrato', 'center', 'titleAll', $n);
        $t->addRow(); $t->addCell(self::nfc($d['filtros']), 'left', 'data', $n);
        if ($d['aviso']) { $t->addRow(); $t->addCell($d['aviso'], 'left', 'data', $n); }
        foreach ($d['contas'] as $k) {
            $cc = $k['conta'];
            $t->addRow(); $t->addCell('', 'left', 'data', $n);
            $t->addRow(); $t->addCell('Conta caixa: ' . self::nfc($cc->nome), 'center', 'titleAll', $n);
            $t->addRow(); $t->addCell('Tipo: ' . $cc->tipo_nome, 'left', 'data', 2); $t->addCell('Data inicial da conta: ' . self::br($cc->dt_inicial), 'left', 'data', $n - 2);
            if ($cc->codigo_agencia || $cc->codigo_conta) {
                $t->addRow(); $t->addCell('Agência: ' . $cc->codigo_agencia, 'left', 'data'); $t->addCell('Conta: ' . $cc->codigo_conta, 'left', 'data'); $t->addCell('Banco: ' . $cc->banco_nome, 'left', 'data', $n - 2);
            }
            if ($k['rotulos'][0]) { $t->addRow(); $t->addCell($k['rotulos'][0] . ':', 'center', 'calcSaldo'); $t->addCell($num($k['saldo_anterior']), 'center', 'calcSaldo'); $t->addCell('', 'left', 'data', $n - 2); }
            $t->addRow();
            $t->addCell($d['coluna_data'], 'center', 'title');
            if ($outraData) { $t->addCell('Compensação', 'center', 'title'); }
            $t->addCell('Tipo', 'center', 'title'); $t->addCell('Histórico', 'center', 'title'); $t->addCell('Valor da entrada', 'center', 'title'); $t->addCell('Valor da saída', 'center', 'title');
            foreach ($k['linhas'] as $l) {
                $t->addRow();
                $t->addCell($l['data'], 'center', 'data');
                if ($outraData) { $t->addCell($l['compensacao'] ?: 'não comp.', 'center', 'data'); }
                $t->addCell($l['tipo'], 'left', 'data'); $t->addCell(self::nfc($l['historico']), 'left', 'data');
                $t->addCell($num($l['entrada']), 'right', 'data'); $t->addCell($num($l['saida']), 'right', 'data');
            }
            $t->addRow(); $t->addCell('Movimentação:', 'center', 'dataSaldo'); $t->addCell($num($k['entrada'] - $k['saida']), 'center', 'dataSaldo'); $t->addCell('', 'left', 'data', $n - 2);
            $t->addRow(); $t->addCell($k['rotulos'][1] . ':', 'center', 'calcSaldo'); $t->addCell($num($k['saldo_posterior']), 'center', 'calcSaldo'); $t->addCell('', 'left', 'data', $n - 2);
        }
        if (!$d['contas']) { $t->addRow(); $t->addCell('Nenhuma movimentação encontrada com os filtros informados.', 'left', 'data', $n); }
        $arquivo = 'tmp/extrato_' . uniqid() . '.xls';
        $t->save($arquivo);
        return ['arquivo' => $arquivo, 'nome' => 'Extrato_' . date('Ymd') . '.xls'];
    }

    private static function br($v): string
    {
        $v = substr((string) $v, 0, 10);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? date('d/m/Y', strtotime($v)) : '';
    }

    private static function nfc($t): string
    {
        $t = (string) $t;
        return class_exists('Normalizer') ? (Normalizer::normalize($t, Normalizer::FORM_C) ?: $t) : $t;
    }
}
