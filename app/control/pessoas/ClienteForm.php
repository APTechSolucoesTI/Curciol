<?php

class ClienteForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'form_Pessoa';

    use BuilderMasterDetailTrait;
    use Adianti\Base\AdiantiFileSaveTrait;

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
        $this->form->setFormTitle("Cadastro de cliente");

        $criteria_tipo_pessoa_id = new TCriteria();
        $criteria_nacionalidade_id = new TCriteria();
        $criteria_sexo_id = new TCriteria();
        $criteria_estado_civil_id = new TCriteria();
        $criteria_situacao_profissional_id = new TCriteria();
        $criteria_classificacoes_cliente_id = new TCriteria();
        $criteria_pessoa_endereco_pessoa_cidade_id = new TCriteria();
        $criteria_pessoa_representantes_legais_pessoa_juridica_representante_id = new TCriteria();

        $filterVar = TipoPessoa::FISICA;
        $criteria_pessoa_representantes_legais_pessoa_juridica_representante_id->add(new TFilter('tipo_pessoa_id', '=', $filterVar)); 

        $id = new TEntry('id');
        $aceita_receber_mensagen_whatsapp = new TCheckButton('aceita_receber_mensagen_whatsapp');
        $tipo_pessoa_id = new TDBCombo('tipo_pessoa_id', 'escritorio', 'TipoPessoa', 'id', '{nome}','id asc' , $criteria_tipo_pessoa_id );
        $btnBuscarCNPJ = new TButton('btnBuscarCNPJ');
        $nome = new TEntry('nome');
        $btnVerificarNome = new TButton('btnVerificarNome');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $usuario = new TEntry('usuario');
        $senha = new TEntry('senha');
        $foto = new TImageCapture('foto');
        $dt_nascimento_abertura = new TDate('dt_nascimento_abertura');
        $cpf_cnpj = new TEntry('cpf_cnpj');
        $rg_ie = new TEntry('rg_ie');
        $orgao_emissor = new TEntry('orgao_emissor');
        $dt_falecimento = new TDate('dt_falecimento');
        $nacionalidade_id = new TDBCombo('nacionalidade_id', 'escritorio', 'Nacionalidade', 'id', '{nome}','id asc' , $criteria_nacionalidade_id );
        $sexo_id = new TDBCombo('sexo_id', 'escritorio', 'Sexo', 'id', '{nome}','id asc' , $criteria_sexo_id );
        $estado_civil_id = new TDBCombo('estado_civil_id', 'escritorio', 'EstadoCivil', 'id', '{nome}','id asc' , $criteria_estado_civil_id );
        $profissao = new TEntry('profissao');
        $nit = new TEntry('nit');
        $ctps = new TEntry('ctps');
        $situacao_profissional_id = new TDBCombo('situacao_profissional_id', 'escritorio', 'SituacaoProfissional', 'id', '{nome}','nome asc' , $criteria_situacao_profissional_id );
        $orgao = new TEntry('orgao');
        $unidade = new TEntry('unidade');
        $classificacoes_cliente_id = new TDBCheckGroup('classificacoes_cliente_id', 'escritorio', 'Classificacoes', 'id', '{nome}','nome asc' , $criteria_classificacoes_cliente_id );
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
        $pessoa_contato_pessoa_descricao = new TEntry('pessoa_contato_pessoa_descricao');
        $pessoa_contato_pessoa_id = new THidden('pessoa_contato_pessoa_id');
        $pessoa_contato_pessoa_telefone = new TEntry('pessoa_contato_pessoa_telefone');
        $pessoa_contato_pessoa_email = new TEntry('pessoa_contato_pessoa_email');
        $button_adicionar_pessoa_contato_pessoa = new TButton('button_adicionar_pessoa_contato_pessoa');
        $pessoa_representantes_legais_pessoa_juridica_descricao = new TEntry('pessoa_representantes_legais_pessoa_juridica_descricao');
        $pessoa_representantes_legais_pessoa_juridica_id = new THidden('pessoa_representantes_legais_pessoa_juridica_id');
        $pessoa_representantes_legais_pessoa_juridica_representante_id = new TDBUniqueSearch('pessoa_representantes_legais_pessoa_juridica_representante_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_pessoa_representantes_legais_pessoa_juridica_representante_id );
        $button_novo_pessoa_representantes_legais_pessoa_juridica = new TButton('button_novo_pessoa_representantes_legais_pessoa_juridica');
        $pessoa_representantes_legais_pessoa_juridica_principal = new TCheckButton('pessoa_representantes_legais_pessoa_juridica_principal');
        $btnRepresentanteAdicionar_pessoa_representantes_legais_pessoa_juridica = new TButton('btnRepresentanteAdicionar_pessoa_representantes_legais_pessoa_juridica');
        $contrato_cliente_list = new BPageContainer();
        $atendimento_historico = new BPageContainer();
        $processos = new BPageContainer();
        $tarefas_cliente = new BPageContainer();

        $tipo_pessoa_id->setChangeAction(new TAction([$this,'onLoadDocuments']));

        $email->setExitAction(new TAction([$this,'onChange']));

        $tipo_pessoa_id->addValidation("Tipo de pessoa", new TRequiredValidator()); 
        $nome->addValidation("Nome", new TRequiredValidator()); 
        $email->addValidation("Email", new TEmailValidator(), []); 

        $id->setEditable(false);
        $tipo_pessoa_id->setDefaultOption(false);
        $tipo_pessoa_id->setValue(TipoPessoa::FISICA);
        $foto->enableFileHandling();
        $foto->setAllowedExtensions(["jpg","jpeg","png","gif"]);
        $foto->setImagePlaceholder(new TImage("fas:camera #dde5ec"));
        $classificacoes_cliente_id->setLayout('horizontal');
        $classificacoes_cliente_id->setUseButton();
        $classificacoes_cliente_id->setBreakItems(4);
        $dt_falecimento->setDatabaseMask('yyyy-mm-dd');
        $dt_nascimento_abertura->setDatabaseMask('yyyy-mm-dd');

        $pessoa_endereco_pessoa_cidade_id->setMinLength(3);
        $pessoa_representantes_legais_pessoa_juridica_representante_id->setMinLength(3);

        $pessoa_endereco_pessoa_principal->setInactiveIndexValue("N");
        $pessoa_representantes_legais_pessoa_juridica_principal->setInactiveIndexValue("N");

        $processos->hide();
        $contrato_cliente_list->hide();

        $pessoa_endereco_pessoa_principal->setUseSwitch(true, 'blue');
        $aceita_receber_mensagen_whatsapp->setUseSwitch(true, 'green');
        $pessoa_representantes_legais_pessoa_juridica_principal->setUseSwitch(true, 'blue');

        $aceita_receber_mensagen_whatsapp->setIndexValue("T");
        $pessoa_endereco_pessoa_principal->setIndexValue("S");
        $pessoa_representantes_legais_pessoa_juridica_principal->setIndexValue("S");

        $nome->setTip("Nome do cliente");
        $pessoa_contato_pessoa_descricao->setTip("Casa, Escritório, Celular");
        $pessoa_representantes_legais_pessoa_juridica_descricao->setTip("Gerente, Sócio, Proprietário");

        $nome->forceUpperCase();
        $pessoa_contato_pessoa_descricao->forceUpperCase();
        $pessoa_representantes_legais_pessoa_juridica_descricao->forceUpperCase();

        $email->forceLowerCase();
        $profissao->forceLowerCase();
        $pessoa_contato_pessoa_email->forceLowerCase();

        $processos->setId('b65ce0c8b211e9');
        $tarefas_cliente->setId('b66bba646654e9');
        $contrato_cliente_list->setId('b65c262c8d1de1');
        $atendimento_historico->setId('b653bf04806304');

        $sexo_id->enableSearch();
        $tipo_pessoa_id->enableSearch();
        $estado_civil_id->enableSearch();
        $nacionalidade_id->enableSearch();
        $situacao_profissional_id->enableSearch();

        $dt_falecimento->setMask('dd/mm/yyyy');
        $dt_nascimento_abertura->setMask('dd/mm/yyyy');
        $pessoa_endereco_pessoa_cep->setMask('99999-999', true);
        $pessoa_endereco_pessoa_cidade_id->setMask('{nome} - {estado->sigla}');
        $pessoa_representantes_legais_pessoa_juridica_representante_id->setMask('{nome}');

        $btnBuscarCNPJ->addStyleClass('btn-default');
        $btnVerificarNome->addStyleClass('btn-success');
        $button_buscar_pessoa_endereco_pessoa->addStyleClass('btn-default');
        $button_adicionar_pessoa_contato_pessoa->addStyleClass('btn-default');
        $button_adicionar_pessoa_endereco_pessoa->addStyleClass('btn-default');
        $button_novo_pessoa_representantes_legais_pessoa_juridica->addStyleClass('btn-success');
        $btnRepresentanteAdicionar_pessoa_representantes_legais_pessoa_juridica->addStyleClass('btn-default');

        $btnBuscarCNPJ->setImage('fas:search #000000');
        $btnVerificarNome->setImage('fas:check #FFFFFF');
        $button_buscar_pessoa_endereco_pessoa->setImage('fas:search #2196F3');
        $button_adicionar_pessoa_contato_pessoa->setImage('fas:plus #2ecc71');
        $button_adicionar_pessoa_endereco_pessoa->setImage('fas:plus #2ecc71');
        $button_novo_pessoa_representantes_legais_pessoa_juridica->setImage('fas:user-plus #FFFFFF');
        $btnRepresentanteAdicionar_pessoa_representantes_legais_pessoa_juridica->setImage('fas:plus #2ecc71');

        $processos->setAction(new TAction(['ProcessoSimpleList', 'onShow']));
        $tarefas_cliente->setAction(new TAction(['ClienteTarefasSimpleList', 'onShow']));
        $contrato_cliente_list->setAction(new TAction(['ContratoClienteList', 'onShow']));
        $btnVerificarNome->setAction(new TAction([$this, 'onChangeNome'],['static' => 1]), "");
        $atendimento_historico->setAction(new TAction(['HistoricosAtendimentoCliente', 'onShow']));
        $button_buscar_pessoa_endereco_pessoa->setAction(new TAction([$this, 'onSearchCep'],['static' => 1]), "Buscar");
        $button_novo_pessoa_representantes_legais_pessoa_juridica->setAction(new TAction(['RepresentanteLegalForm', 'onShow']), "Novo");
        $button_adicionar_pessoa_contato_pessoa->setAction(new TAction([$this, 'onAddDetailPessoaContatoPessoa'],['static' => 1]), "Adicionar");
        $button_adicionar_pessoa_endereco_pessoa->setAction(new TAction([$this, 'onAddDetailPessoaEnderecoPessoa'],['static' => 1]), "Adicionar");
        $btnRepresentanteAdicionar_pessoa_representantes_legais_pessoa_juridica->setAction(new TAction([$this, 'onAddDetailPessoaRepresentantesLegaisPessoaJuridica'],['static' => 1]), "Adicionar");
        $btnBuscarCNPJ->setAction(new TAction(['ModalBuscarCNPJ', 'onShow'],['campo' => '["nome", "telefone" ,"cpf_cnpj", "pessoa_endereco_pessoa_cep" ,"pessoa_endereco_pessoa_cidade_id", "pessoa_endereco_pessoa_id", "pessoa_endereco_pessoa_bairro", "pessoa_endereco_pessoa_rua" ,"pessoa_endereco_pessoa_numero" ,"pessoa_endereco_pessoa_complemento"]',"form" => self::$formName,"page" => "ClienteForm.php"]), "Buscar");

        $nome->setMaxLength(255);
        $email->setMaxLength(255);
        $telefone->setMaxLength(20);
        $pessoa_endereco_pessoa_cep->setMaxLength(8);
        $pessoa_endereco_pessoa_rua->setMaxLength(500);
        $pessoa_contato_pessoa_email->setMaxLength(255);
        $pessoa_endereco_pessoa_bairro->setMaxLength(500);
        $pessoa_endereco_pessoa_numero->setMaxLength(100);
        $pessoa_contato_pessoa_telefone->setMaxLength(20);
        $pessoa_contato_pessoa_descricao->setMaxLength(255);
        $pessoa_endereco_pessoa_complemento->setMaxLength(500);
        $pessoa_representantes_legais_pessoa_juridica_descricao->setMaxLength(255);

        $id->setSize(100);
        $nit->setSize('100%');
        $ctps->setSize('100%');
        $email->setSize('100%');
        $senha->setSize('100%');
        $rg_ie->setSize('100%');
        $orgao->setSize('100%');
        $foto->setSize(150, 150);
        $usuario->setSize('100%');
        $sexo_id->setSize('100%');
        $unidade->setSize('100%');
        $telefone->setSize('100%');
        $cpf_cnpj->setSize('100%');
        $profissao->setSize('100%');
        $processos->setSize('100%');
        $orgao_emissor->setSize('100%');
        $dt_falecimento->setSize('100%');
        $estado_civil_id->setSize('100%');
        $observacao->setSize('100%', 160);
        $tarefas_cliente->setSize('100%');
        $nacionalidade_id->setSize('100%');
        $nome->setSize('calc(100% - 50px)');
        $pessoa_contato_pessoa_id->setSize(200);
        $contrato_cliente_list->setSize('100%');
        $atendimento_historico->setSize('100%');
        $dt_nascimento_abertura->setSize('100%');
        $pessoa_endereco_pessoa_id->setSize(200);
        $situacao_profissional_id->setSize('100%');
        $classificacoes_cliente_id->setSize('100%');
        $pessoa_endereco_pessoa_rua->setSize('100%');
        $tipo_pessoa_id->setSize('calc(100% - 90px)');
        $pessoa_contato_pessoa_email->setSize('100%');
        $pessoa_endereco_pessoa_bairro->setSize('100%');
        $pessoa_endereco_pessoa_numero->setSize('100%');
        $pessoa_contato_pessoa_telefone->setSize('100%');
        $pessoa_contato_pessoa_descricao->setSize('100%');
        $pessoa_endereco_pessoa_cidade_id->setSize('100%');
        $pessoa_endereco_pessoa_complemento->setSize('100%');
        $pessoa_endereco_pessoa_cep->setSize('calc(100% - 120px)');
        $pessoa_representantes_legais_pessoa_juridica_id->setSize(200);
        $pessoa_representantes_legais_pessoa_juridica_descricao->setSize('100%');
        $pessoa_representantes_legais_pessoa_juridica_representante_id->setSize('calc(100% - 100px)');

        $button_adicionar_pessoa_contato_pessoa->id = '64be92cf7046f';
        $button_adicionar_pessoa_endereco_pessoa->id = '60f6c58143f89';
        $btnRepresentanteAdicionar_pessoa_representantes_legais_pessoa_juridica->id = '64be9abc6308c';

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $atendimento_historico->add($loadingContainer);
        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $processos->add($loadingContainer);
        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $tarefas_cliente->add($loadingContainer);

        $this->contrato_cliente_list = $contrato_cliente_list;
        $this->atendimento_historico = $atendimento_historico;
        $this->processos = $processos;
        $this->tarefas_cliente = $tarefas_cliente;

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

        TScript::create(
            "$(document).on('keydown', 'input[name=\"pessoa_contato_pessoa_telefone\"]', function (e) {
            var digit = e.key.replace(/\D/g, '');
            var value = $(this).val().replace(/\D/g, '');
            var size = value.concat(digit).length;
            $(this).mask((size <= 10) ? '(##) ####-####' : '(##) #####-####');
            });"
        );

        $this->form->appendPage("Dados cadastrais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '12px', null, '100%'),$id],[new TLabel("Receber informações do seus agendamentos por whatsapp", null, '12px', null, '100%'),$aceita_receber_mensagen_whatsapp,new TLabel(new TImage('fas:info-circle #03A9F4')."Alguns exemplos de interação são lembrete de consulta, confirmação de agendamento", '#607D8B', '9px', 'I')]);
        $row1->layout = ['col-sm-6',' col-sm-6'];

        $bcontainer_654b89b1308c8 = new BootstrapFormBuilder('bcontainer_654b89b1308c8');
        $this->bcontainer_654b89b1308c8 = $bcontainer_654b89b1308c8;
        $bcontainer_654b89b1308c8->setProperty('style', 'border:none; box-shadow:none;');
        $row2 = $bcontainer_654b89b1308c8->addFields([new TLabel("Tipo de pessoa:", '#FF0000', '13px', null, '100%'),$tipo_pessoa_id,$btnBuscarCNPJ],[new TLabel("Nome:", '#ff0000', '12px', null, '100%'),$nome,$btnVerificarNome]);
        $row2->layout = [' col-sm-4',' col-sm-8'];

        $row3 = $bcontainer_654b89b1308c8->addFields([new TLabel("Telefone:", null, '12px', null, '100%'),$telefone],[new TLabel("Email:", null, '12px', null, '100%'),$email]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $bcontainer_654b89b1308c8->addFields([new TLabel("Usuário:", null, '14px', null),$usuario],[new TLabel("Senha:", null, '14px', null),$senha]);
        $row4->layout = [' col-sm-6',' col-sm-6'];

        $row5 = $this->form->addFields([$bcontainer_654b89b1308c8],[$foto]);
        $row5->layout = [' col-sm-8',' col-sm-4'];

        $row6 = $this->form->addContent([new TFormSeparator("", '#333', '12', '#eee')]);
        $row7 = $this->form->addFields([new TLabel("Data de nascimento:", '#000000', '12px', null, '100%'),$dt_nascimento_abertura],[new TLabel("CPF:", null, '12px', null, '100%'),$cpf_cnpj],[new TLabel("RG:", null, '12px', null, '100%'),$rg_ie],[new TLabel("Órgão emissor:", '#000000', '12px', null, '100%'),$orgao_emissor]);
        $row7->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row8 = $this->form->addFields([new TLabel("Data de falecimento:", '#000000', '12px', null, '100%'),$dt_falecimento],[new TLabel("Nacionalidade:", '#000000', '12px', null, '100%'),$nacionalidade_id],[new TLabel("Sexo:", '#000000', '12px', null, '100%'),$sexo_id],[new TLabel("Estado civil:", '#000000', '12px', null, '100%'),$estado_civil_id]);
        $row8->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        $row9 = $this->form->addContent([new TFormSeparator("", '#333', '12', '#eee')]);
        $row10 = $this->form->addFields([new TLabel("Profissão:", '#000000', '12px', null, '100%'),$profissao],[new TLabel("NIT:", '#000000', '12px', null, '100%'),$nit],[new TLabel("CTPS:", '#000000', '12px', null, '100%'),$ctps]);
        $row10->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row11 = $this->form->addFields([new TLabel("Situação:", '#000000', '12px', null, '100%'),$situacao_profissional_id],[new TLabel("Orgão:", '#000000', '12px', null, '100%'),$orgao],[new TLabel("Unidade:", '#000000', '12px', null, '100%'),$unidade]);
        $row11->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row12 = $this->form->addContent([new TFormSeparator("", '#333', '12', '#eee')]);
        $row13 = $this->form->addFields([new TLabel("Selecione as classificações do cliente:", null, '12px', null, '100%'),$classificacoes_cliente_id]);
        $row13->layout = [' col-sm-12'];

        $row14 = $this->form->addFields([new TLabel("Observação:", null, '12px', null, '100%'),$observacao]);
        $row14->layout = [' col-sm-12'];

        $this->form->appendPage("Endereços");

        $this->detailFormPessoaEnderecoPessoa = new BootstrapFormBuilder('detailFormPessoaEnderecoPessoa');
        $this->detailFormPessoaEnderecoPessoa->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormPessoaEnderecoPessoa->setProperty('class', 'form-horizontal builder-detail-form');

        $row15 = $this->detailFormPessoaEnderecoPessoa->addFields([new TLabel("CEP:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_cep,$button_buscar_pessoa_endereco_pessoa],[new TLabel("Cidade:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_cidade_id,$pessoa_endereco_pessoa_id]);
        $row15->layout = [' col-sm-4',' col-sm-8'];

        $row16 = $this->detailFormPessoaEnderecoPessoa->addFields([new TLabel("Bairro:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_bairro],[new TLabel("Rua:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_rua]);
        $row16->layout = [' col-sm-4',' col-sm-8'];

        $row17 = $this->detailFormPessoaEnderecoPessoa->addFields([new TLabel("Número:", '#ff0000', '12px', null, '100%'),$pessoa_endereco_pessoa_numero],[new TLabel("Complemento:", null, '12px', null, '100%'),$pessoa_endereco_pessoa_complemento],[new TLabel("Principal:", null, '12px', null, '100%'),$pessoa_endereco_pessoa_principal]);
        $row17->layout = ['col-sm-4',' col-sm-5',' col-sm-3'];

        $row18 = $this->detailFormPessoaEnderecoPessoa->addFields([$button_adicionar_pessoa_endereco_pessoa]);
        $row18->layout = [' col-sm-12'];

        $row19 = $this->detailFormPessoaEnderecoPessoa->addFields([new THidden('pessoa_endereco_pessoa__row__id')]);
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

        $action_onEditDetailPessoaEndereco = new TDataGridAction(array('ClienteForm', 'onEditDetailPessoaEndereco'));
        $action_onEditDetailPessoaEndereco->setUseButton(false);
        $action_onEditDetailPessoaEndereco->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailPessoaEndereco->setLabel("Editar");
        $action_onEditDetailPessoaEndereco->setImage('far:edit #478fca');
        $action_onEditDetailPessoaEndereco->setFields(['__row__id', '__row__data']);

        $this->pessoa_endereco_pessoa_list->addAction($action_onEditDetailPessoaEndereco);
        $action_onDeleteDetailPessoaEndereco = new TDataGridAction(array('ClienteForm', 'onDeleteDetailPessoaEndereco'));
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

        });        $row20 = $this->form->addFields([$this->detailFormPessoaEnderecoPessoa]);
        $row20->layout = [' col-sm-12'];

        $this->form->appendPage("Contatos");

        $this->detailFormPessoaContatoPessoa = new BootstrapFormBuilder('detailFormPessoaContatoPessoa');
        $this->detailFormPessoaContatoPessoa->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormPessoaContatoPessoa->setProperty('class', 'form-horizontal builder-detail-form');

        $row21 = $this->detailFormPessoaContatoPessoa->addFields([new TLabel("Descrição:", '#ff0000', '12px', null, '100%'),$pessoa_contato_pessoa_descricao,$pessoa_contato_pessoa_id]);
        $row21->layout = [' col-sm-12'];

        $row22 = $this->detailFormPessoaContatoPessoa->addFields([new TLabel("Telefone:", null, '12px', null, '100%'),$pessoa_contato_pessoa_telefone],[new TLabel("Email:", null, '12px', null, '100%'),$pessoa_contato_pessoa_email]);
        $row22->layout = ['col-sm-6','col-sm-6'];

        $row23 = $this->detailFormPessoaContatoPessoa->addFields([$button_adicionar_pessoa_contato_pessoa]);
        $row23->layout = [' col-sm-12'];

        $row24 = $this->detailFormPessoaContatoPessoa->addFields([new THidden('pessoa_contato_pessoa__row__id')]);
        $this->pessoa_contato_pessoa_criteria = new TCriteria();

        $this->pessoa_contato_pessoa_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->pessoa_contato_pessoa_list->generateHiddenFields();
        $this->pessoa_contato_pessoa_list->setId('pessoa_contato_pessoa_list');

        $this->pessoa_contato_pessoa_list->style = 'width:100%';
        $this->pessoa_contato_pessoa_list->class .= ' table-bordered';

        $column_pessoa_contato_pessoa_descricao = new TDataGridColumn('descricao', "Descrição", 'left');
        $column_pessoa_contato_pessoa_telefone = new TDataGridColumn('telefone', "Telefone", 'left');
        $column_pessoa_contato_pessoa_email = new TDataGridColumn('email', "Email", 'left');

        $column_pessoa_contato_pessoa__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_pessoa_contato_pessoa__row__data->setVisibility(false);

        $action_onEditDetailPessoaContato = new TDataGridAction(array('ClienteForm', 'onEditDetailPessoaContato'));
        $action_onEditDetailPessoaContato->setUseButton(false);
        $action_onEditDetailPessoaContato->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailPessoaContato->setLabel("Editar");
        $action_onEditDetailPessoaContato->setImage('far:edit #478fca');
        $action_onEditDetailPessoaContato->setFields(['__row__id', '__row__data']);

        $this->pessoa_contato_pessoa_list->addAction($action_onEditDetailPessoaContato);
        $action_onDeleteDetailPessoaContato = new TDataGridAction(array('ClienteForm', 'onDeleteDetailPessoaContato'));
        $action_onDeleteDetailPessoaContato->setUseButton(false);
        $action_onDeleteDetailPessoaContato->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailPessoaContato->setLabel("Excluir");
        $action_onDeleteDetailPessoaContato->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailPessoaContato->setFields(['__row__id', '__row__data']);

        $this->pessoa_contato_pessoa_list->addAction($action_onDeleteDetailPessoaContato);

        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_descricao);
        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_telefone);
        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_email);

        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa__row__data);

        $this->pessoa_contato_pessoa_list->createModel();
        $tableResponsiveDiv = new TElement('div');
        $tableResponsiveDiv->class = 'table-responsive';
        $tableResponsiveDiv->add($this->pessoa_contato_pessoa_list);
        $this->detailFormPessoaContatoPessoa->addContent([$tableResponsiveDiv]);
        $row25 = $this->form->addFields([$this->detailFormPessoaContatoPessoa]);
        $row25->layout = [' col-sm-12'];

        $this->form->appendPage("Representantes legais");

        $this->detailFormPessoaRepresentantesLegaisPessoaJuridica = new BootstrapFormBuilder('detailFormPessoaRepresentantesLegaisPessoaJuridica');
        $this->detailFormPessoaRepresentantesLegaisPessoaJuridica->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormPessoaRepresentantesLegaisPessoaJuridica->setProperty('class', 'form-horizontal builder-detail-form');

        $row26 = $this->detailFormPessoaRepresentantesLegaisPessoaJuridica->addFields([new TLabel("Descrição:", '#ff0000', '12px', null, '100%'),$pessoa_representantes_legais_pessoa_juridica_descricao,$pessoa_representantes_legais_pessoa_juridica_id],[new TLabel("Representante:", '#FF0000', '12px', null, '100%'),$pessoa_representantes_legais_pessoa_juridica_representante_id,$button_novo_pessoa_representantes_legais_pessoa_juridica],[new TLabel("Principal:", null, '14px', null, '100%'),$pessoa_representantes_legais_pessoa_juridica_principal]);
        $row26->layout = [' col-sm-4',' col-sm-6','col-sm-2'];

        $row27 = $this->detailFormPessoaRepresentantesLegaisPessoaJuridica->addFields([$btnRepresentanteAdicionar_pessoa_representantes_legais_pessoa_juridica]);
        $row27->layout = [' col-sm-12'];

        $row28 = $this->detailFormPessoaRepresentantesLegaisPessoaJuridica->addFields([new THidden('pessoa_representantes_legais_pessoa_juridica__row__id')]);
        $this->pessoa_representantes_legais_pessoa_juridica_criteria = new TCriteria();

        $this->pessoa_representantes_legais_pessoa_juridica_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->pessoa_representantes_legais_pessoa_juridica_list->generateHiddenFields();
        $this->pessoa_representantes_legais_pessoa_juridica_list->setId('pessoa_representantes_legais_pessoa_juridica_list');

        $this->pessoa_representantes_legais_pessoa_juridica_list->style = 'width:100%';
        $this->pessoa_representantes_legais_pessoa_juridica_list->class .= ' table-bordered';

        $column_pessoa_representantes_legais_pessoa_juridica_descricao = new TDataGridColumn('descricao', "Descrição", 'left');
        $column_pessoa_representantes_legais_pessoa_juridica_representante_nome = new TDataGridColumn('representante->nome', "Representante legal", 'left');
        $column_pessoa_representantes_legais_pessoa_juridica_principal_transformed = new TDataGridColumn('principal', "Principal", 'left');

        $column_pessoa_representantes_legais_pessoa_juridica__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_pessoa_representantes_legais_pessoa_juridica__row__data->setVisibility(false);

        $action_onEditDetailPessoaRepresentantesLegais = new TDataGridAction(array('ClienteForm', 'onEditDetailPessoaRepresentantesLegais'));
        $action_onEditDetailPessoaRepresentantesLegais->setUseButton(false);
        $action_onEditDetailPessoaRepresentantesLegais->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailPessoaRepresentantesLegais->setLabel("Editar");
        $action_onEditDetailPessoaRepresentantesLegais->setImage('far:edit #478fca');
        $action_onEditDetailPessoaRepresentantesLegais->setFields(['__row__id', '__row__data']);

        $this->pessoa_representantes_legais_pessoa_juridica_list->addAction($action_onEditDetailPessoaRepresentantesLegais);
        $action_onDeleteDetailPessoaRepresentantesLegais = new TDataGridAction(array('ClienteForm', 'onDeleteDetailPessoaRepresentantesLegais'));
        $action_onDeleteDetailPessoaRepresentantesLegais->setUseButton(false);
        $action_onDeleteDetailPessoaRepresentantesLegais->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailPessoaRepresentantesLegais->setLabel("Excluir");
        $action_onDeleteDetailPessoaRepresentantesLegais->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailPessoaRepresentantesLegais->setFields(['__row__id', '__row__data']);

        $this->pessoa_representantes_legais_pessoa_juridica_list->addAction($action_onDeleteDetailPessoaRepresentantesLegais);

        $this->pessoa_representantes_legais_pessoa_juridica_list->addColumn($column_pessoa_representantes_legais_pessoa_juridica_descricao);
        $this->pessoa_representantes_legais_pessoa_juridica_list->addColumn($column_pessoa_representantes_legais_pessoa_juridica_representante_nome);
        $this->pessoa_representantes_legais_pessoa_juridica_list->addColumn($column_pessoa_representantes_legais_pessoa_juridica_principal_transformed);

        $this->pessoa_representantes_legais_pessoa_juridica_list->addColumn($column_pessoa_representantes_legais_pessoa_juridica__row__data);

        $this->pessoa_representantes_legais_pessoa_juridica_list->createModel();
        $tableResponsiveDiv = new TElement('div');
        $tableResponsiveDiv->class = 'table-responsive';
        $tableResponsiveDiv->add($this->pessoa_representantes_legais_pessoa_juridica_list);
        $this->detailFormPessoaRepresentantesLegaisPessoaJuridica->addContent([$tableResponsiveDiv]);

        $column_pessoa_representantes_legais_pessoa_juridica_principal_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        });        $row29 = $this->form->addFields([$this->detailFormPessoaRepresentantesLegaisPessoaJuridica]);
        $row29->layout = [' col-sm-12'];

        $this->form->appendPage("Contratos");
        $row30 = $this->form->addFields([$contrato_cliente_list]);
        $row30->layout = [' col-sm-12'];

        $this->form->appendPage("Atendimentos");
        $row31 = $this->form->addFields([$atendimento_historico]);
        $row31->layout = [' col-sm-12'];

        $this->form->appendPage("Processos");
        $row32 = $this->form->addFields([$processos]);
        $row32->layout = [' col-sm-12'];

        $this->form->appendPage("Tarefas");
        $row33 = $this->form->addFields([$tarefas_cliente]);
        $row33->layout = [' col-sm-12'];

        // create the form actions
        $btnSave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btnSave = $btnSave;
        $btnSave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ClienteList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        $btn_delete = $this->form->addHeaderAction("Excluír", new TAction([$this, 'onDelete'],['static' => 1]), 'fas:trash-alt #F44336');
        $this->btn_delete = $btn_delete;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        $btnSave->getAction()->setParameter('origin', $param['origin']??'');
        TScript::create('$("button[name=\'btnBuscarCNPJ\'").attr("disabled", true);');

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ClienteForm]');
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
            TTransaction::open(self::$database);

            //Esconder campos de documento
            ClienteForm::getTipoPessoaSelected($param);

            TTransaction::close();

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
                    $pageParam['nova_pessoa_grupo'] = Grupo::CLIENTE;

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

            if(empty($data->pessoa_endereco_pessoa_principal) )
            {
                $data->pessoa_endereco_pessoa_principal = "F";
            }

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

    public  function onAddDetailPessoaContatoPessoa($param = null) 
    {
        try
        {
            $data = $this->form->getData();

            $data->pessoa_contato_pessoa_telefone = preg_replace('/[^0-9]/', '', $data->pessoa_contato_pessoa_telefone);

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"Descrição", 'name'=>"pessoa_contato_pessoa_descricao", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Email", 'name'=>"pessoa_contato_pessoa_email", 'class'=>'TEmailValidator', 'value'=>[]];
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

            $__row__id = !empty($data->pessoa_contato_pessoa__row__id) ? $data->pessoa_contato_pessoa__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new PessoaContato();
            $grid_data->__row__id = $__row__id;
            $grid_data->descricao = $data->pessoa_contato_pessoa_descricao;
            $grid_data->id = $data->pessoa_contato_pessoa_id;
            $grid_data->telefone = $data->pessoa_contato_pessoa_telefone;
            $grid_data->email = $data->pessoa_contato_pessoa_email;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['descricao'] =  $param['pessoa_contato_pessoa_descricao'] ?? null;
            $__row__data['__display__']['id'] =  $param['pessoa_contato_pessoa_id'] ?? null;
            $__row__data['__display__']['telefone'] =  $param['pessoa_contato_pessoa_telefone'] ?? null;
            $__row__data['__display__']['email'] =  $param['pessoa_contato_pessoa_email'] ?? null;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->pessoa_contato_pessoa_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('pessoa_contato_pessoa_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->pessoa_contato_pessoa_descricao = '';
            $data->pessoa_contato_pessoa_id = '';
            $data->pessoa_contato_pessoa_telefone = '';
            $data->pessoa_contato_pessoa_email = '';
            $data->pessoa_contato_pessoa__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#64be92cf7046f');
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

    public  function onAddDetailPessoaRepresentantesLegaisPessoaJuridica($param = null) 
    {
        try
        {
            $data = $this->form->getData();

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"Descrição", 'name'=>"pessoa_representantes_legais_pessoa_juridica_descricao", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Representante", 'name'=>"pessoa_representantes_legais_pessoa_juridica_representante_id", 'class'=>'TRequiredValidator', 'value'=>[]];
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

            $__row__id = !empty($data->pessoa_representantes_legais_pessoa_juridica__row__id) ? $data->pessoa_representantes_legais_pessoa_juridica__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new PessoaRepresentantesLegais();
            $grid_data->__row__id = $__row__id;
            $grid_data->descricao = $data->pessoa_representantes_legais_pessoa_juridica_descricao;
            $grid_data->id = $data->pessoa_representantes_legais_pessoa_juridica_id;
            $grid_data->representante_id = $data->pessoa_representantes_legais_pessoa_juridica_representante_id;
            $grid_data->principal = $data->pessoa_representantes_legais_pessoa_juridica_principal;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['descricao'] =  $param['pessoa_representantes_legais_pessoa_juridica_descricao'] ?? null;
            $__row__data['__display__']['id'] =  $param['pessoa_representantes_legais_pessoa_juridica_id'] ?? null;
            $__row__data['__display__']['representante_id'] =  $param['pessoa_representantes_legais_pessoa_juridica_representante_id'] ?? null;
            $__row__data['__display__']['principal'] =  $param['pessoa_representantes_legais_pessoa_juridica_principal'] ?? null;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->pessoa_representantes_legais_pessoa_juridica_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('pessoa_representantes_legais_pessoa_juridica_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->pessoa_representantes_legais_pessoa_juridica_descricao = '';
            $data->pessoa_representantes_legais_pessoa_juridica_id = '';
            $data->pessoa_representantes_legais_pessoa_juridica_representante_id = '';
            $data->pessoa_representantes_legais_pessoa_juridica_principal = '';
            $data->pessoa_representantes_legais_pessoa_juridica__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#64be9abc6308c');
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
    public static function onEditDetailPessoaContato($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->pessoa_contato_pessoa_descricao = $__row__data->__display__->descricao ?? null;
            $data->pessoa_contato_pessoa_id = $__row__data->__display__->id ?? null;
            $data->pessoa_contato_pessoa_telefone = $__row__data->__display__->telefone ?? null;
            $data->pessoa_contato_pessoa_email = $__row__data->__display__->email ?? null;
            $data->pessoa_contato_pessoa__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#64be92cf7046f');
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
    public static function onDeleteDetailPessoaContato($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->pessoa_contato_pessoa_descricao = '';
            $data->pessoa_contato_pessoa_id = '';
            $data->pessoa_contato_pessoa_telefone = '';
            $data->pessoa_contato_pessoa_email = '';
            $data->pessoa_contato_pessoa__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('pessoa_contato_pessoa_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#64be92cf7046f');
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
    public static function onEditDetailPessoaRepresentantesLegais($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->pessoa_representantes_legais_pessoa_juridica_descricao = $__row__data->__display__->descricao ?? null;
            $data->pessoa_representantes_legais_pessoa_juridica_id = $__row__data->__display__->id ?? null;
            $data->pessoa_representantes_legais_pessoa_juridica_representante_id = $__row__data->__display__->representante_id ?? null;
            $data->pessoa_representantes_legais_pessoa_juridica_principal = $__row__data->__display__->principal ?? null;
            $data->pessoa_representantes_legais_pessoa_juridica__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#64be9abc6308c');
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
    public static function onDeleteDetailPessoaRepresentantesLegais($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->pessoa_representantes_legais_pessoa_juridica_descricao = '';
            $data->pessoa_representantes_legais_pessoa_juridica_id = '';
            $data->pessoa_representantes_legais_pessoa_juridica_representante_id = '';
            $data->pessoa_representantes_legais_pessoa_juridica_principal = '';
            $data->pessoa_representantes_legais_pessoa_juridica__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('pessoa_representantes_legais_pessoa_juridica_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#64be9abc6308c');
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

            if(empty($data->aceita_receber_mensagen_whatsapp))
            {
                $data->aceita_receber_mensagen_whatsapp = 'F';
            }

            $data->telefone = preg_replace('/[^0-9]/', '', $data->telefone);
            $data->cpf_cnpj = preg_replace('/[^0-9]/', '', $data->cpf_cnpj);

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
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::CLIENTE)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['key'] = $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){

                        new TQuestion("Este CPF já está cadastrado como cliente. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditCad'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Este CPF já está cadastrado. Deseja adicionar como cliente?", new TAction([__CLASS__, 'onAdicionarGrupoCliente'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }

                }elseif($countCPF_CNPJ>0 && $data->tipo_pessoa_id==TipoPessoa::JURIDICA){
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::CLIENTE)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['key'] = $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){
                        new TQuestion("Este CNPJ já está cadastrado como cliente. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditCad'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Este CNPJ já está cadastrado. Deseja adicionar como cliente?", new TAction([__CLASS__, 'onAdicionarGrupoCliente'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
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
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::CLIENTE)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['key'] = $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){
                        new TQuestion("Este RG já está cadastrado como cliente. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditCad'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Este RG já está cadastrado. Deseja adicionar como cliente?", new TAction([__CLASS__, 'onAdicionarGrupoCliente'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }

                }elseif($countCPF_CNPJ>0 && $data->tipo_pessoa_id==TipoPessoa::JURIDICA){
                    $verificarGrupoAtual = PessoaGrupo::where('grupo_id','=',Grupo::CLIENTE)
                                                    ->where('pessoa_id','=',$searchCPF_CNPJ->id)
                                                    ->count();
                    $pageParam['key'] = $pageParam['pessoa_id'] = $searchCPF_CNPJ->id;
                    if($verificarGrupoAtual>0){
                        new TQuestion("Esta Inscrição Estadual já está cadastrada como cliente. Deseja editar o cadastro existente?", new TAction([__CLASS__, 'onEditCad'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
                        exit;
                    }else{
                        new TQuestion("Esta Inscrição Estadual já está cadastrada. Deseja adicionar como cliente?", new TAction([__CLASS__, 'onAdicionarGrupoCliente'], $pageParam), new TAction([__CLASS__, 'btnNaoTQuestion'], $pageParam));
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

            $data->nome_busca = self::makeNomeBusca($data->nome ?? '');

            $object->fromArray( (array) $data); // load the object with data

            $foto_dir = 'fotos';  

            if(!$object->usuario){

                $username = str_replace(' ', '_', $object->nome);
                $username = TextService::slug($username);
                $username = str_replace('_', '.', $username);

                $object->usuario = $username;

                $consulta = Pessoa::where('usuario', '=', $username)->first();
                $count = 1;

                // gerando um nome de usuario unico
                while($consulta)
                {
                    $object->usuario = $username.$count;
                    $consulta = Pessoa::where('usuario', '=', $object->usuario)->first();

                    $count++;
                }

                $bytes = openssl_random_pseudo_bytes(3);
                $pwd = bin2hex($bytes);

                $object->senha = $pwd;
                TForm::sendData(self::$formName, $object);
            }

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            $object->store(); // save the object 

            $repository = ClassificacoesCliente::where('pessoa_id', '=', $object->id);
            $repository->delete(); 

            if ($data->classificacoes_cliente_id) 
            {
                foreach ($data->classificacoes_cliente_id as $classificacoes_cliente_id_value) 
                {
                    $classificacoes_cliente = new ClassificacoesCliente;

                    $classificacoes_cliente->classificacoes_id = $classificacoes_cliente_id_value;
                    $classificacoes_cliente->pessoa_id = $object->id;
                    $classificacoes_cliente->store();
                }
            }

            $this->saveFile($object, $data, 'foto', $foto_dir);
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(!empty(self::$data))
            {
                $loadPageParam["data"] = self::$data;
            }

            if (!empty($param['pessoa_representantes_legais_pessoa_juridica__row__id'])) {
                $quantReps = count((array) $param['pessoa_representantes_legais_pessoa_juridica__row__id']); // Garante que seja array

                if ($quantReps > 0) {
                    $quantValores = array_count_values((array) ($param['pessoa_representantes_legais_pessoa_juridica_principal'] ?? []));

                    if (!isset($quantValores['S']) || $quantValores['S'] != 1) {
                        throw new Exception("Se houver representantes, é necessário selecionar exatamente um como principal!");
                    }
                }
            }

            $pessoa_representantes_legais_pessoa_juridica_items = $this->storeMasterDetailItems('PessoaRepresentantesLegais', 'pessoa_juridica_id', 'pessoa_representantes_legais_pessoa_juridica', $object, $param['pessoa_representantes_legais_pessoa_juridica_list___row__data'] ?? [], $this->form, $this->pessoa_representantes_legais_pessoa_juridica_list, function($masterObject, $detailObject){ 

                $rep_legal_grupos = PessoaGrupo::where('pessoa_id', '=', $detailObject->representante_id)
                                    ->orderBy('id')
                                    ->getIndexedArray('id','grupo_id');

                if(!in_array(Grupo::REPRESENTANTE_LEGAL,$rep_legal_grupos)){
                    $objeto = new PessoaGrupo();
                    $objeto->pessoa_id = $detailObject->representante_id;
                    $objeto->grupo_id = Grupo::REPRESENTANTE_LEGAL;
                    $objeto->store();
                }

            }, $this->pessoa_representantes_legais_pessoa_juridica_criteria); 

//<generatedAutoCode>
            $this->pessoa_contato_pessoa_criteria->setProperty('order', 'id desc');
//</generatedAutoCode>
            $pessoa_contato_pessoa_items = $this->storeMasterDetailItems('PessoaContato', 'pessoa_id', 'pessoa_contato_pessoa', $object, $param['pessoa_contato_pessoa_list___row__data'] ?? [], $this->form, $this->pessoa_contato_pessoa_list, function($masterObject, $detailObject){ 

            }, $this->pessoa_contato_pessoa_criteria); 

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

            }, $this->pessoa_endereco_pessoa_criteria); 

            $object->system_users_id = null;

            if(!$data->id)
            {
                $pessoaGrupo = new PessoaGrupo();
                $pessoaGrupo->pessoa_id = $object->id;
                $pessoaGrupo->grupo_id = Grupo::CLIENTE;
                $pessoaGrupo->store();    
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data

            TTransaction::close(); // close the transaction

            $atendimento = TSession::getValue('atendimento');

            $agendamento = TSession::getValue('agendamento');

            if(!empty($atendimento)){
                $loadPageParam['key'] = $atendimento;
                TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam);
                TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            }else if(!empty($agendamento)){
                $loadPageParam['key'] = $agendamento;
                TApplication::loadPage('AgendamentoFormView', 'onShow', $loadPageParam);
                TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            }else{
                TSession::setValue(__CLASS__.'_filter_data', NULL);
                TSession::setValue(__CLASS__.'_filters', NULL);

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ClienteList', 'onShow', $loadPageParam); 

            }
                        TScript::create("Template.closeRightPanel();"); 

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            //->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    public function onDelete($param = null) 
    {
        if(isset($param['delete']) && $param['delete'] == 1 && $param['id'])
        {
            try
            {
                $key = $param['id'];

                TTransaction::open(self::$database);

                $object = new Pessoa($key, FALSE); 

                $qtdeGrupos = PessoaGrupo::where('pessoa_id', '=', $object->id)->count();

                if ($object->loadComposite('Agendamento', 'cliente_id', $object->id)) {
                    throw new Exception("Esse cliente já possui agendamento e não pode ser removido!");
                }

                if ($object->loadComposite('Conta', 'pessoa_id', $object->id)) {
                    throw new Exception("Esse cliente possui contas vinculadas e não pode ser removido!");
                }

                if ($object->loadComposite('PessoaRepresentantesLegais', 'pessoa_juridica_id', $object->id)) {
                    throw new Exception("Esse cliente possui representantes legais e não pode ser removido!");
                }

                if($qtdeGrupos>1){
                    $grupoExcluir = PessoaGrupo::where('pessoa_id', '=', $object->id)
                                                ->where('grupo_id', '=', Grupo::CLIENTE);
                    $grupoExcluir->delete();
                }else{

                    $object->deleteComposite('PessoaGrupo', 'pessoa_id', $object->id);
                    $object->deleteComposite('PessoaEndereco', 'pessoa_id', $object->id); 
                    $object->delete();
                }

                TTransaction::close();
                $param['delete']=NULL;

                if(!empty($atendimento)){
                    $loadPageParam['key'] = $atendimento;
                    TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam);
                }else if(!empty($agendamento)){
                    $loadPageParam['key'] = $agendamento;
                    TApplication::loadPage('AgendamentoFormView', 'onShow', $loadPageParam);
                }else{
                    TApplication::loadPage('ClienteList', 'onShow');
                }
                TSession::setValue(__CLASS__.'_filter_data', NULL);
                TSession::setValue(__CLASS__.'_filters', NULL);

                TToast::show('success', "Registro excluído", 'topRight', 'far:check-circle');
                TScript::create("Template.closeRightPanel();");

            }
            catch (Exception $e) // in case of exception
            {            
                new TMessage('error', $e->getMessage());                
                TTransaction::rollback();
            }
        }
        else
        {        
            $action = new TAction(array($this, 'onDelete'));
            $action->setParameters($param);
            $action->setParameter('delete', 1);

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

                if(isset($param['atendimento'])){
                    TSession::setValue('atendimento',$param['atendimento']);
                }

                if(isset($param['agendamento'])){
                    TSession::setValue('agendamento',$param['agendamento']);
                }

                $object = new Pessoa($key); // instantiates the Active Record 

                $param['tipo_pessoa_id']=$object->tipo_pessoa_id;
                ClienteForm::getTipoPessoaSelected($param);

                                $this->contrato_cliente_list->unhide();
                $this->contrato_cliente_list->setParameter('key', $object->id);
                $this->atendimento_historico->setParameter('key', $object->id);
                $this->processos->unhide();
                $this->processos->setParameter('key', $object->id);
                $this->tarefas_cliente->setParameter('key', $object->id);

                $object->classificacoes_cliente_id = ClassificacoesCliente::where('pessoa_id', '=', $object->id)->getIndexedArray('classificacoes_id', 'classificacoes_id');

                $pessoa_representantes_legais_pessoa_juridica_items = $this->loadMasterDetailItems('PessoaRepresentantesLegais', 'pessoa_juridica_id', 'pessoa_representantes_legais_pessoa_juridica', $object, $this->form, $this->pessoa_representantes_legais_pessoa_juridica_list, $this->pessoa_representantes_legais_pessoa_juridica_criteria, function($masterObject, $detailObject, $objectItems){ 

                }); 

//<generatedAutoCode>
                $this->pessoa_contato_pessoa_criteria->setProperty('order', 'id desc');
//</generatedAutoCode>
                $pessoa_contato_pessoa_items = $this->loadMasterDetailItems('PessoaContato', 'pessoa_id', 'pessoa_contato_pessoa', $object, $this->form, $this->pessoa_contato_pessoa_list, $this->pessoa_contato_pessoa_criteria, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }); 

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

        TEntry::changeMask(self::$formName, 'cpf_cnpj', '###.###.###-##');
        TScript::create('$("button[name=\'btnBuscarCNPJ\'").attr("disabled", true);');

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    public static function getTipoPessoaSelected($param = null) 
    {
        try 
        {
            if(!isset($param['tipo_pessoa_id'])){
                BootstrapFormBuilder::hideField(self::$formName, 'cpf_cnpj');
                BootstrapFormBuilder::hideField(self::$formName, 'nacionalidade_id');
                TScript::create('$("button[name=\'btnBuscarCNPJ\'").attr("disabled", true);');
            }else{ 
                if($param['tipo_pessoa_id']!=TipoPessoa::FISICA && $param['tipo_pessoa_id']!=TipoPessoa::JURIDICA){

                    BootstrapFormBuilder::hideField(self::$formName, 'cpf_cnpj');
                    BootstrapFormBuilder::hideField(self::$formName, 'nacionalidade_id');

                    TScript::create('$("button[name=\'btnBuscarCNPJ\'").attr("disabled", true);');

                }else if($param['tipo_pessoa_id']==TipoPessoa::FISICA){
                    BootstrapFormBuilder::showField(self::$formName, 'cpf_cnpj');
                    BootstrapFormBuilder::showField(self::$formName, 'nacionalidade_id');

                    TScript::create("$(\"[name='orgao_emissor']\").closest('.fb-inline-field-container').show()");
                    TScript::create("$('label:contains(\"Órgão emissor:\")').show();");

                    TEntry::changeMask(self::$formName, 'cpf_cnpj', '###.###.###-##');

                    TScript::create('$("button[name=\'btnBuscarCNPJ\'").attr("disabled", true);');

                    TScript::create("$('label:contains(\"CNPJ:\")').html('CPF:')");
                    TScript::create("$('label:contains(\"Inscrição Estadual:\")').html('RG:')");
                    TScript::create("$('label:contains(\"Data de abertura:\")').html('Data de nascimento:')");

                }else if($param['tipo_pessoa_id']==TipoPessoa::JURIDICA){

                    BootstrapFormBuilder::showField(self::$formName, 'cpf_cnpj');
                    BootstrapFormBuilder::hideField(self::$formName, 'nacionalidade_id');

                    TScript::create("$(\"[name='orgao_emissor']\").closest('.fb-inline-field-container').hide()");
                    TScript::create("$('label:contains(\"Órgão emissor:\")').hide();");

                    TEntry::changeMask(self::$formName, 'cpf_cnpj', '##.###.###/####-##');

                    TScript::create('$("button[name=\'btnBuscarCNPJ\'").attr("disabled", false);');

                    TScript::create("$('label:contains(\"CPF:\")').html('CNPJ:')");
                    TScript::create("$('label:contains(\"RG:\")').html('Inscrição Estadual:')");
                    TScript::create("$('label:contains(\"Data de nascimento:\")').html('Data de abertura:')");
                }
            }
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
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

    public static function onAdicionarGrupoCliente($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $objeto = new PessoaGrupo();
            $objeto->pessoa_id = $param['pessoa_id'];
            $objeto->grupo_id = Grupo::CLIENTE;
            $objeto->store();
            TTransaction::close();

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ClienteList', 'onShow');
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());   
            TTransaction::rollback();
        }
    }

    public static function onEditCad($param = null) 
    {
        try 
        {
            $pageParam['key']=$param['pessoa_id'];
            TApplication::loadPage(__CLASS__, 'onEdit', $pageParam);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    private static function strNoAccent(string $s): string
    {
        if (class_exists('Transliterator')) {
            $tr = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($tr) { $s = $tr->transliterate($s); }
        } elseif (class_exists('Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
            $s = preg_replace('/\p{Mn}+/u', '', $s);
            $s = \Normalizer::normalize($s, \Normalizer::FORM_C);
        } else {
            $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        }
        return $s;
    }

    private static function makeNomeBusca(?string $nome): string
    {
        $nome = (string) $nome;
        $nome = trim($nome);
        $nome = self::strNoAccent($nome);               // remove acento
        $nome = preg_replace('/\s+/u', ' ', $nome);     // normaliza espaços
        $nome = mb_strtoupper($nome, 'UTF-8');          // **MAIÚSCULO**
        return $nome;
    }

}

