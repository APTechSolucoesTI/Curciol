<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Relatorio de repasses a colaboradores (PDF e Excel), com os mesmos filtros da tela:
 * agrupado por colaborador, cada repasse com recebimento, origem, vencimento, valor e situacao;
 * subtotais por colaborador (a pagar, pago, total) e total geral.
 */
class RepasseColaboradorRelatorioService
{
    /** @return array ['grupos' => [nome => ['linhas' => [...], 'aberto' => f, 'pago' => f]], 'aberto' => f, 'pago' => f, 'filtros' => string] */
    public static function dados(array $f): array
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $linhas = RepasseColaboradorService::listar($f);
            $filtros = self::descreverFiltros($f);
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
        $grupos = [];
        $aberto = 0.0; $pago = 0.0;
        foreach ($linhas as $r) {
            $g = &$grupos[$r->colaborador];
            if (!isset($g)) { $g = ['linhas' => [], 'aberto' => 0.0, 'pago' => 0.0]; }
            $situacao = $r->pago_em
                ? 'Pago em ' . date('d/m/Y', strtotime($r->pago_em)) . ($r->compensado === 'S' ? ' - compensado' : '')
                : 'A pagar';
            $g['linhas'][] = [
                'recebido' => date('d/m/Y', strtotime($r->data_recebimento)),
                'origem' => RepasseColaboradorService::origem($r),
                'vencimento' => $r->vencimento ? date('d/m/Y', strtotime($r->vencimento)) : '',
                'valor' => (float) $r->valor,
                'situacao' => $situacao,
            ];
            if ($r->pago_em) { $g['pago'] += (float) $r->valor; $pago += (float) $r->valor; }
            else { $g['aberto'] += (float) $r->valor; $aberto += (float) $r->valor; }
            unset($g);
        }
        return ['grupos' => $grupos, 'aberto' => round($aberto, 2), 'pago' => round($pago, 2), 'filtros' => $filtros];
    }

    /** @return array ['arquivo' => caminho em tmp/, 'nome' => nome para download] */
    public static function gerarPdf(array $f): array
    {
        $d = self::dados($f);
        $e = function ($t) { return htmlspecialchars(self::nfc($t), ENT_QUOTES, 'UTF-8'); };
        $m = function ($v) { return 'R$ ' . number_format((float) $v, 2, ',', '.'); };

        $corpo = '';
        foreach ($d['grupos'] as $nome => $g) {
            $linhas = '';
            foreach ($g['linhas'] as $l) {
                $linhas .= '<tr><td>' . $e($l['recebido']) . '</td><td>' . $e($l['origem']) . '</td><td>' . $e($l['vencimento']) . '</td><td class="v">' . $e($m($l['valor'])) . '</td><td>' . $e($l['situacao']) . '</td></tr>';
            }
            $corpo .= '<h3>' . $e($nome) . '</h3><table class="t"><thead><tr><th style="width:11%">Recebido em</th><th>Origem</th><th style="width:11%">Vencimento</th><th style="width:13%" class="v">Valor</th><th style="width:22%">Situação</th></tr></thead><tbody>'
                . $linhas . '</tbody></table>'
                . '<div class="sub">A pagar: <b>' . $e($m($g['aberto'])) . '</b> &nbsp;·&nbsp; Pago: <b>' . $e($m($g['pago'])) . '</b> &nbsp;·&nbsp; Total: <b>' . $e($m($g['aberto'] + $g['pago'])) . '</b></div>';
        }
        if ($corpo === '') { $corpo = '<p>Nenhum repasse encontrado com os filtros informados.</p>'; }
        $logo = is_file(LevantamentoPrestacaoContasService::CABECALHO_PADRAO) ? '<img src="' . LevantamentoPrestacaoContasService::CABECALHO_PADRAO . '" style="width:48%">' : '';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            @page { margin: 26px 30px 40px 30px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1b1b1b; }
            h1 { font-size: 13pt; margin: 10px 0 2px 0; }
            .filtros { color: #555; margin-bottom: 8px; }
            h3 { font-size: 10pt; margin: 14px 0 4px 0; color: #1f3a5f; }
            table.t { width: 100%; border-collapse: collapse; }
            table.t th { text-align: left; background: #eaf0f7; border-bottom: 1px solid #1f3a5f; padding: 3px 4px; }
            table.t td { border-bottom: 1px solid #e3e3e3; padding: 3px 4px; vertical-align: top; }
            .v { text-align: right; white-space: nowrap; }
            .sub { text-align: right; margin-top: 4px; }
            .geral { margin-top: 16px; padding-top: 6px; border-top: 2px solid #1f3a5f; text-align: right; font-size: 10pt; }
        </style></head><body>' . $logo
            . '<h1>Repasses a colaboradores</h1><div class="filtros">' . $e($d['filtros']) . ' · emitido em ' . date('d/m/Y H:i') . '</div>'
            . $corpo
            . '<div class="geral">Total geral — a pagar: <b>' . $e($m($d['aberto'])) . '</b> · pago: <b>' . $e($m($d['pago'])) . '</b> · total: <b>' . $e($m($d['aberto'] + $d['pago'])) . '</b></div>'
            . '</body></html>';

        $options = new Options();
        $options->set('dpi', '110');
        $options->setIsRemoteEnabled(false);
        $options->setChroot(getcwd());
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $arquivo = 'tmp/repasses_colaboradores_' . uniqid() . '.pdf';
        file_put_contents($arquivo, $dompdf->output());
        return ['arquivo' => $arquivo, 'nome' => 'Repasses_colaboradores_' . date('Ymd') . '.pdf'];
    }

    /** @return array ['arquivo' => caminho em tmp/, 'nome' => nome para download] */
    public static function gerarXls(array $f): array
    {
        $d = self::dados($f);
        $t = new TTableWriterXLS([220, 120, 400, 120, 120, 260]);
        $t->addStyle('titulo', 'Helvetica', '10', 'B', '#ffffff', '#1F3A5F');
        $t->addStyle('dado', 'Helvetica', '10', '', '#000000', '#FFFFFF', 'LR');
        $t->addStyle('sub', 'Helvetica', '10', 'B', '#000000', '#EAF0F7', 'LR');
        $t->addRow();
        foreach (['Colaborador', 'Recebido em', 'Origem', 'Vencimento', 'Valor', 'Situação'] as $titulo) { $t->addCell($titulo, 'center', 'titulo'); }
        foreach ($d['grupos'] as $nome => $g) {
            foreach ($g['linhas'] as $l) {
                $t->addRow();
                $t->addCell(self::nfc($nome), 'left', 'dado');
                $t->addCell($l['recebido'], 'center', 'dado');
                $t->addCell(self::nfc($l['origem']), 'left', 'dado');
                $t->addCell($l['vencimento'], 'center', 'dado');
                $t->addCell(number_format($l['valor'], 2, ',', '.'), 'right', 'dado');
                $t->addCell($l['situacao'], 'left', 'dado');
            }
            $t->addRow();
            $t->addCell('Subtotal ' . self::nfc($nome), 'left', 'sub');
            $t->addCell('', 'center', 'sub');
            $t->addCell('A pagar ' . number_format($g['aberto'], 2, ',', '.') . ' · Pago ' . number_format($g['pago'], 2, ',', '.'), 'left', 'sub');
            $t->addCell('', 'center', 'sub');
            $t->addCell(number_format($g['aberto'] + $g['pago'], 2, ',', '.'), 'right', 'sub');
            $t->addCell('', 'left', 'sub');
        }
        $t->addRow();
        $t->addCell('Total geral', 'left', 'titulo');
        $t->addCell('', 'center', 'titulo');
        $t->addCell('A pagar ' . number_format($d['aberto'], 2, ',', '.') . ' · Pago ' . number_format($d['pago'], 2, ',', '.'), 'left', 'titulo');
        $t->addCell('', 'center', 'titulo');
        $t->addCell(number_format($d['aberto'] + $d['pago'], 2, ',', '.'), 'right', 'titulo');
        $t->addCell('', 'left', 'titulo');
        $arquivo = 'tmp/repasses_colaboradores_' . uniqid() . '.xls';
        $t->save($arquivo);
        return ['arquivo' => $arquivo, 'nome' => 'Repasses_colaboradores_' . date('Ymd') . '.xls'];
    }

    /** Acentos compostos (NFC): alguns nomes estao gravados decompostos (NFD) e o PDF/Excel perderiam o acento. */
    private static function nfc($t): string
    {
        $t = (string) $t;
        return class_exists('Normalizer') ? (Normalizer::normalize($t, Normalizer::FORM_C) ?: $t) : $t;
    }

    /** Texto com os filtros aplicados. Exige transacao aberta. */
    public static function descreverFiltros(array $f): string
    {
        $c = TTransaction::get();
        $p = [];
        if (!empty($f['pessoa_id'])) {
            $st = $c->prepare('SELECT nome FROM pessoa WHERE id = ?'); $st->execute([(int) $f['pessoa_id']]);
            $p[] = 'Colaborador: ' . $st->fetchColumn();
        }
        if (!empty($f['processo'])) { $p[] = 'Processo: ' . $f['processo']; }
        if (!empty($f['requisicao_id'])) { $p[] = 'Requisição #' . (int) $f['requisicao_id']; }
        if (!empty($f['contrato'])) { $p[] = 'Contrato: ' . $f['contrato']; }
        if (!empty($f['processo_id'])) {
            $st = $c->prepare('SELECT COALESCE(numero_cnj_numero, numero_outro, id::text) FROM processo WHERE id = ?'); $st->execute([(int) $f['processo_id']]);
            $p[] = 'Processo: ' . $st->fetchColumn() . ' (e filhos)';
        }
        if (!empty($f['contrato_id'])) {
            $st = $c->prepare('SELECT numero FROM contrato WHERE id = ?'); $st->execute([(int) $f['contrato_id']]);
            $p[] = 'Contrato: ' . ($st->fetchColumn() ?: '#' . (int) $f['contrato_id']);
        }
        if (trim((string) ($f['texto'] ?? '')) !== '') { $p[] = 'Origem contém "' . trim($f['texto']) . '"'; }
        if (!empty($f['recebido_em'])) { $p[] = 'Recebido em ' . date('d/m/Y', strtotime($f['recebido_em'])); }
        if (!empty($f['vencimento_em'])) { $p[] = 'Vencimento ' . date('d/m/Y', strtotime($f['vencimento_em'])); }
        if (isset($f['valor']) && $f['valor'] !== null && $f['valor'] !== '') { $p[] = 'Valor R$ ' . number_format((float) $f['valor'], 2, ',', '.'); }
        $p[] = ['aberto' => 'A pagar', 'pago' => 'Pagos'][$f['situacao'] ?? ''] ?? 'Todas as situações';
        if (!empty($f['data_de']) || !empty($f['data_ate'])) {
            $rot = ['recebimento' => 'Recebimento', 'vencimento' => 'Vencimento', 'pagamento' => 'Pagamento'][$f['periodo_tipo'] ?? 'recebimento'] ?? 'Recebimento';
            $p[] = $rot . ': ' . (!empty($f['data_de']) ? date('d/m/Y', strtotime($f['data_de'])) : '...') . ' a ' . (!empty($f['data_ate']) ? date('d/m/Y', strtotime($f['data_ate'])) : '...');
        }
        return implode(' · ', $p);
    }
}
