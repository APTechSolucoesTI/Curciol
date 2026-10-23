<?php

/**
 * Janela "Ver financeiro" de um pagamento: mostra a ficha gravada na geracao
 * (dados usados, memoria de calculo, repasses e contas geradas) e permite desfazer.
 *
 * Le SOMENTE a ficha (levantamento_financeiro + repasses): o que vale e o que foi usado na
 * epoca, mesmo que a parceria ou o contrato tenham mudado depois.
 *
 * Parametros: etapa3_id, origem (janela | aba | detalhe), url_recarga (ver LevantamentoFinanceiroForm).
 */
class LevantamentoFinanceiroView extends TWindow
{
    private static $database = 'escritorio';
    const NOME_FORM = 'formView_LevantamentoFinanceiro';
    private static $formName = self::NOME_FORM;

    public function __construct($param)
    {
        parent::__construct();
        self::montar($this, $param);
    }

    public function onShow($param = null)
    {
    }

    /** Preenche a janela (usada tambem pelo LevantamentoFinanceiroForm quando o pagamento ja foi gerado). */
    public static function montar(TWindow $janela, array $param): void
    {
        $janela->setSize(0.8, null);
        $janela->setTitle('Financeiro do levantamento');
        $janela->setProperty('class', 'window_modal');
        $janela->setDialogClass('curciol-janela-rolavel');

        try {
            $etapa3Id = (int) ($param['etapa3_id'] ?? 0);
            if (LevantamentoFinanceiroService::existePara($etapa3Id) === null) {
                throw new Exception('Este pagamento não tem financeiro gerado.');
            }
            $d = self::carregarFicha($etapa3Id);
            $motivo = LevantamentoFinanceiroService::podeDesfazer($etapa3Id);

            $form = new BootstrapFormBuilder(self::$formName);
            $form->setFormTitle('');

            if (!empty($param['recem_gerado'])) {
                $form->addContent([LevantamentoFinanceiroForm::caixaAviso('<b>Financeiro gerado.</b>'
                    . (LevantamentoPrestacaoContasService::disponivel($d->ficha) ? ' Baixe a prestação de contas (PDF ou Word) para enviar ao cliente.' : ''), 'ok')]);
            }

            $form->addContent([self::bloco($d)]);

            if (LevantamentoPrestacaoContasService::disponivel($d->ficha)) {
                $bWord = $form->addAction('Prestação de contas (Word)', new TAction([__CLASS__, 'onPrestacaoWord'], ['static' => '1', 'etapa3_id' => $etapa3Id]), 'far:file-word');
                $bWord->class = 'btn btn-sm btn-default';
                $bPdf = $form->addAction('Prestação de contas (PDF)', new TAction([__CLASS__, 'onPrestacaoPdf'], ['static' => '1', 'etapa3_id' => $etapa3Id]), 'far:file-pdf');
                $bPdf->class = 'btn btn-sm btn-primary';
            }

            if ($motivo !== null) {
                $aviso = new TElement('div');
                $aviso->class = 'lf-linha-bloqueio';
                $aviso->add('<b>Não é possível desfazer:</b> ' . htmlspecialchars($motivo));
                $form->addContent([$aviso]);
            } else {
                $botao = $form->addAction('Desfazer financeiro',
                    new TAction([__CLASS__, 'onConfirmarDesfazer'], ['static' => '1', 'etapa3_id' => $etapa3Id,
                                'origem' => $param['origem'] ?? '', 'url_recarga' => $param['url_recarga'] ?? '',
                                'origem_pai' => $param['origem_pai'] ?? '', 'url_pai' => $param['url_pai'] ?? '']), 'fas:undo');
                $botao->class = 'btn btn-sm btn-danger';
            }

            $janela->add($form);
        } catch (Throwable $e) {
            $janela->add(LevantamentoFinanceiroForm::caixaAviso(htmlspecialchars(LevantamentoFinanceiroForm::mensagem($e), ENT_QUOTES, 'UTF-8'), 'erro'));
        }
        LevantamentoFinanceiroForm::estilo($janela);
        LevantamentoFinanceiroForm::trazerParaFrente($janela);
    }

