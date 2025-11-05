<?php

class PessoaEditHeaderList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'formList_Pessoa';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();
        // creates the form

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->limit = 20;

        $criteria_tipo_pessoa_id = new TCriteria();
        $criteria_tipo_pessoa_nome = new TCriteria();
        $criteria_sexo_id = new TCriteria();
        $criteria_sexo_nome = new TCriteria();
        $criteria_nacionalidade_id = new TCriteria();
        $criteria_nacionalidade_nome = new TCriteria();
        $criteria_estado_civil_id = new TCriteria();
        $criteria_estado_civil_nome = new TCriteria();
        $criteria_situacao_profissional_id = new TCriteria();
        $criteria_situacao_profissional_nome = new TCriteria();
        $criteria_modificacao_user_id = new TCriteria();

        $tipo_pessoa_id = new TDBCombo('tipo_pessoa_id', 'escritorio', 'TipoPessoa', 'id', '{nome}','nome asc' , $criteria_tipo_pessoa_id );
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $email = new TEntry('email');
        $telefone = new TEntry('telefone');
        $aceita_receber_mensagen_whatsapp = new TEntry('aceita_receber_mensagen_whatsapp');
        $dt_nascimento_abertura = new TEntry('dt_nascimento_abertura');
        $dt_falecimento = new TEntry('dt_falecimento');
        $cpf_cnpj = new TEntry('cpf_cnpj');
        $rg_ie = new TEntry('rg_ie');
        $orgao_emissor = new TEntry('orgao_emissor');
        $sexo_id = new TDBCombo('sexo_id', 'escritorio', 'Sexo', 'id', '{nome}','nome asc' , $criteria_sexo_id );
        $nacionalidade_id = new TDBCombo('nacionalidade_id', 'escritorio', 'Nacionalidade', 'id', '{nome}','nome asc' , $criteria_nacionalidade_id );
        $estado_civil_id = new TDBCombo('estado_civil_id', 'escritorio', 'EstadoCivil', 'id', '{nome}','nome asc' , $criteria_estado_civil_id );
        $profissao = new TEntry('profissao');
        $nit = new TEntry('nit');
        $ctps = new TEntry('ctps');
        $situacao_profissional_id = new TDBCombo('situacao_profissional_id', 'escritorio', 'SituacaoProfissional', 'id', '{nome}','nome asc' , $criteria_situacao_profissional_id );
        $orgao = new TEntry('orgao');
        $unidade = new TEntry('unidade');
        $data_modificacao = new TEntry('data_modificacao');
        $modificacao_user_id = new TDBCombo('modificacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_modificacao_user_id );

        $id->exitOnEnter();
        $nome->exitOnEnter();
        $email->exitOnEnter();
        $telefone->exitOnEnter();
        $aceita_receber_mensagen_whatsapp->exitOnEnter();
        $dt_nascimento_abertura->exitOnEnter();
        $dt_falecimento->exitOnEnter();
        $cpf_cnpj->exitOnEnter();
        $rg_ie->exitOnEnter();
        $orgao_emissor->exitOnEnter();
        $profissao->exitOnEnter();
        $nit->exitOnEnter();
        $ctps->exitOnEnter();
        $orgao->exitOnEnter();
        $unidade->exitOnEnter();
        $data_modificacao->exitOnEnter();

        $id->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $nome->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $email->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $telefone->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $aceita_receber_mensagen_whatsapp->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $dt_nascimento_abertura->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $dt_falecimento->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $cpf_cnpj->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $rg_ie->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $orgao_emissor->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $profissao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $nit->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $ctps->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $orgao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $unidade->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $data_modificacao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $tipo_pessoa_id->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $sexo_id->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $nacionalidade_id->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $estado_civil_id->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $situacao_profissional_id->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $modificacao_user_id->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $sexo_id->enableSearch();
        $tipo_pessoa_id->enableSearch();
        $estado_civil_id->enableSearch();
        $nacionalidade_id->enableSearch();
        $modificacao_user_id->enableSearch();
        $situacao_profissional_id->enableSearch();

        $id->setSize('100%');
        $nit->setSize('100%');
        $nome->setSize('100%');
        $ctps->setSize('100%');
        $email->setSize('100%');
        $rg_ie->setSize('100%');
        $orgao->setSize('100%');
        $sexo_id->setSize('100%');
        $unidade->setSize('100%');
        $telefone->setSize('100%');
        $cpf_cnpj->setSize('100%');
        $profissao->setSize('100%');
        $orgao_emissor->setSize('100%');
        $tipo_pessoa_id->setSize('100%');
        $dt_falecimento->setSize('100%');
        $estado_civil_id->setSize('100%');
        $nacionalidade_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $modificacao_user_id->setSize('100%');
        $dt_nascimento_abertura->setSize('100%');
        $situacao_profissional_id->setSize('100%');
        $aceita_receber_mensagen_whatsapp->setSize('100%');


        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->disableDefaultClick();
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_tipo_pessoa_nome = new TDataGridColumn('tipo_pessoa->nome', "Tipo de pessoa", 'left');
        $column_id = new TDataGridColumn('id', "Código", 'center' , '70px');
        $column_nome = new TDataGridColumn('nome', "Nome", 'left');
        $column_email = new TDataGridColumn('email', "Email", 'left');
        $column_telefone = new TDataGridColumn('telefone', "Telefone", 'left');
        $column_aceita_receber_mensagen_whatsapp = new TDataGridColumn('aceita_receber_mensagen_whatsapp', "Aceita receber mensagem whatsapp", 'left');
        $column_dt_nascimento_abertura = new TDataGridColumn('dt_nascimento_abertura', "Data de nascimento/abertura", 'left');
        $column_dt_falecimento = new TDataGridColumn('dt_falecimento', "Data de falecimento", 'left');
        $column_cpf_cnpj = new TDataGridColumn('cpf_cnpj', "CFP/CNPJ", 'left');
        $column_rg_ie = new TDataGridColumn('rg_ie', "RG/IE", 'left');
        $column_orgao_emissor = new TDataGridColumn('orgao_emissor', "Órgão emissor", 'left');
        $column_sexo_nome = new TDataGridColumn('sexo->nome', "Sexo", 'left');
        $column_nacionalidade_nome = new TDataGridColumn('nacionalidade->nome', "Nacionalidade", 'left');
        $column_estado_civil_nome = new TDataGridColumn('estado_civil->nome', "Estado civil", 'left');
        $column_profissao = new TDataGridColumn('profissao', "Profissão", 'left');
        $column_nit = new TDataGridColumn('nit', "NIT", 'left');
        $column_ctps = new TDataGridColumn('ctps', "CTPS", 'left');
        $column_situacao_profissional_nome = new TDataGridColumn('situacao_profissional->nome', "Situação profissional", 'left');
        $column_orgao = new TDataGridColumn('orgao', "Órgão", 'left');
        $column_unidade = new TDataGridColumn('unidade', "Unidade", 'left');
        $column_data_modificacao = new TDataGridColumn('data_modificacao', "Data de modificação", 'left');
        $column_modificacao_user_name = new TDataGridColumn('modificacao_user->name', "Usuário de modificação", 'left');

        $column_tipo_pessoa_nome->setTransformer( function($value, $object, $row) {
            $criteria_tipo_pessoa_nome = new TCriteria(); 

            $pk = $object->getPrimaryKey();

            $tipo_pessoa_nome = new TDBCombo($object->$pk.'_'.'tipo_pessoa_nome', 'escritorio', 'TipoPessoa', 'id', '{nome}','nome asc' , $criteria_tipo_pessoa_nome );
            $tipo_pessoa_nome->setSize('100%');
            $tipo_pessoa_nome->enableSearch();

            $tipo_pessoa_nome->setFormName(self::$formName);
            $tipo_pessoa_nome->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'tipo_pessoa_nome');

            $tipo_pessoa_nome->setChangeAction( $action );

            return $tipo_pessoa_nome;
        });

        $column_nome->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $nome = new TEntry($object->$pk.'_'.'nome');
            $nome->setSize('100%');

            $nome->setFormName(self::$formName);
            $nome->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'nome');

            $nome->setExitAction( $action );

            return $nome;
        });

        $column_email->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $email = new TEntry($object->$pk.'_'.'email');
            $email->setSize('100%');

            $email->setFormName(self::$formName);
            $email->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'email');

            $email->setExitAction( $action );

            return $email;
        });

        $column_telefone->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $telefone = new TEntry($object->$pk.'_'.'telefone');
            $telefone->setSize('100%');

            $telefone->setFormName(self::$formName);
            $telefone->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'telefone');

            $telefone->setExitAction( $action );

            return $telefone;
        });

        $column_aceita_receber_mensagen_whatsapp->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $aceita_receber_mensagen_whatsapp = new TCombo($object->$pk.'_'.'aceita_receber_mensagen_whatsapp');
            $aceita_receber_mensagen_whatsapp->setSize('100%');
            $aceita_receber_mensagen_whatsapp->addItems(["T"=>"Sim","F"=>"Não"]);
            $aceita_receber_mensagen_whatsapp->enableSearch();

            $aceita_receber_mensagen_whatsapp->setFormName(self::$formName);
            $aceita_receber_mensagen_whatsapp->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'aceita_receber_mensagen_whatsapp');

            $aceita_receber_mensagen_whatsapp->setChangeAction( $action );

            return $aceita_receber_mensagen_whatsapp;
        });

        $column_dt_nascimento_abertura->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $dt_nascimento_abertura = new TDate($object->$pk.'_'.'dt_nascimento_abertura');
            $dt_nascimento_abertura->setSize('100%');
            $dt_nascimento_abertura->setMask('dd/mm/yyyy');
            $dt_nascimento_abertura->setDatabaseMask('yyyy-mm-dd');

            $dt_nascimento_abertura->setFormName(self::$formName);
            $dt_nascimento_abertura->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'dt_nascimento_abertura');
            $action->setParameter('_builder_field_options', base64_encode(serialize([
                'viewMask' => 'dd/mm/yyyy',
                'databaseMask' => 'yyyy-mm-dd',
                'component' => 'TDate'
            ])));

            $dt_nascimento_abertura->setExitAction( $action );

            return $dt_nascimento_abertura;
        });

        $column_dt_falecimento->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $dt_falecimento = new TDate($object->$pk.'_'.'dt_falecimento');
            $dt_falecimento->setSize('100%');
            $dt_falecimento->setMask('dd/mm/yyyy');
            $dt_falecimento->setDatabaseMask('yyyy-mm-dd');

            $dt_falecimento->setFormName(self::$formName);
            $dt_falecimento->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'dt_falecimento');
            $action->setParameter('_builder_field_options', base64_encode(serialize([
                'viewMask' => 'dd/mm/yyyy',
                'databaseMask' => 'yyyy-mm-dd',
                'component' => 'TDate'
            ])));

            $dt_falecimento->setExitAction( $action );

            return $dt_falecimento;
        });

        $column_cpf_cnpj->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $cpf_cnpj = new TEntry($object->$pk.'_'.'cpf_cnpj');
            $cpf_cnpj->setSize('100%');

            $cpf_cnpj->setFormName(self::$formName);
            $cpf_cnpj->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'cpf_cnpj');

            $cpf_cnpj->setExitAction( $action );

            return $cpf_cnpj;
        });

        $column_rg_ie->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $rg_ie = new TEntry($object->$pk.'_'.'rg_ie');
            $rg_ie->setSize('100%');

            $rg_ie->setFormName(self::$formName);
            $rg_ie->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'rg_ie');

            $rg_ie->setExitAction( $action );

            return $rg_ie;
        });

        $column_orgao_emissor->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $orgao_emissor = new TEntry($object->$pk.'_'.'orgao_emissor');
            $orgao_emissor->setSize('100%');

            $orgao_emissor->setFormName(self::$formName);
            $orgao_emissor->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'orgao_emissor');

            $orgao_emissor->setExitAction( $action );

            return $orgao_emissor;
        });

        $column_sexo_nome->setTransformer( function($value, $object, $row) {
            $criteria_sexo_nome = new TCriteria(); 

            $pk = $object->getPrimaryKey();

            $sexo_nome = new TDBCombo($object->$pk.'_'.'sexo_nome', 'escritorio', 'Sexo', 'id', '{nome}','nome asc' , $criteria_sexo_nome );
            $sexo_nome->setSize('100%');
            $sexo_nome->enableSearch();

            $sexo_nome->setFormName(self::$formName);
            $sexo_nome->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'sexo_nome');

            $sexo_nome->setChangeAction( $action );

            return $sexo_nome;
        });

        $column_nacionalidade_nome->setTransformer( function($value, $object, $row) {
            $criteria_nacionalidade_nome = new TCriteria(); 

            $pk = $object->getPrimaryKey();

            $nacionalidade_nome = new TDBCombo($object->$pk.'_'.'nacionalidade_nome', 'escritorio', 'Nacionalidade', 'id', '{nome}','nome asc' , $criteria_nacionalidade_nome );
            $nacionalidade_nome->setSize('100%');
            $nacionalidade_nome->enableSearch();

            $nacionalidade_nome->setFormName(self::$formName);
            $nacionalidade_nome->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'nacionalidade_nome');

            $nacionalidade_nome->setChangeAction( $action );

            return $nacionalidade_nome;
        });

        $column_estado_civil_nome->setTransformer( function($value, $object, $row) {
            $criteria_estado_civil_nome = new TCriteria(); 

            $pk = $object->getPrimaryKey();

            $estado_civil_nome = new TDBCombo($object->$pk.'_'.'estado_civil_nome', 'escritorio', 'EstadoCivil', 'id', '{nome}','nome asc' , $criteria_estado_civil_nome );
            $estado_civil_nome->setSize('100%');
            $estado_civil_nome->enableSearch();

            $estado_civil_nome->setFormName(self::$formName);
            $estado_civil_nome->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'estado_civil_nome');

            $estado_civil_nome->setChangeAction( $action );

            return $estado_civil_nome;
        });

        $column_profissao->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $profissao = new TEntry($object->$pk.'_'.'profissao');
            $profissao->setSize('100%');

            $profissao->setFormName(self::$formName);
            $profissao->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'profissao');

            $profissao->setExitAction( $action );

            return $profissao;
        });

        $column_nit->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $nit = new TEntry($object->$pk.'_'.'nit');
            $nit->setSize('100%');

            $nit->setFormName(self::$formName);
            $nit->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'nit');

            $nit->setExitAction( $action );

            return $nit;
        });

        $column_ctps->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $ctps = new TEntry($object->$pk.'_'.'ctps');
            $ctps->setSize('100%');

            $ctps->setFormName(self::$formName);
            $ctps->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'ctps');

            $ctps->setExitAction( $action );

            return $ctps;
        });

        $column_situacao_profissional_nome->setTransformer( function($value, $object, $row) {
            $criteria_situacao_profissional_nome = new TCriteria(); 

            $pk = $object->getPrimaryKey();

            $situacao_profissional_nome = new TDBCombo($object->$pk.'_'.'situacao_profissional_nome', 'escritorio', 'SituacaoProfissional', 'id', '{nome}','nome asc' , $criteria_situacao_profissional_nome );
            $situacao_profissional_nome->setSize('100%');
            $situacao_profissional_nome->enableSearch();

            $situacao_profissional_nome->setFormName(self::$formName);
            $situacao_profissional_nome->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'situacao_profissional_nome');

            $situacao_profissional_nome->setChangeAction( $action );

            return $situacao_profissional_nome;
        });

        $column_orgao->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $orgao = new TEntry($object->$pk.'_'.'orgao');
            $orgao->setSize('100%');

            $orgao->setFormName(self::$formName);
            $orgao->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'orgao');

            $orgao->setExitAction( $action );

            return $orgao;
        });

        $column_unidade->setTransformer( function($value, $object, $row) {

            $pk = $object->getPrimaryKey();

            $unidade = new TEntry($object->$pk.'_'.'unidade');
            $unidade->setSize('100%');

            $unidade->setFormName(self::$formName);
            $unidade->setValue($value);
            $action = new TAction( [$this, 'onSaveInline'] );
            $action->setParameter('column', 'unidade');

            $unidade->setExitAction( $action );

            return $unidade;
        });

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);

        $this->datagrid->addColumn($column_tipo_pessoa_nome);
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_telefone);
        $this->datagrid->addColumn($column_aceita_receber_mensagen_whatsapp);
        $this->datagrid->addColumn($column_dt_nascimento_abertura);
        $this->datagrid->addColumn($column_dt_falecimento);
        $this->datagrid->addColumn($column_cpf_cnpj);
        $this->datagrid->addColumn($column_rg_ie);
        $this->datagrid->addColumn($column_orgao_emissor);
        $this->datagrid->addColumn($column_sexo_nome);
        $this->datagrid->addColumn($column_nacionalidade_nome);
        $this->datagrid->addColumn($column_estado_civil_nome);
        $this->datagrid->addColumn($column_profissao);
        $this->datagrid->addColumn($column_nit);
        $this->datagrid->addColumn($column_ctps);
        $this->datagrid->addColumn($column_situacao_profissional_nome);
        $this->datagrid->addColumn($column_orgao);
        $this->datagrid->addColumn($column_unidade);
        $this->datagrid->addColumn($column_data_modificacao);
        $this->datagrid->addColumn($column_modificacao_user_name);

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        $td_tipo_pessoa_id = TElement::tag('td', $tipo_pessoa_id);
        $tr->add($td_tipo_pessoa_id);
        $td_id = TElement::tag('td', $id);
        $tr->add($td_id);
        $td_nome = TElement::tag('td', $nome);
        $tr->add($td_nome);
        $td_email = TElement::tag('td', $email);
        $tr->add($td_email);
        $td_telefone = TElement::tag('td', $telefone);
        $tr->add($td_telefone);
        $td_aceita_receber_mensagen_whatsapp = TElement::tag('td', $aceita_receber_mensagen_whatsapp);
        $tr->add($td_aceita_receber_mensagen_whatsapp);
        $td_dt_nascimento_abertura = TElement::tag('td', $dt_nascimento_abertura);
        $tr->add($td_dt_nascimento_abertura);
        $td_dt_falecimento = TElement::tag('td', $dt_falecimento);
        $tr->add($td_dt_falecimento);
        $td_cpf_cnpj = TElement::tag('td', $cpf_cnpj);
        $tr->add($td_cpf_cnpj);
        $td_rg_ie = TElement::tag('td', $rg_ie);
        $tr->add($td_rg_ie);
        $td_orgao_emissor = TElement::tag('td', $orgao_emissor);
        $tr->add($td_orgao_emissor);
        $td_sexo_id = TElement::tag('td', $sexo_id);
        $tr->add($td_sexo_id);
        $td_nacionalidade_id = TElement::tag('td', $nacionalidade_id);
        $tr->add($td_nacionalidade_id);
        $td_estado_civil_id = TElement::tag('td', $estado_civil_id);
        $tr->add($td_estado_civil_id);
        $td_profissao = TElement::tag('td', $profissao);
        $tr->add($td_profissao);
        $td_nit = TElement::tag('td', $nit);
        $tr->add($td_nit);
        $td_ctps = TElement::tag('td', $ctps);
        $tr->add($td_ctps);
        $td_situacao_profissional_id = TElement::tag('td', $situacao_profissional_id);
        $tr->add($td_situacao_profissional_id);
        $td_orgao = TElement::tag('td', $orgao);
        $tr->add($td_orgao);
        $td_unidade = TElement::tag('td', $unidade);
        $tr->add($td_unidade);
        $td_data_modificacao = TElement::tag('td', $data_modificacao);
        $tr->add($td_data_modificacao);
        $td_modificacao_user_id = TElement::tag('td', $modificacao_user_id);
        $tr->add($td_modificacao_user_id);

        $this->datagrid_form->addField($tipo_pessoa_id);
        $this->datagrid_form->addField($id);
        $this->datagrid_form->addField($nome);
        $this->datagrid_form->addField($email);
        $this->datagrid_form->addField($telefone);
        $this->datagrid_form->addField($aceita_receber_mensagen_whatsapp);
        $this->datagrid_form->addField($dt_nascimento_abertura);
        $this->datagrid_form->addField($dt_falecimento);
        $this->datagrid_form->addField($cpf_cnpj);
        $this->datagrid_form->addField($rg_ie);
        $this->datagrid_form->addField($orgao_emissor);
        $this->datagrid_form->addField($sexo_id);
        $this->datagrid_form->addField($nacionalidade_id);
        $this->datagrid_form->addField($estado_civil_id);
        $this->datagrid_form->addField($profissao);
        $this->datagrid_form->addField($nit);
        $this->datagrid_form->addField($ctps);
        $this->datagrid_form->addField($situacao_profissional_id);
        $this->datagrid_form->addField($orgao);
        $this->datagrid_form->addField($unidade);
        $this->datagrid_form->addField($data_modificacao);
        $this->datagrid_form->addField($modificacao_user_id);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $this->datagrid->disableDefaultClick(); 

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $panel->getHeader()->style = ' display:none !important; ';
        $panel->getBody()->class .= ' table-responsive';

        $panel->addFooter($this->pageNavigation);

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';

        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';

        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';

        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);

        $this->datagrid_form->add($headerActions);
        $panel->add($this->datagrid_form);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['PessoaEditHeaderList', 'onExportCsv'],['static' => 1]), self::$formName, 'fas:file-csv #00b894' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['PessoaEditHeaderList', 'onExportXls'],['static' => 1]), self::$formName, 'fas:file-excel #4CAF50' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['PessoaEditHeaderList', 'onExportPdf'],['static' => 1]), self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XML", new TAction(['PessoaEditHeaderList', 'onExportXml'],['static' => 1]), self::$formName, 'far:file-code #95a5a6' );

        $head_right_actions->add($dropdown_button_exportar);

        $this->datagrid_form->add($this->datagrid);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Pessoas","Edição de pessoas"]));
        }
        $container->add($panel);

        parent::add($container);

    }

    public function onExportCsv($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.csv';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $objects = $this->onReload();

                if ($objects)
                {
                    $handler = fopen($output, 'w');
                    TTransaction::open(self::$database);

                    foreach ($objects as $object)
                    {
                        $row = [];
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();

                            if (isset($object->$column_name))
                            {
                                $row[] = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $row[] = $object->render($column_name);
                            }
                        }

                        fputcsv($handler, $row);
                    }

                    fclose($handler);
                    TTransaction::close();
                }
                else
                {
                    throw new Exception(_t('No records found'));
                }

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public function onExportXls($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.xls';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $widths = [];
                $titles = [];

                foreach ($this->datagrid->getColumns() as $column)
                {
                    $titles[] = $column->getLabel();
                    $width    = 100;

                    if (is_null($column->getWidth()))
                    {
                        $width = 100;
                    }
                    else if (strpos((string)$column->getWidth(), '%') !== false)
                    {
                        $width = ((int) $column->getWidth()) * 5;
                    }
                    else if (is_numeric($column->getWidth()))
                    {
                        $width = $column->getWidth();
                    }

                    $widths[] = $width;
                }

                $table = new \TTableWriterXLS($widths);
                $table->addStyle('title',  'Helvetica', '10', 'B', '#ffffff', '#617FC3');
                $table->addStyle('data',   'Helvetica', '10', '',  '#000000', '#FFFFFF', 'LR');

                $table->addRow();

                foreach ($titles as $title)
                {
                    $table->addCell($title, 'center', 'title');
                }

                $this->limit = 0;
                $objects = $this->onReload();

                TTransaction::open(self::$database);
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $table->addRow();
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();
                            $value = '';
                            if (isset($object->$column_name))
                            {
                                $value = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $value = $object->render($column_name);
                            }

                            $transformer = $column->getTransformer();
                            if ($transformer)
                            {
                                $value = strip_tags((string)call_user_func($transformer, $value, $object, null));
                            }

                            $table->addCell($value, 'center', 'data');
                        }
                    }
                }
                $table->save($output);
                TTransaction::close();

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public function onExportPdf($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.pdf';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $this->datagrid->prepareForPrinting();
                $this->onReload();

                $html = clone $this->datagrid;
                $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();

                $dompdf = new \Dompdf\Dompdf;
                $dompdf->loadHtml($contents);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                file_put_contents($output, $dompdf->output());

                $window = TWindow::create('PDF', 0.8, 0.8);
                $object = new TElement('iframe');
                $object->src  = $output;
                $object->type  = 'application/pdf';
                $object->style = "width: 100%; height:calc(100% - 10px)";

                $window->add($object);
                $window->show();
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public function onExportXml($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.xml';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $objects = $this->onReload();

                if ($objects)
                {
                    TTransaction::open(self::$database);

                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->{'formatOutput'} = true;
                    $dataset = $dom->appendChild( $dom->createElement('dataset') );

                    foreach ($objects as $object)
                    {
                        $row = $dataset->appendChild( $dom->createElement( self::$activeRecord ) );

                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();
                            $column_name_raw = str_replace(['(','{','->', '-','>','}',')', ' '], ['','','_','','','','','_'], $column_name);

                            if (isset($object->$column_name))
                            {
                                $value = is_scalar($object->$column_name) ? $object->$column_name : '';
                                $row->appendChild($dom->createElement($column_name_raw, $value)); 
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $value = $object->render($column_name);
                                $row->appendChild($dom->createElement($column_name_raw, $value));
                            }
                        }
                    }

                    $dom->save($output);

                    TTransaction::close();
                }
                else
                {
                    throw new Exception(_t('No records found'));
                }

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public static function onSaveInline($param)
    {
        $name   = $param['_field_name'];
        $value  = $param['_field_value'];
        $column = $param['column'];
        $parts  = explode('_', $name);
        $id     = $parts[0];

        if(!empty($param['_builder_field_options']))
        {
            $field_options = unserialize(base64_decode($param['_builder_field_options']));
            if(!empty($field_options['component']) && $field_options['component'] == 'TDate' && !empty($value) && $field_options['viewMask'] != $field_options['databaseMask'])
            {
                $value = TDate::convertToMask($value, $field_options['viewMask'], $field_options['databaseMask']);
            }
            elseif(!empty($field_options['component']) && $field_options['component'] == 'TDateTime' && !empty($value) && $field_options['viewMask'] != $field_options['databaseMask'])
            {
                $value = TDateTime::convertToMask($value, $field_options['viewMask'], $field_options['databaseMask']);
            }
            elseif(!empty($field_options['component']) && $field_options['component'] == 'TNumeric' && !empty($value))
            {
                $value = str_replace( $field_options['thousandSeparator'], '', $value);
                $value = str_replace( $field_options['decimalSeparator'], '.', $value);
            }
        }

        try
        {
            // open transaction
            TTransaction::open(self::$database);
            $class = self::$activeRecord;
            $object = $class::find($id);
            if ($object)
            {
                $object->$column = $value;

                $object->store();

            }

            // close transaction
            TTransaction::close();
        }
        catch (Exception $e)
        {
            // show the exception message
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        // get the search form data
        $data = $this->datagrid_form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->tipo_pessoa_id) AND ( (is_scalar($data->tipo_pessoa_id) AND $data->tipo_pessoa_id !== '') OR (is_array($data->tipo_pessoa_id) AND (!empty($data->tipo_pessoa_id)) )) )
        {

            $filters[] = new TFilter('tipo_pessoa_id', '=', $data->tipo_pessoa_id);// create the filter 
        }

        if (isset($data->id) AND ( (is_scalar($data->id) AND $data->id !== '') OR (is_array($data->id) AND (!empty($data->id)) )) )
        {

            $filters[] = new TFilter('id', '=', $data->id);// create the filter 
        }

        if (isset($data->nome) AND ( (is_scalar($data->nome) AND $data->nome !== '') OR (is_array($data->nome) AND (!empty($data->nome)) )) )
        {

            $filters[] = new TFilter('nome', 'like', "%{$data->nome}%");// create the filter 
        }

        if (isset($data->email) AND ( (is_scalar($data->email) AND $data->email !== '') OR (is_array($data->email) AND (!empty($data->email)) )) )
        {

            $filters[] = new TFilter('email', 'like', "%{$data->email}%");// create the filter 
        }

        if (isset($data->telefone) AND ( (is_scalar($data->telefone) AND $data->telefone !== '') OR (is_array($data->telefone) AND (!empty($data->telefone)) )) )
        {

            $filters[] = new TFilter('telefone', 'like', "%{$data->telefone}%");// create the filter 
        }

        if (isset($data->aceita_receber_mensagen_whatsapp) AND ( (is_scalar($data->aceita_receber_mensagen_whatsapp) AND $data->aceita_receber_mensagen_whatsapp !== '') OR (is_array($data->aceita_receber_mensagen_whatsapp) AND (!empty($data->aceita_receber_mensagen_whatsapp)) )) )
        {

            $filters[] = new TFilter('aceita_receber_mensagen_whatsapp', '=', $data->aceita_receber_mensagen_whatsapp);// create the filter 
        }

        if (isset($data->dt_nascimento_abertura) AND ( (is_scalar($data->dt_nascimento_abertura) AND $data->dt_nascimento_abertura !== '') OR (is_array($data->dt_nascimento_abertura) AND (!empty($data->dt_nascimento_abertura)) )) )
        {

            $filters[] = new TFilter('dt_nascimento_abertura', '=', $data->dt_nascimento_abertura);// create the filter 
        }

        if (isset($data->dt_falecimento) AND ( (is_scalar($data->dt_falecimento) AND $data->dt_falecimento !== '') OR (is_array($data->dt_falecimento) AND (!empty($data->dt_falecimento)) )) )
        {

            $filters[] = new TFilter('dt_falecimento', '=', $data->dt_falecimento);// create the filter 
        }

        if (isset($data->cpf_cnpj) AND ( (is_scalar($data->cpf_cnpj) AND $data->cpf_cnpj !== '') OR (is_array($data->cpf_cnpj) AND (!empty($data->cpf_cnpj)) )) )
        {

            $filters[] = new TFilter('cpf_cnpj', 'like', "%{$data->cpf_cnpj}%");// create the filter 
        }

        if (isset($data->rg_ie) AND ( (is_scalar($data->rg_ie) AND $data->rg_ie !== '') OR (is_array($data->rg_ie) AND (!empty($data->rg_ie)) )) )
        {

            $filters[] = new TFilter('rg_ie', 'like', "%{$data->rg_ie}%");// create the filter 
        }

        if (isset($data->orgao_emissor) AND ( (is_scalar($data->orgao_emissor) AND $data->orgao_emissor !== '') OR (is_array($data->orgao_emissor) AND (!empty($data->orgao_emissor)) )) )
        {

            $filters[] = new TFilter('orgao_emissor', 'like', "%{$data->orgao_emissor}%");// create the filter 
        }

        if (isset($data->sexo_id) AND ( (is_scalar($data->sexo_id) AND $data->sexo_id !== '') OR (is_array($data->sexo_id) AND (!empty($data->sexo_id)) )) )
        {

            $filters[] = new TFilter('sexo_id', '=', $data->sexo_id);// create the filter 
        }

        if (isset($data->nacionalidade_id) AND ( (is_scalar($data->nacionalidade_id) AND $data->nacionalidade_id !== '') OR (is_array($data->nacionalidade_id) AND (!empty($data->nacionalidade_id)) )) )
        {

            $filters[] = new TFilter('nacionalidade_id', '=', $data->nacionalidade_id);// create the filter 
        }

        if (isset($data->estado_civil_id) AND ( (is_scalar($data->estado_civil_id) AND $data->estado_civil_id !== '') OR (is_array($data->estado_civil_id) AND (!empty($data->estado_civil_id)) )) )
        {

            $filters[] = new TFilter('estado_civil_id', '=', $data->estado_civil_id);// create the filter 
        }

        if (isset($data->profissao) AND ( (is_scalar($data->profissao) AND $data->profissao !== '') OR (is_array($data->profissao) AND (!empty($data->profissao)) )) )
        {

            $filters[] = new TFilter('profissao', 'like', "%{$data->profissao}%");// create the filter 
        }

        if (isset($data->nit) AND ( (is_scalar($data->nit) AND $data->nit !== '') OR (is_array($data->nit) AND (!empty($data->nit)) )) )
        {

            $filters[] = new TFilter('nit', 'like', "%{$data->nit}%");// create the filter 
        }

        if (isset($data->ctps) AND ( (is_scalar($data->ctps) AND $data->ctps !== '') OR (is_array($data->ctps) AND (!empty($data->ctps)) )) )
        {

            $filters[] = new TFilter('ctps', 'like', "%{$data->ctps}%");// create the filter 
        }

        if (isset($data->situacao_profissional_id) AND ( (is_scalar($data->situacao_profissional_id) AND $data->situacao_profissional_id !== '') OR (is_array($data->situacao_profissional_id) AND (!empty($data->situacao_profissional_id)) )) )
        {

            $filters[] = new TFilter('situacao_profissional_id', '=', $data->situacao_profissional_id);// create the filter 
        }

        if (isset($data->orgao) AND ( (is_scalar($data->orgao) AND $data->orgao !== '') OR (is_array($data->orgao) AND (!empty($data->orgao)) )) )
        {

            $filters[] = new TFilter('orgao', 'like', "%{$data->orgao}%");// create the filter 
        }

        if (isset($data->unidade) AND ( (is_scalar($data->unidade) AND $data->unidade !== '') OR (is_array($data->unidade) AND (!empty($data->unidade)) )) )
        {

            $filters[] = new TFilter('unidade', 'like', "%{$data->unidade}%");// create the filter 
        }

        if (isset($data->data_modificacao) AND ( (is_scalar($data->data_modificacao) AND $data->data_modificacao !== '') OR (is_array($data->data_modificacao) AND (!empty($data->data_modificacao)) )) )
        {

            $filters[] = new TFilter('data_modificacao', '=', $data->data_modificacao);// create the filter 
        }

        if (isset($data->modificacao_user_id) AND ( (is_scalar($data->modificacao_user_id) AND $data->modificacao_user_id !== '') OR (is_array($data->modificacao_user_id) AND (!empty($data->modificacao_user_id)) )) )
        {

            $filters[] = new TFilter('modificacao_user_id', '=', $data->modificacao_user_id);// create the filter 
        }

        // fill the form with data again
        $this->datagrid_form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            $onReloadParam = ['offset' => 0, 'first_page' => 1, 'target_container' => $param['target_container'] ?? null];
            AdiantiCoreApplication::loadPage($class, 'onReload', $onReloadParam);
            TScript::create('$(".select2").prev().select2("close");');
        }
        else
        {
            $this->onReload(['offset' => 0, 'first_page' => 1]);
        }
    }

    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'escritorio'
            TTransaction::open(self::$database);

            // creates a repository for Pessoa
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'id';    
            }
            if (empty($param['direction']))
            {
                $param['direction'] = 'desc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $this->limit);

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $filter) 
                {
                    $criteria->add($filter);       
                }
            }

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $row = $this->datagrid->addItem($object);
                    $row->id = "row_{$object->id}";

                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);

            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($this->limit); // limit

            // close the transaction
            TTransaction::close();
            $this->loaded = true;

            return $objects;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  $this->showMethods))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new Pessoa($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

