<?php

use phputil\extenso\Extenso;
/*

class GerarContratoForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_GerarContratoForm';

*/
class GerarContratoForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_GerarContratoForm';

    use BuilderMasterDetailTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Gerar contrato");

        $criteria_cliente_id = new TCriteria();
        $criteria_tipoprocesso = new TCriteria();
        $criteria_envolvimento = new TCriteria();
        $criteria_area = new TCriteria();
        $criteria_assunto = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_contrato_evento_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_unidade_indexador_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_contrato_indexador_id = new TCriteria();
        $criteria_tipo_modelo_documento_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $numero = new THidden('numero');
        $cliente_label = new TLabel("Cliente:", '#FF0000', '14px', null);
        $cliente_id = new TDBMultiSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome_busca asc' , $criteria_cliente_id );
        $objeto = new TText('objeto');
        $tipoprocesso = new TDBUniqueSearch('tipoprocesso', 'escritorio', 'TipoProcesso', 'id', 'nome','nome asc' , $criteria_tipoprocesso );
        $envolvimento = new TDBUniqueSearch('envolvimento', 'escritorio', 'Envolvimento', 'id', 'nome','nome asc' , $criteria_envolvimento );
        $area = new TDBUniqueSearch('area', 'escritorio', 'Area', 'id', 'nome','nome asc' , $criteria_area );
        $assunto = new TDBUniqueSearch('assunto', 'escritorio', 'Assunto', 'id', 'nome','nome asc' , $criteria_assunto );
        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id = new TDBCombo('contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id', 'escritorio', 'ContratoPagamentoOpcao', 'id', '{nome}','nome asc' , $criteria_contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id );
        $contrato_pagamento_parcela_contrato_id = new THidden('contrato_pagamento_parcela_contrato_id');
        $contrato_pagamento_parcela_contrato_valor = new TNumeric('contrato_pagamento_parcela_contrato_valor', '2', ',', '.' );
        $contrato_pagamento_parcela_contrato_data_pagamento = new TDate('contrato_pagamento_parcela_contrato_data_pagamento');
        $contrato_pagamento_parcela_contrato_contrato_evento_id = new TDBCombo('contrato_pagamento_parcela_contrato_contrato_evento_id', 'escritorio', 'ContratoPagamentoEvento', 'id', '{nome}','nome asc' , $criteria_contrato_pagamento_parcela_contrato_contrato_evento_id );
        $contrato_pagamento_parcela_contrato_complemento_indexador = new TEntry('contrato_pagamento_parcela_contrato_complemento_indexador');
        $contrato_pagamento_parcela_contrato_unidade_indexador_id = new TDBCombo('contrato_pagamento_parcela_contrato_unidade_indexador_id', 'escritorio', 'UnidadeIndexador', 'id', '{nome}','nome asc' , $criteria_contrato_pagamento_parcela_contrato_unidade_indexador_id );
        $contrato_pagamento_parcela_contrato_contrato_indexador_id = new TDBCombo('contrato_pagamento_parcela_contrato_contrato_indexador_id', 'escritorio', 'ContratoPagamentoIndexador', 'id', '{nome}','nome asc' , $criteria_contrato_pagamento_parcela_contrato_contrato_indexador_id );
        $contrato_pagamento_parcela_contrato_numero_parcelas = new TSpinner('contrato_pagamento_parcela_contrato_numero_parcelas');
        $button_adicionar_contrato_pagamento_parcela_contrato = new TButton('button_adicionar_contrato_pagamento_parcela_contrato');
        $tipo_modelo_documento_id = new TDBCombo('tipo_modelo_documento_id', 'escritorio', 'TipoModeloDocumento', 'id', '{nome}','nome asc' , $criteria_tipo_modelo_documento_id );
        $modelo_documento_id = new TSelect('modelo_documento_id');

        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id->setChangeAction(new TAction([$this,'onSelectOpcaoPagamento']));
        $tipo_modelo_documento_id->setChangeAction(new TAction([$this,'onChangeTipo']));

        $cliente_id->addValidation("Cliente", new TRequiredValidator()); 

        $contrato_pagamento_parcela_contrato_data_pagamento->setDatabaseMask('yyyy-mm-dd');
        $contrato_pagamento_parcela_contrato_complemento_indexador->setMaxLength(255);
        $contrato_pagamento_parcela_contrato_numero_parcelas->setRange(1, 2000, 1);
        $contrato_pagamento_parcela_contrato_numero_parcelas->setValue('1');
        $button_adicionar_contrato_pagamento_parcela_contrato->setAction(new TAction([$this, 'onAddDetailContratoPagamentoParcelaContrato'],['static' => 1]), "Adicionar");
        $button_adicionar_contrato_pagamento_parcela_contrato->addStyleClass('btn-default');
        $button_adicionar_contrato_pagamento_parcela_contrato->setImage('fas:plus #2ecc71');
        $area->setMinLength(0);
        $assunto->setMinLength(0);
        $cliente_id->setMinLength(2);
        $tipoprocesso->setMinLength(0);
        $envolvimento->setMinLength(0);

        $area->setFilterColumns(["nome"]);
        $assunto->setFilterColumns(["nome"]);
        $cliente_id->setFilterColumns(["nome"]);
        $tipoprocesso->setFilterColumns(["nome"]);
        $envolvimento->setFilterColumns(["nome"]);

        $area->setMask('{nome}');
        $assunto->setMask('{nome}');
        $tipoprocesso->setMask('{nome}');
        $envolvimento->setMask('{nome}');
        $cliente_id->setMask('{nome} - {cpf_cnpj}');
        $contrato_pagamento_parcela_contrato_data_pagamento->setMask('dd/mm/yyyy');

        $modelo_documento_id->enableSearch();
        $tipo_modelo_documento_id->enableSearch();
        $contrato_pagamento_parcela_contrato_contrato_evento_id->enableSearch();
        $contrato_pagamento_parcela_contrato_unidade_indexador_id->enableSearch();
        $contrato_pagamento_parcela_contrato_contrato_indexador_id->enableSearch();
        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id->enableSearch();

        $contrato_pagamento_parcela_contrato_valor->setEditable(false);
        $contrato_pagamento_parcela_contrato_data_pagamento->setEditable(false);
        $contrato_pagamento_parcela_contrato_contrato_evento_id->setEditable(false);
        $contrato_pagamento_parcela_contrato_unidade_indexador_id->setEditable(false);
        $contrato_pagamento_parcela_contrato_complemento_indexador->setEditable(false);
        $contrato_pagamento_parcela_contrato_contrato_indexador_id->setEditable(false);

        $numero->setSize(200);
        $area->setSize('100%');
        $assunto->setSize('100%');
        $objeto->setSize('100%', 70);
        $tipoprocesso->setSize('100%');
        $envolvimento->setSize('100%');
        $cliente_id->setSize('100%', 70);
        $modelo_documento_id->setSize('100%', 70);
        $tipo_modelo_documento_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_id->setSize(200);
        $contrato_pagamento_parcela_contrato_valor->setSize('100%');
        $contrato_pagamento_parcela_contrato_data_pagamento->setSize('100%');
        $contrato_pagamento_parcela_contrato_numero_parcelas->setSize('100%');
        $contrato_pagamento_parcela_contrato_contrato_evento_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_contrato_indexador_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_unidade_indexador_id->setSize('calc(50% - 8px)');
        $contrato_pagamento_parcela_contrato_complemento_indexador->setSize('calc(50% - 8px)');

        $button_adicionar_contrato_pagamento_parcela_contrato->id = '66e1956754b6f';

        $row1 = $this->form->addFields([$numero]);
        $row1->layout = ['col-sm-3'];

        $row2 = $this->form->addFields([$cliente_label],[$cliente_id]);
        $row3 = $this->form->addFields([new TLabel("Objeto:", '#FF0000', '14px', null)],[new TLabel("A Contratada patrocinará em favor do Contratante, serviços profissionais de advocacia para:", null, '14px', null),$objeto]);
        $row4 = $this->form->addFields([new TLabel("Tipo de Processo:", null, '14px', null)],[$tipoprocesso]);
        $row5 = $this->form->addFields([new TLabel("Envolvimento:", null, '14px', null)],[$envolvimento]);
        $row6 = $this->form->addFields([new TLabel("Área:", null, '14px', null)],[$area]);
        $row7 = $this->form->addFields([new TLabel("Assunto:", null, '14px', null)],[$assunto]);

        $this->detailFormContratoPagamentoParcelaContrato = new BootstrapFormBuilder('detailFormContratoPagamentoParcelaContrato');
        $this->detailFormContratoPagamentoParcelaContrato->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormContratoPagamentoParcelaContrato->setProperty('class', 'form-horizontal builder-detail-form');

        $row8 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new TLabel("Opção de pagamento:", '', '14px', null, '100%'),$contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id,$contrato_pagamento_parcela_contrato_id]);
        $row8->layout = [' col-sm-8'];

        $row9 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new TLabel("Valor:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_valor],[new TLabel("Data:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_data_pagamento],[new TLabel("Evento:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_contrato_evento_id]);
        $row9->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row10 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new TLabel("Número do indexador:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_complemento_indexador,$contrato_pagamento_parcela_contrato_unidade_indexador_id],[new TLabel("Indexador:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_contrato_indexador_id],[new TLabel("Numero de parcelas:", '#FF0000', '14px', null, '100%'),$contrato_pagamento_parcela_contrato_numero_parcelas]);
        $row10->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row11 = $this->detailFormContratoPagamentoParcelaContrato->addFields([$button_adicionar_contrato_pagamento_parcela_contrato]);
        $row11->layout = [' col-sm-12'];

        $row12 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new THidden('contrato_pagamento_parcela_contrato__row__id')]);
        $this->contrato_pagamento_parcela_contrato_criteria = new TCriteria();

        $this->contrato_pagamento_parcela_contrato_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->contrato_pagamento_parcela_contrato_list->generateHiddenFields();
        $this->contrato_pagamento_parcela_contrato_list->setId('contrato_pagamento_parcela_contrato_list');

        $this->contrato_pagamento_parcela_contrato_list->style = 'width:100%';
        $this->contrato_pagamento_parcela_contrato_list->class .= ' table-bordered';

        $column_contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_nome = new TDataGridColumn('contrato_opcao_pagamento->nome', "Opção de pagamento", 'left');
        $column_contrato_pagamento_parcela_contrato_valor_transformed = new TDataGridColumn('valor', "Valor", 'left');
        $column_contrato_pagamento_parcela_contrato_data_pagamento_transformed = new TDataGridColumn('data_pagamento', "Data", 'left');
        $column_contrato_pagamento_parcela_contrato_contrato_evento_nome = new TDataGridColumn('contrato_evento->nome', "Evento", 'left');
        $column_contrato_pagamento_parcela_contrato_complemento_indexador = new TDataGridColumn('complemento_indexador', "Número do indexador", 'left');
        $column_contrato_pagamento_parcela_contrato_unidade_indexador_nome = new TDataGridColumn('unidade_indexador->nome', "Unidade do indexador", 'left');
        $column_contrato_pagamento_parcela_contrato_contrato_indexador_nome = new TDataGridColumn('contrato_indexador->nome', "Indexador", 'left');
        $column_contrato_pagamento_parcela_contrato_numero_parcelas = new TDataGridColumn('numero_parcelas', "Parcelas", 'left');

        $column_contrato_pagamento_parcela_contrato__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_contrato_pagamento_parcela_contrato__row__data->setVisibility(false);

        $action_onEditDetailContratoPagamentoParcela = new TDataGridAction(array('GerarContratoForm', 'onEditDetailContratoPagamentoParcela'));
        $action_onEditDetailContratoPagamentoParcela->setUseButton(false);
        $action_onEditDetailContratoPagamentoParcela->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailContratoPagamentoParcela->setLabel("Editar");
        $action_onEditDetailContratoPagamentoParcela->setImage('far:edit #478fca');
        $action_onEditDetailContratoPagamentoParcela->setFields(['__row__id', '__row__data']);

        $this->contrato_pagamento_parcela_contrato_list->addAction($action_onEditDetailContratoPagamentoParcela);
        $action_onDeleteDetailContratoPagamentoParcela = new TDataGridAction(array('GerarContratoForm', 'onDeleteDetailContratoPagamentoParcela'));
        $action_onDeleteDetailContratoPagamentoParcela->setUseButton(false);
        $action_onDeleteDetailContratoPagamentoParcela->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailContratoPagamentoParcela->setLabel("Excluir");
        $action_onDeleteDetailContratoPagamentoParcela->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailContratoPagamentoParcela->setFields(['__row__id', '__row__data']);

        $this->contrato_pagamento_parcela_contrato_list->addAction($action_onDeleteDetailContratoPagamentoParcela);

        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_nome);
        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_valor_transformed);
        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_data_pagamento_transformed);
        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_contrato_evento_nome);
        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_complemento_indexador);
        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_unidade_indexador_nome);
        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_contrato_indexador_nome);
        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato_numero_parcelas);

        $this->contrato_pagamento_parcela_contrato_list->addColumn($column_contrato_pagamento_parcela_contrato__row__data);

        $this->contrato_pagamento_parcela_contrato_list->createModel();
        $tableResponsiveDiv = new TElement('div');
        $tableResponsiveDiv->class = 'table-responsive';
        $tableResponsiveDiv->add($this->contrato_pagamento_parcela_contrato_list);
        $this->detailFormContratoPagamentoParcelaContrato->addContent([$tableResponsiveDiv]);

        $column_contrato_pagamento_parcela_contrato_valor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_contrato_pagamento_parcela_contrato_data_pagamento_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });        $row13 = $this->form->addFields([$this->detailFormContratoPagamentoParcelaContrato]);
        $row13->layout = [' col-sm-12  bcontainer_itens'];

        $row14 = $this->form->addFields([new TLabel("Tipo:", null, '14px', null)],[$tipo_modelo_documento_id]);
        $row15 = $this->form->addFields([new TLabel("Documentos:", null, '14px', null)],[$modelo_documento_id]);

        $row15->class = 'modelo_documento';

        // create the form actions
        $btn_onanterior = $this->form->addAction("Anterior", new TAction([$this, 'onAnterior']), 'fas:arrow-alt-circle-left #FFFFFF');
        $this->btn_onanterior = $btn_onanterior;
        $btn_onanterior->addStyleClass('btn-primary'); 

        $btnProximo = $this->form->addAction("Próximo", new TAction([$this, 'onProximo']), 'fas:arrow-alt-circle-right #ffffff');
        $this->btnProximo = $btnProximo;
        $btnProximo->addStyleClass('btn-primary'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Contratos","Gerar contrato"]));
        }
        $container->add($this->form);

        BootstrapFormBuilder::hideField(self::$formName, 'objeto');
        BootstrapFormBuilder::hideField(self::$formName, 'tipoprocesso');
        BootstrapFormBuilder::hideField(self::$formName, 'envolvimento');
        BootstrapFormBuilder::hideField(self::$formName, 'area');
        BootstrapFormBuilder::hideField(self::$formName, 'assunto');

        TScript::create('$(".bcontainer_itens").hide();');

        BootstrapFormBuilder::hideField(self::$formName, 'tipo_modelo_documento_id');
        TScript::create('$(".modelo_documento").hide();');

        parent::add($container);

    }

    public static function onSelectOpcaoPagamento($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            //Recebe o valor selecionado na opção de pagamento
            $opcao = $param['contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id'];

            //Se o valor existir
            if($opcao){

                $opcao = ContratoPagamentoOpcao::find((int) $opcao);

                $object = new stdClass;

                if($opcao->recebe_valor == "N"){
                    $object->contrato_pagamento_parcela_contrato_valor = null;
                    TScript::create("$('label:contains(\"Valor:\")').html('<span style=\"color:#333;\">Valor:</span>')");
                    TNumeric::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_valor');
                }else{
                    TScript::create("$('label:contains(\"Valor:\")').html('<span style=\"color:#f00;\">Valor:</span>')");
                    TNumeric::enableField(self::$formName, 'contrato_pagamento_parcela_contrato_valor');
                }
                if($opcao->recebe_data == "N"){
                    $object->contrato_pagamento_parcela_contrato_data_pagamento = null;
                    TScript::create("$('label:contains(\"Data:\")').html('<span style=\"color:#333;\">Data:</span>')");
                    TDate::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_data_pagamento');
                }else{
                    TScript::create("$('label:contains(\"Data:\")').html('<span style=\"color:#f00;\">Data:</span>')");
                    TDate::enableField(self::$formName, 'contrato_pagamento_parcela_contrato_data_pagamento');
                }
                if($opcao->recebe_evento == "N"){
                    $object->contrato_pagamento_parcela_contrato_contrato_evento_id = null;
                    TScript::create("$('label:contains(\"Evento:\")').html('<span style=\"color:#333;\">Evento:</span>')");
                    TDBCombo::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_contrato_evento_id');
                }else{
                    TScript::create("$('label:contains(\"Evento:\")').html('<span style=\"color:#f00;\">Evento:</span>')");
                    TDBCombo::enableField(self::$formName, 'contrato_pagamento_parcela_contrato_contrato_evento_id');
                }
                if($opcao->recebe_indexador == "N"){
                    $object->contrato_pagamento_parcela_contrato_contrato_indexador_id = null;
                    TScript::create("$('label:contains(\"Indexador:\")').html('<span style=\"color:#333;\">Indexador:</span>')");
                    TDBCombo::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_contrato_indexador_id');
                    TDBCombo::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_unidade_indexador_id');

                    TScript::create("$('label:contains(\"Complemento do indexador:\")').html('<span style=\"color:#333;\">Complemento do indexador:</span>')");
                    TEntry::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_complemento_indexador');

                }else{
                    TScript::create("$('label:contains(\"Indexador:\")').html('<span style=\"color:#f00;\">Indexador:</span>')");
                    TDBCombo::enableField(self::$formName, 'contrato_pagamento_parcela_contrato_contrato_indexador_id');
                    TDBCombo::enableField(self::$formName, 'contrato_pagamento_parcela_contrato_unidade_indexador_id');

                    TScript::create("$('label:contains(\"Complemento do indexador:\")').html('<span style=\"color:#f00;\">Complemento do indexador:</span>')");
                    TEntry::enableField(self::$formName, 'contrato_pagamento_parcela_contrato_complemento_indexador');

                }

                TForm::sendData(self::$formName,$object);
            }

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeTipo($param = null) 
    {
        try 
        {
            TTransaction::open('escritorio');
            $items = array();
            if($param['tipo_modelo_documento_id']){
                foreach(ModeloDocumento::where('tipo_modelo_documento_id','=', $param['tipo_modelo_documento_id'])->orderby('nome')->load() as $value){
                    if(ModeloDocAplicacao::where('modelo_documento_id','=',$value->id)->where('tipo_aplicacao_id','=',ModeloDocTipoAplicacao::GERAR)->first())
                    $items[$value->id] = $value->nome;
                }
            }
            TTransaction::close();

            TSelect::reload(self::$formName, 'modelo_documento_id', $items, true);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onAddDetailContratoPagamentoParcelaContrato($param = null) 
    {
        try
        {
            $data = $this->form->getData();

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"Contrato opcao pagamento id", 'name'=>"contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Número de Parcelas", 'name'=>"contrato_pagamento_parcela_contrato_numero_parcelas", 'class'=>'TRequiredValidator', 'value'=>[]];
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

            $__row__id = !empty($data->contrato_pagamento_parcela_contrato__row__id) ? $data->contrato_pagamento_parcela_contrato__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new ContratoPagamentoParcela();
            $grid_data->__row__id = $__row__id;
            $grid_data->contrato_opcao_pagamento_id = $data->contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id;
            $grid_data->id = $data->contrato_pagamento_parcela_contrato_id;
            $grid_data->valor = $data->contrato_pagamento_parcela_contrato_valor;
            $grid_data->data_pagamento = $data->contrato_pagamento_parcela_contrato_data_pagamento;
            $grid_data->contrato_evento_id = $data->contrato_pagamento_parcela_contrato_contrato_evento_id;
            $grid_data->complemento_indexador = $data->contrato_pagamento_parcela_contrato_complemento_indexador;
            $grid_data->unidade_indexador_id = $data->contrato_pagamento_parcela_contrato_unidade_indexador_id;
            $grid_data->contrato_indexador_id = $data->contrato_pagamento_parcela_contrato_contrato_indexador_id;
            $grid_data->numero_parcelas = $data->contrato_pagamento_parcela_contrato_numero_parcelas;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['contrato_opcao_pagamento_id'] =  $param['contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id'] ?? null;
            $__row__data['__display__']['id'] =  $param['contrato_pagamento_parcela_contrato_id'] ?? null;
            $__row__data['__display__']['valor'] =  $param['contrato_pagamento_parcela_contrato_valor'] ?? null;
            $__row__data['__display__']['data_pagamento'] =  $param['contrato_pagamento_parcela_contrato_data_pagamento'] ?? null;
            $__row__data['__display__']['contrato_evento_id'] =  $param['contrato_pagamento_parcela_contrato_contrato_evento_id'] ?? null;
            $__row__data['__display__']['complemento_indexador'] =  $param['contrato_pagamento_parcela_contrato_complemento_indexador'] ?? null;
            $__row__data['__display__']['unidade_indexador_id'] =  $param['contrato_pagamento_parcela_contrato_unidade_indexador_id'] ?? null;
            $__row__data['__display__']['contrato_indexador_id'] =  $param['contrato_pagamento_parcela_contrato_contrato_indexador_id'] ?? null;
            $__row__data['__display__']['numero_parcelas'] =  $param['contrato_pagamento_parcela_contrato_numero_parcelas'] ?? null;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->contrato_pagamento_parcela_contrato_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('contrato_pagamento_parcela_contrato_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id = '';
            $data->contrato_pagamento_parcela_contrato_id = '';
            $data->contrato_pagamento_parcela_contrato_valor = '';
            $data->contrato_pagamento_parcela_contrato_data_pagamento = '';
            $data->contrato_pagamento_parcela_contrato_contrato_evento_id = '';
            $data->contrato_pagamento_parcela_contrato_complemento_indexador = '';
            $data->contrato_pagamento_parcela_contrato_unidade_indexador_id = '';
            $data->contrato_pagamento_parcela_contrato_contrato_indexador_id = '';
            $data->contrato_pagamento_parcela_contrato_numero_parcelas = '1';
            $data->contrato_pagamento_parcela_contrato__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#66e1956754b6f');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

            TScript::create('$(".bcontainer_itens").show();');
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public static function onEditDetailContratoPagamentoParcela($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id = $__row__data->__display__->contrato_opcao_pagamento_id ?? null;
            $data->contrato_pagamento_parcela_contrato_id = $__row__data->__display__->id ?? null;
            $data->contrato_pagamento_parcela_contrato_valor = $__row__data->__display__->valor ?? null;
            $data->contrato_pagamento_parcela_contrato_data_pagamento = $__row__data->__display__->data_pagamento ?? null;
            $data->contrato_pagamento_parcela_contrato_contrato_evento_id = $__row__data->__display__->contrato_evento_id ?? null;
            $data->contrato_pagamento_parcela_contrato_complemento_indexador = $__row__data->__display__->complemento_indexador ?? null;
            $data->contrato_pagamento_parcela_contrato_unidade_indexador_id = $__row__data->__display__->unidade_indexador_id ?? null;
            $data->contrato_pagamento_parcela_contrato_contrato_indexador_id = $__row__data->__display__->contrato_indexador_id ?? null;
            $data->contrato_pagamento_parcela_contrato_numero_parcelas = $__row__data->__display__->numero_parcelas ?? null;
            $data->contrato_pagamento_parcela_contrato__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#66e1956754b6f');
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

    public static function onDeleteDetailContratoPagamentoParcela($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id = '';
            $data->contrato_pagamento_parcela_contrato_id = '';
            $data->contrato_pagamento_parcela_contrato_valor = '';
            $data->contrato_pagamento_parcela_contrato_data_pagamento = '';
            $data->contrato_pagamento_parcela_contrato_contrato_evento_id = '';
            $data->contrato_pagamento_parcela_contrato_complemento_indexador = '';
            $data->contrato_pagamento_parcela_contrato_unidade_indexador_id = '';
            $data->contrato_pagamento_parcela_contrato_contrato_indexador_id = '';
            $data->contrato_pagamento_parcela_contrato_numero_parcelas = '';
            $data->contrato_pagamento_parcela_contrato__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('contrato_pagamento_parcela_contrato_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#66e1956754b6f');
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

    public static function onAnterior($param = null) 
    {
        try 
        {

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());  
            TApplication::loadPage('GerarContratoForm','onShow');
        }
    }

    public static function onProximo($param = null) 
    {
       try 
        {
            if ($param['numero'] && (!empty($param['cliente_id']) || !empty($param['clientes_ids']))) {

                if($param['objeto']){

                    if(isset($param['contrato_pagamento_parcela_contrato_list_contrato_opcao_pagamento->nome']) 
                        && count($param['contrato_pagamento_parcela_contrato_list_contrato_opcao_pagamento->nome'])>0){
                        BootstrapFormBuilder::hideField(self::$formName, 'numero');
                        BootstrapFormBuilder::hideField(self::$formName, 'cliente_id');

                        BootstrapFormBuilder::hideField(self::$formName, 'objeto');
                        BootstrapFormBuilder::hideField(self::$formName, 'tipoprocesso');
                        BootstrapFormBuilder::hideField(self::$formName, 'envolvimento');
                        BootstrapFormBuilder::hideField(self::$formName, 'area');
                        BootstrapFormBuilder::hideField(self::$formName, 'assunto');

                        TScript::create('$(".bcontainer_itens").hide();');

                        BootstrapFormBuilder::showField(self::$formName, 'tipo_modelo_documento_id');
                        TScript::create('$(".modelo_documento").show();');

                        if($param['tipo_modelo_documento_id'] && $param['modelo_documento_id']){
                            TTransaction::open('escritorio');
                            //VERIFICAR DADOS DOS CLIENTES

                          // Aceita: TDBMultiSearch (cliente_id multi) OU o campo único
                            $idsClientes = [];
                            if (!empty($param['cliente_id'])) {
                                $idsClientes = self::normalizeClientesIds($param['cliente_id']);
                            } elseif (!empty($param['clientes_ids'])) {
                                // caso mantenha um campo separado
                                $idsClientes = self::normalizeClientesIds($param['clientes_ids']);
                            }
                            if (empty($idsClientes)) {
                                throw new Exception('Informe ao menos um cliente.');
                            }

                            $erro = [];

                            foreach ($idsClientes as $cid) {
                                $cliente = Pessoa::find((int)$cid);
                                if (!$cliente) {
                                    $erro[] = "Cliente ID $cid não encontrado.";
                                    continue;
                                }

                                foreach ($param['modelo_documento_id'] as $value_modelo_documento) {
                                    $modelo_documento = ModeloDocumento::find((int)$value_modelo_documento);
                                    if (!$modelo_documento) {
                                        $erro[] = "Modelo de documento ID $value_modelo_documento não encontrado.";
                                        continue;
                                    }

                                    $dadosCliente = ModeloDocumentoService::onVerificarDadosCliente(
                                        $cliente,
                                        $modelo_documento,
                                        $param['objeto'],
                                        count($param['contrato_pagamento_parcela_contrato_list___row__data'])
                                    );

                                    if ($dadosCliente){
                                        $erro[] = "Não é possível gerar documento <b>{$modelo_documento->nome}</b> para <b>{$dadosCliente['cliente']}</b>. 
                                                Cadastre: {$dadosCliente['dadosFaltantes']}.";
                                    }
                                }
                            }

                            if(count($erro)>0){
                                throw new Exception(implode("<br/>", $erro));
                            }

                            $i=0;
                            while($i<1){
                                if(!isset($varNumero)){
                                    $c = '/'.date('Y');
                                    $varNumero = rtrim($param['numero'], $c);
                                }else{
                                    $varNumero++;
                                }
                                $varNumero = str_pad($varNumero, 7, '0', STR_PAD_LEFT);
                                $param['numero'] = $varNumero.'/'.date('Y');

                                $quant = Contrato::where('numero', '=', $param['numero'])->count();
                                if($quant<1){
                                   $i++; 
                                }
                            }

                            $contrato = new Contrato();
                            $contrato->numero = $param['numero'];
                            $contrato->escritorio_id = 1;
                            $contrato->contrato_status_id = 1;

                            $contrato->objeto       = $param['objeto'] ?? null;
                            $contrato->tipo_processo_id = $param['tipoprocesso'] ?? null;
                            $contrato->envolvimento_id = $param['envolvimento'] ?? null;
                            $contrato->area_id         = $param['area'] ?? null;
                            $contrato->assunto_id      = $param['assunto'] ?? null;

                            $contrato->store();

                             // ----------------------------------------------------
                            // Salvar repasse sempre com Curciol + advogado (se for)
                            // ----------------------------------------------------
                            $user_id = TSession::getValue('userid');
                            $usuario = SystemUsers::find($user_id);

                            // Sempre insere Curciol
                            $repasseCurciol = new ContratoRepasse();
                            $repasseCurciol->contrato_id = $contrato->id;
                            $repasseCurciol->pessoa_id   = 1882; // Curciol Sociedade de Advogados
                            $repasseCurciol->percentual  = null;
                            $repasseCurciol->store();

                            // Se o usuário logado for advogado, insere também ele
                            if ($usuario) {
                                $pessoa = Pessoa::where('system_users_id', '=', $usuario->id)->first();
                                if ($pessoa && $pessoa->tipo_profissional_id == 1) {
                                    $repasseAdv = new ContratoRepasse();
                                    $repasseAdv->contrato_id = $contrato->id;
                                    $repasseAdv->pessoa_id   = $pessoa->id;
                                    $repasseAdv->percentual  = null;
                                    $repasseAdv->store();
                                }
                            }

                            TTransaction::close();
                            TTransaction::open('escritorio');

                            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            $detail_items = $param['contrato_pagamento_parcela_contrato_list___row__data'];
                            foreach ($detail_items as $key => $item)
                            {   

                                $item = unserialize(base64_decode($item));
                                if(is_object($item)){
                                    $item = (array) $item;
                                }

                                $contratoPagamento = new ContratoPagamentoParcela();
                                $contratoPagamento->fromArray($item);

                                $contratoPagamento->contrato_id = (int)$contrato->id;
                                $contratoPagamento->contrato_opcao_pagamento_id = (int)$item['contrato_opcao_pagamento_id'] ?: null;
                                $contratoPagamento->valor = (float) $item['valor'];
                                $contratoPagamento->data_pagamento = $item['data_pagamento'];
                                $contratoPagamento->contrato_evento_id = (int)$item['contrato_evento_id'] ?: null;
                                $contratoPagamento->unidade_indexador_id = (int)$item['unidade_indexador_id'] ?: null;
                                $contratoPagamento->complemento_indexador = $item['complemento_indexador'] ?: null;
                                $contratoPagamento->contrato_indexador_id = (int)$item['contrato_indexador_id'] ?: null;
                                $contratoPagamento->numero_parcelas = (int)$item['numero_parcelas'] ?: 1;

                                $opcao = ContratoPagamentoOpcao::find((int) $contratoPagamento->contrato_opcao_pagamento_id);

                                if($contratoPagamento->numero_parcelas == 1){
                                    $descricao = $opcao->descricao1;
                                }else{
                                    $descricao = $opcao->descricaon;
                                }

                                $valor = number_format((float) $contratoPagamento->valor, 2, ',', '');
                                $extenso = new Extenso();

                                $indexador = (ContratoPagamentoIndexador::find((int) $contratoPagamento->contrato_indexador_id))->nome ?? "";
                                $evento = (ContratoPagamentoEvento::find((int) $contratoPagamento->contrato_evento_id))->nome ?? "";

                                $data_pagameno = new DateTime($contratoPagamento->data_pagamento);
                                @$data_extenso = $data_pagameno->format('d') . ' de ' . DateService::getMonthName($contratoPagamento->data_pagamento) . ' de ' . $data_pagameno->format('Y');

                                $tags = [
                                  '[opcao_pagamento]' => $opcao->nome,
                                  '[valor]' => $valor,  
                                  '[valor_extenso]' => $extenso->extenso((float) $valor, Extenso::MOEDA) ?? "",  
                                  '[data]' => $data_pagameno->format('d/m/Y'),
                                  '[data_extenso]' => $data_extenso,
                                  '[numero_parcelas]' => $contratoPagamento->numero_parcelas,
                                  '[numero_parcelas_extenso]' => $extenso->extenso((float) $contratoPagamento->numero_parcelas, Extenso::NUMERO_FEMININO) ?? "",
                                  '[numero_indexador]' => $contratoPagamento->complemento_indexador,
                                  '[numero_indexador_extenso]' => $extenso->extenso((float) $contratoPagamento->complemento_indexador, Extenso::NUMERO_MASCULINO ) ?? "",
                                  '[unidade_indexador]' => (UnidadeIndexador::find($contratoPagamento->unidade_indexador_id))->simbolo ?? "",
                                  '[unidade_indexador_extenso]' => (UnidadeIndexador::find($contratoPagamento->unidade_indexador_id))->extenso ?? "",
                                  '[indexador]' => $indexador,
                                  '[evento]' => $evento
                                ];

                                foreach($tags as $variavel=>$valor){
                                    $descricao = str_replace($variavel, $valor, $descricao);
                                }

                                $contratoPagamento->descritivo = $descricao;

                                $contratoPagamento->store();
                            }

                            TTransaction::close();
                            TTransaction::open('escritorio');

                            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                            $percentualBase = (int) floor(100 / count($idsClientes));
                            $resto = 100 - ($percentualBase * count($idsClientes));
                            $k = 0;

                            foreach ($idsClientes as $cid) {
                                $k++;
                                $perc = $percentualBase + ($k === count($idsClientes) ? $resto : 0); // soma o resto no último

                                $clienteContrato = new ContratoPessoa();
                                $clienteContrato->contrato_id = (int)$contrato->id;
                                $clienteContrato->cliente_id  = (int)$cid;     // <- NUNCA fica NULL aqui
                                $clienteContrato->percentual  = $perc;         // ou null, caso sua coluna aceite
                                $clienteContrato->store();

                                // Vincula representante principal, se houver
                                $representante = PessoaRepresentantesLegais::where('pessoa_juridica_id', '=', (int)$cid)
                                                                        ->where('principal', '=', 'S')
                                                                        ->first();
                                if ($representante) {
                                    $represContrato = new ContratoRepresentante();
                                    $represContrato->contrato_id = (int)$contrato->id;
                                    $represContrato->representante_id = (int)$representante->representante_id;
                                    $represContrato->store();
                                }
                            }

                            TTransaction::close();
                            TTransaction::open('escritorio');

                            foreach($param['modelo_documento_id'] as $value_modelo_documento){

                                $modelo_documento = ModeloDocumento::find((int)$value_modelo_documento);

                                 $serviceParam = array();
                                $serviceParam['objeto']        = $param['objeto'] ?? null;
                                $serviceParam['tipoprocesso']  = $param['tipoprocesso'] ?? null;
                                $serviceParam['envolvimento']  = $param['envolvimento'] ?? null;
                                $serviceParam['area']          = $param['area'] ?? null;
                                $serviceParam['assunto']       = $param['assunto'] ?? null;
                                $serviceParam['modelo_documento_id'] = $modelo_documento->id;

                                if (count($idsClientes) > 1) {
                                    $serviceParam['clientes_ids'] = $idsClientes;
                                } else {
                                    $serviceParam['cliente_id'] = $idsClientes[0];
                                }
                                $serviceParam['contrato_id']   = $contrato->id;

                                $retorno = ModeloDocumentoService::preencherDocumento($serviceParam);

                                $contrato_documento = new ContratoDocumento();
                                $contrato_documento->contrato_id = $contrato->id;
                                $contrato_documento->modelo_documento_id = $modelo_documento->id;
                                $contrato_documento->filename = $retorno['novo_nome_arquivo'];
                                $contrato_documento->dt_preenchimento = date('Y-m-d H:i:s');
                                $contrato_documento->autenticador =
                                isset($retorno['autenticador'])
                                    ? $retorno['autenticador']
                                    : null;
                                $contrato_documento->store();
                            }

                            TTransaction::close();

                            TApplication::loadPage('GerarContratoForm','onShow');
                            sleep(0.2);
                            TApplication::loadPage('ContratoFormView','onShow',['key'=>$contrato->id]);
                        }else{
                            BootstrapFormBuilder::hideField(self::$formName, 'numero');
                            BootstrapFormBuilder::hideField(self::$formName, 'cliente_id');
                             BootstrapFormBuilder::hideField(self::$formName, 'objeto');
                            BootstrapFormBuilder::hideField(self::$formName, 'tipoprocesso');
                            BootstrapFormBuilder::hideField(self::$formName, 'envolvimento');
                            BootstrapFormBuilder::hideField(self::$formName, 'area');
                            BootstrapFormBuilder::hideField(self::$formName, 'assunto');

                            TScript::create('$(".bcontainer_itens").hide();');

                            BootstrapFormBuilder::showField(self::$formName, 'tipo_modelo_documento_id');
                            TScript::create('$(".modelo_documento").show();');
                        }

                    }else{
                        BootstrapFormBuilder::hideField(self::$formName, 'numero');
                        BootstrapFormBuilder::hideField(self::$formName, 'cliente_id');
                        BootstrapFormBuilder::hideField(self::$formName, 'objeto');
                        BootstrapFormBuilder::hideField(self::$formName, 'tipoprocesso');
                        BootstrapFormBuilder::hideField(self::$formName, 'envolvimento');
                        BootstrapFormBuilder::hideField(self::$formName, 'area');
                        BootstrapFormBuilder::hideField(self::$formName, 'assunto');

                        TScript::create('$(".bcontainer_itens").show();');

                        BootstrapFormBuilder::hideField(self::$formName, 'tipo_modelo_documento_id');
                        TScript::create('$(".modelo_documento").hide();');
                    }
                }else{
                    BootstrapFormBuilder::hideField(self::$formName, 'numero');
                    BootstrapFormBuilder::hideField(self::$formName, 'cliente_id');
                    BootstrapFormBuilder::showField(self::$formName, 'objeto');
                    BootstrapFormBuilder::showField(self::$formName, 'tipoprocesso');
                    BootstrapFormBuilder::showField(self::$formName, 'envolvimento');
                    BootstrapFormBuilder::showField(self::$formName, 'area');
                    BootstrapFormBuilder::showField(self::$formName, 'assunto');

                    TScript::create('$(".bcontainer_itens").hide();');

                    BootstrapFormBuilder::hideField(self::$formName, 'tipo_modelo_documento_id');
                    TScript::create('$(".modelo_documento").hide();');
                }
            }else{
                BootstrapFormBuilder::showField(self::$formName, 'numero');
                BootstrapFormBuilder::showField(self::$formName, 'cliente_id');
                BootstrapFormBuilder::hideField(self::$formName, 'objeto');
                BootstrapFormBuilder::hideField(self::$formName, 'tipoprocesso');
                BootstrapFormBuilder::hideField(self::$formName, 'envolvimento');
                BootstrapFormBuilder::hideField(self::$formName, 'area');
                BootstrapFormBuilder::hideField(self::$formName, 'assunto');

                TScript::create('$(".bcontainer_itens").hide();');

                BootstrapFormBuilder::hideField(self::$formName, 'tipo_modelo_documento_id');
                TScript::create('$(".modelo_documento").hide();');
            }
            TTransaction::close();

        }
        catch (Exception $e)
        {
            // Obter informações sobre a linha do erro e a variável problemática
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Supondo que você tenha uma variável específica que causou o erro
            // Aqui você pode adicionar lógica para identificar a variável problemática
            // Exemplo fictício:
            if (isset($var)) {
                $varMessage = "Variável problemática: " . json_encode($var);
            } else {
                $varMessage = "Nenhuma variável específica identificada.";
            }

            // Criar uma mensagem mais detalhada
            $detailedMessage = "Erro na linha $line do arquivo $file: $message. $varMessage";

            // Exibir a mensagem de erro
            new TMessage('error', $e->getMessage());
            TApplication::loadPage('GerarContratoForm','onShow');
        }
    }

    public function onShow($param = null)
    {               

        TTransaction::open(self::$database);
        $object = new stdClass();

        $quant = Contrato::where('numero', 'like', '0000001/'.date('Y'))->count();
        //$object->numero = $quant;

        if($quant<1){
            $object->numero = '0000001/'.date('Y');

        }else{
            $i=0;
            $objeto = Contrato::where('numero', 'like', '%'.date('Y'))->where('numero',"not like",'0000000'.date('Y'))
                            ->load();

            $c = '/'.date('Y');
            $varNumero = rtrim(end($objeto)->numero, $c);

            while($i<1){

                $varNumero++;
                $varNumero = str_pad($varNumero, 7, '0', STR_PAD_LEFT);
                $object->numero = $varNumero.'/'.date('Y');

                $quant = Contrato::where('numero', '=', $object->numero)->count();
                if($quant<1){
                   $i++; 
                }
            }

        }
        TForm::sendData(self::$formName, $object);
        TTransaction::close();

    } 

    public static function onGerar($contrato,$cliente,$objeto,$modelo_documento){
        try{

            $cliente_endereco = PessoaEndereco::where('pessoa_id','=',(int) $cliente->id)->where('principal','=','S')->first();

            if($modelo_documento->objeto === "S"){
                if(empty($objeto)){
                    throw new Exception("Informe o objeto para gerar um documento.");
                }
            }

            // Nome do arquivo tipo
            $nome_arquivo = $modelo_documento->filename;

            @$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($nome_arquivo);

            // Substituir palavras usando str_replace
            $substituicoes = [
                "nome_cliente" => $cliente->nome,
                "nome_profissional" => $profissional->nome ?? null,
                "data_nascimento" => $cliente->dt_nascimento_abertura ?? null,
                "nacionalidade" => $cliente->nacionalidade->nome ?? null,
                "estado_civil" => $cliente->estado_civil->nome ?? null,
                "profissao" => $cliente->profissao ?? null,
                "RG" => $cliente->rg_ie ?? null,
                "orgao_emissor" => $cliente->orgao_emissor ?? null,
                "cpf" => $cliente->cpf_cnpj ?? null,
                "telefone" => $cliente->telefone ?? null,
                "unidade_trabalho" => $cliente->unidade ?? null,
                "email" => $cliente->email ?? null,
                "autenticador" => $autenticador ?? null,
                "informacoes_documento" => $autenticador ?? null,
                "objeto" => $objeto ?? null
            ];

            if($cliente_endereco){
                $substituicoes["rua"]    = $cliente_endereco->rua ?? null;
                $substituicoes["numero"] = ", ".$cliente_endereco->numero ?? null;
                $substituicoes["bairro"] = $cliente_endereco->bairro ?? null;
                $substituicoes["cidade"] = $cliente_endereco->cidade->nome ?? null;
                $substituicoes["uf"]     = "/".$cliente_endereco->cidade->estado->sigla ?? null;
                $substituicoes["cep"]    = $cliente_endereco->cep ?? null;

                if($cliente_endereco->complemento){
                    $substituicoes["complemento"] = " - ".$cliente_endereco->complemento;
                }else{
                    $substituicoes["complemento"] = null;
                }
            }

            $pagamentosContrato = ContratoPagamentoParcela::where('contrato_id','=',$contrato->id)->orderby('contrato_opcao_pagamento_id')->load();
            if($pagamentosContrato){

                $tags = '';
                for($i=0;$i<count($pagamentosContrato);$i++){
                    $tags .= "\${informacoes_pagamento$i}";
                }
                $templateProcessor->setValue('informacoes_pagamento',$tags);

                $clausula = $modeloDocumento->clausula_pagamento ?? (ContratoConfig::find(1))->clausula_pagamento;
                $subClausula = 1;

                foreach ($pagamentosContrato as $i=>$pagamentoContrato) {

                    $textRun = new \PhpOffice\PhpWord\Element\TextRun();

                    if ($subClausula > 1) {
                        $textRun->addTextBreak();
                        $textRun->addTextBreak();
                    }

                    $textRun->addText("$clausula.$subClausula ", ['bold' => true, 'name' => 'Calibri Light', 'size' => 10]);
                    $textRun->addText($pagamentoContrato->descritivo, ['bold' => false, 'name' => 'Calibri Light', 'size' => 10]);

                    $subClausula++;
                    $templateProcessor->setComplexValue("informacoes_pagamento$i", $textRun);
                }
            }

            $templateProcessor->setValues($substituicoes);

            $destino = "files/documents/$modelo_documento->id/$cliente->nome/";
            $nome_arquivo = str_replace(' ','_',$modelo_documento->nome."_".date("Y-m-d"));

            // Checking whether file exists or not
            if (!file_exists($destino))
                mkdir($destino, 0777, true);

            // Enviar conteúdo para novo arquivo
            @$templateProcessor->saveAs($destino.$nome_arquivo.".docx");

            //Autenticador
            $i=0;
            while($i<1){
                $autenticador = base64_encode(rand() . '-' . TSession::getValue('userid') .'-'. TSession::getValue('unitid'));
                $verifAutenticadoDoc = Documento::where('autenticador','=',$autenticador)->count();
                $verifAutenticadoContDoc = ContratoDocumento::where('autenticador','=',$autenticador)->count();
                if($verifAutenticadoDoc==0 && $verifAutenticadoContDoc==0){
                    $i++;
                }
            }

            $docContrato = new ContratoDocumento();
            $docContrato->contrato_id = $contrato->id;
            $docContrato->modelo_documento_id = $modelo_documento->id;
            $docContrato->filename = $destino.$nome_arquivo;
            $docContrato->dt_preenchimento = date('Y-m-d');
            $docContrato->autenticador = $autenticador;
            $docContrato->store();

        }catch (Exception $e){
            TApplication::loadPage('GerarContratoForm','onShow');
        }
    }

    private static function normalizeClientesIds($raw): array {
        // "1,2,3"
        if (is_string($raw)) {
            return array_values(
                array_filter(array_map('intval', array_map('trim', explode(',', $raw))))
            );
        }
        // ["1","2","3"] (sequencial)
        if (is_array($raw)) {
            $isAssoc = array_keys($raw) !== range(0, count($raw)-1);
            if ($isAssoc) {
                // {"1":"João","5":"Maria"}
                return array_values(
                    array_filter(array_map('intval', array_keys($raw)))
                );
            }
            // Pode ser [{id:1,name:"João"}, ...]
            if (!empty($raw) && (is_array($raw[0]) || is_object($raw[0]))) {
                $out = [];
                foreach ($raw as $item) {
                    if (is_array($item) && isset($item['id']))       $out[] = (int)$item['id'];
                    elseif (is_object($item) && isset($item->id))    $out[] = (int)$item->id;
                    else                                             $out[] = (int)$item; // fallback
                }
                return array_values(array_filter($out));
            }
            // sequencial simples
            return array_values(array_filter(array_map('intval', $raw)));
        }
        // single int
        if (is_numeric($raw)) return [ (int)$raw ];

        return [];
    }

}

