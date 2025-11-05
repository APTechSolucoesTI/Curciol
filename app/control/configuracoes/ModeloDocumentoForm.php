<?php

class ModeloDocumentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'ModeloDocumento';
    private static $primaryKey = 'id';
    private static $formName = 'form_ModeloDocumento';

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
        $this->form->setFormTitle("Cadastro de modelo de documento");

        $criteria_tipo_modelo_documento_id = new TCriteria();
        $criteria_aplicacao_id = new TCriteria();

        $variaveis_pf  = implode(' | ', array_keys(ModeloDocumento::VARIAVEIS_PF));
        $variaveis_pj  = implode(' | ', array_keys(ModeloDocumento::VARIAVEIS_PJ));
        $variaveis_pfr = implode(' | ', array_keys(ModeloDocumento::VARIAVEIS_PFR));

        $id = new TEntry('id');
        $ativo = new TCheckButton('ativo');
        $nome = new TEntry('nome');
        $tipo_modelo_documento_id = new TDBCombo('tipo_modelo_documento_id', 'escritorio', 'TipoModeloDocumento', 'id', '{nome}','nome asc' , $criteria_tipo_modelo_documento_id );
        $aplicacao_id = new TDBCheckGroup('aplicacao_id', 'escritorio', 'ModeloDocTipoAplicacao', 'id', '{nome}','nome asc' , $criteria_aplicacao_id );
        $pf_objeto = new TCheckButton('pf_objeto');
        $pf_pagamento = new TCheckButton('pf_pagamento');
        $Selecionar = new TCheckButton('Selecionar');
        $pf_rg = new TCheckButton('pf_rg');
        $pf_nacionalidade = new TCheckButton('pf_nacionalidade');
        $pf_estado_civil = new TCheckButton('pf_estado_civil');
        $pf_profissao = new TCheckButton('pf_profissao');
        $pf_endereco = new TCheckButton('pf_endereco');
        $pf_data_nascimento = new TCheckButton('pf_data_nascimento');
        $pf_filename = new TFile('pf_filename');
        $pj_objeto = new TCheckButton('pj_objeto');
        $pj_pagamento = new TCheckButton('pj_pagamento');
        $pj_cnpj = new TCheckButton('pj_cnpj');
        $pj_endereco = new TCheckButton('pj_endereco');
        $pj_data_abertura = new TCheckButton('pj_data_abertura');
        $pj_rep_cpf = new TCheckButton('pj_rep_cpf');
        $pj_rep_rg = new TCheckButton('pj_rep_rg');
        $pj_rep_data_nascimento = new TCheckButton('pj_rep_data_nascimento');
        $pj_rep_nacionalidade = new TCheckButton('pj_rep_nacionalidade');
        $pj_rep_estado_civil = new TCheckButton('pj_rep_estado_civil');
        $pj_rep_profissao = new TCheckButton('pj_rep_profissao');
        $pj_rep_endereco = new TCheckButton('pj_rep_endereco');
        $pj_filename = new TFile('pj_filename');
        $pfr_objeto = new TCheckButton('pfr_objeto');
        $pfr_pagamento = new TCheckButton('pfr_pagamento');
        $pfr_cpf = new TCheckButton('pfr_cpf');
        $pfr_rg = new TCheckButton('pfr_rg');
        $pfr_data_nascimento = new TCheckButton('pfr_data_nascimento');
        $pfr_nacionalidade = new TCheckButton('pfr_nacionalidade');
        $pfr_profissao = new TCheckButton('pfr_profissao');
        $pfr_estado_civil = new TCheckButton('pfr_estado_civil');
        $pfr_endereco = new TCheckButton('pfr_endereco');
        $pfr_rep_cpf = new TCheckButton('pfr_rep_cpf');
        $pfr_rep_rg = new TCheckButton('pfr_rep_rg');
        $pfr_rep_data_nascimento = new TCheckButton('pfr_rep_data_nascimento');
        $pfr_rep_nacionalidade = new TCheckButton('pfr_rep_nacionalidade');
        $pfr_rep_profissao = new TCheckButton('pfr_rep_profissao');
        $pfr_rep_estado_civil = new TCheckButton('pfr_rep_estado_civil');
        $pfr_rep_endereco = new TCheckButton('pfr_rep_endereco');
        $pfr_filename = new TFile('pfr_filename');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $ativo->addValidation("Ativo", new TRequiredValidator()); 
        $nome->addValidation("Nome", new TRequiredValidator()); 
        $tipo_modelo_documento_id->addValidation("Tipo de modelo", new TRequiredValidator()); 

        $nome->forceUpperCase();
        $tipo_modelo_documento_id->enableSearch();
        $aplicacao_id->setLayout('horizontal');
        $aplicacao_id->setUseButton();
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $pf_filename->enableFileHandling();
        $pj_filename->enableFileHandling();
        $pfr_filename->enableFileHandling();

        $pf_filename->setAllowedExtensions(["docx"]);
        $pj_filename->setAllowedExtensions(["docx"]);
        $pfr_filename->setAllowedExtensions(["docx"]);

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $ativo->setValue('S');
        $pj_data_abertura->setValue('N');
        $pf_data_nascimento->setValue('N');
        $pfr_data_nascimento->setValue('N');
        $pj_rep_data_nascimento->setValue('N');
        $pfr_rep_data_nascimento->setValue('N');

        $id->setSize(100);
        $nome->setSize('100%');
        $aplicacao_id->setSize(120);
        $pf_filename->setSize('100%');
        $pj_filename->setSize('100%');
        $pfr_filename->setSize('100%');
        $data_criacao->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $tipo_modelo_documento_id->setSize('100%');

        $ativo->setUseSwitch(true, 'blue');
        $pf_rg->setUseSwitch(true, 'blue');
        $pfr_rg->setUseSwitch(true, 'blue');
        $pj_cnpj->setUseSwitch(true, 'blue');
        $pfr_cpf->setUseSwitch(true, 'blue');
        $pf_objeto->setUseSwitch(true, 'blue');
        $pj_objeto->setUseSwitch(true, 'blue');
        $pj_rep_rg->setUseSwitch(true, 'blue');
        $Selecionar->setUseSwitch(true, 'blue');
        $pj_rep_cpf->setUseSwitch(true, 'blue');
        $pfr_objeto->setUseSwitch(true, 'blue');
        $pfr_rep_rg->setUseSwitch(true, 'blue');
        $pf_endereco->setUseSwitch(true, 'blue');
        $pj_endereco->setUseSwitch(true, 'blue');
        $pfr_rep_cpf->setUseSwitch(true, 'blue');
        $pf_pagamento->setUseSwitch(true, 'blue');
        $pf_profissao->setUseSwitch(true, 'blue');
        $pj_pagamento->setUseSwitch(true, 'blue');
        $pfr_endereco->setUseSwitch(true, 'blue');
        $pfr_pagamento->setUseSwitch(true, 'blue');
        $pfr_profissao->setUseSwitch(true, 'blue');
        $pf_estado_civil->setUseSwitch(true, 'blue');
        $pj_rep_endereco->setUseSwitch(true, 'blue');
        $pf_nacionalidade->setUseSwitch(true, 'blue');
        $pj_data_abertura->setUseSwitch(true, 'blue');
        $pj_rep_profissao->setUseSwitch(true, 'blue');
        $pfr_estado_civil->setUseSwitch(true, 'blue');
        $pfr_rep_endereco->setUseSwitch(true, 'blue');
        $pfr_nacionalidade->setUseSwitch(true, 'blue');
        $pfr_rep_profissao->setUseSwitch(true, 'blue');
        $pf_data_nascimento->setUseSwitch(true, 'blue');
        $pj_rep_estado_civil->setUseSwitch(true, 'blue');
        $pfr_data_nascimento->setUseSwitch(true, 'blue');
        $pj_rep_nacionalidade->setUseSwitch(true, 'blue');
        $pfr_rep_estado_civil->setUseSwitch(true, 'blue');
        $pfr_rep_nacionalidade->setUseSwitch(true, 'blue');
        $pj_rep_data_nascimento->setUseSwitch(true, 'blue');
        $pfr_rep_data_nascimento->setUseSwitch(true, 'blue');

        $ativo->setIndexValue("S");
        $pf_rg->setIndexValue("S");
        $pfr_rg->setIndexValue("S");
        $pj_cnpj->setIndexValue("S");
        $pfr_cpf->setIndexValue("S");
        $pf_objeto->setIndexValue("S");
        $pj_objeto->setIndexValue("S");
        $pj_rep_rg->setIndexValue("S");
        $Selecionar->setIndexValue("S");
        $pj_rep_cpf->setIndexValue("S");
        $pfr_objeto->setIndexValue("S");
        $pfr_rep_rg->setIndexValue("S");
        $pf_endereco->setIndexValue("S");
        $pj_endereco->setIndexValue("S");
        $pfr_rep_cpf->setIndexValue("S");
        $pf_pagamento->setIndexValue("S");
        $pf_profissao->setIndexValue("S");
        $pj_pagamento->setIndexValue("S");
        $pfr_endereco->setIndexValue("S");
        $pfr_pagamento->setIndexValue("S");
        $pfr_profissao->setIndexValue("S");
        $pf_estado_civil->setIndexValue("S");
        $pj_rep_endereco->setIndexValue("S");
        $pf_nacionalidade->setIndexValue("S");
        $pj_data_abertura->setIndexValue("S");
        $pj_rep_profissao->setIndexValue("S");
        $pfr_estado_civil->setIndexValue("S");
        $pfr_rep_endereco->setIndexValue("S");
        $pfr_nacionalidade->setIndexValue("S");
        $pfr_rep_profissao->setIndexValue("S");
        $pf_data_nascimento->setIndexValue("S");
        $pj_rep_estado_civil->setIndexValue("S");
        $pfr_data_nascimento->setIndexValue("S");
        $pj_rep_nacionalidade->setIndexValue("S");
        $pfr_rep_estado_civil->setIndexValue("S");
        $pfr_rep_nacionalidade->setIndexValue("S");
        $pj_rep_data_nascimento->setIndexValue("S");
        $pfr_rep_data_nascimento->setIndexValue("S");

        $ativo->setInactiveIndexValue("N");
        $pf_rg->setInactiveIndexValue("N");
        $pfr_rg->setInactiveIndexValue("N");
        $pj_cnpj->setInactiveIndexValue("N");
        $pfr_cpf->setInactiveIndexValue("N");
        $pf_objeto->setInactiveIndexValue("N");
        $pj_objeto->setInactiveIndexValue("N");
        $pj_rep_rg->setInactiveIndexValue("N");
        $Selecionar->setInactiveIndexValue("N");
        $pj_rep_cpf->setInactiveIndexValue("N");
        $pfr_objeto->setInactiveIndexValue("N");
        $pfr_rep_rg->setInactiveIndexValue("N");
        $pf_endereco->setInactiveIndexValue("N");
        $pj_endereco->setInactiveIndexValue("N");
        $pfr_rep_cpf->setInactiveIndexValue("N");
        $pf_pagamento->setInactiveIndexValue("N");
        $pf_profissao->setInactiveIndexValue("N");
        $pj_pagamento->setInactiveIndexValue("N");
        $pfr_endereco->setInactiveIndexValue("N");
        $pfr_pagamento->setInactiveIndexValue("N");
        $pfr_profissao->setInactiveIndexValue("N");
        $pf_estado_civil->setInactiveIndexValue("N");
        $pj_rep_endereco->setInactiveIndexValue("N");
        $pf_nacionalidade->setInactiveIndexValue("N");
        $pj_data_abertura->setInactiveIndexValue("N");
        $pj_rep_profissao->setInactiveIndexValue("N");
        $pfr_estado_civil->setInactiveIndexValue("N");
        $pfr_rep_endereco->setInactiveIndexValue("N");
        $pfr_nacionalidade->setInactiveIndexValue("N");
        $pfr_rep_profissao->setInactiveIndexValue("N");
        $pf_data_nascimento->setInactiveIndexValue("N");
        $pj_rep_estado_civil->setInactiveIndexValue("N");
        $pfr_data_nascimento->setInactiveIndexValue("N");
        $pj_rep_nacionalidade->setInactiveIndexValue("N");
        $pfr_rep_estado_civil->setInactiveIndexValue("N");
        $pfr_rep_nacionalidade->setInactiveIndexValue("N");
        $pj_rep_data_nascimento->setInactiveIndexValue("N");
        $pfr_rep_data_nascimento->setInactiveIndexValue("N");

        $this->form->appendPage("Dados cadastrais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[new TLabel("Ativo:", '#ff0000', '14px', null, '100%'),$ativo]);
        $row1->layout = [' col-sm-8',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Tipo de modelo:", '#FF0000', '14px', null, '100%'),$tipo_modelo_documento_id]);
        $row2->layout = [' col-sm-8','col-sm-4'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row4 = $this->form->addFields([new TLabel("Aplicações:", null, '14px', 'B', '100%'),$aplicacao_id]);
        $row4->layout = ['col-sm-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);

        $tab_67b72cfbbce07 = new BootstrapFormBuilder('tab_67b72cfbbce07');
        $this->tab_67b72cfbbce07 = $tab_67b72cfbbce07;
        $tab_67b72cfbbce07->setProperty('style', 'border:none; box-shadow:none;');

        $tab_67b72cfbbce07->appendPage("Pessoa Física");

        $tab_67b72cfbbce07->addFields([new THidden('current_tab_tab_67b72cfbbce07')]);
        $tab_67b72cfbbce07->setTabFunction("$('[name=current_tab_tab_67b72cfbbce07]').val($(this).attr('data-current_page'));");

        $row6 = $tab_67b72cfbbce07->addFields([new TLabel("Variáveis", null, '14px', 'B', '100%'),new TLabel("<small>Use as variáveis abaixo para preencher o documento automaticamente</small>", null, '14px', null, '100%'),new TLabel("{$variaveis_pf}", null, '14px', 'B', '100%')]);
        $row6->layout = [' col-sm-12'];

        $row7 = $tab_67b72cfbbce07->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row8 = $tab_67b72cfbbce07->addFields([new TLabel("Informações obrigatórias:", null, '14px', 'B', '100%'),new TLabel("<small>Selecione as informações necessárias para este modelo de documento. ATENÇÃO: Não será possível gerar o documento se alguma informação obrigatória estiver vazia.</small>", null, '14px', null)]);
        $row8->layout = [' col-sm-12'];

        $row9 = $tab_67b72cfbbce07->addFields([new TLabel("Do contrato:", null, '14px', 'B')]);
        $row9->layout = [' col-sm-12'];

        $row10 = $tab_67b72cfbbce07->addFields([new TLabel("Objeto:", null, '14px', null, '100%'),$pf_objeto],[new TLabel("Informações de Pagamento:", null, '14px', null, '100%'),$pf_pagamento]);
        $row10->layout = ['col-sm-2','col-sm-2'];

        $row11 = $tab_67b72cfbbce07->addFields([new TLabel("Do cliente:", null, '14px', 'B')]);
        $row11->layout = [' col-sm-12'];

        $row12 = $tab_67b72cfbbce07->addFields([new TLabel("CPF:", null, '14px', null, '100%'),$Selecionar],[new TLabel("RG + Orgão emissor:", null, '14px', null, '100%'),$pf_rg],[new TLabel("Nacionalidade:", null, '14px', null, '100%'),$pf_nacionalidade],[new TLabel("Estado Civil:", null, '14px', null, '100%'),$pf_estado_civil],[new TLabel("Profissão:", null, '14px', null, '100%'),$pf_profissao],[new TLabel("Endereço:", null, '14px', null, '100%'),$pf_endereco],[new TLabel("Data de nascimento:", null, '14px', null, '100%'),$pf_data_nascimento]);
        $row12->layout = ['col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2'];

        $row13 = $tab_67b72cfbbce07->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row14 = $tab_67b72cfbbce07->addFields([new TLabel("Arquivo:", '#FF0000', '14px', null, '100%'),$pf_filename]);
        $row14->layout = [' col-sm-12'];

        $tab_67b72cfbbce07->appendPage("Pessoa Jurídica");
        $row15 = $tab_67b72cfbbce07->addFields([new TLabel("Variáveis", null, '14px', 'B', '100%'),new TLabel("<small>Use as variáveis abaixo para preencher o documento automaticamente</small>", null, '14px', null, '100%'),new TLabel("{$variaveis_pj}", null, '14px', 'B')]);
        $row15->layout = [' col-sm-12'];

        $row16 = $tab_67b72cfbbce07->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row17 = $tab_67b72cfbbce07->addFields([new TLabel("Informações obrigatórias:", null, '14px', 'B', '100%'),new TLabel("<small>Selecione as informações necessárias para este modelo de documento. ATENÇÃO: Não será possível gerar o documento se alguma informação obrigatória estiver vazia.</small>", null, '14px', null, '100%')]);
        $row17->layout = [' col-sm-12'];

        $row18 = $tab_67b72cfbbce07->addFields([new TLabel("Do contrato:", null, '14px', 'B')]);
        $row18->layout = [' col-sm-12'];

        $row19 = $tab_67b72cfbbce07->addFields([new TLabel("Objeto:", null, '12px', null, '100%'),$pj_objeto],[new TLabel("Informação de pagamento:", null, '12px', null, '100%'),$pj_pagamento]);
        $row19->layout = ['col-sm-2','col-sm-2'];

        $row20 = $tab_67b72cfbbce07->addFields([new TLabel("Do cliente:", null, '14px', 'B')]);
        $row20->layout = [' col-sm-12'];

        $row21 = $tab_67b72cfbbce07->addFields([new TLabel("CNPJ:", null, '12px', null, '100%'),$pj_cnpj],[new TLabel("Endereço:", null, '12px', null, '100%'),$pj_endereco],[new TLabel("Data de abertura:", null, '14px', null, '100%'),$pj_data_abertura]);
        $row21->layout = ['col-sm-2','col-sm-2','col-sm-2'];

        $row22 = $tab_67b72cfbbce07->addFields([new TLabel("Do representante:", null, '14px', 'B')]);
        $row22->layout = [' col-sm-12'];

        $row23 = $tab_67b72cfbbce07->addFields([new TLabel("<small>CPF:</small>", null, '12px', null, '100%'),$pj_rep_cpf],[new TLabel("RG + Orgão emissor:", null, '12px', null, '100%'),$pj_rep_rg],[new TLabel("Data de nascimento:", null, '14px', null, '100%'),$pj_rep_data_nascimento],[new TLabel("Nacionalidade:", null, '12px', null, '100%'),$pj_rep_nacionalidade],[new TLabel("Estado civil:", null, '12px', null, '100%'),$pj_rep_estado_civil],[new TLabel("Profissão:", null, '12px', null, '100%'),$pj_rep_profissao],[new TLabel("Endereço:", null, '12px', null, '100%'),$pj_rep_endereco]);
        $row23->layout = ['col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2'];

        $row24 = $tab_67b72cfbbce07->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row25 = $tab_67b72cfbbce07->addFields([new TLabel("Arquivo:", '#FF0000', '14px', null, '100%'),$pj_filename]);
        $row25->layout = [' col-sm-12'];

        $tab_67b72cfbbce07->appendPage("Pessoa Física com Representante");
        $row26 = $tab_67b72cfbbce07->addFields([new TLabel("Variáveis", null, '14px', 'B', '100%'),new TLabel("<small>Use as variáveis abaixo para preencher o documento automaticamente</small>", null, '14px', null, '100%'),new TLabel("{$variaveis_pfr}", null, '14px', 'B', '100%')]);
        $row26->layout = [' col-sm-12'];

        $row27 = $tab_67b72cfbbce07->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row28 = $tab_67b72cfbbce07->addFields([new TLabel("Informações obrigatórias:", null, '14px', 'B', '100%'),new TLabel("<small>Selecione as informações necessárias para este modelo de documento. ATENÇÃO: Não será possível gerar o documento se alguma informação obrigatória estiver vazia.</small>", null, '14px', null, '100%')]);
        $row28->layout = [' col-sm-12'];

        $row29 = $tab_67b72cfbbce07->addFields([new TLabel("Do contrato:", null, '14px', 'B')]);
        $row29->layout = [' col-sm-12'];

        $row30 = $tab_67b72cfbbce07->addFields([new TLabel("Objeto:", null, '14px', null, '100%'),$pfr_objeto],[new TLabel("Informações de pagamento:", null, '14px', null, '100%'),$pfr_pagamento]);
        $row30->layout = ['col-sm-2','col-sm-2'];

        $row31 = $tab_67b72cfbbce07->addFields([new TLabel("Do cliente:", null, '14px', 'B')]);
        $row31->layout = [' col-sm-12'];

        $row32 = $tab_67b72cfbbce07->addFields([new TLabel("CPF:", null, '14px', null, '100%'),$pfr_cpf],[new TLabel("RG + Orgão emissor:", null, '14px', null, '100%'),$pfr_rg],[new TLabel("Data de nascimento:", null, '14px', null, '100%'),$pfr_data_nascimento],[new TLabel("Nacionalidade:", null, '14px', null, '100%'),$pfr_nacionalidade],[new TLabel("Profissão:", null, '14px', null, '100%'),$pfr_profissao],[new TLabel("Estado civil:", null, '14px', null, '100%'),$pfr_estado_civil],[new TLabel("Endereço:", null, '14px', null, '100%'),$pfr_endereco]);
        $row32->layout = ['col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2'];

        $row33 = $tab_67b72cfbbce07->addFields([new TLabel("Do representante:", null, '14px', 'B', '100%')]);
        $row33->layout = [' col-sm-12'];

        $row34 = $tab_67b72cfbbce07->addFields([new TLabel("CPF:", null, '14px', null, '100%'),$pfr_rep_cpf],[new TLabel("RG + Orgão emissor:", null, '14px', null),$pfr_rep_rg],[new TLabel("Data de nascimento:", null, '14px', null, '100%'),$pfr_rep_data_nascimento],[new TLabel("Nacionalidade:", null, '14px', null, '100%'),$pfr_rep_nacionalidade],[new TLabel("Profissão:", null, '14px', null, '100%'),$pfr_rep_profissao],[new TLabel("Estado civil:", null, '14px', null, '100%'),$pfr_rep_estado_civil],[new TLabel("Endereço:", null, '14px', null, '100%'),$pfr_rep_endereco]);
        $row34->layout = ['col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2','col-sm-2'];

        $row35 = $tab_67b72cfbbce07->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row36 = $tab_67b72cfbbce07->addFields([new TLabel("Arquivo:", '#FF0000', '14px', null, '100%'),$pfr_filename]);
        $row36->layout = [' col-sm-12'];

        $row37 = $this->form->addFields([$tab_67b72cfbbce07]);
        $row37->layout = [' col-sm-12'];

        $this->form->appendPage("Informações de cadastro");
        $row38 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row38->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ModeloDocumentoHeaderList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ModeloDocumentoForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new ModeloDocumento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
            $object->nome = str_replace('/', '_', $object->nome);

            $pf_filename_dir = 'files/documents';
            $pj_filename_dir = 'files/documents';
            $pfr_filename_dir = 'files/documents';  

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
                $new = true;
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
                $new = false;
            }
            $object->store(); // save the object 

            $repository = ModeloDocAplicacao::where('modelo_documento_id', '=', $object->id);
            $repository->delete(); 

            if ($data->aplicacao_id) 
            {
                foreach ($data->aplicacao_id as $aplicacao_id_value) 
                {
                    $modelo_doc_aplicacao = new ModeloDocAplicacao;

                    $modelo_doc_aplicacao->tipo_aplicacao_id = $aplicacao_id_value;
                    $modelo_doc_aplicacao->modelo_documento_id = $object->id;
                    $modelo_doc_aplicacao->store();
                }
            }

            $this->saveFile($object, $data, 'pf_filename', $pf_filename_dir);
            $this->saveFile($object, $data, 'pj_filename', $pj_filename_dir);
            $this->saveFile($object, $data, 'pfr_filename', $pfr_filename_dir);
            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(isset($data->pf_filename_file_data)){
                if(!$data->pf_filename_file_data->fileAdded && !$data->id) throw new Exception("Falha ao armazenar arquivo de pessoa física.");

                $pessoa_fisica = ModeloDocumentoPf::where('modelo_documento_id','=',$object->id)->first() ??  new ModeloDocumentoPf();

                if(!$pessoa_fisica) throw new Exception("Erro ao criar objeto de pessoa fisica.");

                $pessoa_fisica->modelo_documento_id = $object->id;
                $pessoa_fisica->filename = $data->pf_filename_file_data->fileName;
                $pessoa_fisica->objeto = $data->pf_objeto;
                $pessoa_fisica->informacoes_pagamento = $data->pf_pagamento;
                $pessoa_fisica->nacionalidade = $data->pf_nacionalidade;
                $pessoa_fisica->estado_civil = $data->pf_estado_civil;
                $pessoa_fisica->profissao = $data->pf_profissao;
                $pessoa_fisica->rg = $data->pf_rg;
                $pessoa_fisica->cpf = $data->pf_cpf;
                $pessoa_fisica->data_nascimento = $data->pf_data_nascimento;
                $pessoa_fisica->endereco = $data->pf_endereco;
                $pessoa_fisica->store();
            }

            if(isset($data->pj_filename_file_data)){
                if(!$data->pj_filename_file_data->fileAdded && !$data->id) throw new Exception("Falha ao armazenar arquivo de pessoa jurídica.");

                $pessoa_juridica = ModeloDocumentoPj::where('modelo_documento_id','=',$object->id)->first() ?? new ModeloDocumentoPj();

                if(!$pessoa_juridica) throw new Exception("Erro ao criar objeto de pessoa jurídica.");

                $pessoa_juridica->modelo_documento_id = $object->id;
                $pessoa_juridica->filename = $data->pj_filename_file_data->fileName;
                $pessoa_juridica->objeto = $data->pj_objeto;
                $pessoa_juridica->informacoes_pagamento = $data->pj_pagamento;
                $pessoa_juridica->cnpj = $data->pj_cnpj;
                $pessoa_juridica->data_abertura = $data->pj_data_abertura;
                $pessoa_juridica->endereco = $data->pj_endereco;
                $pessoa_juridica->nacionalidade_rep = $data->pj_rep_nacionalidade;
                $pessoa_juridica->estado_civil_rep = $data->pj_rep_estado_civil;
                $pessoa_juridica->profissao_rep = $data->pj_rep_profissao;
                $pessoa_juridica->rg_rep = $data->pj_rep_rg;
                $pessoa_juridica->cpf_rep = $data->pj_rep_cpf;
                $pessoa_juridica->data_nascimento_rep = $data->pj_rep_data_nascimento;
                $pessoa_juridica->endereco_rep = $data->pj_rep_endereco;
                $pessoa_juridica->store();
            }

            if(isset($data->pfr_filename_file_data)){
                if(!$data->pfr_filename_file_data->fileAdded && !$data->id) throw new Exception("Falha ao armazenar arquivo de pessoa física com representante.");

                $pessoa_fisica_repres = ModeloDocumentoPfrep::where('modelo_documento_id','=',$object->id)->first() ?? new ModeloDocumentoPfrep();

                if(!$pessoa_fisica_repres) throw new Exception("Erro ao criar objeto de pessoa fisica com representante.");

                $pessoa_fisica_repres->modelo_documento_id = $object->id;
                $pessoa_fisica_repres->filename = $data->pfr_filename_file_data->fileName;
                $pessoa_fisica_repres->objeto = $data->pfr_objeto;
                $pessoa_fisica_repres->informacoes_pagamento = $data->pfr_pagamento;
                $pessoa_fisica_repres->nacionalidade = $data->pfr_nacionalidade;
                $pessoa_fisica_repres->estado_civil = $data->pfr_estado_civil;
                $pessoa_fisica_repres->profissao = $data->pfr_profissao;
                $pessoa_fisica_repres->rg = $data->pfr_rg;
                $pessoa_fisica_repres->cpf = $data->pfr_cpf;
                $pessoa_fisica_repres->data_nascimento = $data->pfr_data_nascimento;
                $pessoa_fisica_repres->endereco = $data->pfr_endereco;
                $pessoa_fisica_repres->nacionalidade_rep = $data->pfr_rep_nacionalidade;
                $pessoa_fisica_repres->estado_civil_rep = $data->pfr_rep_estado_civil;
                $pessoa_fisica_repres->profissao_rep = $data->pfr_rep_profissao;
                $pessoa_fisica_repres->rg_rep = $data->pfr_rep_rg;
                $pessoa_fisica_repres->cpf_rep = $data->pfr_rep_cpf;
                $pessoa_fisica_repres->data_nascimento_rep = $data->pfr_rep_data_nascimento;
                $pessoa_fisica_repres->endereco_rep = $data->pfr_rep_endereco;
                $pessoa_fisica_repres->store();
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ModeloDocumentoHeaderList', 'onShow', $loadPageParam); 

                        TScript::create("Template.closeRightPanel();"); 

        }
        catch (Exception $e) // in case of exception
        {

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

                $object = new ModeloDocumento($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $object->aplicacao_id = ModeloDocAplicacao::where('modelo_documento_id', '=', $object->id)->getIndexedArray('tipo_aplicacao_id', 'tipo_aplicacao_id');

                $pessoa_fisica = ModeloDocumentoPf::where('modelo_documento_id','=',$object->id)->first();

                if($pessoa_fisica){
                    $object->pf_filename = $pessoa_fisica->filename;
                    $object->pf_objeto = $pessoa_fisica->objeto;
                    $object->pf_pagamento = $pessoa_fisica->informacoes_pagamento;
                    $object->pf_dt_nascimento = $pessoa_fisica->data_nascimento;
                    $object->pf_nacionalidade = $pessoa_fisica->nacionalidade;
                    $object->pf_estado_civil = $pessoa_fisica->estado_civil;
                    $object->pf_profissao = $pessoa_fisica->profissao;
                    $object->pf_rg = $pessoa_fisica->rg;
                    $object->pf_cpf = $pessoa_fisica->cpf;
                    $object->pf_data_nascimento = $pessoa_fisica->data_nascimento;
                    $object->pf_endereco = $pessoa_fisica->endereco;
                    $object->pf_telefone = $pessoa_fisica->telefone;
                    $object->pf_email = $pessoa_fisica->email;
                    $object->pf_uni_trabalho = $pessoa_fisica->unidade_trabalho;
                }

                $pessoa_juridica = ModeloDocumentoPj::where('modelo_documento_id','=',$object->id)->first();

                if($pessoa_juridica){
                    $object->pj_filename = $pessoa_juridica->filename;
                    $object->pj_objeto = $pessoa_juridica->objeto;
                    $object->pj_pagamento = $pessoa_juridica->informacoes_pagamento;
                    $object->pj_cnpj = $pessoa_juridica->cnpj;
                    $object->pj_data_abertura = $pessoa_juridica->data_abertura;
                    $object->pj_endereco = $pessoa_juridica->endereco;
                    $object->pj_rep_nacionalidade = $pessoa_juridica->nacionalidade_rep;
                    $object->pj_rep_estado_civil = $pessoa_juridica->estado_civil_rep;
                    $object->pj_rep_profissao = $pessoa_juridica->profissao_rep;
                    $object->pj_rep_rg = $pessoa_juridica->rg_rep;
                    $object->pj_rep_cpf = $pessoa_juridica->cpf_rep;
                    $object->pj_rep_data_nascimento = $pessoa_juridica->data_nascimento_rep;
                    $object->pj_rep_endereco = $pessoa_juridica->endereco_rep;
                }

                $pessoa_fisica_repres = ModeloDocumentoPfrep::where('modelo_documento_id','=',$object->id)->first();

                if($pessoa_fisica_repres){
                    $object->pfr_filename = $pessoa_fisica_repres->filename;
                    $object->pfr_objeto = $pessoa_fisica_repres->objeto;
                    $object->pfr_pagamento = $pessoa_fisica_repres->informacoes_pagamento;
                    $object->pfr_nacionalidade = $pessoa_fisica_repres->nacionalidade;
                    $object->pfr_estado_civil = $pessoa_fisica_repres->estado_civil;
                    $object->pfr_profissao = $pessoa_fisica_repres->profissao;
                    $object->pfr_rg = $pessoa_fisica_repres->rg;
                    $object->pfr_cpf = $pessoa_fisica_repres->cpf;
                    $object->pfr_data_nascimento = $pessoa_fisica_repres->data_nascimento;
                    $object->pfr_endereco = $pessoa_fisica_repres->endereco;
                    $object->pfr_rep_nacionalidade = $pessoa_fisica_repres->nacionalidade_rep;
                    $object->pfr_rep_estado_civil = $pessoa_fisica_repres->estado_civil_rep;
                    $object->pfr_rep_profissao = $pessoa_fisica_repres->profissao_rep;
                    $object->pfr_rep_rg = $pessoa_fisica_repres->rg_rep;
                    $object->pfr_rep_cpf = $pessoa_fisica_repres->cpf_rep;
                    $object->pfr_rep_data_nascimento = $pessoa_fisica_repres->data_nascimento_rep;
                    $object->pfr_rep_endereco = $pessoa_fisica_repres->endereco_rep;
                }

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

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

