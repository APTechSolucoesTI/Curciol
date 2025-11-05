<?php

class FornecedorForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'form_Pessoa';

    use BuilderMasterDetailTrait;

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
        $this->form->setFormTitle("Cadastro de fornecedor");

        $criteria_tipo_pessoa_id = new TCriteria();
        $criteria_nacionalidade_id = new TCriteria();
        $criteria_sexo_id = new TCriteria();
        $criteria_estado_civil_id = new TCriteria();
        $criteria_pessoa_endereco_pessoa_cidade_id = new TCriteria();

        $id = new TEntry('id');
        $tipo_pessoa_id = new TDBCombo('tipo_pessoa_id', 'escritorio', 'TipoPessoa', 'id', '{nome}','nome asc' , $criteria_tipo_pessoa_id );
        $btnBuscarCNPJ = new TButton('btnBuscarCNPJ');
        $nome = new TEntry('nome');
        $btnVerificarNome = new TButton('btnVerificarNome');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $dt_nascimento_abertura = new TDate('dt_nascimento_abertura');
        $cpf_cnpj = new TEntry('cpf_cnpj');
        $rg_ie = new TEntry('rg_ie');
        $orgao_emissor = new TEntry('orgao_emissor');
        $dt_falecimento = new TDate('dt_falecimento');
        $nacionalidade_id = new TDBCombo('nacionalidade_id', 'escritorio', 'Nacionalidade', 'id', '{nome}','id asc' , $criteria_nacionalidade_id );
        $sexo_id = new TDBCombo('sexo_id', 'escritorio', 'Sexo', 'id', '{nome}','id asc' , $criteria_sexo_id );
        $estado_civil_id = new TDBCombo('estado_civil_id', 'escritorio', 'EstadoCivil', 'id', '{nome}','id asc' , $criteria_estado_civil_id );
        $observacao = new THtmlEditor('observacao');
        $pessoa_endereco_pessoa_cep = new TEntry('pessoa_endereco_pessoa_cep');
        $button_buscar_pessoa_endereco_pessoa = new TButton('button_buscar_pessoa_endereco_pessoa');
        $pessoa_endereco_pessoa_cidade_id = new TDBUniqueSearch('pessoa_endereco_pessoa_cidade_id', 'escritorio', 'Cidade', 'id', 'nome','nome asc' , $criteria_pessoa_endereco_pessoa_cidade_id );
        $pessoa_endereco_pessoa_id = new THidden('pessoa_endereco_pessoa_id');
        $pessoa_endereco_pessoa_bairro = new TEntry('pessoa_endereco_pessoa_bairro');
        $pessoa_endereco_pessoa_rua = new TEntry('pessoa_endereco_pessoa_rua');
        $pessoa_endereco_pessoa_numero = new TEntry('pessoa_endereco_pessoa_numero');
        $pessoa_endereco_pessoa_complemento = new TEntry('pessoa_endereco_pessoa_complemento');
        $pessoa_endereco_pessoa_principal = new TCheckButton('pessoa_endereco_pessoa_principal');
        $button_adicionar_pessoa_endereco_pessoa = new TButton('button_adicionar_pessoa_endereco_pessoa');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $tipo_pessoa_id->setChangeAction(new TAction([$this,'onLoadDocuments']));

        $email->setExitAction(new TAction([$this,'onChange']));

        $tipo_pessoa_id->addValidation("Tipo de pessoa", new TRequiredValidator()); 
        $nome->addValidation("Nome", new TRequiredValidator()); 

        $tipo_pessoa_id->setDefaultOption(false);
        $tipo_pessoa_id->setValue(TipoPessoa::FISICA);
        $nome->setTip("Nome do fornecedor");
        $nome->forceUpperCase();
        $email->forceLowerCase();
        $pessoa_endereco_pessoa_cidade_id->setMinLength(3);
        $pessoa_endereco_pessoa_principal->setIndexValue("S");
        $pessoa_endereco_pessoa_principal->setInactiveIndexValue("N");
        $sexo_id->enableSearch();
        $tipo_pessoa_id->enableSearch();
        $estado_civil_id->enableSearch();
        $nacionalidade_id->enableSearch();

        $btnVerificarNome->setAction(new TAction([$this, 'onChangeNome'],['static' => 1]), "");
        $button_buscar_pessoa_endereco_pessoa->setAction(new TAction([$this, 'onSearchCep'],['static' => 1]), "Buscar");
        $button_adicionar_pessoa_endereco_pessoa->setAction(new TAction([$this, 'onAddDetailPessoaEnderecoPessoa'],['static' => 1]), "Adicionar");
        $btnBuscarCNPJ->setAction(new TAction(['ModalBuscarCNPJ', 'onShow'],['campo' => '["nome", "telefone" ,"cpf_cnpj", "pessoa_endereco_pessoa_cep" ,"pessoa_endereco_pessoa_cidade_id", "pessoa_endereco_pessoa_id", "pessoa_endereco_pessoa_bairro", "pessoa_endereco_pessoa_rua" ,"pessoa_endereco_pessoa_numero" ,"pessoa_endereco_pessoa_complemento"]',"form" => self::$formName]), "Buscar");

        $btnBuscarCNPJ->addStyleClass('btn-default');
        $btnVerificarNome->addStyleClass('btn-success');
        $button_buscar_pessoa_endereco_pessoa->addStyleClass('btn-default');
        $button_adicionar_pessoa_endereco_pessoa->addStyleClass('btn-default');

        $btnBuscarCNPJ->setImage('fas:search #000000');
        $btnVerificarNome->setImage('fas:check #FFFFFF');
        $button_buscar_pessoa_endereco_pessoa->setImage('fas:search #2196F3');
        $button_adicionar_pessoa_endereco_pessoa->setImage('fas:plus #2ecc71');

        $dt_falecimento->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $dt_nascimento_abertura->setDatabaseMask('yyyy-mm-dd');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $rg_ie->setMask('##.###.###-A');
        $dt_falecimento->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $dt_nascimento_abertura->setMask('dd/mm/yyyy');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $pessoa_endereco_pessoa_cep->setMask('99999-999', true);
        $pessoa_endereco_pessoa_cidade_id->setMask('{nome} - {estado->sigla}');

        $nome->setMaxLength(255);
        $email->setMaxLength(255);
        $telefone->setMaxLength(20);
        $pessoa_endereco_pessoa_cep->setMaxLength(8);
        $pessoa_endereco_pessoa_rua->setMaxLength(500);
        $pessoa_endereco_pessoa_bairro->setMaxLength(500);
        $pessoa_endereco_pessoa_numero->setMaxLength(100);
        $pessoa_endereco_pessoa_complemento->setMaxLength(500);

        $id->setSize('100%');
        $email->setSize('100%');
        $rg_ie->setSize('100%');
        $sexo_id->setSize('100%');
        $telefone->setSize('100%');
        $cpf_cnpj->setSize('100%');
        $data_criacao->setSize('100%');
        $orgao_emissor->setSize('100%');
        $dt_falecimento->setSize('100%');
        $estado_civil_id->setSize('100%');
        $observacao->setSize('100%', 150);
        $nacionalidade_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $nome->setSize('calc(100% - 50px)');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $dt_nascimento_abertura->setSize('100%');
        $pessoa_endereco_pessoa_id->setSize(200);
        $pessoa_endereco_pessoa_rua->setSize('100%');
        $tipo_pessoa_id->setSize('calc(100% - 100px)');
        $pessoa_endereco_pessoa_bairro->setSize('100%');
        $pessoa_endereco_pessoa_numero->setSize('100%');
        $pessoa_endereco_pessoa_cidade_id->setSize('100%');
        $pessoa_endereco_pessoa_complemento->setSize('100%');
        $pessoa_endereco_pessoa_cep->setSize('calc(100% - 120px)');

        $button_adicionar_pessoa_endereco_pessoa->id = '60f6c58143f89';

        $btnVerificarNome->{'title'} = "Verificar homônimo";
        $email->addValidation("E-mail", new TEmailValidator()); 
        TScript::create(
            "$(document).on('keydown', 'input[name=\"telefone\"]', function (e) {
            var digit = e.key.replace(/\D/g, '');
            var value = $(this).val().replace(/\D/g, '');
            var size = value.concat(digit).length;
            $(this).mask((size <= 10) ? '(##) ####-####' : '(##) #####-####');
            });"
        );

        $this->form->appendPage("Dados cadastrais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '12px', null, '100%'),$id],[],[]);
        $row1->layout = [' col-sm-2',' col-sm-3',' col-sm-7'];

        $row2 = $this->form->addFields([new TLabel("Tipo de pessoa:", '#FF0000', '12px', null, '100%'),$tipo_pessoa_id,$btnBuscarCNPJ],[new TLabel("Nome:", '#ff0000', '12px', null, '100%'),$nome,$btnVerificarNome]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Telefone:", null, '12px', null, '100%'),$telefone],[new TLabel("Email:", null, '12px', null, '100%'),$email]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row5 = $this->form->addFields([new TLabel("Data de nascimento:", null, '12px', null, '100%'),$dt_nascimento_abertura],[new TLabel("CPF:", null, '12px', null, '100%'),$cpf_cnpj],[new TLabel("RG:", null, '12px', null, '100%'),$rg_ie],[new TLabel("Órgão emissor:", '#000000', '12px', null, '100%'),$orgao_emissor]);
        $row5->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row6 = $this->form->addFields([new TLabel("Data de falecimento:", '#000000', '12px', null, '100%'),$dt_falecimento],[new TLabel("Nacionalidade:", '#000000', '12px', null, '100%'),$nacionalidade_id],[new TLabel("Sexo:", '#000000', '12px', null, '100%'),$sexo_id],[new TLabel("Estado civil:", '#000000', '12px', null, '100%'),$estado_civil_id]);
        $row6->layout = ['col-sm-3','col-sm-3','col-sm-3','col-sm-3'];

        $row7 = $this->form->addFields([new TLabel("Observação:", null, '12px', null, '100%'),$observacao]);
        $row7->layout = [' col-sm-12'];

        $this->form->appendPage("Endereços");

        $this->detailFormPessoaEnderecoPessoa = new BootstrapFormBuilder('detailFormPessoaEnderecoPessoa');
        $this->detailFormPessoaEnderecoPessoa->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormPessoaEnderecoPessoa->setProperty('class', 'form-horizontal builder-detail-form');

        $row8 = $this->detailFormPessoaEnderecoPessoa->addFields([new TLabel("CEP:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_cep,$button_buscar_pessoa_endereco_pessoa],[new TLabel("Cidade:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_cidade_id,$pessoa_endereco_pessoa_id]);
        $row8->layout = [' col-sm-4',' col-sm-8'];

        $row9 = $this->detailFormPessoaEnderecoPessoa->addFields([new TLabel("Bairro:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_bairro],[new TLabel("Rua:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_rua]);
        $row9->layout = [' col-sm-4',' col-sm-8'];

        $row10 = $this->detailFormPessoaEnderecoPessoa->addFields([new TLabel("Número:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_numero],[new TLabel("Complemento:", null, '12px', null, '100%'),$pessoa_endereco_pessoa_complemento],[new TLabel("Principal:", null, '12px', null, '100%'),$pessoa_endereco_pessoa_principal]);
        $row10->layout = ['col-sm-4',' col-sm-5',' col-sm-3'];

        $row11 = $this->detailFormPessoaEnderecoPessoa->addFields([$button_adicionar_pessoa_endereco_pessoa]);
        $row11->layout = [' col-sm-12'];

        $row12 = $this->detailFormPessoaEnderecoPessoa->addFields([new THidden('pessoa_endereco_pessoa__row__id')]);
        $this->pessoa_endereco_pessoa_criteria = new TCriteria();

        $this->pessoa_endereco_pessoa_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->pessoa_endereco_pessoa_list->generateHiddenFields();
        $this->pessoa_endereco_pessoa_list->setId('pessoa_endereco_pessoa_list');
        $this->pessoa_endereco_pessoa_list->datatable = 'true';

        $this->pessoa_endereco_pessoa_list->style = 'width:100%';
        $this->pessoa_endereco_pessoa_list->class .= ' table-bordered';

        $column_pessoa_endereco_pessoa_cidade_nome = new TDataGridColumn('cidade->nome', "Cidade", 'left');
        $column_pessoa_endereco_pessoa_cep_transformed = new TDataGridColumn('cep', "CEP", 'left');
        $column_pessoa_endereco_pessoa_rua = new TDataGridColumn('rua', "Rua", 'left');
        $column_pessoa_endereco_pessoa_bairro = new TDataGridColumn('bairro', "Bairro", 'left');
        $column_pessoa_endereco_pessoa_numero = new TDataGridColumn('numero', "Número", 'left');
        $column_pessoa_endereco_pessoa_complemento = new TDataGridColumn('complemento', "Complemento", 'left');
        $column_pessoa_endereco_pessoa_principal_transformed = new TDataGridColumn('principal', "Principal", 'left');

        $column_pessoa_endereco_pessoa__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_pessoa_endereco_pessoa__row__data->setVisibility(false);

        $action_onEditDetailPessoaEndereco = new TDataGridAction(array('FornecedorForm', 'onEditDetailPessoaEndereco'));
        $action_onEditDetailPessoaEndereco->setUseButton(false);
        $action_onEditDetailPessoaEndereco->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailPessoaEndereco->setLabel("Editar");
        $action_onEditDetailPessoaEndereco->setImage('far:edit #478fca');
        $action_onEditDetailPessoaEndereco->setFields(['__row__id', '__row__data']);

        $this->pessoa_endereco_pessoa_list->addAction($action_onEditDetailPessoaEndereco);
        $action_onDeleteDetailPessoaEndereco = new TDataGridAction(array('FornecedorForm', 'onDeleteDetailPessoaEndereco'));
        $action_onDeleteDetailPessoaEndereco->setUseButton(false);
        $action_onDeleteDetailPessoaEndereco->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailPessoaEndereco->setLabel("Excluir");
        $action_onDeleteDetailPessoaEndereco->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailPessoaEndereco->setFields(['__row__id', '__row__data']);

        $this->pessoa_endereco_pessoa_list->addAction($action_onDeleteDetailPessoaEndereco);

        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa_cidade_nome);
        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa_cep_transformed);
        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa_rua);
        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa_bairro);
        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa_numero);
        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa_complemento);
        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa_principal_transformed);

        $this->pessoa_endereco_pessoa_list->addColumn($column_pessoa_endereco_pessoa__row__data);

        $this->pessoa_endereco_pessoa_list->createModel();
        $this->detailFormPessoaEnderecoPessoa->addContent([$this->pessoa_endereco_pessoa_list]);

        $column_pessoa_endereco_pessoa_cep_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            return $cep = substr($value,0,-3)."-".substr($value,-3);

        });

        $column_pessoa_endereco_pessoa_principal_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        });        $row13 = $this->form->addFields([$this->detailFormPessoaEnderecoPessoa]);
        $row13->layout = [' col-sm-12'];

        $this->form->appendPage("Informações de cadastro");
        $row14 = $this->form->addFields([new TLabel("Criado em:", null, '12px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '12px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '12px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '12px', null, '100%'),$modificacao_user_name]);
        $row14->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btnSave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btnSave = $btnSave;
        $btnSave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['FornecedorList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        $btn_ondelete = $this->form->addHeaderAction("Excluir", new TAction([$this, 'onDelete']), 'fas:trash-alt #FF0000');
        $this->btn_ondelete = $btn_ondelete;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        $btnSave->getAction()->setParameter('origin', $param['origin']??'');

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=FornecedorForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChange($param = null) 
    {
        try 
        {

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onLoadDocuments($param = null) 
    {
        try 
        {
            ClienteForm::getTipoPessoaSelected($param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeNome($param = null) 
    {
        try 
        {
            if(empty($param['id']) && !empty($param['nome'])){
                TTransaction::open(self::$database);
                $count = Pessoa::where('nome',  '=', $param['nome'])
                          ->count();

                if($count>0){
                    $objeto = new Pessoa;
                    $objeto = Pessoa::where('nome',  '=', $param['nome'])->first();
                    $pageParam['id'] = $objeto->id;
                    $pageParam['nova_pessoa_grupo'] = Grupo::FORNECEDOR;

                    // Código gerado pelo snippet: "Questionamento"
                    new TQuestion("Nome já cadastrado, deseja visualizar?", new TAction([__CLASS__, 'onVisualizarCadastrado'], $pageParam), new TAction([__CLASS__, 'onContinuarCadastro'], $param));
                    // -----
                }else{
                    TScript::create('$("button[name=\'btnVerificarNome\'").attr("disabled", true);');
                    TToast::show("success", "Nome não cadastrado", "bottomRight", "fas:check");
                    TScript::create('$("input[name=\'telefone\']").focus();');
                }

                TTransaction::close();

            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onSearchCep($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $dadosCEP = CEPService::get($param['pessoa_endereco_pessoa_cep']);
            TTransaction::close();

            if($dadosCEP)
            {
                $data = new stdClass;
                $data->pessoa_endereco_pessoa_cidade_id = $dadosCEP->cidade_id;
                $data->pessoa_endereco_pessoa_bairro = $dadosCEP->bairro;
                $data->pessoa_endereco_pessoa_rua = $dadosCEP->rua;    
                TForm::sendData(self::$formName, $data);
            }
            else
            {
                throw new Exception('CEP não encontrado');
            }

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onAddDetailPessoaEnderecoPessoa($param = null) 
    {
        try
        {
            $data = $this->form->getData();

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"CEP", 'name'=>"pessoa_endereco_pessoa_cep", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Cidade", 'name'=>"pessoa_endereco_pessoa_cidade_id", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Bairro", 'name'=>"pessoa_endereco_pessoa_bairro", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Rua", 'name'=>"pessoa_endereco_pessoa_rua", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Número", 'name'=>"pessoa_endereco_pessoa_numero", 'class'=>'TRequiredValidator', 'value'=>[]];
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

            $__row__id = !empty($data->pessoa_endereco_pessoa__row__id) ? $data->pessoa_endereco_pessoa__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new PessoaEndereco();
            $grid_data->__row__id = $__row__id;
            $grid_data->cep = $data->pessoa_endereco_pessoa_cep;
            $grid_data->cidade_id = $data->pessoa_endereco_pessoa_cidade_id;
            $grid_data->id = $data->pessoa_endereco_pessoa_id;
            $grid_data->bairro = $data->pessoa_endereco_pessoa_bairro;
            $grid_data->rua = $data->pessoa_endereco_pessoa_rua;
            $grid_data->numero = $data->pessoa_endereco_pessoa_numero;
            $grid_data->complemento = $data->pessoa_endereco_pessoa_complemento;
            $grid_data->principal = $data->pessoa_endereco_pessoa_principal;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['cep'] =  $param['pessoa_endereco_pessoa_cep'] ?? null;
            $__row__data['__display__']['cidade_id'] =  $param['pessoa_endereco_pessoa_cidade_id'] ?? null;
            $__row__data['__display__']['id'] =  $param['pessoa_endereco_pessoa_id'] ?? null;
            $__row__data['__display__']['bairro'] =  $param['pessoa_endereco_pessoa_bairro'] ?? null;
            $__row__data['__display__']['rua'] =  $param['pessoa_endereco_pessoa_rua'] ?? null;
            $__row__data['__display__']['numero'] =  $param['pessoa_endereco_pessoa_numero'] ?? null;
            $__row__data['__display__']['complemento'] =  $param['pessoa_endereco_pessoa_complemento'] ?? null;
            $__row__data['__display__']['principal'] =  $param['pessoa_endereco_pessoa_principal'] ?? null;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->pessoa_endereco_pessoa_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('pessoa_endereco_pessoa_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->pessoa_endereco_pessoa_cep = '';
            $data->pessoa_endereco_pessoa_cidade_id = '';
            $data->pessoa_endereco_pessoa_id = '';
            $data->pessoa_endereco_pessoa_bairro = '';
            $data->pessoa_endereco_pessoa_rua = '';
            $data->pessoa_endereco_pessoa_numero = '';
            $data->pessoa_endereco_pessoa_complemento = '';
            $data->pessoa_endereco_pessoa_principal = '';
            $data->pessoa_endereco_pessoa__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#60f6c58143f89');
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

    public static function onEditDetailPessoaEndereco($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->pessoa_endereco_pessoa_cep = $__row__data->__display__->cep ?? null;
            $data->pessoa_endereco_pessoa_cidade_id = $__row__data->__display__->cidade_id ?? null;
            $data->pessoa_endereco_pessoa_id = $__row__data->__display__->id ?? null;
            $data->pessoa_endereco_pessoa_bairro = $__row__data->__display__->bairro ?? null;
            $data->pessoa_endereco_pessoa_rua = $__row__data->__display__->rua ?? null;
            $data->pessoa_endereco_pessoa_numero = $__row__data->__display__->numero ?? null;
            $data->pessoa_endereco_pessoa_complemento = $__row__data->__display__->complemento ?? null;
            $data->pessoa_endereco_pessoa_principal = $__row__data->__display__->principal ?? null;
            $data->pessoa_endereco_pessoa__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#60f6c58143f89');
               if(!element.attr('add')){
                   element.attr('add', base64_encode(element.html()));
               }
               element.html(\"<span><i class='fas fa-save' style='color:#000000;padding-right:4px;'></i>Salvar</span>\");
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
    public static function onDeleteDetailPessoaEndereco($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->pessoa_endereco_pessoa_cep = '';
            $data->pessoa_endereco_pessoa_cidade_id = '';
            $data->pessoa_endereco_pessoa_id = '';
            $data->pessoa_endereco_pessoa_bairro = '';
            $data->pessoa_endereco_pessoa_rua = '';
            $data->pessoa_endereco_pessoa_numero = '';
            $data->pessoa_endereco_pessoa_complemento = '';
            $data->pessoa_endereco_pessoa_principal = '';
            $data->pessoa_endereco_pessoa__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('pessoa_endereco_pessoa_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#60f6c58143f89');
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

            $object = new Pessoa(); // create an empty object 

            $data = $this->form->getData(); // get form data as array

            $data->telefone = preg_replace('/[^0-9]/', '', $data->telefone);
            $data->cpf_cnpj = preg_replace('/[^0-9]/', '', $data->cpf_cnpj);
            $data->rg_ie    = preg_replace('/[^0-9]/', '', $data->rg_ie);

            $idAux = $data->id ?? -1;
            //CPF/CNPJ
            if($data->cpf_cnpj){
                $countCPF_CNPJ = Pessoa::where('cpf_cnpj', '=', $data->cpf_cnpj)
                                        ->where('id','!=',(int) $idAux)
                                        ->count();
                $searchCPF_CNPJ = Pessoa::where('cpf_cnpj', '=', $data->cpf_cnpj)
                                        ->where('id','!=',(int) $idAux)
                                        ->first();
                if($countCPF_CNPJ>0 && $data->tipo_pessoa_id==TipoPessoa::FISICA){
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::FORNECEDOR)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){
                        new TQuestion("Este CPF já está cadastrado como fornecedor. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditarFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Este CPF já está cadastrado. Deseja adicionar como fornecedor?", new TAction([__CLASS__, 'onAdicionarGrupoFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }
                }elseif($countCPF_CNPJ>0 && $data->tipo_pessoa_id==TipoPessoa::JURIDICA){
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::FORNECEDOR)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){
                        new TQuestion("Este CNPJ já está cadastrado como fornecedor. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditarFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Este CNPJ já está cadastrado. Deseja adicionar como fornecedor?", new TAction([__CLASS__, 'onAdicionarGrupoFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }
                }
            }
            //RG/IE
            if($data->rg_ie){
                $countCPF_CNPJ = Pessoa::where('rg_ie', '=', $data->rg_ie)
                                        ->where('id','!=',(int) $idAux)
                                        ->count();
                $searchCPF_CNPJ = Pessoa::where('rg_ie', '=', $data->rg_ie)
                                        ->where('id','!=',(int) $idAux)
                                        ->first();
                if($countCPF_CNPJ>0 && $data->tipo_pessoa_id==TipoPessoa::FISICA){
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::FORNECEDOR)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){
                        new TQuestion("Este RG já está cadastrado como fornecedor. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditarFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Este RG já está cadastrado. Deseja adicionar como fornecedor?", new TAction([__CLASS__, 'onAdicionarGrupoFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }
                }elseif($countCPF_CNPJ>0 && $data->tipo_pessoa_id==TipoPessoa::JURIDICA){
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::FORNECEDOR)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){
                        new TQuestion("Esta Inscrição Estadual já está cadastrada como fornecedor. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditarFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Esta Inscrição Estadual já está cadastrada. Deseja adicionar como fornecedor?", new TAction([__CLASS__, 'onAdicionarGrupoFornecedor'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }
                }
            }

            if($data->cpf_cnpj){
                if($data->tipo_pessoa_id && $data->tipo_pessoa_id==TipoPessoa::FISICA){
                    (new TCPFValidator())->validate('CPF', $data->cpf_cnpj);
                }elseif ($data->tipo_pessoa_id && $data->tipo_pessoa_id==TipoPessoa::JURIDICA) {
                    (new TCNPJValidator())->validate('CNPJ', $data->cpf_cnpj);
                }
            }

            $object->fromArray( (array) $data); // load the object with data

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            $object->store(); // save the object 

            TForm::sendData(self::$formName, (object)['id' => $object->id]);

            if(isset($param['pessoa_endereco_pessoa_list___row__data'])){
                $quantEnderecos = count($param['pessoa_endereco_pessoa_list___row__data']);
                $quantValores = array_count_values($param['pessoa_endereco_pessoa_list_principal']);

                if(array_key_exists('S', $quantValores)){
                    if($quantValores['S']!=1){
                        throw new Exception("Selecione apenas um endereço como principal!");
                    }
                }else{
                    throw new Exception("Selecione algum endereço como principal!");
                }
            }

            $pessoa_endereco_pessoa_items = $this->storeMasterDetailItems('PessoaEndereco', 'pessoa_id', 'pessoa_endereco_pessoa', $object, $param['pessoa_endereco_pessoa_list___row__data'] ?? [], $this->form, $this->pessoa_endereco_pessoa_list, function($masterObject, $detailObject){ 

                //code here

            }, $this->pessoa_endereco_pessoa_criteria); 

            $object->system_users_id = null;

            if(!$data->id)
            {
                $pessoaGrupo = new PessoaGrupo();
                $pessoaGrupo->pessoa_id = $object->id;
                $pessoaGrupo->grupo_id = Grupo::FORNECEDOR;
                $pessoaGrupo->store();    
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

            if(isset($param['page'])){
                TApplication::loadPage('FornecedorList', 'onShow');
            }
                        TScript::create("Template.closeRightPanel();"); 

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
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
                $object = new Pessoa($key, FALSE); 

                $qtdeGrupos = PessoaGrupo::where('pessoa_id', '=', $object->id)->count();

                if ($object->loadComposite('Conta', 'pessoa_id', $object->id)) {
                    throw new Exception("Esse fornecedor possui contas vinculadas e não pode ser removido!");
                }

                if($qtdeGrupos>1){
                    $grupoExcluir = PessoaGrupo::where('pessoa_id', '=', $object->id)
                                                ->where('grupo_id', '=', Grupo::FORNECEDOR);
                    $grupoExcluir->delete();
                }else{
                    // deletes the object from the database
                    $object->deleteComposite('PessoaGrupo', 'pessoa_id', $object->id);
                    $object->deleteComposite('PessoaEndereco', 'pessoa_id', $object->id); 
                    $object->delete();
                }

                // close the transaction
                TTransaction::close();

                TApplication::loadPage('FornecedorList', 'onShow');
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

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Pessoa($key); // instantiates the Active Record 

                $param['tipo_pessoa_id']=$object->tipo_pessoa_id;
                ClienteForm::getTipoPessoaSelected($param);
                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $pessoa_endereco_pessoa_items = $this->loadMasterDetailItems('PessoaEndereco', 'pessoa_id', 'pessoa_endereco_pessoa', $object, $this->form, $this->pessoa_endereco_pessoa_list, $this->pessoa_endereco_pessoa_criteria, function($masterObject, $detailObject, $objectItems){ 

                }); 

                $this->form->setData($object); // fill the form 

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

    }

    public function onShow($param = null)
    {

        TScript::create('$("button[name=\'btnBuscarCNPJ\'").attr("disabled", true);');

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    public static function onVisualizarCadastrado($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $pageParam['key']=$param['id'];
            $pageParam['nova_pessoa_grupo']=$param['nova_pessoa_grupo'];
            $objeto = Grupo::find( $param['nova_pessoa_grupo'] );
            $pageParam['nova_pessoa_grupo_nome']=$objeto->nome;

            TSession::setValue('nova_pessoa_grupo', $param['nova_pessoa_grupo']);

            TApplication::loadPage('PessoaConsultaFormView', 'onShow', $pageParam);

            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onContinuarCadastro($param = null) 
    {
        try 
        {
            //code here
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onAdicionarGrupoFornecedor($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database); 
            $objeto = new PessoaGrupo();
            $objeto->pessoa_id = $param['pessoa_id'];
            $objeto->grupo_id = Grupo::FORNECEDOR;
            $objeto->store();
            TApplication::loadPage('FornecedorList', 'onShow');
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onEditarFornecedor($param = null) 
    {
        try 
        {
            $pageParam = ['key' => $param['pessoa_id']]; // ex.: = ['key' => 10]
            TApplication::loadPage('FornecedorForm', 'onEdit', $pageParam);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function btnNaoTQuestion($param = null) 
    {
        try 
        {
            //code here
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

