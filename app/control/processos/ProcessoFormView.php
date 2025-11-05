<?php

class ProcessoFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Processo';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Processo';

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

        $processo = new Processo($param['key']);
        // define the form title
        $this->form->setFormTitle("Visualizar Processo");

        $transformed_processo_gratuidade_processual = call_user_func(function($value, $object, $row) 
        {
            if($value === true || $value == 't' || $value === 1 || $value == '1' || $value == 's' || $value == 'S' || $value == 'T')
            {
                return 'Sim';
            }
            elseif($value === false || $value == 'f' || $value === 0 || $value == '0' || $value == 'n' || $value == 'N' || $value == 'F')   
            {
                return 'Não';
            }

            return $value;

        }, $processo->gratuidade_processual, $processo, null);

        $label2 = new TLabel("Tipo de processo:", '', '13px', 'B', '100%');
        $text2 = new TTextDisplay($processo->tipo_processo->nome, '', '12px', '');
        $label3 = new TLabel("Número padrão CNJ:", '', '13px', 'B', '100%');
        $text3 = new TTextDisplay($processo->numero_cnj_numero, '', '12px', '');
        $label4 = new TLabel("Número de outro padrão:", '', '13px', 'B', '100%');
        $text4 = new TTextDisplay($processo->numero_outro, '', '12px', '');
        $label5 = new TLabel("Tribunal:", '', '13px', 'B', '100%');
        $text5 = new TTextDisplay($processo->tribunal->nome, '', '12px', '');
        $label6 = new TLabel("Foro:", '', '13px', 'B', '100%');
        $text6 = new TTextDisplay($processo->foro->nome, '', '12px', '');
        $label28 = new TLabel("Envolvimento:", '', '12px', 'B', '100%');
        $text188 = new TTextDisplay($processo->envolvimento->nome, '', '12px', '');
        $label7 = new TLabel("Comarca:", '', '13px', 'B', '100%');
        $text7 = new TTextDisplay($processo->comarca->nome, '', '12px', '');
        $label8 = new TLabel("Vara:", '', '13px', 'B', '100%');
        $text8 = new TTextDisplay($processo->vara->nome, '', '12px', '');
        $label9 = new TLabel("Orgão:", '', '13px', 'B', '100%');
        $text9 = new TTextDisplay($processo->orgao->nome, '', '12px', '');
        $label10 = new TLabel("Data da distribuição:", '', '13px', 'B', '100%');
        $text10 = new TTextDisplay(TDate::convertToMask($processo->data_distribuicao_protocolo, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '12px', '');
        $label12 = new TLabel("Área:", '', '13px', 'B', '100%');
        $text12 = new TTextDisplay($processo->area->nome, '', '12px', '');
        $label13 = new TLabel("Assunto:", '', '13px', 'B', '100%');
        $text13 = new TTextDisplay($processo->assunto->nome, '', '12px', '');
        $label11 = new TLabel("Valor da causa:", '', '13px', 'B', '100%');
        $text11 = new TTextDisplay(number_format((double)$processo->valor_causa, '2', ',', '.'), '', '12px', '');
        $label14 = new TLabel("Gratuidade processual:", '', '13px', 'B', '100%');
        $text14 = new TTextDisplay($transformed_processo_gratuidade_processual, '', '12px', '');
        $label15 = new TLabel("Status processual:", '', '13px', 'B', '100%');
        $text15 = new TTextDisplay($processo->status_processual->nome, '', '12px', '');
        $label16 = new TLabel("Responsável:", '', '13px', 'B', '100%');
        $text16 = new TTextDisplay($processo->responsavel->nome, '', '12px', '');
        $label17 = new TLabel("Observação:", '', '13px', 'B', '100%');
        $text17 = new TTextDisplay($processo->observacao, '', '12px', '');
        $label18 = new TLabel("Criado em:", '', '13px', 'B', '100%');
        $text18 = new TTextDisplay(TDateTime::convertToMask($processo->data_criacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '10px', '');
        $label19 = new TLabel("Criado por:", '', '13px', 'B', '100%');
        $text19 = new TTextDisplay($processo->criacao_user->name, '', '10px', '');
        $label20 = new TLabel("Atualizado em:", '', '13px', 'B', '100%');
        $text20 = new TTextDisplay(TDateTime::convertToMask($processo->data_modificacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '10px', '');
        $label21 = new TLabel("Atualizado por:", '', '13px', 'B', '100%');
        $text21 = new TTextDisplay($processo->modificacao_user->name, '', '10px', '');
        $btnAddContrato = new TButton('btnAddContrato');
        $btnAddPrincipal = new TButton('btnAddPrincipal');
        $btnAddIncidente = new TButton('btnAddIncidente');
        $btnCriarIncidente = new TButton('btnCriarIncidente');
        $bpagecontainer2 = new BPageContainer();
        $pessoasPage = new BPageContainer();
        $bpagecontainer3 = new BPageContainer();

        $pessoasPage->setSize('100%');
        $bpagecontainer2->setSize('100%');
        $bpagecontainer3->setSize('100%');

        $pessoasPage->setId('b668be8c016723');
        $bpagecontainer2->setId('b65cb618d9c4be');
        $bpagecontainer3->setId('b6800e6d174560');

        $btnAddContrato->addStyleClass('btn-default');
        $btnAddPrincipal->addStyleClass('btn-default');
        $btnAddIncidente->addStyleClass('btn-default');
        $btnCriarIncidente->addStyleClass('btn-default');

        $btnAddContrato->setImage('fas:plus #4CAF50');
        $btnAddPrincipal->setImage('fas:plus #4CAF50');
        $btnAddIncidente->setImage('fas:plus #4CAF50');
        $btnCriarIncidente->setImage('fas:plus #4CAF50');

        $btnCriarIncidente->setAction(new TAction(['ProcessoForm', 'onShow']), "Criar incidente");
        $btnAddContrato->setAction(new TAction(['ContratoProcessoForm', 'onShow']), "Adicionar Contrato");
        $bpagecontainer3->setAction(new TAction(['TarefaSimpleList', 'onShow'], ['processo_id' => $processo->id]));
        $btnAddIncidente->setAction(new TAction([$this, 'onAddIncidente'],['key' => 'key']), "Adicionar incidente");
        $bpagecontainer2->setAction(new TAction(['ViewAndamentosPublicacoesProcesso', 'onShow'], ['key' => $processo->id]));
        $pessoasPage->setAction(new TAction(['ProcessoContratoPessoaSimpleList', 'onShow'], ['processo_id' => $processo->id]));
        $btnAddPrincipal->setAction(new TAction([$this, 'onAddPrincipal'],['processo_incidente_id' => '$processo->id']), "Adicionar principal");

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $bpagecontainer2->add($loadingContainer);
        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $pessoasPage->add($loadingContainer);
        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $bpagecontainer3->add($loadingContainer);

        $btnAddPrincipal->setAction(new TAction([$this, 'onAddPrincipal'],['processo_incidente_id' => $processo->id, 'key' => $processo->id, 'tela' => "PRINCIPAL"]), "Adicionar principal");
        $btnAddIncidente->setAction(new TAction([$this, 'onAddIncidente'],['processo_principal_id' => $processo->id, 'key' => $processo->id, 'tela' => "INCIDENTE"]), "Adicionar incidente");
        $btnAddContrato->setAction(new TAction(['ContratoProcessoForm', 'onShow'],['processo_id' => $param['key']]), "Adicionar Contrato");
        $btnCriarIncidente->setAction(new TAction(['ProcessoForm', 'onShow'],['key' => $processo->id, 'principal_id' => $processo->id]), "Criar incidente");

        $this->form->appendPage("Processo");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([$label2,$text2]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([$label3,$text3],[$label4,$text4]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([$label5,$text5],[$label6,$text6]);
        $row3->layout = ['col-sm-6','col-sm-6'];

        $row4 = $this->form->addFields([$label28,$text188]);
        $row4->layout = [' col-sm-6'];

        $row5 = $this->form->addFields([$label7,$text7],[$label8,$text8]);
        $row5->layout = ['col-sm-6','col-sm-6'];

        $row6 = $this->form->addFields([$label9,$text9],[$label10,$text10]);
        $row6->layout = ['col-sm-6','col-sm-6'];

        $row7 = $this->form->addFields([$label12,$text12],[$label13,$text13]);
        $row7->layout = ['col-sm-6','col-sm-6'];

        $row8 = $this->form->addFields([$label11,$text11],[$label14,$text14]);
        $row8->layout = ['col-sm-6','col-sm-6'];

        $row9 = $this->form->addFields([$label15,$text15],[$label16,$text16]);
        $row9->layout = ['col-sm-6','col-sm-6'];

        $row10 = $this->form->addFields([$label17,$text17]);
        $row10->layout = [' col-sm-12'];

        $row11 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row12 = $this->form->addFields([$label18,$text18],[$label19,$text19],[$label20,$text20],[$label21,$text21]);
        $row12->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $this->form->appendPage("Contratos");
        $row13 = $this->form->addFields([$btnAddContrato],[],[]);
        $row13->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $this->contrato_processo_processo_id_list = new TQuickGrid;
        $this->contrato_processo_processo_id_list->style = 'width:100%';
        $this->contrato_processo_processo_id_list->disableDefaultClick();

        $action_onShow = new TDataGridAction(array('ContratoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{contrato_id}');
        $this->contrato_processo_processo_id_list->addAction($action_onShow);

        $column_contrato_numero = $this->contrato_processo_processo_id_list->addQuickColumn("Contrato", 'contrato->numero', 'left');

        $this->contrato_processo_processo_id_list->createModel();

        $criteria_contrato_processo_processo_id = new TCriteria();
        $criteria_contrato_processo_processo_id->add(new TFilter('processo_id', '=', $processo->id));

        $criteria_contrato_processo_processo_id->setProperty('order', 'id desc');

        $contrato_processo_processo_id_items = ContratoProcesso::getObjects($criteria_contrato_processo_processo_id);

        $this->contrato_processo_processo_id_list->addItems($contrato_processo_processo_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->contrato_processo_processo_id_list));

        $this->form->addContent([$panel]);

        $this->form->appendPage("Vinculos");
        $row14 = $this->form->addFields([$btnAddPrincipal]);
        $row14->layout = ['col-sm-3'];

        $this->processo_vinculo_processo_incidente_id_list = new TQuickGrid;
        $this->processo_vinculo_processo_incidente_id_list->style = 'width:100%';
        $this->processo_vinculo_processo_incidente_id_list->disableDefaultClick();

        $action_onDeletePrincipal = new TDataGridAction(array('ProcessoFormView', 'onDeletePrincipal'));
        $action_onDeletePrincipal->setUseButton(false);
        $action_onDeletePrincipal->setButtonClass('btn btn-default btn-sm');
        $action_onDeletePrincipal->setLabel("Excluir");
        $action_onDeletePrincipal->setImage('fas:trash-alt #F44336');
        $action_onDeletePrincipal->setField('id');

        $action_onDeletePrincipal->setParameter('vinculo_id', '{id}');
        $this->processo_vinculo_processo_incidente_id_list->addAction($action_onDeletePrincipal);

        $action_onShow = new TDataGridAction(array('ProcessoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{processo_principal_id}');
        $this->processo_vinculo_processo_incidente_id_list->addAction($action_onShow);

        $column_processo_principal_numero_cnj_numero = $this->processo_vinculo_processo_incidente_id_list->addQuickColumn("Processo principal", 'processo_principal->numero_cnj_numero', 'left');

        $this->processo_vinculo_processo_incidente_id_list->createModel();

        $criteria_processo_vinculo_processo_incidente_id = new TCriteria();
        $criteria_processo_vinculo_processo_incidente_id->add(new TFilter('processo_incidente_id', '=', $processo->id));

        $processo_vinculo_processo_incidente_id_items = ProcessoVinculo::getObjects($criteria_processo_vinculo_processo_incidente_id);

        $this->processo_vinculo_processo_incidente_id_list->addItems($processo_vinculo_processo_incidente_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->processo_vinculo_processo_incidente_id_list));

        $this->form->addContent([$panel]);
        $row15 = $this->form->addFields([$btnAddIncidente],[$btnCriarIncidente],[]);
        $row15->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $this->processo_vinculo_processo_principal_id_list = new TQuickGrid;
        $this->processo_vinculo_processo_principal_id_list->style = 'width:100%';
        $this->processo_vinculo_processo_principal_id_list->disableDefaultClick();

        $action_onShow = new TDataGridAction(array('ProcessoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{processo_incidente_id}');
        $this->processo_vinculo_processo_principal_id_list->addAction($action_onShow);

        $column_processo_incidente_numero_cnj_numero = $this->processo_vinculo_processo_principal_id_list->addQuickColumn("Processo incidente", 'processo_incidente->numero_cnj_numero', 'left');

        $this->processo_vinculo_processo_principal_id_list->createModel();

        $criteria_processo_vinculo_processo_principal_id = new TCriteria();
        $criteria_processo_vinculo_processo_principal_id->add(new TFilter('processo_principal_id', '=', $processo->id));

        $processo_vinculo_processo_principal_id_items = ProcessoVinculo::getObjects($criteria_processo_vinculo_processo_principal_id);

        $this->processo_vinculo_processo_principal_id_list->addItems($processo_vinculo_processo_principal_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->processo_vinculo_processo_principal_id_list));

        $this->form->addContent([$panel]);

        $this->form->appendPage("Andamentos");
        $row16 = $this->form->addFields([$bpagecontainer2]);
        $row16->layout = [' col-sm-12'];

        $this->form->appendPage("Parte contraria");

        $this->contraparte_processo_id_list = new TQuickGrid;
        $this->contraparte_processo_id_list->style = 'width:100%';
        $this->contraparte_processo_id_list->disableDefaultClick();

        $column_pessoa_nome = $this->contraparte_processo_id_list->addQuickColumn("Parte contraria", 'pessoa->nome', 'left');
        $column_data_criacao_transformed = $this->contraparte_processo_id_list->addQuickColumn("Adicionado em", 'data_criacao', 'left');
        $column_criacao_user_name = $this->contraparte_processo_id_list->addQuickColumn("Adicionado por", 'criacao_user->name', 'left');
        $column_data_modificacao_transformed = $this->contraparte_processo_id_list->addQuickColumn("Atualizado em", 'data_modificacao', 'left');
        $column_modificacao_user_name = $this->contraparte_processo_id_list->addQuickColumn("Atualizado por", 'modificacao_user->name', 'left');

        $column_data_criacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y H:i');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_data_modificacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y H:i');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $this->contraparte_processo_id_list->createModel();

        $criteria_contraparte_processo_id = new TCriteria();
        $criteria_contraparte_processo_id->add(new TFilter('processo_id', '=', $processo->id));

        $criteria_contraparte_processo_id->setProperty('order', 'id desc');

        $contraparte_processo_id_items = Contraparte::getObjects($criteria_contraparte_processo_id);

        $this->contraparte_processo_id_list->addItems($contraparte_processo_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->contraparte_processo_id_list));

        $this->form->addContent([$panel]);

        $this->form->appendPage("Clientes");
        $row17 = $this->form->addFields([$pessoasPage]);
        $row17->layout = [' col-sm-12'];

        $this->form->appendPage("Tarefa");
        $row18 = $this->form->addFields([$bpagecontainer3]);
        $row18->layout = [' col-sm-12'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        $btn_ondeleteAction = new TAction([$this, 'onDelete'],['key'=>$processo->id]);
        $btn_ondeleteLabel = new TLabel("Excluir");

        $btn_ondelete = $this->form->addHeaderAction($btn_ondeleteLabel, $btn_ondeleteAction, 'fas:trash-alt #FF0000'); 
        $btn_ondeleteLabel->setFontSize('12px'); 
        $btn_ondeleteLabel->setFontColor('#333'); 

        $btnProcessoFormOnEditAction = new TAction(['ProcessoForm', 'onEdit'],['key'=>$processo->id]);
        $btnProcessoFormOnEditLabel = new TLabel("Editar");

        $btnProcessoFormOnEdit = $this->form->addHeaderAction($btnProcessoFormOnEditLabel, $btnProcessoFormOnEditAction, 'fas:edit #03A9F4'); 
        $btnProcessoFormOnEditLabel->setFontSize('12px'); 
        $btnProcessoFormOnEditLabel->setFontColor('#333'); 

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

        $style = new TStyle('right-panel > .container-part[page-name=ProcessoFormView]');
        $style->width = '60% !important';   
        $style->show(true);

    }

    public  function onAddPrincipal($param = null) 
    {
        try 
        {
            if($param['processo_incidente_id']){
                TTransaction::open(self::$database);

                $isIncidente = ProcessoVinculo::where('processo_incidente_id','=',$param['processo_incidente_id'])->count();

                if($isIncidente!=0){
                    throw new Exception("Não é possível adicionar mais de um processo principal.");
                }else{
                    TApplication::loadPage('ProcessoVinculoForm', 'onShow', $param);
                }

                TTransaction::close();
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onAddIncidente($param = null) 
    {
        try 
        {
            if($param['processo_principal_id']){
                TApplication::loadPage('ProcessoVinculoForm', 'onShow', $param);
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onDeletePrincipal($param = null) 
    {
        try 
        {
            if($param['vinculo_id']){
                TTransaction::open(self::$database);

                $vinculo = ProcessoVinculo::find($param['vinculo_id']);

                $pageParam['key'] = $vinculo->processo_incidente_id;
                $pageParam['current_tab'] = 2;

                $vinculo->delete();

                TToast::show('success', "Vinculo excluído", 'topRight', 'far:check-circle');
                TApplication::loadPage('ProcessoFormView','onShow', $pageParam);

                TTransaction::close();
            }

            //</autoCode>
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

                // instantiates object
                $object = new Processo($key, FALSE);

                if($object->loadComposite('Publicacao', 'processo_id', $object->id)){
                    throw new Exception("Esse processo ja tem publicação e não pode ser removido!");
                }
                if($object->loadComposite('ProcessoVinculo', 'processo_principal_id', $object->id)){
                    throw new Exception("Esse processo possui incidentes e não pode ser removido!");
                }
                if($object->loadComposite('ProcessoVinculo', 'processo_incidente_id', $object->id)){
                    throw new Exception("Esse processo é um incidente e não pode ser removido!");
                }
                if($object->loadComposite('ContratoProcesso', 'processo_id', $object->id)){
                    throw new Exception("Esse processo possui contratos e não pode ser removido!");
                }

                // deletes the object from the database
                Contraparte::where('processo_id','=',$object->id)->delete();
                $object->delete();

                TTransaction::close();

                TApplication::loadPage('ProcessoList', 'onShow');
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

    public function onShow($param = null)
    {     

        TTransaction::open(self::$database);

        $processo = Processo::find($param['key']);

        if (isset($processo->tipo_processo_id) && $processo->tipo_processo_id){
            if($processo->tipo_processo_id == TipoProcesso::EXTRAJUDICIAL){
                TScript::create("$('label:contains(\"Número padrão CNJ:\")').html('Número:')");
                TScript::create("$('label:contains(\"Data da distribuição:\")').html('Data do protocolo:')");

                TScript::create("$('label:contains(\"Número outro padrão:\")').hide();");
                TScript::create("$('label:contains(\"Tribunal:\")').hide();");
                TScript::create("$('label:contains(\"Foro:\")').hide();");
                TScript::create("$('label:contains(\"Comarca:\")').hide();");
                TScript::create("$('label:contains(\"Vara:\")').hide();");
                TScript::create("$('label:contains(\"Valor da causa:\")').hide();");
                TScript::create("$('label:contains(\"Gratuidade processual:\")').hide();");
                TScript::create("$('label:contains(\"Orgão:\")').show();");

            }else{
                TScript::create("$('label:contains(\"Número:\")').html('Número padrão CNJ:')");
                TScript::create("$('label:contains(\"Data do protocolo:\")').html('Data da distribuição:')");

                TScript::create("$('label:contains(\"Número outro padrão:\")').show();");
                TScript::create("$('label:contains(\"Tribunal:\")').show();");
                TScript::create("$('label:contains(\"Foro:\")').show();");
                TScript::create("$('label:contains(\"Comarca:\")').show();");
                TScript::create("$('label:contains(\"Vara:\")').show();");
                TScript::create("$('label:contains(\"Valor da causa:\")').show();");
                TScript::create("$('label:contains(\"Gratuidade processual:\")').show();");
                TScript::create("$('label:contains(\"Orgão:\")').hide();");
            }
        }

        TTransaction::close();
    }

}

