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

            $form->addContent([self::bloco($d)]);

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
        } catch (Exception $e) {
            $janela->add(LevantamentoFinanceiroForm::caixaAviso(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), 'erro'));
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
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onDesfazer($param)
    {
        try {
            LevantamentoFinanceiroService::desfazer((int) ($param['etapa3_id'] ?? 0));

            LevantamentoFinanceiroForm::fecharJanela();
            TToast::show('success', 'Financeiro desfeito', 'topRight', 'far:check-circle');
            LevantamentoFinanceiroForm::recarregarOrigem($param);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
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

            $r->receber = $ficha->conta_receber_id ? $c->query('SELECT id, total_conta, quitada FROM conta WHERE id = ' . (int) $ficha->conta_receber_id)->fetch(PDO::FETCH_OBJ) : null;
            $r->pagar = $ficha->conta_pagar_id ? $c->query('SELECT id, total_conta, quitada FROM conta WHERE id = ' . (int) $ficha->conta_pagar_id)->fetch(PDO::FETCH_OBJ) : null;

            // origem (so para exibir; se o pagamento mudou e o contexto nao carrega, a ficha continua valendo)
            $r->ctx = null;
            try { $r->ctx = LevantamentoContextoService::carregar($etapa3Id); } catch (Exception $e) { $r->ctx = null; }

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
            . '</div>';

        // memoria de calculo
        $o .= '<div class="lf-titulo">Memória de cálculo</div><div class="lf-grade lf-grade3">'
            . $par('S - descontos legais', $m($f->descontos))
            . $par('U - honorários sobre o bruto', $m($f->u))
            . $par('V - parte do cliente no bruto', $m($f->v))
            . $par('W - líquido (U + V)', $m($f->w))
            . $par('AA - honorários sobre o levantado', $m($f->aa))
            . $par('AC - custo de NF', $m($f->ac))
            . $par('AD - honorários líquidos', $m($f->ad))
            . $par('Z - valor ao cliente', $m($f->z));
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
        $o .= '</div></div>';

        $el = new TElement('div');
        $el->add($o);
        return $el;
    }

    private static function linkConta(object $conta, string $texto, string $situacao): string
    {
        $url = "index.php?class=ContaFormView&method=onShow&key={$conta->id}&id={$conta->id}&target_container=adianti_right_panel";
        return '<a href="javascript:void(0)" onclick="' . htmlspecialchars("$(this).closest('.ui-dialog-content').dialog('close'); Adianti.waitMessage = false; __adianti_load_page(" . json_encode($url) . "); return false;") . '">'
            . '<i class="fas fa-external-link-alt"></i> ' . htmlspecialchars($texto) . '</a>'
            . '<span class="lf-nota" style="margin-right:18px">(' . LevantamentoFinanceiroForm::moeda($conta->total_conta) . ', ' . htmlspecialchars($situacao) . ')</span>';
    }
}
