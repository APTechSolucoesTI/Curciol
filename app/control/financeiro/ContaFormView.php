<?php

class ContaFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Conta';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Conta';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $conta = new Conta($param['key']);
        // define the form title
        $this->form->setFormTitle("Detalhe da conta #{$param['key']}");

        $transformed_conta_pessoa_nome = call_user_func(function($value, $object, $row)
        {
             try
            {
                if (empty($object->id)) {
                    return '';
                }

                TTransaction::open('escritorio');

                $conta = Conta::find((int) $object->id);

                if (!$conta) {
                    TTransaction::close();
                    return '';
                }

                $nomes = [];

                /*
                 * Se a conta pertence a contrato,
                 * busca todos os clientes do contrato.
                 */
                // conta gerada pelo financeiro do levantamento (tem requisicao): a pessoa e a da propria conta
                if (!empty($conta->contrato_id) && empty($conta->requisicao_pagamento_id))
                {
                    $clientesContrato = ContratoPessoa::where(
                        'contrato_id',
                        '=',
                        $conta->contrato_id
                    )
                    ->orderBy('id')
                    ->load();

                    foreach ($clientesContrato as $contratoPessoa)
                    {
                        if (!empty($contratoPessoa->cliente_id))
                        {
                            $cliente = Pessoa::find($contratoPessoa->cliente_id);

                            if ($cliente && !empty($cliente->nome)) {
                                $nomes[] = $cliente->nome;
                            }
                        }
                    }
                }

                /*
                 * Conta normal / sem contrato:
                 * mantém a pessoa diretamente vinculada à conta.
                 */
                if (empty($nomes) && !empty($conta->pessoa_id))
                {
                    $pessoa = Pessoa::find($conta->pessoa_id);

                    if ($pessoa && !empty($pessoa->nome)) {
                        $nomes[] = $pessoa->nome;
                    }
                }

                TTransaction::close();

                $nomes = array_unique($nomes);

                return implode(', ', $nomes);
            }
            catch (Exception $e)
            {
                if (TTransaction::get()) {
                    TTransaction::rollback();
                }

                return '';
            }

        }, $conta->pessoa->nome, $conta, null);    

        $transformed_conta_total_conta = call_user_func(function($value, $object, $row) 
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        }, $conta->total_conta, $conta, null);

        $label = new TElement('span');
        $label->{'class'} = 'label label-';

        if ($conta->quitada  == 'S') {
            $label->{'class'} .= 'success';
            $label->add('Sim');    
        } else {
            $label->{'class'} .= 'danger';
            $label->add('Não');
        }

        $conta->quitada = $label;

        $text13 = new TTextDisplay(self::vinculosHtml($conta), '', '12px', '');
        $label2 = new TLabel("Pessoa:", '', '14px', 'B', '100%');
        $text2 = new TTextDisplay($transformed_conta_pessoa_nome, '', '14px', '');
        $label6 = new TLabel("Tipo:", '', '14px', 'B', '100%');
        $text4 = new TTextDisplay($conta->tipo_conta->nome, '', '14px', '');
        $label8 = new TLabel("Quitada:", '', '14px', 'B', '100%');
        $text8 = new TTextDisplay($conta->quitada, '', '14px', '');
        $label4 = new TLabel("Categoria:", '', '14px', 'B', '100%');
        $text3 = new TTextDisplay($conta->categoria_conta->nome, '', '14px', '');
        $label25 = new TLabel("Clínica:", '', '14px', 'B', '100%');
        $text7 = new TTextDisplay($conta->escritorio->nome, '', '14px', '');
        $label12 = new TLabel("Total:", '', '14px', 'B', '100%');
        $text6 = new TTextDisplay($transformed_conta_total_conta, '', '14px', '');
        $label10 = new TLabel("Descrição:", '', '14px', 'B', '100%');
        $text9 = new TTextDisplay($conta->descricao, '', '14px', '');
        $label14 = new TLabel("Tipo de documento:", '', '14px', 'B', '100%');
        $text14 = new TTextDisplay($conta->tipo_documento_financeiro->nome, '', '14px', '');
        $label15 = new TLabel("Número do documento:", '', '14px', 'B', '100%');
        $text15 = new TTextDisplay($conta->contrato->numero, '', '14px', '');
        $text16 = new TTextDisplay($conta->atendimento_id, '', '16px', '');
        $text17 = new TTextDisplay($conta->numero_documento, '', '14px', '');
        $label16 = new TLabel("Motivo do cancelamento:", '', '14px', 'B', '100%');
        $label17 = new TLabel("MOTIVO CANCELAMENTO", '', '16px', '');

        $row1 = $this->form->addFields([$text13]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([$label2,$text2],[$label6,$text4],[$label8,$text8]);
        $row2->layout = [' col-sm-6',' col-sm-3',' col-sm-3'];

        $row3 = $this->form->addFields([$label4,$text3],[$label25,$text7],[$label12,$text6]);
        $row3->layout = [' col-sm-6','col-sm-3',' col-sm-3'];

        $row4 = $this->form->addFields([$label10,$text9],[$label14,$text14],[$label15,$text15,$text16,$text17]);
        $row4->layout = [' col-sm-6',' col-sm-3',' col-sm-3'];

        $row5 = $this->form->addFields([$label16,$label17]);
        $row5->layout = [' col-sm-6'];

        $this->lancamento_conta_id_list = new TQuickGrid;
        $this->lancamento_conta_id_list->style = 'width:100%';
        $this->lancamento_conta_id_list->disableDefaultClick();

        $action_onPagar = new TDataGridAction(array('ContaFormView', 'onPagar'));
        $action_onPagar->setUseButton(true);
        $action_onPagar->setButtonClass('btn btn-default btn-sm');
        $action_onPagar->setLabel("Quitar");
        $action_onPagar->setImage('fas:check #4CAF50');
        $action_onPagar->setField('id');
        $action_onPagar->setDisplayCondition('ContaFormView::canPagar');
        $action_onPagar->setParameter('lancamento_id', '{id}');
        $this->lancamento_conta_id_list->addAction($action_onPagar);

        $action_onDesquitar = new TDataGridAction(array('ContaFormView', 'onDesquitar'));
        $action_onDesquitar->setUseButton(true);
        $action_onDesquitar->setButtonClass('btn btn-default btn-sm');
        $action_onDesquitar->setLabel("Desquitar");
        $action_onDesquitar->setImage('fas:times #000000');
        $action_onDesquitar->setField('id');
        $action_onDesquitar->setDisplayCondition('ContaFormView::canDesquitar');
        $action_onDesquitar->setParameter('key', '{conta_id}');
        $this->lancamento_conta_id_list->addAction($action_onDesquitar);

        // vencimento: qualquer conta (a pagar, a receber, repasse, cliente) enquanto a parcela nao estiver paga
        $action_onVencimento = new TDataGridAction(array('ContaFormView', 'onAlterarVencimento'));
        $action_onVencimento->setUseButton(true);
        $action_onVencimento->setButtonClass('btn btn-default btn-sm');
        $action_onVencimento->setLabel("Vencimento");
        $action_onVencimento->setImage('far:calendar-alt #1f3a5f');
        $action_onVencimento->setField('id');
        $action_onVencimento->setDisplayCondition('ContaFormView::canPagar');
        $action_onVencimento->setParameter('lancamento_id', '{id}');
        $action_onVencimento->setParameter('static', '1');
        $this->lancamento_conta_id_list->addAction($action_onVencimento);

        $column_dt_vencimento_transformed = $this->lancamento_conta_id_list->addQuickColumn("Vencimento", 'dt_vencimento', 'center');
        $column_tipo_pagamento_nome = $this->lancamento_conta_id_list->addQuickColumn("Tipo", 'tipo_pagamento->nome', 'center');
        $column_valor_total_transformed = $this->lancamento_conta_id_list->addQuickColumn("Valor", 'valor_total', 'center');
        $column_saldo_transformed = $this->lancamento_conta_id_list->addQuickColumn("Saldo", 'saldo', 'left');
        $column_dt_pagamento_transformed = $this->lancamento_conta_id_list->addQuickColumn("Pago", 'dt_pagamento', 'center');
        $column_dt_pagamento_transformed1 = $this->lancamento_conta_id_list->addQuickColumn("Data da baixa", 'dt_pagamento', 'center' , '150px');
        $column_extrato_data_compensacao_transformed = $this->lancamento_conta_id_list->addQuickColumn("Data da compensação", 'extrato->data_compensacao', 'center');
        $column_cancelado_transformed = $this->lancamento_conta_id_list->addQuickColumn("Cancelado", 'cancelado', 'center');

        $column_dt_vencimento_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_valor_total_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $column_saldo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $saldo = (float) ($value ?? 0);

            if($saldo > 0 && empty($object->dt_pagamento)){
                $row->style = 'background-color: #fff8e1; border-left: 4px solid #f59e0b;';

                $badge = new TElement('span');
                $badge->style = 'display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; background: #fff3cd; color: #8a5a00; border: 1px solid #ffe69c; border-radius: 12px; font-size: 12px; font-weight: 600;';
                $badge->title = 'Essa parcela já teve um pagamento parcial e ainda possui saldo em aberto.';

                $icone = new TElement('i');
                $icone->class = 'fas fa-exclamation-circle';

                $badge->add($icone);
                $badge->add('Saldo R$ '.number_format($saldo, 2, ',', '.'));

                return $badge;
            }

            if(!empty($object->dt_pagamento)){
                $badge = new TElement('span');
                $badge->style = 'display: inline-block; padding: 4px 8px; background: #d1e7dd; color: #0f5132; border-radius: 12px; font-size: 12px; font-weight: 600;';
                $badge->add('Quitada');

                return $badge;
            }

            return '<span style="color: #999;">—</span>';

        });

        $column_dt_pagamento_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            $label = new TElement('span');
            $label->{'class'} = 'label label-';

            if ($value) {
                $label->{'class'} .= 'success';
                $label->add('Sim');    

                return $label;
            }

            $label->{'class'} .= 'danger';
            $label->add('Não');

            return $label;
        });

        $column_dt_pagamento_transformed1->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_extrato_data_compensacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_cancelado_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            $label = new TElement('span');
            $label->{'class'} = 'label label-';

            if ($value == 'S' || $value == 'T') {
                $label->{'class'} .= 'success';
                $label->add('Sim');    

                return $label;
            }

            $label->{'class'} .= 'danger';
            $label->add('Não');

            return $label;
        });

        $this->lancamento_conta_id_list->createModel();

        $criteria_lancamento_conta_id = new TCriteria();
        $criteria_lancamento_conta_id->add(new TFilter('conta_id', '=', $conta->id));

        $criteria_lancamento_conta_id->setProperty('order', 'parcela asc');

        $lancamento_conta_id_items = Lancamento::getObjects($criteria_lancamento_conta_id);

        $this->lancamento_conta_id_list->addItems($lancamento_conta_id_items);

        $icon = new TImage('fas:dollar-sign #4CAF50');
        $title = new TTextDisplay("{$icon} Parcelas", '#555555', '16px', '{$fontStyle}');

        $panel = new TPanelGroup($title, '#FFFFFF');
        $panel->class = 'panel panel-default formView-detail';
        $tableResponsiveDiv = new TElement('div');
        $tableResponsiveDiv->class = 'table-responsive';
        $tableResponsiveDiv->add(new BootstrapDatagridWrapper($this->lancamento_conta_id_list));
        $panel->add($tableResponsiveDiv);

        $this->form->addContent([$panel]);

        // conta a receber do levantamento: a conta a pagar ao cliente gerada junto (a volta e o botao de origem no topo dela)
        $contaCliente = ((string) $conta->tipo_conta_id === (string) TipoConta::RECEBER) ? self::contaPagarClienteHtml((int) $conta->id) : '';
        if ($contaCliente !== '') {
            $panelCliente = new TPanelGroup(new TTextDisplay(new TImage('fas:user #1f3a5f') . ' Conta a pagar ao cliente', '#555555', '16px'), '#FFFFFF');
            $panelCliente->class = 'panel panel-default formView-detail';
            $divCliente = new TElement('div');
            $divCliente->class = 'table-responsive';
            $divCliente->add($contaCliente);
            $panelCliente->add($divCliente);
            $this->form->addContent([$panelCliente]);
        }

        // repasses aos colaboradores: so na conta a receber (as contas a pagar geradas), na ordem da planilha do escritorio;
        // a conta de repasse mostra a origem nos vinculos do topo
        $repassesDaConta = ((string) $conta->tipo_conta_id === (string) TipoConta::RECEBER)
            ? RepasseColaboradorService::ordenarPorColaborador(
                RepasseColaboradorService::listar(['conta_receber_id' => (int) $conta->id]),
                function ($r) { return $r->pessoa_id; },
                function ($r) { return $r->colaborador; })
            : [];
        if ($repassesDaConta) {
            $tituloRepasses = new TTextDisplay(new TImage('fas:hand-holding-usd #1f3a5f') . ' Repasses aos colaboradores', '#555555', '16px');
            $panelRepasses = new TPanelGroup($tituloRepasses, '#FFFFFF');
            $panelRepasses->class = 'panel panel-default formView-detail';
            $divRepasses = new TElement('div');
            $divRepasses->class = 'table-responsive';
            $divRepasses->add(RepasseColaboradorList::secaoHtml($repassesDaConta));
            $panelRepasses->add($divRepasses);
            $this->form->addContent([$panelRepasses]);
        }

        TScript::create("$('label:contains(\"Motivo do cancelamento:\")').hide();");
        TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').hide();");

        $btn_ondeleteAction = new TAction([$this, 'onDelete'],['key'=>$conta->id]);
        $btn_ondeleteLabel = new TLabel("Excluir");

        $btn_ondelete = $this->form->addHeaderAction($btn_ondeleteLabel, $btn_ondeleteAction, 'fas:trash-alt #FF0000'); 
        $btn_ondeleteLabel->setFontSize('12px'); 
        $btn_ondeleteLabel->setFontColor('#333'); 

        $btn_oneditAction = new TAction([$this, 'onEdit'],['key'=>$conta->id]);
        $btn_oneditLabel = new TLabel("Editar");

        $btn_onedit = $this->form->addHeaderAction($btn_oneditLabel, $btn_oneditAction, 'fas:edit #03A9F4'); 
        $btn_oneditLabel->setFontSize('12px'); 
        $btn_oneditLabel->setFontColor('#333'); 

        // conta a receber gerada pelo financeiro do levantamento: prestacao de contas ao cliente
        $etapa3Prestacao = LevantamentoPrestacaoContasService::etapa3DaContaReceber((int) $conta->id);
        if ($etapa3Prestacao) {
            $lblWord = new TLabel("Prestação de contas (Word)");
            $lblWord->setFontSize('12px');
            $lblWord->setFontColor('#333');
            $this->form->addHeaderAction($lblWord, new TAction(['LevantamentoFinanceiroView', 'onPrestacaoWord'], ['static' => '1', 'etapa3_id' => $etapa3Prestacao]), 'far:file-word #2B579A');
            $lblPdf = new TLabel("Prestação de contas (PDF)");
            $lblPdf->setFontSize('12px');
            $lblPdf->setFontColor('#333');
            $this->form->addHeaderAction($lblPdf, new TAction(['LevantamentoFinanceiroView', 'onPrestacaoPdf'], ['static' => '1', 'etapa3_id' => $etapa3Prestacao]), 'far:file-pdf #D32F2F');
        }
        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TTransaction::close();
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ContaFormView]');
        $style->width = '50% !important';   
        $style->show(true);

    }

    /**
     * Botoes com os vinculos da conta, conforme o caso: contrato e processo (com o numero), requisicao de pagamento,
     * financeiro do levantamento, conta a receber que originou (repasse ou conta a pagar ao cliente) e conta a pagar
     * ao cliente (na conta a receber do levantamento). Ids nao aparecem: so o titulo da conta mostra o numero dela.
     * Usa a transacao aberta.
     */
    private static function vinculosHtml(Conta $conta): string
    {
        $c = TTransaction::get();
        $um = function (string $sql, array $par) use ($c) { $st = $c->prepare($sql); $st->execute($par); return $st->fetch(PDO::FETCH_OBJ) ?: null; };
        $botao = function (string $url, string $rotulo, string $icone) {
            return '<a href="javascript:void(0)" class="btn btn-sm btn-default conta-vinculo" onclick="'
                . htmlspecialchars('Adianti.waitMessage = false; __adianti_load_page(' . json_encode($url) . '); return false;') . '">'
                . '<i class="' . $icone . '"></i> ' . htmlspecialchars($rotulo) . '</a>';
        };
        $botoes = [];
        $id = (int) $conta->id;

        $repasse = $um('SELECT conta_receber_id, levantamento_financeiro_id FROM repasse_colaborador WHERE conta_pagar_id = ?', [$id]);
        // conta a pagar ao cliente do levantamento (uma por recebimento)
        $doCliente = $um('SELECT conta_receber_id, levantamento_financeiro_id FROM levantamento_conta_cliente WHERE conta_pagar_id = ?', [$id]);
        $ficha = $um('SELECT id, requisicao_pagamento_etapa3_id, conta_receber_id, conta_pagar_id FROM levantamento_financeiro
                       WHERE conta_receber_id = ? OR conta_pagar_id = ? OR id = ? ORDER BY id DESC LIMIT 1', [$id, $id, $doCliente ? (int) $doCliente->levantamento_financeiro_id : 0]);
        if (!$ficha && $repasse) {
            // repasse: o levantamento vem do proprio repasse ou da conta a receber que o originou
            $ficha = $um('SELECT id, requisicao_pagamento_etapa3_id, conta_receber_id, conta_pagar_id FROM levantamento_financeiro WHERE id = ? OR conta_receber_id = ? ORDER BY id DESC LIMIT 1',
                [(int) $repasse->levantamento_financeiro_id, (int) $repasse->conta_receber_id]);
        }

        if (!empty($conta->contrato_id)) {
            $numero = $um('SELECT numero FROM contrato WHERE id = ?', [(int) $conta->contrato_id]);
            $cid = (int) $conta->contrato_id;
            $botoes[] = $botao("index.php?class=ContratoFormView&method=onShow&key={$cid}&id={$cid}&target_container=adianti_right_panel",
                'Contrato' . (!empty($numero->numero) ? ' ' . $numero->numero : ''), 'fas fa-file-signature');
        }
        if (!empty($conta->processo_id)) {
            $proc = $um('SELECT COALESCE(numero_cnj_numero, numero_outro) AS numero FROM processo WHERE id = ?', [(int) $conta->processo_id]);
            $pid = (int) $conta->processo_id;
            $botoes[] = $botao("engine.php?class=ProcessoFormView&method=onShow&key={$pid}&register_state=false&target_container=adianti_right_panel",
                'Processo' . (!empty($proc->numero) ? ' ' . $proc->numero : ''), 'fas fa-folder-open');
        }
        if (!empty($conta->requisicao_pagamento_id)) {
            $req = $um('SELECT t.nome FROM requisicao_pagamento r LEFT JOIN tipos_requisicao_pagamento t ON t.id = r.tipos_requisicao_pagamento_id WHERE r.id = ?', [(int) $conta->requisicao_pagamento_id]);
            $rid = (int) $conta->requisicao_pagamento_id;
            // abre na aba do cliente a que a conta se refere (a requisicao pode ter varios clientes)
            $rpc = LevantamentoFinanceiroService::clienteDaRequisicao($id);
            $aba = $rpc ? "&requisicao_pagamento_cliente_id={$rpc}" : '';
            $botoes[] = $botao("engine.php?class=RequisicaoPagamentoVisualizacao&method=onShow&key={$rid}{$aba}&register_state=false",
                'Requisição de pagamento' . (!empty($req->nome) ? ' (' . $req->nome . ')' : ''), 'fas fa-hand-holding-usd');
        }
        if ($ficha) {
            $botoes[] = $botao('engine.php?class=LevantamentoFinanceiroView&method=onShow&etapa3_id=' . (int) $ficha->requisicao_pagamento_etapa3_id . '&register_state=false',
                'Financeiro do levantamento', 'fas fa-calculator');
        }
        // conta a receber que deu origem: do repasse ou da conta a pagar ao cliente do levantamento
        $origem = $repasse ? (int) $repasse->conta_receber_id
            : ($doCliente ? (int) $doCliente->conta_receber_id : (($ficha && (int) $ficha->conta_pagar_id === $id) ? (int) $ficha->conta_receber_id : 0));
        if ($origem > 0) {
            $botoes[] = $botao("index.php?class=ContaFormView&method=onShow&key={$origem}&id={$origem}&target_container=adianti_right_panel", 'Conta a receber de origem', 'fas fa-arrow-down');
        }
        // conta a receber do levantamento: a conta a pagar ao cliente gerada junto
        if ($ficha && (int) $ficha->conta_receber_id === $id && (int) $ficha->conta_pagar_id > 0) {
            $pagar = (int) $ficha->conta_pagar_id;
            $botoes[] = $botao("index.php?class=ContaFormView&method=onShow&key={$pagar}&id={$pagar}&target_container=adianti_right_panel", 'Conta a pagar ao cliente', 'fas fa-arrow-up');
        }

        if (!$botoes) {
            return '';
        }
        return '<div class="conta-vinculos">' . implode('', $botoes) . '</div>'
            . '<style>.conta-vinculos { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:4px; } .conta-vinculos .conta-vinculo { font-size:12px; padding:3px 9px; } .conta-vinculos .conta-vinculo i { color:#0d4069; margin-right:3px; }</style>';
    }

    /**
     * Quadro das contas a pagar ao cliente do levantamento (uma por recebimento da conta a receber, como os repasses).
     * Usa a transacao aberta.
     */
    private static function contaPagarClienteHtml(int $contaReceberId): string
    {
        $st = TTransaction::get()->prepare("
            SELECT c.id, pe.nome AS cliente, c.total_conta, l.dt_vencimento, l.dt_pagamento, x.compensado, lc.data_recebimento
              FROM levantamento_conta_cliente lc
              JOIN conta c ON c.id = lc.conta_pagar_id
              LEFT JOIN pessoa pe ON pe.id = c.pessoa_id
              LEFT JOIN LATERAL (SELECT * FROM lancamento WHERE conta_id = c.id ORDER BY parcela, id LIMIT 1) l ON true
              LEFT JOIN extrato x ON x.id = l.extrato_id
             WHERE lc.conta_receber_id = ?
             ORDER BY lc.id");
        $st->execute([$contaReceberId]);
        $contas = $st->fetchAll(PDO::FETCH_OBJ);
        if (!$contas) {
            return '';
        }
        $td = 'padding:4px 8px;border-bottom:1px solid #eef2f7;font-size:12px;text-align:left';
        $th = $td . ';background:#f8fafc';
        $linhas = '';
        foreach ($contas as $r) {
            $id = (int) $r->id;
            $url = "index.php?class=ContaFormView&method=onShow&key={$id}&id={$id}&target_container=adianti_right_panel";
            $abrir = '<a href="javascript:void(0)" title="Abrir a conta a pagar ao cliente" onclick="' . htmlspecialchars('Adianti.waitMessage = false; __adianti_load_page(' . json_encode($url) . '); return false;') . '"><i class="fas fa-external-link-alt"></i> Abrir conta</a>';
            $sit = $r->dt_pagamento
                ? "<span style='color:#065f46'>Pago em " . date('d/m/Y', strtotime($r->dt_pagamento)) . ($r->compensado === 'S' ? ' · compensado' : '') . '</span>'
                : "<span style='color:#9a3412;font-weight:600'>A pagar</span>";
            $linhas .= "<tr><td style='{$td}'>" . htmlspecialchars((string) $r->cliente) . "</td><td style='{$td}'>" . ($r->data_recebimento ? date('d/m/Y', strtotime($r->data_recebimento)) : '')
                . "</td><td style='{$td}'>" . ($r->dt_vencimento ? date('d/m/Y', strtotime($r->dt_vencimento)) : '')
                . "</td><td style='{$td};text-align:right;white-space:nowrap'>R$ " . number_format((float) $r->total_conta, 2, ',', '.')
                . "</td><td style='{$td}'>{$sit}</td><td style='{$td}'>{$abrir}</td></tr>";
        }
        return "<table style='width:100%;border-collapse:collapse'><thead><tr>"
            . "<th style='{$th}'>Cliente</th><th style='{$th}'>Recebido em</th><th style='{$th}'>Vencimento</th><th style='{$th};text-align:right'>Valor</th><th style='{$th}'>Situação</th><th style='{$th}'></th></tr></thead><tbody>"
            . $linhas . "</tbody></table>";
    }

    /** Pergunta o novo vencimento da parcela (so parcela nao paga e nao cancelada). */
    public static function onAlterarVencimento($param = null)
    {
        try {
            TTransaction::open(self::$database);
            $lancamento = Lancamento::find((int) ($param['lancamento_id'] ?? $param['id'] ?? 0));
            if (!$lancamento) {
                throw new Exception('Parcela não encontrada.');
            }
            if (!self::canPagar($lancamento)) {
                throw new Exception('Só é possível alterar o vencimento de parcela que ainda não foi quitada.');
            }
            $minimo = self::vencimentoMinimo((int) $lancamento->conta_id);
            TTransaction::close();

            $form = new BootstrapFormBuilder('form_ContaAlterarVencimento');
            $id = new THidden('lancamento_id');
            $id->setValue($lancamento->id);
            $data = new TDate('novo_vencimento');
            $data->setMask('dd/mm/yyyy');
            $data->setDatabaseMask('yyyy-mm-dd');
            $data->setSize('100%');
            $data->setValue($lancamento->dt_vencimento ? date('d/m/Y', strtotime($lancamento->dt_vencimento)) : '');
            $form->addFields([new TLabel('Novo vencimento', '#ff0000')], [$data]);
            if ($minimo) {
                // conta gerada automaticamente pelo recebimento: nao vence antes da baixa da conta a receber
                $data->setOption('startDate', date('d/m/Y', strtotime($minimo)));
                $form->addContent(["<div style='font-size:12px;color:#64748b'>Não pode ser anterior a " . date('d/m/Y', strtotime($minimo))
                    . ', data da baixa da conta a receber que gerou esta conta.</div>']);
            }
            $form->addFields([$id]);
            $form->addAction('Salvar', new TAction([__CLASS__, 'onSalvarVencimento'], ['static' => '1']), 'fas:save #ffffff')->class = 'btn btn-sm btn-primary';
            new TInputDialog('Alterar vencimento da parcela ' . (int) $lancamento->parcela, $form);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Conta a pagar gerada automaticamente por um recebimento (repasse a colaborador ou conta do cliente do levantamento):
     * data da baixa desse recebimento, antes da qual ela nao pode vencer. Null nas demais contas. Usa a transacao aberta.
     */
    private static function vencimentoMinimo(int $contaId): ?string
    {
        $st = TTransaction::get()->prepare('SELECT COALESCE(
                (SELECT MAX(data_recebimento) FROM repasse_colaborador WHERE conta_pagar_id = :c),
                (SELECT MAX(data_recebimento) FROM levantamento_conta_cliente WHERE conta_pagar_id = :c))');
        $st->execute(['c' => $contaId]);
        $data = $st->fetchColumn();
        return $data ? substr((string) $data, 0, 10) : null;
    }

    /** Grava o novo vencimento da parcela e o proximo vencimento da conta. */
    public static function onSalvarVencimento($param = null)
    {
        try {
            $texto = trim((string) ($param['novo_vencimento'] ?? ''));
            $d = DateTime::createFromFormat('d/m/Y', $texto) ?: DateTime::createFromFormat('Y-m-d', $texto);
            if (!$d || ($d->format('d/m/Y') !== $texto && $d->format('Y-m-d') !== $texto)) {
                throw new Exception('Informe uma data de vencimento válida.');
            }

            TTransaction::open(self::$database);
            $lancamento = Lancamento::find((int) ($param['lancamento_id'] ?? 0));
            if (!$lancamento) {
                throw new Exception('Parcela não encontrada.');
            }
            if (!self::canPagar($lancamento)) {
                throw new Exception('Só é possível alterar o vencimento de parcela que ainda não foi quitada.');
            }
            $minimo = self::vencimentoMinimo((int) $lancamento->conta_id);
            if ($minimo && $d->format('Y-m-d') < $minimo) {
                throw new Exception('O vencimento não pode ser anterior a ' . date('d/m/Y', strtotime($minimo)) . ', data da baixa da conta a receber que gerou esta conta.');
            }
            $lancamento->dt_vencimento = $d->format('Y-m-d');
            $lancamento->store();

            // proximo vencimento da conta: a parcela em aberto que vence primeiro
            $conta = Conta::find((int) $lancamento->conta_id);
            $proximo = null;
            foreach (Lancamento::where('conta_id', '=', $conta->id)->load() as $l) {
                if (self::canPagar($l) && ($proximo === null || $l->dt_vencimento < $proximo)) {
                    $proximo = $l->dt_vencimento;
                }
            }
            $conta->proximo_vencimento_lancamento = $proximo ?? $lancamento->dt_vencimento;
            $conta->store();
            $contaId = (int) $conta->id;
            TTransaction::close();

            TToast::show('success', 'Vencimento alterado', 'topRight', 'far:check-circle');
            TApplication::loadPage('ContaFormView', 'onShow', ['key' => $contaId, 'id' => $contaId]);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onPagar($param = null) 
    {

    try{
        $lancamentoId = $param['lancamento_id'] ?? $param['id'] ?? null;

        if (empty($lancamentoId))
        {
            throw new Exception('Lançamento não informado.');
        }

        /*
         * Quitação completa da parcela.
         * Continua abrindo o modal que já existia.
         */
        $actionQuitarParcela = new TAction(
            array('ModalQuitarParcela', 'onShow')
        );

        $actionQuitarParcela->setParameter(
            'lancamento_id',
            $lancamentoId
        );

        /*
         * Quitação parcial.
         * Abre o novo modal clonado.
         */
        $actionQuitarParcial = new TAction(
            array('ModalQuitarParcelaParcial', 'onShow')
        );

        $actionQuitarParcial->setParameter(
            'lancamento_id',
            $lancamentoId
        );

        new TQuestion(
            'Como deseja quitar este lançamento?',
            $actionQuitarParcela,
            $actionQuitarParcial,
            'Escolha o tipo de quitação',
            'Quitar total',
            'Quitar parcial'
        );

            //</autoCode>
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canPagar($object)
    {
        try 
        {
            if($object->dt_pagamento || $object->cancelado=='S'){
                return false;
            }else{
                return true;
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    /**
     * Desquitar: desfaz a quitacao da parcela.
     * - apaga o registro da quitacao no extrato (sem criar lancamento inverso);
     * - volta a parcela ao que era antes de quitar: sem acrescimo/desconto, sem os
     *   ajustes dos profissionais daquela quitacao e, se houve pagamentos parciais
     *   (que continuam no extrato), com o saldo que faltava pagar;
     * - recalcula o total e o proximo vencimento da conta e o saldo da conta-caixa.
     */
    public function onDesquitar($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $lancamento = Lancamento::find($param['id'] ?? 0);
            if (!$lancamento) {
                throw new Exception('Parcela não encontrada.');
            }
            // conta a receber do levantamento: so se a conta a pagar ao cliente nao tiver pagamento
            LevantamentoFinanceiroService::exigirDesquitavel((int) $lancamento->conta_id);
            $doLevantamento = LevantamentoFinanceiroService::contaDoLevantamento((int) $lancamento->conta_id);

            if (empty($lancamento->dt_pagamento)) {
                throw new Exception('Esta parcela não está quitada.');
            }

            $extratoQuitacao = !empty($lancamento->extrato_id) ? Extrato::find($lancamento->extrato_id) : null;

            if (empty($param['confirmado'])) {
                $valor = $extratoQuitacao ? (float) $extratoQuitacao->entrada_valor + (float) $extratoQuitacao->saida_valor : (float) $lancamento->valor_total;
                $mensagem = 'Desquitar a parcela ' . (int) $lancamento->parcela . '?<br><br>O registro do pagamento de R$ ' . number_format($valor, 2, ',', '.');
                if ($extratoQuitacao && $extratoQuitacao->conta_caixa) {
                    $mensagem .= ' será apagado do extrato da conta-caixa <b>' . htmlspecialchars((string) $extratoQuitacao->conta_caixa->nome, ENT_QUOTES, 'UTF-8') . '</b>'
                        . ($extratoQuitacao->compensado == 'S' ? ' (já compensado)' : '')
                        . ' e o saldo dela será recalculado.';
                } else {
                    $mensagem .= ' será desfeito.';
                }
                if ($doLevantamento && $doLevantamento['papel'] === 'RECEBER') {
                    $mensagem .= '<br><br>As contas a pagar dos colaboradores geradas por este recebimento e a conta a pagar ao cliente gerada por ele serão apagadas; voltam a ser geradas ao quitar de novo (a do cliente com o vencimento original).';
                }
                TTransaction::close();

                $acao = new TAction([$this, 'onDesquitar']);
                $acao->setParameter('id', $lancamento->id);
                $acao->setParameter('key', $param['key'] ?? $lancamento->conta_id);
                $acao->setParameter('confirmado', 1);
                new TQuestion($mensagem, $acao);
                return;
            }

            $conta = $lancamento->conta;

            // repasses: desquitar a receber apaga os repasses gerados por esta quitacao (recusa se algum ja foi pago)
            if ((string) $conta->tipo_conta_id === (string) TipoConta::RECEBER && $extratoQuitacao) {
                RepasseColaboradorService::estornarPorRecebimento((int) $extratoQuitacao->id);
            }

            // pagamentos parciais anteriores continuam no extrato: a parcela volta a dever so o que faltava
            $parciais = 0.0;
            foreach (Extrato::where('lancamento_id', '=', $lancamento->id)->load() as $x) {
                if ($extratoQuitacao && (int) $x->id === (int) $extratoQuitacao->id) {
                    continue;
                }
                $parciais += (float) $x->entrada_valor + (float) $x->saida_valor;
            }

            // valor da parcela antes da quitacao (a quitacao gravou valor_total = base + acrescimo - desconto)
            $valorTotal = ($lancamento->valor_total !== null && $lancamento->valor_total !== '') ? (float) $lancamento->valor_total : (float) $lancamento->valor;
            $base = round($valorTotal - (float) $lancamento->acrescimo + (float) $lancamento->desconto, 2);

            // ajustes de acrescimo/desconto dos profissionais sao criados so na quitacao
            foreach (LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)->load() as $lp) {
                LancamentoProfissionalAjuste::where('lancamento_profissional_id', '=', $lp->id)->delete();
            }

            $contaCaixaId = $extratoQuitacao ? (int) $extratoQuitacao->conta_caixa_id : null;

            $lancamento->dt_pagamento = null;
            $lancamento->ano_pagamento = null;
            $lancamento->mes_pagamento = null;
            $lancamento->ano_mes_pagamento = null;
            $lancamento->extrato_id = null;
            $lancamento->acrescimo = 0;
            $lancamento->desconto = 0;
            $lancamento->valor_total = $base;
            $lancamento->saldo = ($parciais > 0) ? round($base - $parciais, 2) : null;
            $lancamento->store();

            if ($extratoQuitacao) {
                LevantamentoFinanceiroService::desvincularExtrato((int) $extratoQuitacao->id);
                $extratoQuitacao->delete();
                ContaCaixaSaldoService::recalcular($contaCaixaId);
            }

            // conta: total pelas parcelas nao canceladas, em aberto, proximo vencimento em aberto
            $totalConta = 0.0;
            $proximo = null;
            foreach (Lancamento::where('conta_id', '=', $conta->id)->load() as $l) {
                if ($l->cancelado == 'S') {
                    continue;
                }
                $totalConta += ($l->valor_total !== null && $l->valor_total !== '') ? (float) $l->valor_total : (float) $l->valor;
                if (empty($l->dt_pagamento) && ($proximo === null || $l->dt_vencimento < $proximo)) {
                    $proximo = $l->dt_vencimento;
                }
            }
            $conta->total_conta = round($totalConta, 2);
            $conta->quitada = 'N';
            $conta->proximo_vencimento_lancamento = $proximo ?? $lancamento->dt_vencimento;
            $conta->store();

            $tipoConta = $conta->tipo_conta_id;
            $contaId = $conta->id;

            TTransaction::close();

            TToast::show('success', 'Lançamento desquitado com sucesso', 'bottom right');
            TScript::create("Template.closeRightPanel();");

            if ($tipoConta == TipoConta::RECEBER) {
                TApplication::loadPage('ContaReceberList', 'onShow');
            } elseif ($tipoConta == TipoConta::PAGAR) {
                TApplication::loadPage('ContaPagarList', 'onShow');
            }

            TApplication::loadPage('ContaFormView', 'onShow', ['key' => $contaId]);
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canDesquitar($object)
    {
        // qualquer parcela quitada pode ser desquitada (inclusive com extrato ja compensado:
        // o registro sai do extrato e o saldo da conta-caixa e recalculado)
        return !empty($object->dt_pagamento);
    }
    public function onDelete($param = null) 
    {
        if(isset($param['delete']) && $param['delete'] == 1)
        {
            try
            {
                // get the paramseter $key
                $key = $param['key'];
                // open a transaction with database
                TTransaction::open(self::$database);

                $object = new Conta($key, FALSE); 

                LevantamentoFinanceiroService::exigirContaNaoGerada((int) $object->id);

                // parcela com pagamento registrado no extrato: desquitar antes (o extrato e o saldo da conta-caixa dependem dele)
                $stPagas = TTransaction::get()->prepare('SELECT COUNT(*) FROM extrato WHERE lancamento_id IN (SELECT id FROM lancamento WHERE conta_id = ?)');
                $stPagas->execute([(int) $object->id]);
                if ((int) $stPagas->fetchColumn() > 0) {
                    throw new Exception('Esta conta tem parcela paga (total ou parcialmente). Desquite as parcelas pagas antes de excluir a conta.');
                }

                if($object->tipo_conta_id == 2){
                    $tela = 'ContaPagarList';
                }else{
                    $tela = 'ContaReceberList';
                }

                // deletes the object from the database
               // Se for uma conta gerada por contrato, volta o status das parcelas do contrato

                if (!empty($object->contrato_id))
                {
                    $lancamentosContrato = Lancamento::where('conta_id', '=', $object->id)->load();

                    $contratoParcelaIds = [];

                    if ($lancamentosContrato)
                    {
                        foreach ($lancamentosContrato as $lancamentoContrato)
                        {
                            if (!empty($lancamentoContrato->contrato_parcela_id)) {
                                $contratoParcelaIds[] = (int) $lancamentoContrato->contrato_parcela_id;
                            }
                        }
                    }

                    $contratoParcelaIds = array_values(array_unique($contratoParcelaIds));

                    foreach ($contratoParcelaIds as $contratoParcelaId)
                    {
                        $contratoPagamentoParcela = ContratoPagamentoParcela::find($contratoParcelaId);

                        if ($contratoPagamentoParcela)
                        {
                            $contratoPagamentoParcela->status_contrato_pagamento_id = null;

                            /*
                            * Se está deletando o financeiro, a parcela volta a ficar em aberto pelo valor cheio.
                            */
                            $contratoPagamentoParcela->saldo = null;

                            $contratoPagamentoParcela->store();
                        }
                    }
                }

                // deletes the object from the database
               $lancamentos = Lancamento::where('conta_id', '=', $object->id)->load();

                if($lancamentos){
                    foreach($lancamentos as $lancamento){
                        $lancamentosProfissionais = LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)->load();

                        if($lancamentosProfissionais){
                            foreach($lancamentosProfissionais as $lancamentoProfissional){
                                LancamentoProfissionalAjuste::where('lancamento_profissional_id', '=', $lancamentoProfissional->id)->delete();
                                $lancamentoProfissional->delete();
                            }
                        }

                        $lancamento->delete();
                    }
                }

                ContaProfissional::where('conta_id', '=', $object->id)->delete();

                $object->delete();
                // close the transaction
                TTransaction::close();

                TApplication::loadPage($tela, 'onShow');
                TToast::show('success', "Registro excluído", 'topRight', 'far:check-circle');
                TScript::create("Template.closeRightPanel();");
            }
            catch (Exception $e) // in case of exception
            {
                // shows the exception error message
                new TMessage('error', $e->getMessage());
                // undo all pending operations
                TTransaction::rollback();
            }
        }
        else
        {
            // define the delete action
            $action = new TAction(array($this, 'onDelete'));
            $action->setParameters($param); // pass the key paramseter ahead
            $action->setParameter('delete', 1);
            // shows a dialog to the user
            new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);   
        }

    }
    public function onEdit($param = null) 
    {
        try 
        {
            $key = $param['key'] ?? $param['id'] ?? null;

            if (empty($key)) {
                throw new Exception('ID da conta não informado.');
            }

            TTransaction::open(self::$database);

            $object = new Conta($key, FALSE); 

            if (empty($object->id)) {
                throw new Exception('Conta não encontrada.');
            }

            $tela = ($object->tipo_conta_id == 2) ? 'ContaPagarForm' : 'ContaForm';

            $conta_id = $object->id;
            LevantamentoFinanceiroService::exigirContaNaoGerada((int) $conta_id);

            TTransaction::close();

            TApplication::loadPage($tela, 'onEdit', [
                'key' => $conta_id,
                'register_state' => 'false'
            ]);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {     

            TTransaction::open(self::$database);

            $object = Conta::find($param['key']);

            if($object->numero_documento!='' && $object->numero_documento!=null){
            }
            if($object->atendimento_id!='' && $object->atendimento_id!=null){
                TScript::create("$('label:contains(\"Número do documento:\")').html('Número do atendimento:')");
            }
            if($object->contrato_nome!='' && $object->contrato_nome!=null){
                TScript::create("$('label:contains(\"Número do documento:\")').html('Número do contrato:')");
            }

            $lancamentos = Lancamento::where('conta_id','=',$param['key'])->load();
            foreach ($lancamentos as $lancamento) {
                if($lancamento->cancelado == 'S'){
                    $cancelado = true;
                    $motivo_cancelamento = $lancamento->motivo_cancelamento;
                }else{
                    $cancelado = false;
                }
            }
            if($cancelado){
                TScript::create("$('label:contains(\"Motivo do cancelamento:\")').show();");
                TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').show();");
                TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').html('".$motivo_cancelamento."')");
            }
            TTransaction::close();
    }

}