    /** Pergunta antes de desfazer. */
    public static function onConfirmarDesfazer($param)
    {
        try {
            $etapa3Id = (int) ($param['etapa3_id'] ?? 0);
            $d = self::carregarFicha($etapa3Id);
            $f = $d->ficha;
            $mensagem = 'Desfazer o financeiro deste pagamento? A baixa de ' . LevantamentoFinanceiroForm::moeda($f->valor_levantado)
                . ' na conta-caixa ' . htmlspecialchars((string) ($d->conta_caixa_nome ?: ('#' . $f->conta_caixa_id)))
                . ' será estornada e as contas geradas serão excluídas.';
            $acao = new TAction([__CLASS__, 'onDesfazer'], ['static' => '1', 'etapa3_id' => $etapa3Id,
                                'origem' => $param['origem'] ?? '', 'url_recarga' => $param['url_recarga'] ?? '',
                                'origem_pai' => $param['origem_pai'] ?? '', 'url_pai' => $param['url_pai'] ?? '']);
            new TQuestion($mensagem, $acao);
        } catch (Throwable $e) {
            new TMessage('error', LevantamentoFinanceiroForm::mensagem($e));
        }
    }

    public static function onDesfazer($param)
    {
        try {
            LevantamentoFinanceiroService::desfazer((int) ($param['etapa3_id'] ?? 0));

            LevantamentoFinanceiroForm::fecharJanela();
            TToast::show('success', 'Financeiro desfeito', 'topRight', 'far:check-circle');
            LevantamentoFinanceiroForm::recarregarOrigem($param);
        } catch (Throwable $e) {
            new TMessage('error', LevantamentoFinanceiroForm::mensagem($e));
        }
    }

    /** Baixa a prestacao de contas ao cliente em PDF. */
    public static function onPrestacaoPdf($param)
    {
        try {
            $doc = LevantamentoPrestacaoContasService::gerarPdf(self::etapa3DoParametro($param));
            TPage::openFile($doc['arquivo'], $doc['nome']);
        } catch (Throwable $e) {
            new TMessage('error', LevantamentoFinanceiroForm::mensagem($e));
        }
    }

    /** Baixa a prestacao de contas ao cliente em Word (editavel). */
    public static function onPrestacaoWord($param)
    {
        try {
            $doc = LevantamentoPrestacaoContasService::gerarWord(self::etapa3DoParametro($param));
            TPage::openFile($doc['arquivo'], $doc['nome']);
        } catch (Throwable $e) {
            new TMessage('error', LevantamentoFinanceiroForm::mensagem($e));
        }
    }

    /** etapa3_id direto (Ver financeiro) ou a partir da conta a receber (lista e detalhe da conta). */
    private static function etapa3DoParametro(array $param): int
    {
        if (!empty($param['etapa3_id'])) { return (int) $param['etapa3_id']; }
        $etapa3 = LevantamentoPrestacaoContasService::etapa3DaContaReceber((int) ($param['conta_id'] ?? 0));
        if (!$etapa3) { throw new Exception('Esta conta não tem prestação de contas do financeiro do levantamento.'); }
        return $etapa3;
    }

    // ---------------------------------------------------------------

