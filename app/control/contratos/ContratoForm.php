<?php

use phputil\extenso\Extenso;

class ContratoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Contrato';
    private static $primaryKey = 'id';
    private static $formName = 'form_ContratoForm';

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
        $this->form->setFormTitle("Cadastro de contrato");

        $criteria_escritorio_id = new TCriteria();
        $criteria_tipo_processo_id = new TCriteria();
        $criteria_area_id = new TCriteria();
        $criteria_contrato_status_id = new TCriteria();
        $criteria_contrato_pessoa_contrato_cliente_id = new TCriteria();
        $criteria_contrato_representante_contrato_representante_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_contrato_evento_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_unidade_indexador_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_contrato_indexador_id = new TCriteria();
        $criteria_contrato_repasse_contrato_pessoa_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_contrato_pessoa_contrato_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::REPRESENTANTE_LEGAL;
        $criteria_contrato_representante_contrato_representante_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = [Grupo::PARCEIRO, Grupo::PROFISSIONAL];
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_contrato_repasse_contrato_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id in ($filterVar))")); 

        $id = new TEntry('id');
        $tela = new THidden('tela');
        $atendimento_id = new THidden('atendimento_id');
        $numero = new THidden('numero');
        $escritorio_id = new TDBCombo('escritorio_id', 'escritorio', 'Escritorio', 'id', '{nome}','nome asc' , $criteria_escritorio_id );
        $tipo_processo_id = new TDBCombo('tipo_processo_id', 'escritorio', 'TipoProcesso', 'id', '{nome}','nome asc' , $criteria_tipo_processo_id );
        $envolvimento_id = new TCombo('envolvimento_id');
        $area_id = new TDBCombo('area_id', 'escritorio', 'Area', 'id', '{nome}','nome asc' , $criteria_area_id );
        $assunto_id = new TCombo('assunto_id');
        $contrato_status_id = new TDBCombo('contrato_status_id', 'escritorio', 'ContratoStatus', 'id', '{nome}','nome asc' , $criteria_contrato_status_id );
        $objeto = new TText('objeto');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');
        $contrato_pessoa_contrato_id = new THidden('contrato_pessoa_contrato_id[]');
        $contrato_pessoa_contrato___row__id = new THidden('contrato_pessoa_contrato___row__id[]');
        $contrato_pessoa_contrato___row__data = new THidden('contrato_pessoa_contrato___row__data[]');
        $contrato_pessoa_contrato_cliente_id = new TDBCombo('contrato_pessoa_contrato_cliente_id[]', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_contrato_pessoa_contrato_cliente_id );
        $contrato_pessoa_contrato_percentual = new TEntry('contrato_pessoa_contrato_percentual[]');
        $this->fieldList_contrato_cliente = new TFieldList();
        $contrato_representante_contrato_id = new THidden('contrato_representante_contrato_id[]');
        $contrato_representante_contrato___row__id = new THidden('contrato_representante_contrato___row__id[]');
        $contrato_representante_contrato___row__data = new THidden('contrato_representante_contrato___row__data[]');
        $contrato_representante_contrato_representante_id = new TDBUniqueSearch('contrato_representante_contrato_representante_id[]', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_contrato_representante_contrato_representante_id );
        $this->fieldList_representantes = new TFieldList();
        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id = new TDBCombo('contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id', 'escritorio', 'ContratoPagamentoOpcao', 'id', '{nome}','id asc' , $criteria_contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id );
        $contrato_pagamento_parcela_contrato_id = new THidden('contrato_pagamento_parcela_contrato_id');
        $contrato_pagamento_parcela_contrato_valor = new TNumeric('contrato_pagamento_parcela_contrato_valor', '2', ',', '.' );
        $contrato_pagamento_parcela_contrato_data_pagamento = new TDate('contrato_pagamento_parcela_contrato_data_pagamento');
        $contrato_pagamento_parcela_contrato_contrato_evento_id = new TDBCombo('contrato_pagamento_parcela_contrato_contrato_evento_id', 'escritorio', 'ContratoPagamentoEvento', 'id', '{nome}','nome asc' , $criteria_contrato_pagamento_parcela_contrato_contrato_evento_id );
        $contrato_pagamento_parcela_contrato_complemento_indexador = new TEntry('contrato_pagamento_parcela_contrato_complemento_indexador');
        $contrato_pagamento_parcela_contrato_unidade_indexador_id = new TDBCombo('contrato_pagamento_parcela_contrato_unidade_indexador_id', 'escritorio', 'UnidadeIndexador', 'id', '{nome}','nome asc' , $criteria_contrato_pagamento_parcela_contrato_unidade_indexador_id );
        $contrato_pagamento_parcela_contrato_contrato_indexador_id = new TDBCombo('contrato_pagamento_parcela_contrato_contrato_indexador_id', 'escritorio', 'ContratoPagamentoIndexador', 'id', '{nome}','nome asc' , $criteria_contrato_pagamento_parcela_contrato_contrato_indexador_id );
        $contrato_pagamento_parcela_contrato_numero_parcelas = new TEntry('contrato_pagamento_parcela_contrato_numero_parcelas');
        $button_adicionar_contrato_pagamento_parcela_contrato = new TButton('button_adicionar_contrato_pagamento_parcela_contrato');
        $contrato_repasse_contrato_id = new THidden('contrato_repasse_contrato_id[]');
        $contrato_repasse_contrato___row__id = new THidden('contrato_repasse_contrato___row__id[]');
        $contrato_repasse_contrato___row__data = new THidden('contrato_repasse_contrato___row__data[]');
        $contrato_repasse_contrato_pessoa_id = new TDBUniqueSearch('contrato_repasse_contrato_pessoa_id[]', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_contrato_repasse_contrato_pessoa_id );
        $contrato_repasse_contrato_percentual = new TEntry('contrato_repasse_contrato_percentual[]');
        $this->fieldList_contrato_profissional = new TFieldList();

        $this->fieldList_contrato_cliente->addField(null, $contrato_pessoa_contrato_id, []);
        $this->fieldList_contrato_cliente->addField(null, $contrato_pessoa_contrato___row__id, ['uniqid' => true]);
        $this->fieldList_contrato_cliente->addField(null, $contrato_pessoa_contrato___row__data, []);
        $this->fieldList_contrato_cliente->addField(new TLabel("Cliente", null, '14px', null), $contrato_pessoa_contrato_cliente_id, ['width' => '50%']);
        $this->fieldList_contrato_cliente->addField(new TLabel("Percentual", null, '14px', null), $contrato_pessoa_contrato_percentual, ['width' => '50%','sum' => true]);

        $this->fieldList_contrato_cliente->width = '100%';
        $this->fieldList_contrato_cliente->setFieldPrefix('contrato_pessoa_contrato');
        $this->fieldList_contrato_cliente->name = 'fieldList_contrato_cliente';

        $this->criteria_fieldList_contrato_cliente = new TCriteria();
        $this->default_item_fieldList_contrato_cliente = new stdClass();

        $this->form->addField($contrato_pessoa_contrato_id);
        $this->form->addField($contrato_pessoa_contrato___row__id);
        $this->form->addField($contrato_pessoa_contrato___row__data);
        $this->form->addField($contrato_pessoa_contrato_cliente_id);
        $this->form->addField($contrato_pessoa_contrato_percentual);

        $this->fieldList_contrato_cliente->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $this->fieldList_representantes->addField(null, $contrato_representante_contrato_id, []);
        $this->fieldList_representantes->addField(null, $contrato_representante_contrato___row__id, ['uniqid' => true]);
        $this->fieldList_representantes->addField(null, $contrato_representante_contrato___row__data, []);
        $this->fieldList_representantes->addField(new TLabel("Representante", null, '14px', null), $contrato_representante_contrato_representante_id, ['width' => '100%']);

        $this->fieldList_representantes->width = '100%';
        $this->fieldList_representantes->setFieldPrefix('contrato_representante_contrato');
        $this->fieldList_representantes->name = 'fieldList_representantes';

        $this->criteria_fieldList_representantes = new TCriteria();
        $this->default_item_fieldList_representantes = new stdClass();

        $this->form->addField($contrato_representante_contrato_id);
        $this->form->addField($contrato_representante_contrato___row__id);
        $this->form->addField($contrato_representante_contrato___row__data);
        $this->form->addField($contrato_representante_contrato_representante_id);

        $this->fieldList_representantes->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $this->fieldList_contrato_profissional->addField(null, $contrato_repasse_contrato_id, []);
        $this->fieldList_contrato_profissional->addField(null, $contrato_repasse_contrato___row__id, ['uniqid' => true]);
        $this->fieldList_contrato_profissional->addField(null, $contrato_repasse_contrato___row__data, []);
        $this->fieldList_contrato_profissional->addField(new TLabel("Parceiro", null, '14px', null), $contrato_repasse_contrato_pessoa_id, ['width' => '50%']);
        $this->fieldList_contrato_profissional->addField(new TLabel("Percentual", null, '14px', null), $contrato_repasse_contrato_percentual, ['width' => '50%','sum' => true]);

        $this->fieldList_contrato_profissional->width = '100%';
        $this->fieldList_contrato_profissional->setFieldPrefix('contrato_repasse_contrato');
        $this->fieldList_contrato_profissional->name = 'fieldList_contrato_profissional';

        $this->criteria_fieldList_contrato_profissional = new TCriteria();
        $this->default_item_fieldList_contrato_profissional = new stdClass();

        $this->form->addField($contrato_repasse_contrato_id);
        $this->form->addField($contrato_repasse_contrato___row__id);
        $this->form->addField($contrato_repasse_contrato___row__data);
        $this->form->addField($contrato_repasse_contrato_pessoa_id);
        $this->form->addField($contrato_repasse_contrato_percentual);

        $this->fieldList_contrato_profissional->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $tipo_processo_id->setChangeAction(new TAction([$this,'onChangetipo_processo_id']));
        $area_id->setChangeAction(new TAction([$this,'onSelectAreaContrato']));
        $assunto_id->setChangeAction(new TAction([$this,'onSelectAssuntoContrato']));
        $contrato_pessoa_contrato_cliente_id->setChangeAction(new TAction([$this,'onSelectCliente']));
        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id->setChangeAction(new TAction([$this,'onSelectOpcaoPagamento']));
        $contrato_repasse_contrato_pessoa_id->setChangeAction(new TAction([$this,'onSelectPessoaRepasse']));

        $escritorio_id->addValidation("Escritório", new TRequiredValidator()); 
        $objeto->addValidation("Objeto", new TRequiredValidator()); 
        $contrato_pessoa_contrato_cliente_id->addValidation("Cliente", new TRequiredListValidator()); 

        $escritorio_id->setDefaultOption(false);
        $contrato_representante_contrato_representante_id->setFilterColumns(["cpf_cnpj","nome","rg_ie"]);
        $contrato_pagamento_parcela_contrato_complemento_indexador->setMaxLength(255);
        $button_adicionar_contrato_pagamento_parcela_contrato->setAction(new TAction([$this, 'onAddDetailContratoPagamentoParcelaContrato'],['static' => 1]), "Adicionar");
        $button_adicionar_contrato_pagamento_parcela_contrato->addStyleClass('btn-default');
        $button_adicionar_contrato_pagamento_parcela_contrato->setImage('fas:plus #2ecc71');
        $contrato_repasse_contrato_pessoa_id->setMinLength(3);
        $contrato_representante_contrato_representante_id->setMinLength(2);

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $contrato_pagamento_parcela_contrato_data_pagamento->setDatabaseMask('yyyy-mm-dd');

        $contrato_status_id->setValue('1');
        $tela->setValue($param['tela'] ?? null);
        $contrato_repasse_contrato_percentual->setValue('100');
        $atendimento_id->setValue($param['atendimento_id'] ?? null);

        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $contrato_repasse_contrato_pessoa_id->setMask('{nome}');
        $contrato_representante_contrato_representante_id->setMask('{nome}');
        $contrato_pagamento_parcela_contrato_data_pagamento->setMask('dd/mm/yyyy');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);
        $contrato_pagamento_parcela_contrato_valor->setEditable(false);
        $contrato_pagamento_parcela_contrato_data_pagamento->setEditable(false);
        $contrato_pagamento_parcela_contrato_contrato_evento_id->setEditable(false);
        $contrato_pagamento_parcela_contrato_unidade_indexador_id->setEditable(false);
        $contrato_pagamento_parcela_contrato_complemento_indexador->setEditable(false);
        $contrato_pagamento_parcela_contrato_contrato_indexador_id->setEditable(false);

        $area_id->enableSearch();
        $assunto_id->enableSearch();
        $escritorio_id->enableSearch();
        $envolvimento_id->enableSearch();
        $tipo_processo_id->enableSearch();
        $contrato_status_id->enableSearch();
        $contrato_pessoa_contrato_cliente_id->enableSearch();
        $contrato_pagamento_parcela_contrato_contrato_evento_id->enableSearch();
        $contrato_pagamento_parcela_contrato_unidade_indexador_id->enableSearch();
        $contrato_pagamento_parcela_contrato_contrato_indexador_id->enableSearch();
        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id->enableSearch();

        $tela->setSize(200);
        $id->setSize('100%');
        $numero->setSize(200);
        $area_id->setSize('100%');
        $assunto_id->setSize('100%');
        $atendimento_id->setSize(200);
        $objeto->setSize('100%', 250);
        $data_criacao->setSize('100%');
        $escritorio_id->setSize('100%');
        $envolvimento_id->setSize('100%');
        $tipo_processo_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $contrato_status_id->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $contrato_pessoa_contrato_cliente_id->setSize('100%');
        $contrato_pessoa_contrato_percentual->setSize('100%');
        $contrato_pagamento_parcela_contrato_id->setSize(200);
        $contrato_repasse_contrato_pessoa_id->setSize('100%');
        $contrato_repasse_contrato_percentual->setSize('100%');
        $contrato_pagamento_parcela_contrato_valor->setSize('100%');
        $contrato_representante_contrato_representante_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_data_pagamento->setSize('100%');
        $contrato_pagamento_parcela_contrato_numero_parcelas->setSize('100%');
        $contrato_pagamento_parcela_contrato_contrato_evento_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_contrato_indexador_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id->setSize('100%');
        $contrato_pagamento_parcela_contrato_unidade_indexador_id->setSize('calc(50% - 8px)');
        $contrato_pagamento_parcela_contrato_complemento_indexador->setSize('calc(50% - 8px)');

        $button_adicionar_contrato_pagamento_parcela_contrato->id = '6579e20b9eee2';

        $this->form->appendPage("Informações");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id,$tela,$atendimento_id],[$numero],[new TLabel("Escritório:", '#FF0000', '14px', null, '100%'),$escritorio_id]);
        $row1->layout = ['col-sm-3',' col-sm-6',' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Tipo de processo:", null, '14px', null, '100%'),$tipo_processo_id],[new TLabel("Envolvimento:", null, '14px', null, '100%'),$envolvimento_id]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Área:", null, '14px', null, '100%'),$area_id],[new TLabel("Assunto:", null, '14px', null, '100%'),$assunto_id],[new TLabel("Status:", '#FF0000', '14px', null, '100%'),$contrato_status_id]);
        $row3->layout = [' col-sm-3',' col-sm-6',' col-sm-3'];

        $row4 = $this->form->addFields([new TLabel("Objeto:", '#ff0000', '14px', null, '100%'),$objeto]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#797979', '18', '#797979')]);
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $this->form->appendPage("Clientes");
        $row7 = $this->form->addFields([$this->fieldList_contrato_cliente]);
        $row7->layout = [' col-sm-12'];

        $this->form->appendPage("Representante");
        $row8 = $this->form->addFields([$this->fieldList_representantes]);
        $row8->layout = [' col-sm-12'];

        $this->form->appendPage("Pagamentos");

        $this->detailFormContratoPagamentoParcelaContrato = new BootstrapFormBuilder('detailFormContratoPagamentoParcelaContrato');
        $this->detailFormContratoPagamentoParcelaContrato->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormContratoPagamentoParcelaContrato->setProperty('class', 'form-horizontal builder-detail-form');

        $row9 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new TLabel("Opção de pagamento:", '#ff0000', '14px', null, '100%'),$contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id,$contrato_pagamento_parcela_contrato_id]);
        $row9->layout = [' col-sm-8'];

        $row10 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new TLabel("Valor:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_valor],[new TLabel("Data:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_data_pagamento],[new TLabel("Evento:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_contrato_evento_id]);
        $row10->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row11 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new TLabel("Número do indexador:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_complemento_indexador,$contrato_pagamento_parcela_contrato_unidade_indexador_id],[new TLabel("Indexador:", null, '14px', null, '100%'),$contrato_pagamento_parcela_contrato_contrato_indexador_id],[new TLabel("Número de parcelas:", '#FF0000', '14px', null, '100%'),$contrato_pagamento_parcela_contrato_numero_parcelas]);
        $row11->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row12 = $this->detailFormContratoPagamentoParcelaContrato->addFields([$button_adicionar_contrato_pagamento_parcela_contrato]);
        $row12->layout = [' col-sm-12'];

        $row13 = $this->detailFormContratoPagamentoParcelaContrato->addFields([new THidden('contrato_pagamento_parcela_contrato__row__id')]);
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
        $column_contrato_pagamento_parcela_contrato_numero_parcelas = new TDataGridColumn('numero_parcelas', "Número de parcelas", 'left');

        $column_contrato_pagamento_parcela_contrato__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_contrato_pagamento_parcela_contrato__row__data->setVisibility(false);

        $action_onEditDetailContratoPagamentoParcela = new TDataGridAction(array('ContratoForm', 'onEditDetailContratoPagamentoParcela'));
        $action_onEditDetailContratoPagamentoParcela->setUseButton(false);
        $action_onEditDetailContratoPagamentoParcela->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailContratoPagamentoParcela->setLabel("Editar");
        $action_onEditDetailContratoPagamentoParcela->setImage('far:edit #478fca');
        $action_onEditDetailContratoPagamentoParcela->setFields(['__row__id', '__row__data']);

        $this->contrato_pagamento_parcela_contrato_list->addAction($action_onEditDetailContratoPagamentoParcela);
        $action_onDeleteDetailContratoPagamentoParcela = new TDataGridAction(array('ContratoForm', 'onDeleteDetailContratoPagamentoParcela'));
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
        });        $row14 = $this->form->addFields([$this->detailFormContratoPagamentoParcelaContrato]);
        $row14->layout = [' col-sm-12'];

        $this->form->appendPage("Repasse");
        $row15 = $this->form->addFields([$this->fieldList_contrato_profissional]);
        $row15->layout = ['col-sm-12'];

/*

        // create the form actions
        $onSaveFalso = $this->form->addAction("Salvar", new TAction([$this, 'onChamaDateForm']), 'fas:save #FFFFFF');
        $this->onSaveFalso = $onSaveFalso;
        $onSaveFalso->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ContratoList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        $onSaveReal = $this->form->addAction("SalvarReal", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->onSaveReal = $onSaveReal;
        $onSaveReal->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

*/      TScript::create("
            var seletorPercentualContrato = 'input[name=\"contrato_pessoa_contrato_percentual[]\"], input[name=\"contrato_repasse_contrato_percentual[]\"]';

            $(document).off('input', seletorPercentualContrato);
            $(document).off('keydown', seletorPercentualContrato);
            $(document).off('paste', seletorPercentualContrato);
            $(document).off('focus', seletorPercentualContrato);
            $(document).off('blur', seletorPercentualContrato);

            if (window.mascaraPercentualContratoInterval) {
                clearInterval(window.mascaraPercentualContratoInterval);
            }

            function percentualContratoFormatarNumero(numero) {
                numero = parseFloat(numero || 0);

                if (isNaN(numero)) {
                    numero = 0;
                }

                return numero.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function percentualContratoNumeroDoValor(valor) {
                valor = (valor || '').toString().trim();

                if (valor === '') {
                    return null;
                }

                valor = valor.replace('%', '');
                valor = valor.replace(/\\s/g, '');

                if (valor.indexOf(',') !== -1) {
                    valor = valor.replace(/\\./g, '');
                    valor = valor.replace(',', '.');
                }

                var numero = parseFloat(valor);

                if (isNaN(numero)) {
                    return null;
                }

                if (numero > 100) {
                    numero = 100;
                }

                if (numero < 0) {
                    numero = 0;
                }

                return numero;
            }

            function percentualContratoDigitosDoValor(valor) {
                var numero = percentualContratoNumeroDoValor(valor);

                if (numero === null) {
                    return '';
                }

                return Math.round(numero * 100).toString();
            }

            function percentualContratoDigitosDoTextoColado(texto) {
                texto = (texto || '').toString().trim();

                if (texto === '') {
                    return '';
                }

                if (texto.indexOf(',') !== -1 || texto.indexOf('.') !== -1) {
                    return percentualContratoDigitosDoValor(texto);
                }

                return texto.replace(/[^0-9]/g, '');
            }

            function percentualContratoFormatarPorDigitos(digitos) {
                digitos = (digitos || '').toString().replace(/[^0-9]/g, '');

                if (digitos === '') {
                    return '';
                }

                var numero = parseInt(digitos, 10) / 100;

                if (isNaN(numero)) {
                    return '';
                }

                if (numero > 100) {
                    numero = 100;
                }

                return percentualContratoFormatarNumero(numero);
            }

            function percentualContratoCursorFinal(campo) {
                setTimeout(function() {
                    if (campo && typeof campo.setSelectionRange === 'function') {
                        var tamanho = campo.value.length;
                        campo.setSelectionRange(tamanho, tamanho);
                    }
                }, 0);
            }

            function atualizarTotalPercentualPorCampo(nomeCampo) {
                var campos = $('input[name=\"' + nomeCampo + '[]\"]');

                if (!campos.length) {
                    return;
                }

                var total = 0;

                campos.each(function() {
                    var numero = percentualContratoNumeroDoValor($(this).val());

                    if (numero !== null) {
                        total += numero;
                    }
                });

                total = Math.round(total * 100) / 100;

                var texto = percentualContratoFormatarNumero(total);

                var tabela = campos.first().closest('table');

                if (!tabela.length) {
                    return;
                }

                var campoTotal = tabela.find('input:visible').filter(function() {
                    var name = $(this).attr('name') || '';

                    if (name === nomeCampo + '[]') {
                        return false;
                    }

                    var linha = $(this).closest('tr');

                    if (linha.find('input[name=\"' + nomeCampo + '[]\"]').length > 0) {
                        return false;
                    }

                    return true;
                }).last();

                if (campoTotal.length) {
                    campoTotal.val(texto);
                }
            }

            function atualizarTotaisPercentuaisContrato() {
                atualizarTotalPercentualPorCampo('contrato_pessoa_contrato_percentual');
                atualizarTotalPercentualPorCampo('contrato_repasse_contrato_percentual');
            }

            function aplicarMascaraPercentualContrato() {
                $(seletorPercentualContrato).each(function() {
                    var campo = $(this);

                    campo.attr('inputmode', 'numeric');
                    campo.attr('autocomplete', 'off');
                    campo.attr('placeholder', '0,00');

                    if (!campo.data('mascara-percentual-contrato')) {
                        campo.data('mascara-percentual-contrato', true);
                    }

                    if (!campo.is(':focus')) {
                        var valorAtual = campo.val();

                        if (valorAtual !== '') {
                            var digitos = percentualContratoDigitosDoValor(valorAtual);
                            var valorFormatado = percentualContratoFormatarPorDigitos(digitos);

                            campo.data('percentual-digitos', digitos);
                            campo.val(valorFormatado);
                        }
                    }
                });

                atualizarTotaisPercentuaisContrato();
            }

            $(document).on('focus', seletorPercentualContrato, function() {
                var campo = $(this);

                campo.data('percentual-digitos', percentualContratoDigitosDoValor(campo.val()));

                this.select();
            });

            $(document).on('keydown', seletorPercentualContrato, function(e) {
                var tecla = e.key;
                var campoHtml = this;
                var campo = $(this);

                if (
                    tecla === 'Tab' ||
                    tecla === 'ArrowLeft' ||
                    tecla === 'ArrowRight' ||
                    tecla === 'Home' ||
                    tecla === 'End'
                ) {
                    return;
                }

                e.preventDefault();

                var selecionouTudo = campoHtml.selectionStart === 0 && campoHtml.selectionEnd === campoHtml.value.length;

                var digitos = campo.data('percentual-digitos');

                if (typeof digitos === 'undefined') {
                    digitos = percentualContratoDigitosDoValor(campo.val());
                }

                digitos = (digitos || '').toString();

                if (/^[0-9]$/.test(tecla)) {
                    if (selecionouTudo) {
                        digitos = tecla;
                    } else {
                        digitos = digitos + tecla;
                    }
                } else if (tecla === 'Backspace' || tecla === 'Delete') {
                    if (selecionouTudo) {
                        digitos = '';
                    } else {
                        digitos = digitos.slice(0, -1);
                    }
                } else {
                    return;
                }

                digitos = digitos.replace(/^0+(?=\\d)/, '');

                var numero = parseInt(digitos || '0', 10) / 100;

                if (numero > 100) {
                    digitos = '10000';
                }

                campo.data('percentual-digitos', digitos);
                campo.val(percentualContratoFormatarPorDigitos(digitos));

                atualizarTotaisPercentuaisContrato();
                percentualContratoCursorFinal(campoHtml);
            });

            $(document).on('paste', seletorPercentualContrato, function(e) {
                e.preventDefault();

                var campo = $(this);
                var texto = (e.originalEvent || e).clipboardData.getData('text') || '';
                var digitos = percentualContratoDigitosDoTextoColado(texto);

                var numero = parseInt(digitos || '0', 10) / 100;

                if (numero > 100) {
                    digitos = '10000';
                }

                campo.data('percentual-digitos', digitos);
                campo.val(percentualContratoFormatarPorDigitos(digitos));

                atualizarTotaisPercentuaisContrato();
                percentualContratoCursorFinal(this);
            });

            $(document).on('blur', seletorPercentualContrato, function() {
                var campo = $(this);
                var digitos = campo.data('percentual-digitos');

                if (typeof digitos === 'undefined') {
                    digitos = percentualContratoDigitosDoValor(campo.val());
                }

                campo.val(percentualContratoFormatarPorDigitos(digitos));

                atualizarTotaisPercentuaisContrato();
            });

            aplicarMascaraPercentualContrato();

            $(document).on('click', '.tfieldlist_add, .tfieldlist_clone, .btn', function() {
                setTimeout(function() {
                    aplicarMascaraPercentualContrato();
                    atualizarTotaisPercentuaisContrato();
                }, 300);
            });

            window.mascaraPercentualContratoInterval = setInterval(function() {
                aplicarMascaraPercentualContrato();
                atualizarTotaisPercentuaisContrato();
            }, 1000);
        ");

        $onSaveFalso = $this->form->addAction("Salvar", new TAction([$this, 'onChamaDateForm']), 'fas:save #FFFFFF');
        $this->onSaveFalso = $onSaveFalso;
        $onSaveFalso->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ContratoList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        $onSaveReal = $this->form->addAction("SalvarReal", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->onSaveReal = $onSaveReal;
        $onSaveReal->addStyleClass('btn-primary'); 

        $onSaveReal->id = 'onsavereal';
        $onSaveReal->style = 'display:none';
        TScript::create("$('#onsavereal').hide();");

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);   

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ContratoForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChangetipo_processo_id($param)
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

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    } 

    public static function onSelectAreaContrato($param = null) 
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

    public static function onSelectAssuntoContrato($param = null) 
    {
        try 
        {
            if(isset($param['assunto_contrato_id']) && $param['objeto']==null){
                TTransaction::open(self::$database);
                $object = new stdClass();
                $object->objeto = (AssuntoContrato::find($param['assunto_contrato_id']))->descricao ?? null;

                TForm::sendData(self::$formName, $object);
                TTransaction::close();
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onSelectCliente($param = null) 
    {
        try 
        {
            $object = new stdClass();

            $porcentagem = 100 - self::somarPercentuais($param['contrato_pessoa_contrato_percentual'] ?? []);
            $porcentagem = number_format($porcentagem, 2, ',', '');

            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);

            $object->{"contrato_pessoa_contrato_percentual_{$field_id}"} = $porcentagem;
            /*
            TTransaction::open(self::$database);

            $aaa = array_key_last($param['contrato_representante_contrato___row__id']);

            if($param['_field_value']){
                $pessoa = PessoaRepresentantesLegais::where('pessoa_juridica_id','=',(int) $param['_field_value'])->first();
                if($pessoa){
                    if($pessoa->representante_id != null){
                        $object->{"contrato_representante_contrato_representante_id_{$aaa}"} = $pessoa->representante_id;
                        //TFieldList::addRows('fieldList_representantes',1);
                    }
                }
            }

            TTransaction::close();
            */

            TForm::sendData(self::$formName, $object);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
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
            TTransaction::rollback();
        }
    }

    public static function onSelectPessoaRepasse($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);

            $object = new stdClass();

            if($param['_field_value']){
                $parceiro = Parceiro::where('pessoa_id','=',(int) $param['_field_value'])->first();
                if($parceiro){
                    if($parceiro->percentual != null){

                        $object->{"contrato_repasse_contrato_percentual_{$field_id}"} = $parceiro->percentual;

                        TForm::sendData(self::$formName, $object);
                    }
                }
            }

            if(!isset($object->{"contrato_repasse_contrato_percentual_{$field_id}"})){

                $porcentagem = 100 - self::somarPercentuais($param['contrato_repasse_contrato_percentual'] ?? []);
                $porcentagem = number_format($porcentagem, 2, ',', '');

                $object->{"contrato_repasse_contrato_percentual_{$field_id}"} = $porcentagem;

            }

            TForm::sendData(self::$formName, $object);

            TTransaction::close();

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
            $requiredFields[] = ['label'=>"Opção de pagamento", 'name'=>"contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Número de parcelas", 'name'=>"contrato_pagamento_parcela_contrato_numero_parcelas", 'class'=>'TRequiredValidator', 'value'=>[]];
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

                TTransaction::open(self::$database);
                $opcao = ContratoPagamentoOpcao::find((int) $data->contrato_pagamento_parcela_contrato_contrato_opcao_pagamento_id);

                if(!$data->contrato_pagamento_parcela_contrato_numero_parcelas){
                    throw new Exception("O campo Número de parcelas é obrigatório.");
                }

                if($opcao->recebe_valor == "S"){
                    if(!$data->contrato_pagamento_parcela_contrato_valor){
                        throw new Exception("O campo Valor é obrigatório.");
                    }
                }
                if($opcao->recebe_data == "S"){
                    if(!$data->contrato_pagamento_parcela_contrato_data_pagamento){
                        throw new Exception("O campo Data é obrigatório.");
                    }
                }
                if($opcao->recebe_evento == "S"){
                    if(!$data->contrato_pagamento_parcela_contrato_contrato_evento_id){
                        throw new Exception("O campo Evento é obrigatório.");
                    }
                }
                if($opcao->recebe_indexador == "S"){
                    if(!$data->contrato_pagamento_parcela_contrato_contrato_indexador_id){
                        throw new Exception("O campo Indexador é obrigatório.");
                    }
                    if(!$data->contrato_pagamento_parcela_contrato_complemento_indexador){
                        throw new Exception("O campo Complemento do indexador é obrigatório.");
                    }
                }

                TTransaction::close();

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
            $data->contrato_pagamento_parcela_contrato_numero_parcelas = '';
            $data->contrato_pagamento_parcela_contrato__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#6579e20b9eee2');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

                TScript::create("$('label:contains(\"Valor:\")').html('<span style=\"color:#333;\">Valor:</span>')");
                TNumeric::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_valor');
                TScript::create("$('label:contains(\"Data:\")').html('<span style=\"color:#333;\">Data:</span>')");
                TDate::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_data_pagamento');
                TScript::create("$('label:contains(\"Evento:\")').html('<span style=\"color:#333;\">Evento:</span>')");
                TDBCombo::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_contrato_evento_id');
                TScript::create("$('label:contains(\"Indexador:\")').html('<span style=\"color:#333;\">Indexador:</span>')");
                TDBCombo::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_contrato_indexador_id');
                TScript::create("$('label:contains(\"Indexador:\")').html('<span style=\"color:#333;\">Indexador:</span>')");
                TEntry::disableField(self::$formName, 'contrato_pagamento_parcela_contrato_complemento_indexador');
                TScript::create("$('label:contains(\"Complemento do indexador:\")').html('<span style=\"color:#333;\">Complemento do indexador:</span>')");
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback();
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
               var element = $('#6579e20b9eee2');
               if(!element.attr('add')){
                   element.attr('add', base64_encode(element.html()));
               }
               element.html(\"<span><i class='fas fa-save' style='color:#478fca;padding-right:4px;'></i>Salvar</span>\");
               if(!element.attr('edit')){
                   element.attr('edit', base64_encode(element.html()));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback();
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
               var element = $('#6579e20b9eee2');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback();
        }
    }
    public function onChamaDateForm($param = null) 
    {
        try 
        {
            $this->form->validate();

            if (!empty($param['id'])) {
                $this->onSave($param);
                return;
            }

            unset($param['class']);
            unset($param['method']);
            unset($param['static']);
            unset($param['target_container']);

            TSession::setValue('contrato_save_param_pendente', $param);

            TApplication::loadPage('ContratoDateForm', 'onShow');      

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $param = $param ?? [];

            $salvandoPacoteContrato = !empty($param['_salvar_pacote_contrato']);

            if ($salvandoPacoteContrato) {

                unset($param['class']);
                unset($param['method']);
                unset($param['static']);
                unset($param['target_container']);

                $data = (object) $param;

                $this->form->setData($data);

            } else {

                $this->form->validate();
                $data = $this->form->getData();
            }

            $anoReferenciaContrato = $data->ano_referencia_contrato ?? null;

            unset($data->ano_referencia_contrato);
            unset($data->_salvar_pacote_contrato);

            $object = new Contrato(); // create an empty object 

            $object->fromArray( (array) $data); // load the object with data

            // validação de alteração de status
            $userId = TSession::getValue('userid');

            if (!in_array($userId, [3, 4, 5]) && !empty($data->id)) {
                // busca o contrato original no banco
                $contratoAntigo = new Contrato($data->id);

                if ($contratoAntigo->contrato_status_id != $object->contrato_status_id) {
                    throw new Exception('Você não tem permissão para alterar o status do contrato.');
                }
            }                      

            if (empty($data->id)) {

                if (empty($anoReferenciaContrato)) {
                    throw new Exception('Ano de referência do contrato não informado.');
                }

                $object->numero = self::gerarProximoNumeroContrato($anoReferenciaContrato);
            }

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            $object->id = $data->id ?? null;
            $object->tela = $data->tela ?? null;
            $object->atendimento_id = $data->atendimento_id ?? null;
            $object->numero = $object->numero ?? ($data->numero ?? null);

            $object->escritorio_id = $data->escritorio_id ?? null;
            $object->tipo_processo_id = $data->tipo_processo_id ?? null;
            $object->envolvimento_id = $data->envolvimento_id ?? null;
            $object->area_id = $data->area_id ?? null;
            $object->assunto_id = $data->assunto_id ?? null;
            $object->contrato_status_id = $data->contrato_status_id ?? null;
            $object->objeto = $data->objeto ?? null;

            $object->data_criacao = $data->data_criacao ?? null;
            $object->data_modificacao = $data->data_modificacao ?? null;

            $object->store(); // save the object 

            $this->fireEvents($object);

            $contrato_representante_contrato_items = $this->storeItems('ContratoRepresentante', 'contrato_id', $object, $this->fieldList_representantes, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_representantes); 

//<generatedAutoCode>
            $this->contrato_pagamento_parcela_contrato_criteria->setProperty('order', 'contrato_opcao_pagamento_id asc');
//</generatedAutoCode>
            $contrato_pagamento_parcela_contrato_items = $this->storeMasterDetailItems('ContratoPagamentoParcela', 'contrato_id', 'contrato_pagamento_parcela_contrato', $object, $param['contrato_pagamento_parcela_contrato_list___row__data'] ?? [], $this->form, $this->contrato_pagamento_parcela_contrato_list, function($masterObject, $detailObject){ 

                try{
                    TTransaction::open(self::$database);

                    $opcao = ContratoPagamentoOpcao::find((int) $detailObject->contrato_opcao_pagamento_id);

                    if($detailObject->numero_parcelas==1){
                        $descricao = $opcao->descricao1;
                    }else{
                        $descricao = $opcao->descricaon;
                    }

                    $valor = number_format((float) $detailObject->valor, 2, ',', '');
                    $extenso = new Extenso();

                    $indexador = (ContratoPagamentoIndexador::find((int) $detailObject->contrato_indexador_id))->nome ?? "";
                    $evento = (ContratoPagamentoEvento::find((int) $detailObject->contrato_evento_id))->nome ?? "";

                    $data = new DateTime($detailObject->data_pagamento);
                    $data_extenso = $data->format('d') . ' de ' . DateService::getMonthName($detailObject->data_pagamento) . ' de ' . $data->format('Y');

                    $tags = [
                      '[opcao_pagamento]' => $opcao->nome,
                      '[valor]' => $valor,  
                      '[valor_extenso]' => $extenso->extenso((float) $valor, Extenso::MOEDA),  
                      '[data]' => $data->format('d/m/Y'),
                      '[data_extenso]' => $data_extenso,
                      '[numero_parcelas]' => $detailObject->numero_parcelas,
                      '[numero_parcelas_extenso]' => $extenso->extenso((float) $detailObject->numero_parcelas, Extenso::NUMERO_FEMININO),
                      '[numero_indexador]' => $detailObject->complemento_indexador,
                      '[numero_indexador_extenso]' => $extenso->extenso((float) $detailObject->complemento_indexador, Extenso::NUMERO_MASCULINO ),
                      '[unidade_indexador]' => $detailObject->unidade_indexador->simbolo,
                      '[unidade_indexador_extenso]' => $detailObject->unidade_indexador->extenso,
                      '[indexador]' => $indexador,
                      '[evento]' => $evento
                    ];

                    foreach($tags as $variavel=>$valor){
                        $descricao = str_replace($variavel, $valor, $descricao);
                    }

                    $detailObject->descritivo = $descricao;

                    TTransaction::close();

                }catch (Exception $e){
                    new TMessage('error', $e->getMessage()); // shows the exception error message
                    TTransaction::rollback(); // undo all pending operations
                }

            }, $this->contrato_pagamento_parcela_contrato_criteria); 

            $object->valor = 0;

            if($object->valor == 0){
                $object->valor = null;
            }

            $totalRepasse = self::somarPercentuais($data->contrato_repasse_contrato_percentual ?? []);

            if ($totalRepasse > 0 && abs($totalRepasse - 100) > 0.0001) {
                throw new Exception("A soma do percentual de repasse deve ser igual a 100! Total atual: " . number_format($totalRepasse, 2, ',', '.'));
            }
            $contrato_repasse_contrato_items = $this->storeItems('ContratoRepasse', 'contrato_id', $object, $this->fieldList_contrato_profissional, function($masterObject, $detailObject){ 

                $detailObject->percentual = self::normalizarNumero($detailObject->percentual ?? null);

            }, $this->criteria_fieldList_contrato_profissional); 

            $totalClientes = self::somarPercentuais($data->contrato_pessoa_contrato_percentual ?? []);

            if ($totalClientes > 0 && abs($totalClientes - 100) > 0.0001) {
                throw new Exception("A soma do percentual de clientes deve ser igual a 100! Total atual: " . number_format($totalClientes, 2, ',', '.'));
            }
            $contrato_pessoa_contrato_items = $this->storeItems('ContratoPessoa', 'contrato_id', $object, $this->fieldList_contrato_cliente, function($masterObject, $detailObject){ 

                $detailObject->percentual = self::normalizarNumero($detailObject->percentual ?? null);

            }, $this->criteria_fieldList_contrato_cliente); 
            if ($salvandoPacoteContrato) {
                self::salvarFieldListsContratoManual($object, $data);
            }
            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            if(!isset($numeroInicial) || $numeroInicial === $object->numero){

            TToast::show('success', "Registro salvo.", 'topRight', 'far:check-circle'); 

            } else {
                new TMessage('info', "Número do contrato alterado para $object->numero.");
            }

                        TScript::create("Template.closeRightPanel();");
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

            if (!empty($data->atendimento_id)) {
                $atendimentoContrato = new AtendimentoContrato();
                $atendimentoContrato->contrato_id = $object->id;
                $atendimentoContrato->atendimento_id = $data->atendimento_id;
                $atendimentoContrato->store();

                $loadPageParam = [
                    "key" => $data->atendimento_id,
                    "id" => $data->atendimento_id,
                    "current_tab_abas" => "4"
                ];

                TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam);
            } elseif (!isset($numeroInicial)) {
                TApplication::loadPage('ContratoFormView', 'onShow', ['key' => $object->id, 'id' => $object->id]);
            }

            TSession::setValue('contrato_save_param_pendente', null);
            TSession::setValue('contrato_save_ano_pendente', null);

            TTransaction::close(); // close the transaction
        }
        catch (Exception $e) // in case of exception
        {

            $this->fireEvents($this->form->getData());  

            TTransaction::rollback(); // undo all pending operations
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
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

                $object = new Contrato($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->fieldList_representantes_items = $this->loadItems('ContratoRepresentante', 'contrato_id', $object, $this->fieldList_representantes, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_representantes); 

//<generatedAutoCode>
                $this->contrato_pagamento_parcela_contrato_criteria->setProperty('order', 'contrato_opcao_pagamento_id asc');
//</generatedAutoCode>
                $contrato_pagamento_parcela_contrato_items = $this->loadMasterDetailItems('ContratoPagamentoParcela', 'contrato_id', 'contrato_pagamento_parcela_contrato', $object, $this->form, $this->contrato_pagamento_parcela_contrato_list, $this->contrato_pagamento_parcela_contrato_criteria, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }); 

                $object->contrato_documento_contrato_dt_validade = date('d-m-Y');

                $this->fieldList_contrato_profissional_items = $this->loadItems('ContratoRepasse', 'contrato_id', $object, $this->fieldList_contrato_profissional, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_contrato_profissional); 

                $this->fieldList_contrato_cliente_items = $this->loadItems('ContratoPessoa', 'contrato_id', $object, $this->fieldList_contrato_cliente, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_contrato_cliente); 

                $this->form->setData($object); // fill the form 

                $this->fireEvents($object);

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

        $this->fieldList_contrato_cliente->addHeader();
        $this->fieldList_contrato_cliente->addDetail($this->default_item_fieldList_contrato_cliente);

        $this->fieldList_contrato_cliente->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_representantes->addHeader();
        $this->fieldList_representantes->addDetail($this->default_item_fieldList_representantes);

        $this->fieldList_representantes->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_contrato_profissional->addHeader();
        $this->fieldList_contrato_profissional->addDetail($this->default_item_fieldList_contrato_profissional);

        $this->fieldList_contrato_profissional->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_contrato_cliente->addHeader();
        $this->fieldList_contrato_cliente->addDetail($this->default_item_fieldList_contrato_cliente);

        $this->fieldList_contrato_cliente->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_representantes->addHeader();
        $this->fieldList_representantes->addDetail($this->default_item_fieldList_representantes);

        $this->fieldList_representantes->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_contrato_profissional->addHeader();
        $this->fieldList_contrato_profissional->addDetail($this->default_item_fieldList_contrato_profissional);

        $this->fieldList_contrato_profissional->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

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
        }
        TForm::sendData(self::$formName, $obj);
    }  

    public static function getFormName()
    {
        return self::$formName;
    }

    private static function gerarProximoNumeroContrato($ano)
    {
        $ano = (int) $ano;

        $conn = TTransaction::get();

        $conn->exec('LOCK TABLE contrato IN SHARE ROW EXCLUSIVE MODE');

        $sql = "
            SELECT 
                COALESCE(MAX(CAST(split_part(numero, '/', 1) AS INTEGER)), 0) + 1 AS proximo
            FROM contrato
            WHERE numero IS NOT NULL
            AND numero LIKE :ano
            AND numero <> :zero
            AND numero ~ '^[0-9]+/[0-9]{4}$'
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ano'  => '%/' . $ano,
            ':zero' => '0000000/' . $ano
        ]);

        $proximo = (int) $stmt->fetchColumn();

        return str_pad($proximo, 7, '0', STR_PAD_LEFT) . '/' . $ano;
    }

    public function onSalvarContratoPendente($param = null)
    {
        try
        {
            $dadosContrato = TSession::getValue('contrato_save_param_pendente');
            $anoReferencia = TSession::getValue('contrato_save_ano_pendente');

            if (empty($dadosContrato) || !is_array($dadosContrato)) {
                throw new Exception('Dados temporários do contrato não encontrados.');
            }

            if (empty($anoReferencia)) {
                throw new Exception('Ano de referência do contrato não encontrado.');
            }

            $dadosContrato['ano_referencia_contrato'] = $anoReferencia;
            $dadosContrato['_salvar_pacote_contrato'] = 1;

            $this->onSave($dadosContrato);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    private static function salvarFieldListsContratoManual($contrato, $dados)
    {
        if (empty($contrato->id)) {
            throw new Exception('Contrato não encontrado para salvar os vínculos.');
        }

        self::limparDetalhesContrato('ContratoPessoa', $contrato->id);
        self::limparDetalhesContrato('ContratoRepresentante', $contrato->id);
        self::limparDetalhesContrato('ContratoRepasse', $contrato->id);

        self::salvarClientesContratoManual($contrato, $dados);
        self::salvarRepresentantesContratoManual($contrato, $dados);
        self::salvarRepassesContratoManual($contrato, $dados);
    }

    private static function limparDetalhesContrato($classe, $contratoId)
    {
        $criteria = new TCriteria();
        $criteria->add(new TFilter('contrato_id', '=', $contratoId));

        $repository = new TRepository($classe);
        $repository->delete($criteria);
    }

    private static function salvarClientesContratoManual($contrato, $dados)
    {
        $clientes = self::campoArray($dados, 'contrato_pessoa_contrato_cliente_id');
        $percentuais = self::campoArray($dados, 'contrato_pessoa_contrato_percentual');

        foreach ($clientes as $i => $clienteId) {
            $clienteId = trim((string) $clienteId);

            if ($clienteId === '') {
                continue;
            }

            $item = new ContratoPessoa();
            $item->contrato_id = $contrato->id;
            $item->cliente_id = (int) $clienteId;
            $item->percentual = self::normalizarNumero($percentuais[$i] ?? null);
            $item->store();
        }
    }

    private static function salvarRepresentantesContratoManual($contrato, $dados)
    {
        $representantes = self::campoArray($dados, 'contrato_representante_contrato_representante_id');

        foreach ($representantes as $representanteId) {
            $representanteId = trim((string) $representanteId);

            if ($representanteId === '') {
                continue;
            }

            $item = new ContratoRepresentante();
            $item->contrato_id = $contrato->id;
            $item->representante_id = (int) $representanteId;
            $item->store();
        }
    }

    private static function salvarRepassesContratoManual($contrato, $dados)
    {
        $pessoas = self::campoArray($dados, 'contrato_repasse_contrato_pessoa_id');
        $percentuais = self::campoArray($dados, 'contrato_repasse_contrato_percentual');

        foreach ($pessoas as $i => $pessoaId) {
            $pessoaId = trim((string) $pessoaId);

            if ($pessoaId === '') {
                continue;
            }

            $item = new ContratoRepasse();
            $item->contrato_id = $contrato->id;
            $item->pessoa_id = (int) $pessoaId;
            $item->percentual = self::normalizarNumero($percentuais[$i] ?? null);
            $item->store();
        }
    }

    private static function campoArray($dados, $campo)
    {
        if (!isset($dados->{$campo})) {
            return [];
        }

        $valor = $dados->{$campo};

        if (is_array($valor)) {
            return array_values($valor);
        }

        if ($valor === null || $valor === '') {
            return [];
        }

        return [$valor];
    }

    private static function normalizarNumero($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = trim((string) $valor);
        $valor = str_replace('%', '', $valor);
        $valor = str_replace(' ', '', $valor);

        if (strpos($valor, ',') !== false) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    }

    private static function somarPercentuais($valores)
    {
        if (empty($valores)) {
            return 0;
        }

        if (!is_array($valores)) {
            $valores = [$valores];
        }

        $total = 0;

        foreach ($valores as $valor) {
            if ($valor === null || trim((string) $valor) === '') {
                continue;
            }

            $total += self::normalizarNumero($valor);
        }

        return round($total, 4);
    }

}

