<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Prestacao de contas ao cliente do financeiro do levantamento (modelo da aba "Prestacao de Contas"
 * da planilha Valores Pagos RPV-Precatorio). Os valores vem da ficha gravada ao gerar o financeiro,
 * entao a carta nao muda se o cadastro mudar depois. Gera PDF e Word com o mesmo conteudo.
 */
class LevantamentoPrestacaoContasService
{
    const CABECALHO_PADRAO = 'app/images/prestacao_contas_cabecalho.jpg';

    /** Ha prestacao de contas para este financeiro? (so quando ha valor ao cliente, ex.: Sucumbencia nao tem) */
    public static function disponivel(LevantamentoFinanceiro $ficha): bool
    {
        return $ficha->regra !== BeneficiarioCredito::REGRA_SOMENTE_HONORARIOS && (float) $ficha->z > 0;
    }

    /** Pagamento (etapa 3) da conta a receber gerada pelo levantamento, se ela tiver prestacao de contas; senao null. */
    public static function etapa3DaContaReceber(int $contaId): ?int
    {
        if ($contaId <= 0) { return null; }
        $propria = self::abrir();
        try {
            $ficha = LevantamentoFinanceiro::where('conta_receber_id', '=', $contaId)->first();
            $r = ($ficha && self::disponivel($ficha)) ? (int) $ficha->requisicao_pagamento_etapa3_id : null;
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /** Condicao de exibicao na lista de contas a receber (so contas do levantamento com prestacao). */
    public static function temPrestacao($conta): bool
    {
        return !empty($conta->requisicao_pagamento_id) && self::etapa3DaContaReceber((int) $conta->id) !== null;
    }

    /**
     * Dados da carta. Exige transacao aberta.
     * @return array cliente, documento, processo, objeto, linhas [rotulo, valor, destaque], escritorio, logo
     */
    public static function dados(int $etapa3Id): array
    {
        $c = TTransaction::get();
        if (!$c) { throw new Exception('Prestação de contas exige uma transação aberta.'); }

        $ficha = LevantamentoFinanceiro::where('requisicao_pagamento_etapa3_id', '=', $etapa3Id)->first();
        if (!$ficha) { throw new Exception('Este pagamento não tem financeiro gerado.'); }
        if (!self::disponivel($ficha)) { throw new Exception('Este financeiro não tem valor ao cliente: não há prestação de contas a enviar.'); }

        $st = $c->prepare('SELECT p.nome, p.cpf_cnpj, rpc.valor AS requisitado, e.saldo_bruto, e.processo_filho_id
                             FROM requisicao_pagamento_etapa3 e
                             JOIN requisicao_pagamento_cliente rpc ON rpc.id = e.requisicao_pagamento_cliente_id
                             JOIN pessoa p ON p.id = rpc.pessoa_id
                            WHERE e.id = ?');
        $st->execute([$etapa3Id]);
        $b = $st->fetch(PDO::FETCH_OBJ);

        // processo do pagamento e, se for incidente, o principal entre parenteses (como na planilha)
        $processoId = (int) ($b->processo_filho_id ?: $ficha->processo_id);
        $processo = Processo::find($processoId);
        $numero = $processo ? self::numeroProcesso($processo) : '';
        $ancestrais = $processoId ? ProcessoFamiliaService::ancestrais($processoId) : [];
        $raiz = $ancestrais ? Processo::find((int) end($ancestrais)) : null;
        if ($raiz && self::numeroProcesso($raiz) !== '' && self::numeroProcesso($raiz) !== $numero) {
            $numero .= ' (' . self::numeroProcesso($raiz) . ')';
        }
        $objeto = '';
        foreach (array_filter([$processo, $raiz]) as $p) {
            if (!empty($p->assunto_id) && ($a = Assunto::find($p->assunto_id))) { $objeto = (string) $a->nome; break; }
        }

        $bruto = (float) $ficha->bruto_depositado;
        $linhas = [];
        if ((float) $b->requisitado > 0) { $linhas[] = ['Cálculos Homologados (bruto)', (float) $b->requisitado, false]; }
        $linhas[] = ['Valor Atualizado para Depósito (bruto)', round($bruto + (float) $b->saldo_bruto, 2), false];
        $linhas[] = ['Valor Depositado (atualizado)', $bruto, false];
        if ($ficha->desconto_previdenciario !== null && $ficha->desconto_previdenciario !== '') {
            $linhas[] = ['Desconto Previdenciário (-)', (float) $ficha->desconto_previdenciario, false];
            $linhas[] = ['Desconto Assistencial (-)', (float) $ficha->desconto_assistencial, false];
            $linhas[] = ['Desconto IRRF (-)', (float) $ficha->desconto_irrf, false];
        } else {
            $linhas[] = ['Descontos Legais (-)', (float) $ficha->descontos, false];
        }
        if ((float) $ficha->u > 0) { $linhas[] = ['Desconto de Honorários Contratuais (-)', (float) $ficha->u, false]; }
        $linhas[] = ['Líquido Cliente na data do Depósito', (float) $ficha->v, false];
        $linhas[] = ['separador', null, false];
        if ((float) $ficha->aa > 0) { $linhas[] = ['Líquido Honorários Levantado (atualizado)', (float) $ficha->aa, false]; }
        $linhas[] = ['Líquido Cliente Levantado (atualizado)', (float) $ficha->z, true];

        $conta = Conta::find((int) $ficha->conta_receber_id);
        $esc = $conta ? Escritorio::find((int) $conta->escritorio_id) : null;

        return [
            'cliente' => (string) $b->nome,
            'documento' => self::documento((string) $b->cpf_cnpj),
            'processo' => $numero,
            'objeto' => $objeto,
            'linhas' => $linhas,
            'escritorio' => self::escritorio($esc),
            // logo do cadastro do escritorio; se o arquivo nao existir, o cabecalho do modelo da planilha
            'logo' => ($esc && $esc->logo_documento && is_file($esc->logo_documento)) ? $esc->logo_documento
                    : (is_file(self::CABECALHO_PADRAO) ? self::CABECALHO_PADRAO : null),
        ];
    }

    /** Gera o PDF em tmp/. @return array ['arquivo' => caminho, 'nome' => nome para download] */
    public static function gerarPdf(int $etapa3Id): array
    {
        $propria = self::abrir();
        try {
            $d = self::dados($etapa3Id);
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }

        $options = new Options();
        $options->set('dpi', '128');
        $options->setIsRemoteEnabled(false);
        $options->setChroot(getcwd());
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::html($d));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $arquivo = 'tmp/prestacao_contas_' . $etapa3Id . '_' . uniqid() . '.pdf';
        file_put_contents($arquivo, $dompdf->output());
        return ['arquivo' => $arquivo, 'nome' => self::nomeArquivo($d['cliente'], 'pdf')];
    }

    /** Gera o Word (.docx) em tmp/. @return array ['arquivo' => caminho, 'nome' => nome para download] */
    public static function gerarWord(int $etapa3Id): array
    {
        $propria = self::abrir();
        try {
            $d = self::dados($etapa3Id);
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $w = new \PhpOffice\PhpWord\PhpWord();
        $w->setDefaultFontName('Calibri');
        $w->setDefaultFontSize(11);
        $secao = $w->addSection(['marginTop' => 700, 'marginBottom' => 700, 'marginLeft' => 1000, 'marginRight' => 1000]);

        if ($d['logo']) {
            $secao->addImage($d['logo'], ['width' => 480, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        }
        $p0 = ['spaceAfter' => 0];
        $secao->addTextBreak(1);
        $secao->addText('Ilmo(a) Sr(a)', [], $p0);
        $secao->addText($d['cliente'], ['bold' => true], $p0);
        if ($d['documento'] !== '') { $secao->addText($d['documento'], [], $p0); }
        $secao->addTextBreak(1);
        $secao->addText('Processo ' . $d['processo'], ['bold' => true], $p0);
        if ($d['objeto'] !== '') { $secao->addText('Objeto: ' . $d['objeto'], [], $p0); }
        $secao->addTextBreak(1);
        $just = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'spaceAfter' => 120];
        foreach (self::paragrafosAbertura() as $t) { $secao->addText($t, [], $just); }

        $tabela = $secao->addTable(['alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER, 'cellMargin' => 60]);
        foreach ($d['linhas'] as [$rotulo, $valor, $destaque]) {
            if ($rotulo === 'separador') { $tabela->addRow(120); $tabela->addCell(5400); $tabela->addCell(2400); continue; }
            $fonte = $destaque ? ['bold' => true] : [];
            $estilo = $destaque ? ['borderTopSize' => 8, 'borderTopColor' => '1F3A5F', 'bgColor' => 'EAF0F7'] : ['borderBottomSize' => 4, 'borderBottomColor' => 'D9D9D9'];
            $tabela->addRow();
            $tabela->addCell(5400, $estilo)->addText($rotulo, $fonte, ['spaceAfter' => 0]);
            $tabela->addCell(2400, $estilo)->addText(self::moeda($valor), $fonte, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END, 'spaceAfter' => 0]);
        }

        $secao->addTextBreak(1);
        $secao->addText(self::textoDadosBancarios(), [], $just);
        $caixa = $secao->addTable(['borderSize' => 6, 'borderColor' => '1F3A5F', 'cellMargin' => 120, 'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER]);
        $caixa->addRow();
        $cel = $caixa->addCell(7800);
        $cel->addText('DADOS BANCÁRIOS PARA CRÉDITO', ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 120]);
        foreach (self::linhasDadosBancarios() as $t) { $cel->addText($t, [], ['spaceAfter' => 80]); }
        $cel->addText(self::notaPix(), ['italic' => true, 'size' => 9], ['spaceAfter' => 0]);

        $secao->addTextBreak(1);
        $secao->addText('Atenciosamente,', [], $p0);
        $secao->addTextBreak(2);
        $centro = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0];
        $secao->addText(mb_strtoupper($d['escritorio']['nome']), ['bold' => true], $centro);
        $rodape = $secao->addFooter();
        foreach ($d['escritorio']['rodape'] as $t) { $rodape->addText($t, ['size' => 8, 'color' => '555555'], $centro); }

        $arquivo = 'tmp/prestacao_contas_' . $etapa3Id . '_' . uniqid() . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($w, 'Word2007')->save($arquivo);
        return ['arquivo' => $arquivo, 'nome' => self::nomeArquivo($d['cliente'], 'docx')];
    }

    /** HTML A4 da carta (dompdf). */
    public static function html(array $d): string
    {
        $e = function ($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); };
        $linhas = '';
        foreach ($d['linhas'] as [$rotulo, $valor, $destaque]) {
            if ($rotulo === 'separador') { $linhas .= '<tr><td colspan="2" style="height:8px;border:0"></td></tr>'; continue; }
            $cls = $destaque ? ' class="destaque"' : '';
            $linhas .= "<tr{$cls}><td>{$e($rotulo)}</td><td class=\"valor\">{$e(self::moeda($valor))}</td></tr>";
        }
        $abertura = implode('', array_map(function ($t) use ($e) { return '<p class="just">' . $e($t) . '</p>'; }, self::paragrafosAbertura()));
        $banco = implode('', array_map(function ($t) use ($e) { return '<p class="banco">' . $e($t) . '</p>'; }, self::linhasDadosBancarios()));
        $rodape = implode('<br>', array_map($e, $d['escritorio']['rodape']));
        $logo = $d['logo'] ? '<img src="' . $e($d['logo']) . '" style="width:100%">' : '';
        $documento = $d['documento'] !== '' ? '<div>' . $e($d['documento']) . '</div>' : '';
        $objeto = $d['objeto'] !== '' ? '<div>Objeto: ' . $e($d['objeto']) . '</div>' : '';

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            @page { margin: 28px 46px 70px 46px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1b1b1b; }
            .rodape { position: fixed; bottom: -48px; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: #555; border-top: 1px solid #bbb; padding-top: 5px; }
            .dest div { margin: 0; }
            .proc { margin-top: 14px; }
            .just { text-align: justify; margin: 0 0 8px 0; }
            table.valores { width: 82%; margin: 6px auto 14px auto; border-collapse: collapse; }
            table.valores td { padding: 4px 6px; border-bottom: 1px solid #ddd; }
            table.valores td.valor { text-align: right; white-space: nowrap; width: 32%; }
            table.valores tr.destaque td { font-weight: bold; background: #eaf0f7; border-top: 2px solid #1f3a5f; border-bottom: 0; }
            .caixa { border: 1px solid #1f3a5f; padding: 8px 12px; margin: 8px auto 0 auto; width: 82%; }
            .caixa h4 { text-align: center; margin: 0 0 8px 0; font-size: 10.5pt; }
            .banco { margin: 0 0 6px 0; }
            .nota { font-size: 8pt; font-style: italic; margin: 6px 0 0 0; }
            .assinatura { text-align: center; font-weight: bold; margin-top: 34px; }
        </style></head><body>'
            . '<div class="rodape">' . $rodape . '</div>'
            . $logo
            . '<div class="dest" style="margin-top:14px"><div>Ilmo(a) Sr(a)</div><div><b>' . $e($d['cliente']) . '</b></div>' . $documento . '</div>'
            . '<div class="dest proc"><div><b>Processo ' . $e($d['processo']) . '</b></div>' . $objeto . '</div>'
            . '<div style="margin-top:14px">' . $abertura . '</div>'
            . '<table class="valores">' . $linhas . '</table>'
            . '<p class="just">' . $e(self::textoDadosBancarios()) . '</p>'
            . '<div class="caixa"><h4>DADOS BANCÁRIOS PARA CRÉDITO</h4>' . $banco . '<p class="nota">' . $e(self::notaPix()) . '</p></div>'
            . '<p style="margin-top:18px">Atenciosamente,</p>'
            . '<div class="assinatura">' . $e(mb_strtoupper($d['escritorio']['nome'])) . '</div>'
            . '</body></html>';
    }

    // --- textos fixos do modelo ---

    private static function paragrafosAbertura(): array
    {
        return [
            'Temos a satisfação de comunicar que ocorreu a liberação do depósito judicial realizado no processo em epígrafe.',
            'Nos termos dos cálculos apurados nos autos, Vossa Senhoria faz jus à importância líquida atualizada conforme o seguinte:',
        ];
    }

    private static function textoDadosBancarios(): string
    {
        return 'Por gentileza, informe os dados bancários para que possamos efetuar o pagamento do valor líquido que lhe é devido. '
            . 'O pagamento será realizado em até 2 (dois) dias úteis, contados do envio dos dados bancários:';
    }

    private static function linhasDadosBancarios(): array
    {
        return [
            'Banco: ______________________________________________________',
            'Agência: ________________     Conta: ____________________________',
            'Tipo de Conta:   ( ) Conta Corrente   ( ) Poupança',
            'Chave PIX*: ___________________________________________________',
            'Tipo de Chave:   ( ) CPF/CNPJ   ( ) Celular   ( ) E-mail   ( ) Chave Aleatória',
        ];
    }

    private static function notaPix(): string
    {
        return '* Em caso de pagamento via PIX, será descontado do valor a ser creditado o montante correspondente à respectiva tarifa bancária.';
    }

    // --- auxiliares ---

    private static function abrir(): bool
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        return $propria;
    }

    private static function escritorio(?Escritorio $esc): array
    {
        $nome = $esc ? (string) $esc->nome : 'Curciol Sociedade de Advogados';
        $linha1 = [];
        if ($esc) {
            $end = trim((string) $esc->endereco . ($esc->numero ? ', ' . $esc->numero : ''));
            if ($end !== '') { $linha1[] = $end; }
            if ($esc->bairro) { $linha1[] = (string) $esc->bairro; }
            $cidade = !empty($esc->cidade_id) ? Cidade::find($esc->cidade_id) : null;
            if ($cidade) {
                $uf = (!empty($cidade->estado_id) && ($est = Estado::find($cidade->estado_id))) ? (string) $est->sigla : '';
                $linha1[] = $cidade->nome . ($uf ? '/' . $uf : '');
            }
            if ($esc->cep) { $linha1[] = 'CEP ' . self::cep((string) $esc->cep); }
        }
        $linha2 = [];
        if ($esc && $esc->telefone) { $linha2[] = self::telefone((string) $esc->telefone); }
        if ($esc && $esc->email) { $linha2[] = (string) $esc->email; }
        return ['nome' => $nome, 'rodape' => array_values(array_filter([implode('  |  ', $linha1), implode('  |  ', $linha2)]))];
    }

    private static function numeroProcesso(Processo $p): string
    {
        return trim((string) ($p->numero_cnj_numero ?: $p->numero_outro));
    }

    private static function moeda($v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    private static function documento(string $doc): string
    {
        $n = preg_replace('/\D/', '', $doc);
        if (strlen($n) === 11) { return 'CPF: ' . substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9); }
        if (strlen($n) === 14) { return 'CNPJ: ' . substr($n, 0, 2) . '.' . substr($n, 2, 3) . '.' . substr($n, 5, 3) . '/' . substr($n, 8, 4) . '-' . substr($n, 12); }
        return $doc !== '' ? 'CPF/CNPJ: ' . $doc : '';
    }

    private static function cep(string $cep): string
    {
        $n = preg_replace('/\D/', '', $cep);
        return strlen($n) === 8 ? substr($n, 0, 5) . '-' . substr($n, 5) : $cep;
    }

    private static function telefone(string $tel): string
    {
        $n = preg_replace('/\D/', '', $tel);
        if (strlen($n) === 10) { return '(' . substr($n, 0, 2) . ') ' . substr($n, 2, 4) . '-' . substr($n, 6); }
        if (strlen($n) === 11) { return '(' . substr($n, 0, 2) . ') ' . substr($n, 2, 5) . '-' . substr($n, 7); }
        return $tel;
    }

    /** Nome do arquivo para download, sem acentos (vai no cabecalho HTTP). */
    private static function nomeArquivo(string $cliente, string $ext): string
    {
        $base = class_exists('Normalizer') ? Normalizer::normalize($cliente, Normalizer::FORM_D) : $cliente;
        $base = preg_replace('/[^A-Za-z0-9 ]+/', '', preg_replace('/\p{Mn}/u', '', $base));
        $base = trim(preg_replace('/\s+/', '_', $base), '_');
        return 'Prestacao_de_contas_' . ($base !== '' ? $base : 'cliente') . '.' . $ext;
    }
}
