<?php

class ProcessoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Processo';
    private static $primaryKey = 'id';
    private static $formName = 'form_ProcessoForm';

    use BuilderMasterDetailTrait;
    use BuilderMasterDetailFieldListTrait;

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

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Cadastro de processo");

        $criteria_tipo_processo_id = new TCriteria();
        $criteria_tribunal_id = new TCriteria();
        $criteria_foro_id = new TCriteria();
        $criteria_orgao_id = new TCriteria();
        $criteria_comarca_id = new TCriteria();
        $criteria_vara_id = new TCriteria();
        $criteria_area_id = new TCriteria();
        $criteria_responsavel_id = new TCriteria();
        $criteria_contraparte_processo_pessoa_id = new TCriteria();

        $filterVar = Grupo::PROFISSIONAL;
        $criteria_responsavel_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::CONTRAPARTE;
        $criteria_contraparte_processo_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $id = new TEntry('id');
        $publicacao = new THidden('publicacao');
        $vinculo = new THidden('vinculo');
        $principal_id = new THidden('principal_id');
        $exibir_cliente = new TCheckButton('exibir_cliente');

        /* PRE-PROCESSO */
        $pre_processo = new TCheckButton('pre_processo');
        $descricao_pre_processo = new TEntry('descricao_pre_processo');

        $tipo_processo_id = new TDBCombo('tipo_processo_id', 'escritorio', 'TipoProcesso', 'id', '{nome}','nome asc' , $criteria_tipo_processo_id );
        $envolvimento_id = new TCombo('envolvimento_id');
        $numero_cnj_numero = new TEntry('numero_cnj_numero');
        $numero_outro = new TEntry('numero_outro');
        $tribunal_id = new TDBCombo('tribunal_id', 'escritorio', 'Tribunal', 'id', '{nome}','nome asc' , $criteria_tribunal_id );
        $foro_id = new TDBCombo('foro_id', 'escritorio', 'Foro', 'id', '{nome}','nome asc' , $criteria_foro_id );
        $orgao_id = new TDBCombo('orgao_id', 'escritorio', 'Orgao', 'id', '{nome}','nome asc' , $criteria_orgao_id );
        $comarca_id = new TDBCombo('comarca_id', 'escritorio', 'Comarca', 'id', '{nome}','nome asc' , $criteria_comarca_id );
        $vara_id = new TDBCombo('vara_id', 'escritorio', 'Vara', 'id', '{nome}','nome asc' , $criteria_vara_id );
        $valor_causa = new TNumeric('valor_causa', '2', ',', '.' );
        $area_id = new TDBCombo('area_id', 'escritorio', 'Area', 'id', '{nome}','nome asc' , $criteria_area_id );
        $assunto_id = new TCombo('assunto_id');
        $data_distribuicao_protocolo = new TDate('data_distribuicao_protocolo');
        $status_processual_id = new TCombo('status_processual_id');
        $responsavel_id = new TDBUniqueSearch('responsavel_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_responsavel_id );
        $gratuidade_processual = new TRadioGroup('gratuidade_processual');
        $observacao = new THtmlEditor('observacao');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');
        $contraparte_processo_id = new THidden('contraparte_processo_id[]');
        $contraparte_processo___row__id = new THidden('contraparte_processo___row__id[]');
        $contraparte_processo___row__data = new THidden('contraparte_processo___row__data[]');
        $contraparte_processo_pessoa_id = new TDBUniqueSearch('contraparte_processo_pessoa_id[]', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_contraparte_processo_pessoa_id );
        $this->fieldList_contraparte = new TFieldList();
        $contrato_processo_processo_contrato_id = new TSeekButton('contrato_processo_processo_contrato_id');
        $contrato_processo_processo_id = new THidden('contrato_processo_processo_id');
        $contrato_processo_processo_contrato_numero = new TEntry('contrato_processo_processo_contrato_numero');
        $button_adicionar_contrato_processo_processo = new TButton('button_adicionar_contrato_processo_processo');

        $this->fieldList_contraparte->addField(null, $contraparte_processo_id, []);
        $this->fieldList_contraparte->addField(null, $contraparte_processo___row__id, ['uniqid' => true]);
        $this->fieldList_contraparte->addField(null, $contraparte_processo___row__data, []);
        $this->fieldList_contraparte->addField(new TLabel("Parte contraria", null, '14px', null), $contraparte_processo_pessoa_id, ['width' => '100%']);

        $this->fieldList_contraparte->width = '100%';
        $this->fieldList_contraparte->setFieldPrefix('contraparte_processo');
        $this->fieldList_contraparte->name = 'fieldList_contraparte';

        $this->criteria_fieldList_contraparte = new TCriteria();
        $this->default_item_fieldList_contraparte = new stdClass();

        $this->form->addField($contraparte_processo_id);
        $this->form->addField($contraparte_processo___row__id);
        $this->form->addField($contraparte_processo___row__data);
        $this->form->addField($contraparte_processo_pessoa_id);

        $this->fieldList_contraparte->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $area_id->setChangeAction(new TAction([$this,'onChangearea_id']));
        $tipo_processo_id->setChangeAction(new TAction([$this,'onSelectTipoProcesso']));

        $tipo_processo_id->addValidation("Tipo de processo", new TRequiredValidator());

        /*
            PRE-PROCESSO: a obrigatoriedade do numero deixou de ser fixa.
            Ela passou para PreProcessoService::validarCadastro(), chamado no
            onSave, porque agora depende do estado do registro - e porque
            esconder um campo no navegador nao torna o dado opcional no
            servidor. Processo convencional continua exigindo o numero.
        */

        $exibir_cliente->setUseSwitch(true, 'blue');
        $exibir_cliente->setIndexValue("S");
        $exibir_cliente->setInactiveIndexValue("N");

        /* PRE-PROCESSO */
        $pre_processo->setUseSwitch(true, 'orange');
        $pre_processo->setIndexValue("S");
        $pre_processo->setInactiveIndexValue("N");
        $pre_processo->setValue('N');
        $pre_processo->setChangeAction(new TAction([$this, 'onChangePreProcesso'], ['static' => 1]));
        $descricao_pre_processo->setMaxLength(255);
        $descricao_pre_processo->placeholder = 'Ex.: Reclamação trabalhista — João da Silva';
        $tipo_processo_id->setDefaultOption(false);
        $gratuidade_processual->addItems(["T"=>"Sim","F"=>"Não"]);
        $gratuidade_processual->setLayout('horizontal');
        $gratuidade_processual->setUseButton();
        $button_adicionar_contrato_processo_processo->setAction(new TAction([$this, 'onAddDetailContratoProcessoProcesso'],['static' => 1]), "Adicionar");
        $button_adicionar_contrato_processo_processo->addStyleClass('btn-default');
        $button_adicionar_contrato_processo_processo->setImage('fas:plus #2ecc71');
        $responsavel_id->setMinLength(3);
        $contraparte_processo_pessoa_id->setMinLength(3);

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_distribuicao_protocolo->setDatabaseMask('yyyy-mm-dd');

        $responsavel_id->setMask('{nome}');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $contraparte_processo_pessoa_id->setMask('{nome}');
        $data_distribuicao_protocolo->setMask('dd/mm/yyyy');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);
        $contrato_processo_processo_contrato_numero->setEditable(false);

        $foro_id->enableSearch();
        $vara_id->enableSearch();
        $area_id->enableSearch();
        $orgao_id->enableSearch();
        $comarca_id->enableSearch();
        $assunto_id->enableSearch();
        $tribunal_id->enableSearch();
        $envolvimento_id->enableSearch();
        $tipo_processo_id->enableSearch();
        $status_processual_id->enableSearch();

        $exibir_cliente->setValue('N');
        $gratuidade_processual->setValue('F');
        $vinculo->setValue($param['vinculo'] ?? null);
        $foro_id->setValue($param['foro_id'] ?? null);
        $vara_id->setValue($param['vara_id'] ?? null);
        $area_id->setValue($param['area_id'] ?? null);
        $publicacao->setValue($param['publicacao'] ?? null);
        $tipo_processo_id->setValue(TipoProcesso::JUDICIAL);
        $comarca_id->setValue($param['comarca_id'] ?? null);
        $assunto_id->setValue($param['assunto_id'] ?? null);
        $tribunal_id->setValue($param['tribunal_id'] ?? null);
        $principal_id->setValue($param['principal_id'] ?? null);
        $numero_cnj_numero->setValue($param['numero_processo'] ?? null);

        $id->setSize(100);
        $descricao_pre_processo->setSize('100%');
        $vinculo->setSize(200);
        $publicacao->setSize(200);
        $foro_id->setSize('100%');
        $vara_id->setSize('100%');
        $area_id->setSize('100%');
        $orgao_id->setSize('100%');
        $principal_id->setSize(200);
        $comarca_id->setSize('100%');
        $assunto_id->setSize('100%');
        $tribunal_id->setSize('100%');
        $valor_causa->setSize('100%');
        $numero_outro->setSize('100%');
        $data_criacao->setSize('100%');
        $responsavel_id->setSize('100%');
        $envolvimento_id->setSize('100%');
        $observacao->setSize('100%', 150);
        $tipo_processo_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $numero_cnj_numero->setSize('100%');
        $gratuidade_processual->setSize(80);
        $criacao_user_name->setSize('100%');
        $status_processual_id->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $contrato_processo_processo_id->setSize(200);
        $data_distribuicao_protocolo->setSize('100%');
        $contraparte_processo_pessoa_id->setSize('100%');
        $contrato_processo_processo_contrato_id->setSize('100%');
        $contrato_processo_processo_contrato_numero->setSize('100%');

        $button_adicionar_contrato_processo_processo->id = '65a804e473f68';

        $seed = AdiantiApplicationConfig::get()['general']['seed'];
        $contrato_processo_processo_contrato_id_seekAction = new TAction(['ContratoSeekWindow', 'onShow']);
        $seekFilters = [];
        $seekFields = base64_encode(serialize([
            ['name'=> 'contrato_processo_processo_contrato_id', 'column'=>'{id}'],
            ['name'=> 'contrato_processo_processo_contrato_id', 'column'=>'{id}'],
            ['name'=> 'contrato_processo_processo_contrato_numero', 'column'=>'{numero}']
        ]));

        $seekFilters = base64_encode(serialize($seekFilters));
        $contrato_processo_processo_contrato_id_seekAction->setParameter('_seek_fields', $seekFields);
        $contrato_processo_processo_contrato_id_seekAction->setParameter('_seek_filters', $seekFilters);
        $contrato_processo_processo_contrato_id_seekAction->setParameter('_seek_hash', md5($seed.$seekFields.$seekFilters));

        /*
            Nao acrescente static=1 nesta acao. Testado em 23/09/2026: com o
            parametro, ProcessoForm::onShow da janela de busca responde vazio e
            a lupa para de abrir. A protecao do painel lateral vem de
            ContratoSeekWindow::onSelect, que deixou de fechar o painel.
        */
        $contrato_processo_processo_contrato_id->setAction($contrato_processo_processo_contrato_id_seekAction);

        $contrato_processo_processo_contrato_id_seekAction->setParameter('processo_id',$param['key'] ?? null);

        $this->form->appendPage("Informações cadastrais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id,$publicacao,$vinculo,$principal_id],[new TLabel("Exibir processo para o cliente:", null, '14px', null, '100%'),$exibir_cliente]);
        $row1->layout = ['col-sm-6',' col-sm-6'];

        /*
            PRE-PROCESSO: trabalho iniciado para o cliente antes da
            distribuicao judicial. Enquanto a chave estiver ligada o registro
            vive sem numero; a descricao e o que permite encontra-lo.
        */
        $row1a = $this->form->addFields([new TLabel("É um pré-processo?", null, '14px', null, '100%'),$pre_processo],[new TLabel("Descrição do pré-processo:", null, '14px', null, '100%'),$descricao_pre_processo]);
        $row1a->layout = ['col-sm-4',' col-sm-8'];
        $row1a->class = trim(($row1a->class ?? '') . ' linha-pre-processo');

        /*
            Nao ha campo de cliente aqui de proposito. Quem diz de quem e o
            processo e o contrato, na aba "Contratos" - e todo processo tem
            contrato. Um campo proprio criaria um segundo lugar para a mesma
            verdade, que e como se produz divergencia.
        */

        $row2 = $this->form->addFields([new TLabel("Tipo de processo:", '#FF0000', '14px', null, '100%'),$tipo_processo_id],[new TLabel("Envolvimento:", null, '14px', null, '100%'),$envolvimento_id]);
        $row2->layout = ['col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Número padrão CNJ:", '#ff0000', '14px', null, '100%'),$numero_cnj_numero],[new TLabel("Número outro padrão:", null, '14px', null),$numero_outro]);
        $row3->layout = ['col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Tribunal:", null, '14px', null, '100%'),$tribunal_id],[new TLabel("Foro:", null, '14px', null, '100%'),$foro_id]);
        $row4->layout = ['col-sm-6',' col-sm-6'];

        $row5 = $this->form->addFields([new TLabel("Orgão:", null, '14px', null, '100%'),$orgao_id]);
        $row5->layout = ['col-sm-6'];

        $row6 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row7 = $this->form->addFields([new TLabel("Comarca:", null, '14px', null, '100%'),$comarca_id],[new TLabel("Vara:", null, '14px', null, '100%'),$vara_id],[new TLabel("Valor da causa:", null, '14px', null, '100%'),$valor_causa]);
        $row7->layout = ['col-sm-4','col-sm-4','col-sm-4'];

        $row8 = $this->form->addFields([new TLabel("Area:", null, '14px', null, '100%'),$area_id],[new TLabel("Assunto:", null, '14px', null, '100%'),$assunto_id],[new TLabel("Data da distribuição:", null, '14px', null, '100%'),$data_distribuicao_protocolo]);
        $row8->layout = ['col-sm-4','col-sm-4','col-sm-4'];

        $row9 = $this->form->addFields([new TLabel("Status processual:", null, '14px', null, '100%'),$status_processual_id],[new TLabel("Responsável:", null, '14px', null, '100%'),$responsavel_id],[new TLabel("Gratuidade processual:", null, '14px', null, '100%'),$gratuidade_processual]);
        $row9->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row10 = $this->form->addFields([new TLabel("Observação:", null, '14px', null, '100%'),$observacao]);
        $row10->layout = [' col-sm-12'];

        $row11 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row12 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row12->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $this->form->appendPage("Parte contraria");
        $row13 = $this->form->addFields([$this->fieldList_contraparte]);
        $row13->layout = [' col-sm-12'];

        $this->form->appendPage("Contratos");

        $this->detailFormContratoProcessoProcesso = new BootstrapFormBuilder('detailFormContratoProcessoProcesso');
        $this->detailFormContratoProcessoProcesso->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormContratoProcessoProcesso->setProperty('class', 'form-horizontal builder-detail-form');

        $row14 = $this->detailFormContratoProcessoProcesso->addFields([new TLabel("Contrato:", '#ff0000', '14px', null, '100%'),$contrato_processo_processo_contrato_id,$contrato_processo_processo_id],[new TLabel(" ", null, '14px', null, '100%'),$contrato_processo_processo_contrato_numero]);
        $row14->layout = [' col-sm-4',' col-sm-8'];

        $row15 = $this->detailFormContratoProcessoProcesso->addFields([$button_adicionar_contrato_processo_processo]);
        $row15->layout = [' col-sm-12'];

        $row16 = $this->detailFormContratoProcessoProcesso->addFields([new THidden('contrato_processo_processo__row__id')]);
        $this->contrato_processo_processo_criteria = new TCriteria();

        $this->contrato_processo_processo_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->contrato_processo_processo_list->generateHiddenFields();
        $this->contrato_processo_processo_list->setId('contrato_processo_processo_list');

        $this->contrato_processo_processo_list->style = 'width:100%';
        $this->contrato_processo_processo_list->class .= ' table-bordered';

        $column_contrato_processo_processo_contrato_numero = new TDataGridColumn('contrato->numero', "Contrato", 'left');

        $column_contrato_processo_processo__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_contrato_processo_processo__row__data->setVisibility(false);

        $action_onEditDetailContratoProcesso = new TDataGridAction(array('ProcessoForm', 'onEditDetailContratoProcesso'));
        $action_onEditDetailContratoProcesso->setUseButton(false);
        $action_onEditDetailContratoProcesso->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailContratoProcesso->setLabel("Editar");
        $action_onEditDetailContratoProcesso->setImage('far:edit #478fca');
        $action_onEditDetailContratoProcesso->setFields(['__row__id', '__row__data']);

        $this->contrato_processo_processo_list->addAction($action_onEditDetailContratoProcesso);
        $action_onDeleteDetailContratoProcesso = new TDataGridAction(array('ProcessoForm', 'onDeleteDetailContratoProcesso'));
        $action_onDeleteDetailContratoProcesso->setUseButton(false);
        $action_onDeleteDetailContratoProcesso->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailContratoProcesso->setLabel("Excluir");
        $action_onDeleteDetailContratoProcesso->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailContratoProcesso->setFields(['__row__id', '__row__data']);

        $this->contrato_processo_processo_list->addAction($action_onDeleteDetailContratoProcesso);

        $this->contrato_processo_processo_list->addColumn($column_contrato_processo_processo_contrato_numero);

        $this->contrato_processo_processo_list->addColumn($column_contrato_processo_processo__row__data);

        $this->contrato_processo_processo_list->createModel();
        $tableResponsiveDiv = new TElement('div');
        $tableResponsiveDiv->class = 'table-responsive';
        $tableResponsiveDiv->add($this->contrato_processo_processo_list);
        $this->detailFormContratoProcessoProcesso->addContent([$tableResponsiveDiv]);
        $row17 = $this->form->addFields([$this->detailFormContratoProcessoProcesso]);
        $row17->layout = [' col-sm-12'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['ProcessoList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TScript::create("$('label:contains(\"Orgão:\")').hide();");
        TScript::create("$(\"[name='orgao_id']\").closest('.fb-inline-field-container').hide()");
        BootstrapFormBuilder::hideField(self::$formName, 'orgao_id');

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ProcessoForm]');
        $style->width = '60% !important';   
        $style->show(true);

    }

    public static function onChangearea_id($param)
    {
        try
        {

            if (isset($param['area_id']) && $param['area_id'])
            { 
                $criteria = TCriteria::create(['area_id' => $param['area_id']]);
                TDBCombo::reloadFromModel(self::$formName, 'assunto_id', 'escritorio', 'Assunto', 'id', '{nome}', 'nome asc', $criteria, TRUE); 
            } 
            else 
            { 
                TCombo::clearField(self::$formName, 'assunto_id'); 
            }  

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    } 

    public static function onSelectTipoProcesso($param = null) 
    {
        try 
        {

            if (isset($param['tipo_processo_id']) && $param['tipo_processo_id'])
            { 
                $criteria = TCriteria::create(['tipo_processo_id' => $param['tipo_processo_id']]);
                TDBCombo::reloadFromModel(self::$formName, 'envolvimento_id', 'escritorio', 'Envolvimento', 'id', '{nome}', 'nome asc', $criteria, TRUE); 
            } 
            else 
            { 
                TCombo::clearField(self::$formName, 'envolvimento_id'); 
            }  

            if (isset($param['tipo_processo_id']) && $param['tipo_processo_id'])
            { 
                $criteria = TCriteria::create(['tipo_processo_id' => $param['tipo_processo_id']]);
                TDBCombo::reloadFromModel(self::$formName, 'status_processual_id', 'escritorio', 'StatusProcessual', 'id', '{nome}', 'nome asc', $criteria, TRUE); 
            } 
            else 
            { 
                TCombo::clearField(self::$formName, 'status_processual_id'); 
            }  

            $formulario = $param['formulario'] ?? self::$formName;

            if (isset($param['tipo_processo_id']) && $param['tipo_processo_id']){
                if($param['tipo_processo_id'] == TipoProcesso::EXTRAJUDICIAL){
                    TScript::create("$('label:contains(\"Número padrão CNJ:\")').html('Número:')");
                    TScript::create("$('label:contains(\"Data da distribuição:\")').html('Data do protocolo:')");

                    TScript::create("$('label:contains(\"Número outro padrão:\")').hide();");
                    TScript::create("$(\"[name='numero_outro']\").closest('.fb-inline-field-container').hide()");

                    TScript::create("$('label:contains(\"Tribunal:\")').hide();");
                    TScript::create("$(\"[name='tribunal_id']\").closest('.fb-inline-field-container').hide()");
                    BootstrapFormBuilder::hideField($formulario, 'tribunal_id');

                    TScript::create("$('label:contains(\"Foro:\")').hide();");
                    TScript::create("$(\"[name='foro_id']\").closest('.fb-inline-field-container').hide()");
                    BootstrapFormBuilder::hideField($formulario, 'foro_id');

                    TScript::create("$('label:contains(\"Comarca:\")').hide();");
                    TScript::create("$(\"[name='comarca_id']\").closest('.fb-inline-field-container').hide()");
                    BootstrapFormBuilder::hideField($formulario, 'comarca_id');

                    TScript::create("$('label:contains(\"Vara:\")').hide();");
                    TScript::create("$(\"[name='vara_id']\").closest('.fb-inline-field-container').hide()");

                    TScript::create("$('label:contains(\"Valor da causa:\")').hide();");
                    TScript::create("$(\"[name='valor_causa']\").closest('.fb-inline-field-container').hide()");

                    TScript::create("$('label:contains(\"Gratuidade processual:\")').hide();");
                    TScript::create("$(\"[name='gratuidade_processual']\").closest('.fb-inline-field-container').hide()");

                    TScript::create("$('label:contains(\"Orgão:\")').show();");
                    TScript::create("$(\"[name='orgao_id']\").closest('.fb-inline-field-container').show()");
                    BootstrapFormBuilder::showField($formulario, 'orgao_id');

                }else{
                    TScript::create("$('label:contains(\"Número:\")').html('Número padrão CNJ:')");
                    TScript::create("$('label:contains(\"Data do protocolo:\")').html('Data da distribuição:')");

                    TScript::create("$('label:contains(\"Número outro padrão:\")').show();");
                    TScript::create("$(\"[name='numero_outro']\").closest('.fb-inline-field-container').show()");

                    TScript::create("$('label:contains(\"Tribunal:\")').show();");
                    TScript::create("$(\"[name='tribunal_id']\").closest('.fb-inline-field-container').show()");
                    BootstrapFormBuilder::showField($formulario, 'tribunal_id');

                    TScript::create("$('label:contains(\"Foro:\")').show();");
                    TScript::create("$(\"[name='foro_id']\").closest('.fb-inline-field-container').show()");

                    TScript::create("$('label:contains(\"Comarca:\")').show();");
                    TScript::create("$(\"[name='comarca_id']\").closest('.fb-inline-field-container').show()");
                    BootstrapFormBuilder::showField($formulario, 'comarca_id');

                    TScript::create("$('label:contains(\"Vara:\")').show();");
                    TScript::create("$(\"[name='vara_id']\").closest('.fb-inline-field-container').show()");

                    TScript::create("$('label:contains(\"Valor da causa:\")').show();");
                    TScript::create("$(\"[name='valor_causa']\").closest('.fb-inline-field-container').show()");

                    TScript::create("$('label:contains(\"Gratuidade processual:\")').show();");
                    TScript::create("$(\"[name='gratuidade_processual']\").closest('.fb-inline-field-container').show()");

                    TScript::create("$('label:contains(\"Orgão:\")').hide();");
                    TScript::create("$(\"[name='orgao_id']\").closest('.fb-inline-field-container').hide()");
                    BootstrapFormBuilder::hideField($formulario, 'orgao_id');
                }
            }

            /*
                PRE-PROCESSO: sempre por ultimo.

                Esta rotina e a dona dos campos judiciais - e quem decide o que
                aparece para Judicial e para Extrajudicial. O estado de
                pre-processo e uma camada por cima: ele so retira da tela o que
                nao pode existir antes da distribuicao.

                Aplicar aqui, no fim, e o que resolve o conflito: nao importa se
                o usuario mexeu primeiro na chave ou primeiro no tipo, a ultima
                palavra sobre o numero e sempre do pre-processo.
            */
            self::aplicarEstadoPreProcesso($param);

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    /*
        PRE-PROCESSO: reage a chave "E um pre-processo?".

        Delega para onSelectTipoProcesso, que reconstroi a tela conforme o tipo
        e, no fim dela, aplica o estado de pre-processo. Assim existe um unico
        caminho que monta o formulario, em vez de duas rotinas disputando os
        mesmos campos.
    */
    public static function onChangePreProcesso($param = null)
    {
        self::onSelectTipoProcesso($param);
    }

    /*
        PRE-PROCESSO: camada que roda por cima da decisao do tipo de processo.

        Isto e conforto visual, nao validacao. A regra de verdade esta em
        PreProcessoService::validarCadastro(), no onSave, que roda mesmo se a
        requisicao nao passar por esta tela.

        Regra de convivencia com onSelectTipoProcesso: quando a chave esta
        ligada, esta rotina retira da tela o que nao pode existir antes da
        distribuicao. Quando esta desligada, ela devolve apenas o que ela
        propria escondeu - o numero padrao CNJ. O restante (numero outro
        padrao, tribunal, foro, comarca, vara) fica por conta do tipo de
        processo, que ja decidiu antes; mexer neles aqui desfaria a escolha
        de Extrajudicial.
    */
    private static function aplicarEstadoPreProcesso($param = null)
    {
        $eh_pre = (($param['pre_processo'] ?? 'N') === 'S');

        if ($eh_pre)
        {
            /*
                Sem distribuicao nao ha numero. Os campos saem da tela e sao
                limpos, para nao restar valor digitado antes de marcar a chave.
                Os demais campos judiciais continuam disponiveis: comarca ou
                area ja podem ser conhecidas nesta fase.
            */
            TScript::create("$('label:contains(\"Número padrão CNJ:\"), label:contains(\"Número:\")').hide();");
            TScript::create("$(\"[name='numero_cnj_numero']\").val('').closest('.fb-inline-field-container').hide()");

            TScript::create("$('label:contains(\"Número outro padrão:\")').hide();");
            TScript::create("$(\"[name='numero_outro']\").val('').closest('.fb-inline-field-container').hide()");

            TScript::create("$('.linha-pre-processo label:contains(\"Descrição do pré-processo:\")').css('color', '#ff0000');");
        }
        else
        {
            TScript::create("$('label:contains(\"Número padrão CNJ:\"), label:contains(\"Número:\")').show();");
            TScript::create("$(\"[name='numero_cnj_numero']\").closest('.fb-inline-field-container').show()");

            TScript::create("$('.linha-pre-processo label:contains(\"Descrição do pré-processo:\")').css('color', '');");
        }

        TScript::create("$(\"[name='descricao_pre_processo']\").closest('.fb-inline-field-container').show()");
    }

    public  function onAddDetailContratoProcessoProcesso($param = null)
    {
        try
        {
            $data = $this->form->getData();

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"Contrato", 'name'=>"contrato_processo_processo_contrato_id", 'class'=>'TRequiredValidator', 'value'=>[]];
            foreach($requiredFields as $requiredField)
            {
                try
                {
                    (new $requiredField['class'])->validate($requiredField['label'], $data->{$requiredField['name']}, $requiredField['value']);
                }
                catch(Exception $e)
                {
                    $errors[] = $e->getMessage() . '.';
                }
             }
             if(count($errors) > 0)
             {
                 throw new Exception(implode('<br>', $errors));
             }

            $__row__id = !empty($data->contrato_processo_processo__row__id) ? $data->contrato_processo_processo__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new ContratoProcesso();
            $grid_data->__row__id = $__row__id;
            $grid_data->contrato_id = $data->contrato_processo_processo_contrato_id;
            $grid_data->id = $data->contrato_processo_processo_id;
            $grid_data->contrato_numero = $data->contrato_processo_processo_contrato_numero;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['contrato_id'] =  $param['contrato_processo_processo_contrato_id'] ?? null;
            $__row__data['__display__']['id'] =  $param['contrato_processo_processo_id'] ?? null;
            $__row__data['__display__']['contrato_numero'] =  $param['contrato_processo_processo_contrato_numero'] ?? null;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->contrato_processo_processo_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('contrato_processo_processo_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->contrato_processo_processo_contrato_id = '';
            $data->contrato_processo_processo_id = '';
            $data->contrato_processo_processo_contrato_numero = '';
            $data->contrato_processo_processo__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#65a804e473f68');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public static function onEditDetailContratoProcesso($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->contrato_processo_processo_contrato_id = $__row__data->__display__->contrato_id ?? null;
            $data->contrato_processo_processo_id = $__row__data->__display__->id ?? null;
            $data->contrato_processo_processo_contrato_numero = $__row__data->__display__->contrato_numero ?? null;
            $data->contrato_processo_processo__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#65a804e473f68');
               if(!element.attr('add')){
                   element.attr('add', base64_encode(element.html()));
               }
               element.html(\"<span><i class='far fa-edit' style='color:#478fca;padding-right:4px;'></i>Editar</span>\");
               if(!element.attr('edit')){
                   element.attr('edit', base64_encode(element.html()));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public static function onDeleteDetailContratoProcesso($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->contrato_processo_processo_contrato_id = '';
            $data->contrato_processo_processo_id = '';
            $data->contrato_processo_processo_contrato_numero = '';
            $data->contrato_processo_processo__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('contrato_processo_processo_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#65a804e473f68');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Processo(); // create an empty object 

            $data = $this->form->getData(); // get form data as array

            /*
                PRE-PROCESSO.

                normalizarDadosCadastro grava o numero ausente como NULL, e
                nao como '': o indice unico parcial do banco ignora NULL, entao
                varios pre-processos convivem sem numero, mas duas strings
                vazias colidiriam a partir do segundo registro.

                validarCadastro substitui o TRequiredValidator que estava preso
                ao campo numero e a checagem de duplicidade que vinha logo
                abaixo. Ela cobre os dois estados e roda sempre, inclusive se a
                requisicao nao tiver passado pela tela.
            */
            PreProcessoService::normalizarDadosCadastro($data);

            /*
                O contrato pode estar em dois lugares neste momento: nas linhas
                que o usuario acabou de adicionar na aba "Contratos" (ainda nao
                gravadas) ou ja no banco, se for edicao. As duas contam.
            */
            $tem_contrato = !empty($param['contrato_processo_processo_list___row__data'])
                || PreProcessoService::temContrato($data->id ?? null);

            PreProcessoService::validarCadastro($data, $data->id ?? null, $tem_contrato);

            $eh_novo = empty($data->id);

            $object->fromArray( (array) $data); // load the object with data

            $object->numero_cnj_numero = $data->numero_cnj_numero;

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object

            /*
                PRE-PROCESSO: andamento inicial.

                Um pre-processo cadastrado a mao nasce com o mesmo andamento
                que o wizard de contratos ja criava - a etapa marcada como
                "Padrão pré-processo" para a trilha do processo. O cliente
                passa a ver o acompanhamento sem ninguem precisar lancar nada.

                So na inclusao: reabrir e salvar um registro existente nao
                ressuscita um andamento que alguem apagou de proposito.
            */
            if ($eh_novo && PreProcessoService::ehPreProcesso($object))
            {
                $andamento_inicial = PreProcessoService::criarAndamentoInicial($object, TSession::getValue('userid'));

                if (!$andamento_inicial)
                {
                    /*
                        Nao e erro: o pre-processo esta gravado e valido. Falta
                        cadastro, e o usuario precisa saber disso para ir
                        marcar a etapa.
                    */
                    TToast::show(
                        'warning',
                        'O pré-processo foi salvo, mas nenhuma etapa está marcada como "Padrão pré-processo" para este tipo de processo. Marque uma em Configurações › Etapas para o andamento inicial ser criado.',
                        'topRight',
                        'fas:exclamation-triangle'
                    );
                }
            }

            /*
                PRE-PROCESSO: o bloco abaixo casa publicacoes pelo numero do
                processo. Um pre-processo nao tem numero, entao nao ha o que
                casar - e as consultas com NULL nao encontrariam nada de
                qualquer forma. Processo convencional segue igual.
            */
            if(!empty($object->numero_cnj_numero)){

            if($data->publicacao != null){
                $publicacao = Publicacao::find($data->publicacao);
                if($data->vinculo !== "PRINCIPAL"){
                    if(empty($publicacao->numero_unico_processo) || !isset($publicacao->numero_unico_processo)){
                        $publicacao->numero_unico_processo = $object->numero_cnj_numero;
                    }
                }else{
                    if(empty($publicacao->numero_processo_principal) || !isset($publicacao->numero_processo_principal)){
                        $publicacao->numero_processo_principal = $object->numero_cnj_numero;
                    }
                }
                $publicacao->store();
            }

            $publicacoes = Publicacao::where('numero_unico_processo','=',$object->numero_cnj_numero)->load();

            foreach ($publicacoes as $publicacao) {
                $publicacao->processo_id = $object->id;
                $publicacao->store();

                APIPublicacaoController::adicionarMovimentacao($publicacao->id, "Processo adicionado.", null, $object->id);
            }

            $publicacoes = Publicacao::where('numero_processo_principal','=',$object->numero_cnj_numero)->load();
            foreach ($publicacoes as $publicacao) {
                if($publicacao->processo_id){
                    $vinculo = ProcessoVinculo::where('processo_principal_id','=',$object->id)
                                              ->where('processo_incidente_id','=',$publicacao->processo_id)
                                              ->count();
                    if($vinculo<1){                      
                        $vinculo = new ProcessoVinculo();
                        $vinculo->processo_principal_id = $object->id;
                        $vinculo->processo_incidente_id = $publicacao->processo_id;
                        $vinculo->store();
                    }
                }
            }

            } // fim do bloco que depende do numero (PRE-PROCESSO)

            if(isset($data->principal_id) && !empty($data->principal_id)){
                $vinculo = new ProcessoVinculo();
                $vinculo->processo_principal_id = $data->principal_id;
                $vinculo->processo_incidente_id = $object->id;
                $vinculo->store();
            }

            if(isset($param['processo_id']) && !empty($param['processo_id'])){
                $processoContrato = ContratoProcesso::where('processo_id','=',$param['processo_id'])->first();

                if($processoContrato){
                    foreach($processoContrato as $vinculoContrato){
                        $contrato = Contrato::find($vinculoContrato->contrato_id);
                        $contrato->tipo_processo_id = $object->tipo_processo_id;
                        $contrato->envolvimento_id = $object->envolvimento_id;
                        $contrato->area_id = $object->area_id;
                        $contrato->assunto_id = $object->asdasunto_id;
                        $contrato->data_modificacao = date('Y-m-d H:i:s');
                        $contrato->modificacao_user_id = TSession::getValue('userid');
                        $contrato->store();
                    }
                }
            }

            $this->fireEvents($object);

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

//<generatedAutoCode>
            $this->criteria_fieldList_contraparte->setProperty('order', 'id desc');
//</generatedAutoCode>
            $contraparte_processo_items = $this->storeItems('Contraparte', 'processo_id', $object, $this->fieldList_contraparte, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_contraparte); 

            $contrato_processo_processo_items = $this->storeMasterDetailItems('ContratoProcesso', 'processo_id', 'contrato_processo_processo', $object, $param['contrato_processo_processo_list___row__data'] ?? [], $this->form, $this->contrato_processo_processo_list, function($masterObject, $detailObject){ 

                //code here

            }, $this->contrato_processo_processo_criteria); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            if($data->publicacao != null){
                TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
                TScript::create("Template.closeRightPanel();");
                TApplication::loadPage('PublicacaoHeaderList', 'onShow');
            }elseif(isset($data->principal_id) && !empty($data->principal_id)){
                TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
                TScript::create("Template.closeRightPanel();");
                TApplication::loadPage('ProcessoFormView', 'onShow',['key'=>$data->principal_id,'current_tab'=>2]);
            }else{

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ProcessoList', 'onShow', $loadPageParam); 

                        TScript::create("Template.closeRightPanel();");
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

            }
        }
        catch (Exception $e) // in case of exception
        {

            $this->fireEvents($this->form->getData());  

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Processo($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->criteria_fieldList_contraparte->setProperty('order', 'id desc');
                $this->fieldList_contraparte_items = $this->loadItems('Contraparte', 'processo_id', $object, $this->fieldList_contraparte, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_contraparte); 

                $contrato_processo_processo_items = $this->loadMasterDetailItems('ContratoProcesso', 'processo_id', 'contrato_processo_processo', $object, $this->form, $this->contrato_processo_processo_list, $this->contrato_processo_processo_criteria, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                    $objectItems->contrato_processo_processo_contrato_id = null;
                    if(isset($detailObject->contrato_id) && $detailObject->contrato_id)
                    {
                        $objectItems->__display__->contrato_id = $detailObject->contrato_id;
                    }

                    $objectItems->contrato_processo_processo_contrato_id = null;
                    if(isset($detailObject->contrato_id) && $detailObject->contrato_id)
                    {
                        $objectItems->__display__->contrato_id = $detailObject->contrato_id;
                    }

                }); 

                $this->form->setData($object); // fill the form

                $this->fireEvents($object);

                /*
                    PRE-PROCESSO: uma chamada so. onSelectTipoProcesso monta a
                    tela pelo tipo e aplica o estado de pre-processo no fim,
                    entao basta que os dois valores cheguem juntos.
                */
                $param['tipo_processo_id'] = $object->tipo_processo_id;
                $param['pre_processo']     = trim((string) $object->pre_processo);
                $this->onSelectTipoProcesso($param);

                /*
                    Processo ja convertido: a descricao do pre-processo nao
                    aparece mais. O campo so sai da tela - o valor continua no
                    formulario e e gravado igual ao salvar.
                */
                if (PreProcessoService::foiConvertido($object))
                {
                    TScript::create("$('.linha-pre-processo label:contains(\"Descrição do pré-processo:\")').hide();");
                    TScript::create("$(\"[name='descricao_pre_processo']\").closest('.fb-inline-field-container').hide()");
                }

                TTransaction::close(); // close the transaction 

            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);

        $this->fieldList_contraparte->addHeader();
        $this->fieldList_contraparte->addDetail($this->default_item_fieldList_contraparte);

        $this->fieldList_contraparte->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_contraparte->addHeader();
        $this->fieldList_contraparte->addDetail($this->default_item_fieldList_contraparte);

        $this->fieldList_contraparte->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->onSelectTipoProcesso(['tipo_processo_id' => TipoProcesso::JUDICIAL]);

        if($param['principal_id']){
            TTransaction::open(self::$database);
            $object = Processo::find($param['principal_id']);
            if(!$object){
                throw new Exception("Processo principal não encontrado {$param['principal_id']}.");
            }

            $data = new stdClass();
            $data->tipo_processo_id = $object->tipo_processo_id;
            $data->tribunal_id = $object->tribunal_id;
            $data->foro_id = $object->foro_id;
            $data->comarca_id = $object->comarca_id;
            $data->vara_id = $object->vara_id;
            $data->orgao_id = $object->orgao_id;
            $data->data_distribuicao_protocolo = date('d/m/Y',  strtotime($object->data_distribuicao_protocolo));
            $data->valor_causa = (float) $object->valor_causa;
            $data->area_id = $object->area_id;
            $data->assunto_id = $object->assunto_id;
            $data->gratuidade_processual = $object->gratuidade_processual;
            $data->status_processual_id = $object->status_processual_id;
            $data->responsavel_id = $object->responsavel_id;
            $data->envolvimento_id = $object->envolvimento_id;
            $data->observacao = $object->observacao;

            $contrapartes = Contraparte::where('processo_id','=',(int) $object->id)->load();
            $pessoas = array();
            foreach ($contrapartes as $contraparte) {
                $pessoas[] = $contraparte->pessoa_id;
            }

            $contratos_processo = ContratoProcesso::where('processo_id','=',$object->id)->load();

            foreach ($contratos_processo as $contrato_processo)
            {
                $contrato = $contrato_processo->get_contrato();

                $__row__id = 'b'.uniqid();

                $grid_data = new ContratoProcesso();
                $grid_data->__row__id = $__row__id;
                $grid_data->contrato_id = $contrato->id;
                $grid_data->contrato_numero = $contrato->numero;

                $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
                $__row__data['__row__id'] = $__row__id;
                $__row__data['__display__']['contrato_id'] = $contrato->id;
                $__row__data['__display__']['contrato_numero'] = $contrato->numero;

                $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
                $row = $this->contrato_processo_processo_list->addItem($grid_data);
                $row->id = $grid_data->__row__id;

                TDataGrid::replaceRowById('contrato_processo_processo_list', $grid_data->__row__id, $row);
            }

            TTransaction::close();

            $data->contraparte_processo_pessoa_id = $pessoas;

            TFieldList::clearRows('fieldList_contraparte');    
            TFieldList::addRows('fieldList_contraparte', count($pessoas)-1);

            TForm::sendData(self::$formName, $data, false, true);
        }

    } 

    public function fireEvents( $object )
    {
        $obj = new stdClass;
        if(is_object($object) && get_class($object) == 'stdClass')
        {
            if(isset($object->tipo_processo_id))
            {
                $value = $object->tipo_processo_id;

                $obj->tipo_processo_id = $value;
            }
            if(isset($object->envolvimento_id))
            {
                $value = $object->envolvimento_id;

                $obj->envolvimento_id = $value;
            }
            if(isset($object->area_id))
            {
                $value = $object->area_id;

                $obj->area_id = $value;
            }
            if(isset($object->assunto_id))
            {
                $value = $object->assunto_id;

                $obj->assunto_id = $value;
            }
            if(isset($object->status_processual_id))
            {
                $value = $object->status_processual_id;

                $obj->status_processual_id = $value;
            }
            if(isset($object->contrato_processo_processo_contrato_id))
            {
                $value = $object->contrato_processo_processo_contrato_id;

                $obj->contrato_processo_processo_contrato_id = $value;
            }
        }
        elseif(is_object($object))
        {
            if(isset($object->tipo_processo_id))
            {
                $value = $object->tipo_processo_id;

                $obj->tipo_processo_id = $value;
            }
            if(isset($object->envolvimento_id))
            {
                $value = $object->envolvimento_id;

                $obj->envolvimento_id = $value;
            }
            if(isset($object->area_id))
            {
                $value = $object->area_id;

                $obj->area_id = $value;
            }
            if(isset($object->assunto_id))
            {
                $value = $object->assunto_id;

                $obj->assunto_id = $value;
            }
            if(isset($object->status_processual_id))
            {
                $value = $object->status_processual_id;

                $obj->status_processual_id = $value;
            }
            if(isset($object->contrato_id))
            {
                $value = $object->contrato_id;

                $obj->contrato_processo_processo_contrato_id = $value;
            }
        }
        TForm::sendData(self::$formName, $obj);
    }  

    public static function getFormName()
    {
        return self::$formName;
    }

}

