<?php

class DocumentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Documento';
    private static $primaryKey = 'id';
    private static $formName = 'form_DocumentoForm';

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
        $this->form->setFormTitle("Documento");

        $criteria_modelo_documento_id = new TCriteria();

        $filterVar = "S";
        $criteria_modelo_documento_id->add(new TFilter('ativo', '=', $filterVar)); 
        $filterVar = ModeloDocTipoAplicacao::ATENDIMENTO;
        $criteria_modelo_documento_id->add(new TFilter('id', 'in', "(SELECT modelo_documento_id FROM modelo_doc_aplicacao WHERE tipo_aplicacao_id = '{$filterVar}')")); 

        $variaveis = implode(' | ', array_keys(ModeloDocumento::VARIAVEIS));

        $id = new TEntry('id');
        $atendimento_id = new THidden('atendimento_id');
        $modelo_documento_id = new TDBCombo('modelo_documento_id', 'escritorio', 'ModeloDocumento', 'id', '{nome}','nome asc' , $criteria_modelo_documento_id );
        $dt_validade = new TDate('dt_validade');
        $observacao = new TText('observacao');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $modelo_documento_id->setChangeAction(new TAction([$this,'changeModeloDocumento']));

        $modelo_documento_id->addValidation("Tipo documento", new TRequiredValidator()); 

        $atendimento_id->setValue($param['atendimento_id']??null);
        $modelo_documento_id->enableSearch();
        $dt_validade->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $dt_validade->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $atendimento_id->setSize(200);
        $dt_validade->setSize('100%');
        $data_criacao->setSize('100%');
        $observacao->setSize('100%', 70);
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modelo_documento_id->setSize('100%');
        $modificacao_user_name->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[$atendimento_id]);
        $row1->layout = ['col-sm-6','col-sm-2'];

        $row2 = $this->form->addFields([new TLabel("Modelo de documento:", '#ff0000', '14px', null, '100%'),$modelo_documento_id],[new TLabel("Validade:", null, '14px', null, '100%'),$dt_validade]);
        $row2->layout = [' col-sm-9',' col-sm-3'];

        $row3 = $this->form->addFields([new TLabel("Observação:", null, '14px', null, '100%'),$observacao]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row5 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row5->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        $row1->style = 'display: none';
        $modelo_documento_id->setDefaultOption('Manual');

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=DocumentoForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function changeModeloDocumento($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $data = new stdClass;

            if (empty($param['modelo_documento_id']))
            {
                $data->texto = '';
            }
            else
            {
                $modelo_documento = ModeloDocumento::find($param['modelo_documento_id']);
                $data->texto = $modelo_documento->texto_padrao;
            }

            TForm::sendData(self::$formName, $data);

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onSave($param = null) 
    {
        try
        {

            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Documento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$data->modelo_documento_id){
                throw new Exception("O campo Modelo de Documento é obrigatório.");
            }

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            $object->dt_preenchimento = date('Y-m-d H:i:s');

            $modeloDocumento = ModeloDocumento::find($data->modelo_documento_id);

            $atendimento = Atendimento::find($object->atendimento_id);

            //VERIFICAR OBRIGATORIEDADES
            $serviceParam = [
                'modelo_documento_id' => $modeloDocumento->id,
                'cliente_id' => $atendimento->cliente_id,
                'profissional_id' => $atendimento->profissional_id,
                'atendimento_id' => $atendimento->id
            ];

            if($object->dt_validade){
                $serviceParam['dt_validade'] = $object->dt_validade;
            }

            $validarDados = ModeloDocumentoService::validarDadosObriatoriosDocumento($serviceParam);

            if($validarDados!==""){
                throw new Exception($validarDados);
            }

            $returnParam = ModeloDocumentoService::preencherDocumento($serviceParam);

            $object->autenticador = $returnParam['autenticador'];
            $object->atendimento_id = $returnParam['complemento_id'];
            $object->filename = $returnParam['novo_nome_arquivo'];
            $object->criacao_user_id = TSession::getValue('userid');

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["key"] = $object->atendimento_id;
            }

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["id"] = $object->atendimento_id;
            }

            $loadPageParam["current_tab_abas"] = "3"; 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam); 

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

                $object = new Documento($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

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

