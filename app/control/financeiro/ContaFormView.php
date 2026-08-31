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

        $transformed_conta_contrato_id = call_user_func(function($value, $object, $row)
        {
           if (empty($value)) {
                return '';
            }

            // value é o contrato_id vinculado na conta
            $contratoId = (int) $value;

            if (empty($contratoId)) {
                return '';
            }

            $action = new TAction(['ContratoFormView', 'onShow']);
            $action->setParameter('key', $contratoId);
            $action->setParameter('id', $contratoId);
            $action->setParameter('target_container', 'adianti_right_panel');

            $button = new TActionLink('Ver contrato', $action, '#ffffff', 10, null, 'fas:eye');
            $button->class = 'btn btn-sm btn-primary';
            $button->style = 'padding: 3px 8px; font-size: 12px;';

            return $button;
        }, $conta->contrato_id, $conta, null);    

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
                if (!empty($conta->contrato_id))
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

        $text13 = new TTextDisplay($transformed_conta_contrato_id, '', '12px', '');
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
    public function onDesquitar($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $lancamento = Lancamento::find($param['id']);

            $lancamento->extrato->lancamento_id = null;
            $lancamento->extrato->store();

            $extrato_id = $lancamento->extrato_id;
            $lancamento->dt_pagamento = null;
            $lancamento->ano_pagamento = null;
            $lancamento->mes_pagamento = null;
            $lancamento->ano_mes_pagamento = null;
            $lancamento->extrato_id = null;
            $lancamento->store();

            $lancamento->conta->quitada = 'N';
            $lancamento->conta->proximo_vencimento_lancamento = $lancamento->dt_vencimento;
            $lancamento->conta->store();

            $search = Extrato::find($extrato_id);
            $search->delete();

            TToast::show('success', 'Lançamento desquitado com sucesso', 'bottom right');
            TScript::create("Template.closeRightPanel();");

            if($lancamento->conta->tipo_conta_id==TipoConta::RECEBER){
                TApplication::loadPage('ContaReceberList', 'onShow');
            }elseif($lancamento->conta->tipo_conta_id==TipoConta::pagar){
                TApplication::loadPage('ContaPagarList', 'onShow');
            }

            $pageParam = ['key' => $lancamento->conta_id];

            TApplication::loadPage('ContaFormView', 'onShow', $pageParam);
            TTransaction::close();

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canDesquitar($object)
    {
        try 
        {
            if($object->dt_pagamento){
                $extratos = Extrato::where('lancamento_id','=',$object->id)->load();
                foreach($extratos as $extrato){
                    if($extrato->compensado != 'S'){
                        return true;
                    }
                }
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
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

