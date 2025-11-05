<?php

use phputil\extenso\Extenso;

class ContratoDocumentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'ContratoDocumento';
    private static $primaryKey = 'id';
    private static $formName = 'form_ContratoDocumentoForm';

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
        $this->form->setFormTitle("Adicionar documento ao contrato");

        $criteria_modelo_documento_id = new TCriteria();

        $filterVar = ModeloDocTipoAplicacao::CONTRATO;
        $criteria_modelo_documento_id->add(new TFilter('id', 'in', "(SELECT modelo_documento_id FROM modelo_doc_aplicacao WHERE tipo_aplicacao_id = '{$filterVar}')")); 
        $filterVar = "S";
        $criteria_modelo_documento_id->add(new TFilter('ativo', '=', $filterVar)); 

        $id = new TEntry('id');
        $contrato_id = new THidden('contrato_id');
        $modelo_documento_id = new TDBCombo('modelo_documento_id', 'escritorio', 'ModeloDocumento', 'id', '{nome}','nome asc' , $criteria_modelo_documento_id );
        $dt_validade = new TDate('dt_validade');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');


        $contrato_id->setValue($param['contrato_id']);
        $modelo_documento_id->enableSearch();
        $dt_validade->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $dt_validade->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $dt_validade->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $contrato_id->setSize(200);
        $dt_validade->setSize('100%');
        $data_criacao->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modelo_documento_id->setSize('100%');
        $modificacao_user_name->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id,$contrato_id]);
        $row1->layout = [' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Modelo de documento:", '#FF0000', '14px', null, '100%'),$modelo_documento_id],[new TLabel("Data de validade:", null, '14px', null),$dt_validade]);
        $row2->layout = [' col-sm-9','col-sm-3'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row4 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row4->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ContratoDocumentoForm]');
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

            $object = new ContratoDocumento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->dt_preenchimento = date('Y-m-d H:i:s');

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            $serviceParam = array();

            //Buscar dados
            $contrato = Contrato::find((int)$object->contrato_id);
            $serviceParam['contrato_id'] = $contrato->id;
            $serviceParam['objeto'] = $contrato->objeto;

            $serviceParam['cliente_id'] = (ContratoPessoa::where('contrato_id','=',$contrato->id)->orderby('id')->first())->cliente_id;

            $repasses = ContratoRepasse::where('contrato_id','=',(int) $data->contrato_id)->orderby('id')->load();
            foreach($repasses as $repasse){
                $grupo = PessoaGrupo::where('pessoa_id','=',$repasse->pessoa_id)->where('grupo_id','=',Grupo::PROFISSIONAL)->first();
                if($grupo){
                    $profissional = Pessoa::find($repasse->pessoa_id);
                    $serviceParam['profissional_id'] = $profissional->id;
                    break;
                }
            }

            $modeloDocumento = ModeloDocumento::find($data->modelo_documento_id);
            $serviceParam['modelo_documento_id'] = $modeloDocumento->id;

            $cliente = Pessoa::find((int)$serviceParam['cliente_id']);
            $qtdePagamento = ContratoPagamentoParcela::where('contrato_id','=',$contrato->id)->count();
            //VERIFICAR OBRIGATORIEDADES
            $validarDados = ModeloDocumentoService::onVerificarDadosCliente($cliente, $modeloDocumento, $serviceParam['objeto'], $qtdePagamento);

            $erro = array();
            if($validarDados){
                $erro[] = "Não é possível gerar documento $modelo_documento->nome para <b>".$dadosCliente['cliente']."</b>, cadastre os seguintes campos para gerar: ".$dadosCliente['dadosFaltantes'].".";
            }

            if(count($erro)>0){
                throw new Exception(implode("<br/>", $erro));
            }

            $returnParam = ModeloDocumentoService::preencherDocumento($serviceParam);

            $object->autenticador = $returnParam['autenticador'];
            $object->contrato_id = $returnParam['complemento_id'];
            $object->filename = $returnParam['novo_nome_arquivo'];
            $object->criacao_user_id = TSession::getValue('userid');

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(!empty($param['contrato_id']))
            {
                $loadPageParam["key"] = $param['contrato_id'];
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ContratoFormView', 'onShow', $loadPageParam); 

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

                $object = new ContratoDocumento($key); // instantiates the Active Record 

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