    private static function carregarFicha(int $etapa3Id): object
    {
        TTransaction::open(self::$database);
        try {
            $c = TTransaction::get();
            $id = LevantamentoFinanceiroService::existePara($etapa3Id);
            $ficha = new LevantamentoFinanceiro($id);
            $r = (object) ['ficha' => $ficha, 'repasses' => $ficha->repasses()];

            $um = function (string $sql, $valor) use ($c) {
                $st = $c->prepare($sql);
                $st->execute([$valor]);
                $v = $st->fetchColumn();
                return ($v === false) ? null : $v;
            };
            $r->contrato_numero = $um('SELECT numero FROM contrato WHERE id = ?', $ficha->contrato_id);
            $r->beneficiario_nome = $um('SELECT nome FROM beneficiario_credito WHERE id = ?', $ficha->beneficiario_credito_id);
            $r->parceria_nome = $ficha->parceria_levantamento_id ? $um('SELECT nome FROM parceria_levantamento WHERE id = ?', $ficha->parceria_levantamento_id) : null;
            $r->conta_caixa_nome = $um('SELECT nome FROM conta_caixa WHERE id = ?', $ficha->conta_caixa_id);

            $r->nomes = [];
            $ids = array_map(function ($x) { return (int) $x->pessoa_id; }, $r->repasses);
            if ($ids) {
                foreach ($c->query('SELECT id, nome FROM pessoa WHERE id IN (' . implode(',', array_unique($ids)) . ')')->fetchAll(PDO::FETCH_OBJ) as $p) {
                    $r->nomes[(int) $p->id] = $p->nome;
                }
            }

            // colaboradores na ordem da planilha do escritorio
            $r->repasses = RepasseColaboradorService::ordenarPorColaborador($r->repasses,
                function ($x) { return $x->pessoa_id; }, function ($x) use ($r) { return $r->nomes[(int) $x->pessoa_id] ?? ''; });

            $r->receber = $ficha->conta_receber_id ? $c->query('SELECT id, total_conta, quitada FROM conta WHERE id = ' . (int) $ficha->conta_receber_id)->fetch(PDO::FETCH_OBJ) : null;
            $r->repassesColaboradores = RepasseColaboradorService::ordenarPorColaborador(
                RepasseColaboradorService::listar(['levantamento_ids' => [(int) $ficha->id]]),
                function ($x) { return $x->pessoa_id; }, function ($x) { return $x->colaborador; });
            $r->pagar = $ficha->conta_pagar_id ? $c->query('SELECT id, total_conta, quitada FROM conta WHERE id = ' . (int) $ficha->conta_pagar_id)->fetch(PDO::FETCH_OBJ) : null;

            // origem (so para exibir; se o pagamento mudou e o contexto nao carrega, a ficha continua valendo)
            $r->ctx = null;
            try { $r->ctx = LevantamentoContextoService::carregar($etapa3Id); } catch (Throwable $e) { $r->ctx = null; }

            TTransaction::close();
            return $r;
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    private static function bloco(object $d): TElement
    {
        $f = $d->ficha;
        $ctx = $d->ctx;
        $m = function ($v) { return LevantamentoFinanceiroForm::moeda($v); };
        $p = function ($v) { return ($v === null) ? '-' : LevantamentoFinanceiroForm::pctTexto($v) . '%'; };
        $h = function ($v) { return htmlspecialchars((string) $v); };
        $par = function (string $rotulo, string $valorHtml) {
            return '<div class="lf-item"><span>' . htmlspecialchars($rotulo) . '</span><strong style="font-size:13px">' . $valorHtml . '</strong></div>';
        };

        $o = '<div class="lf-ver">';

        // origem
        $o .= '<div class="lf-titulo">Origem do pagamento</div><div class="lf-grade lf-grade3">';
        if ($ctx) {
            $o .= $par('Cliente', $h($ctx->cliente_nome . ($ctx->cliente_documento ? ' - ' . LevantamentoFinanceiroForm::documento($ctx->cliente_documento) : '')))
                . $par('Entidade devedora', $h($ctx->devedor_nome))
                . $par('Requisição', $h('#' . $ctx->requisicao_id . ($ctx->tipo_requisicao ? ' - ' . $ctx->tipo_requisicao : '')))
                . $par('Processo do pagamento', $h($ctx->processo_numero))
                . (LevantamentoFinanceiroForm::raizDiferente($ctx) ? $par('Processo principal', $h($ctx->processo_raiz_numero)) : '')
                . $par('Pagamento', $h($ctx->numero_pagamento . 'º pagamento'))
                . $par('Data do depósito', $h(LevantamentoFinanceiroForm::dataBr($ctx->data_deposito)));
        }
        $o .= $par('Bruto depositado', $m($f->bruto_depositado))
            . $par('Valor do MLE', $m($f->valor_mle))
            . $par('Descontos (bruto - MLE)', $m($f->descontos))
            . '</div>';

        // dados usados
        $o .= '<div class="lf-titulo">Dados usados no cálculo</div><div class="lf-grade lf-grade3">'
            . $par('Contrato', $h($d->contrato_numero ?: ('#' . $f->contrato_id)))
            . $par('Beneficiário do crédito', $h($d->beneficiario_nome))
            . $par('Data do levantamento', $h(LevantamentoFinanceiroForm::dataBr($f->data_levantamento)))
            . $par('Valor levantado', $m($f->valor_levantado))
            . $par('% honorários', $p($f->pct_honorarios))
            . $par('% custo de NF', $p($f->pct_nf))
            . $par('Parceria', $h($d->parceria_nome ?: '-'))
            . $par('Data de distribuição', $h(LevantamentoFinanceiroForm::dataBr($f->data_distribuicao)))
            . $par('Conta-caixa', $h($d->conta_caixa_nome))
            . (($f->desconto_previdenciario !== null && $f->desconto_previdenciario !== '')
                ? $par('Desconto previdenciário', $m($f->desconto_previdenciario)) . $par('Desconto assistencial', $m($f->desconto_assistencial)) . $par('Desconto IRRF', $m($f->desconto_irrf))
                : '')
            . '</div>';

        // memoria de calculo
        $o .= '<div class="lf-titulo">Memória de cálculo</div><div class="lf-grade lf-grade3">'
            . $par('Descontos legais', $m($f->descontos))
            . $par('Honorários sobre o bruto', $m($f->u))
            . $par('Parte do cliente no bruto', $m($f->v))
            . $par('Líquido', $m($f->w))
            . $par('Honorários sobre o levantado', $m($f->aa))
            . $par('Custo de NF', $m($f->ac))
            . $par('Honorários líquidos', $m($f->ad))
            . $par('Valor ao cliente', $m($f->z));
        if ($f->fracao_a !== null) {
            $o .= $par('a - fração antes do 1º marco', $p((float) $f->fracao_a * 100))
                . $par('p - fração antes do 2º marco', $p((float) $f->fracao_p * 100))
                . $par('D - peso do nível 1', $p((float) $f->fator_d * 100));
        }
        $o .= '</div>';

        // repasses
        $o .= '<div class="lf-titulo">Repasses</div>';
        if ($d->repasses) {
            $o .= '<table class="lf-tabela"><thead><tr><th>Pessoa</th><th class="lf-num">%</th><th class="lf-num">Valor</th><th class="lf-num">Ajuste de centavos</th></tr></thead><tbody>';
            foreach ($d->repasses as $r) {
                $o .= '<tr><td>' . $h($d->nomes[(int) $r->pessoa_id] ?? ('Pessoa ' . $r->pessoa_id)) . '</td>'
                    . '<td class="lf-num">' . number_format((float) $r->percentual, 4, ',', '.') . '%</td>'
                    . '<td class="lf-num">' . $m($r->valor) . '</td>'
                    . '<td class="lf-num">' . ((float) $r->ajuste_centavos != 0.0 ? $m($r->ajuste_centavos) : '-') . '</td></tr>';
            }
            $o .= '</tbody></table>';
        } else {
            $o .= '<div class="lf-nota">Sem repasses.</div>';
        }

        // contas geradas
        $o .= '<div class="lf-titulo">Contas geradas</div><div class="lf-contas-geradas">';
        $o .= $d->receber ? self::linkConta($d->receber, 'Abrir conta a receber', 'quitada') : '';
        $o .= $d->pagar ? self::linkConta($d->pagar, 'Abrir conta a pagar ao cliente', ($d->pagar->quitada === 'S') ? 'paga' : 'em aberto')
                          : '<span class="lf-nota">Sem conta a pagar ao cliente.</span>';
        $o .= '</div>';

        // contas a pagar dos colaboradores geradas pelo recebimento
        $o .= '<div class="lf-titulo">Repasses aos colaboradores</div>';
        $o .= $d->repassesColaboradores ? RepasseColaboradorList::secaoHtml($d->repassesColaboradores) : '<div class="lf-nota">Nenhuma conta a pagar de repasse (sem colaboradores além do escritório).</div>';
        $o .= '</div>';

        $el = new TElement('div');
        $el->add($o);
        return $el;
    }

    private static function linkConta(object $conta, string $texto, string $situacao): string
    {
        $url = "index.php?class=ContaFormView&method=onShow&key={$conta->id}&id={$conta->id}&target_container=adianti_right_panel";
        return '<a href="javascript:void(0)" onclick="' . htmlspecialchars("Adianti.waitMessage = false; __adianti_load_page(" . json_encode($url) . "); return false;") . '">'
            . '<i class="fas fa-external-link-alt"></i> ' . htmlspecialchars($texto) . '</a>'
            . '<span class="lf-nota" style="margin-right:18px">(' . LevantamentoFinanceiroForm::moeda($conta->total_conta) . ', ' . htmlspecialchars($situacao) . ')</span>';
    }
}
