<?php

class PublicacaoPrazoForm extends TWindow
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Publicacao';
    private static $primaryKey = 'id';
    private static $formName = 'form_PublicacaoPrazoForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        parent::setSize(400, null);
        parent::setTitle("Adicionar prazo na publicação");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Adicionar prazo na publicação");


        $id = new THidden('id');
        $tela = new THidden('tela');
        $prazo = new TDate('prazo');
        $data_entrega = new TDate('data_entrega');


        $prazo->setMask('dd/mm/yyyy');
        $data_entrega->setMask('dd/mm/yyyy');

        $prazo->setValue($param['prazo'] ?? null);
        $data_entrega->setValue($param['entrega'] ?? null);

        $prazo->setDatabaseMask('yyyy-mm-dd');
        $data_entrega->setDatabaseMask('yyyy-mm-dd');

        $id->setSize(200);
        $tela->setSize(200);
        $prazo->setSize('100%');
        $data_entrega->setSize('100%');

        $row1 = $this->form->addFields([$id],[$tela],[]);
        $row1->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Prazo:", null, '14px', null)],[$prazo]);
        $row3 = $this->form->addFields([new TLabel("Data de entrega:", null, '14px', null)],[$data_entrega]);

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        if($param['tela'] == "Entrega"){
            TScript::create("$('label:contains(\"Prazo:\")').hide();");
            TScript::create("$(\"[name='prazo']\").closest('.fb-inline-field-container').hide()");
            BootstrapFormBuilder::hideField(self::$formName,'prazo');
        }elseif($param['tela'] == "Prazo"){
            TScript::create("$('label:contains(\"Data de entrega:\")').hide();");
            TScript::create("$(\"[name='data_entrega']\").closest('.fb-inline-field-container').hide()");
            BootstrapFormBuilder::hideField(self::$formName,'data_entrega');
        }

        parent::add($this->form);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Publicacao(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if($data->tela == "Entrega"){
                if(!$object->data_entrega){
                    throw new Exception("O campo Data de entrega é obrigatório.");
                }
            }elseif($param['tela'] == "Prazo"){
                if(!$object->prazo){
                    throw new Exception("O campo Prazo é obrigatório.");
                }

                //TAREFAS
                $infoTarefaCompara = $infoTarefaInforma = "";
                $tarefas = Tarefa::where('publicacao_id','=',$object->id)->load();

                if(count($tarefas)>0){
                    foreach($tarefas as $tarefa){
                        if($object->prazo < $tarefa->prazo_entrega){
                            $prazoMaiorTarefa = true;
                            $infoTarefaCompara .= "<hr/><b>#$tarefa->id</b><br/>$tarefa->titulo <br/>Data de entrega: ".implode('/', array_reverse(explode('-', $tarefa->prazo_entrega))).".";
                        }
                        $infoTarefaInforma .= "<hr/><b>#$tarefa->id</b><br/>$tarefa->titulo <br/>Data de entrega: ".implode('/', array_reverse(explode('-', $tarefa->prazo_entrega))).".";
                    }
                    if($infoTarefaCompara != ""){
                        throw new Exception("Não é possível alterar o prazo.<br/>Existem tarefas com o prazo superior ao informado, altere-as e tente novamente. <hr/> $infoTarefaCompara");
                    }
                    if(count($tarefas)>1){
                        $quantidadeTarefas = count($tarefas)." tarefas criadas";
                    }else{
                        $quantidadeTarefas = "1 tarefa criada";
                    }
                    new TQuestion("A publicação atual tem $quantidadeTarefas.<br/>Deseja alterar o prazo para a data informada? <hr/> $infoTarefaInforma", 
                                        new TAction([__CLASS__, 'simAlterarTarefa'], ['publicacao_id' => $object->id, 'prazo' => $object->prazo]), 
                                        new TAction([__CLASS__, 'naoAlterarTarefa'], $param));
                }
            }

            $object->store(); // save the object 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            if($object->data_entrega){
                APIPublicacaoController::adicionarMovimentacao($object->id, "Data de entrega adicionada.", null, null);
            }else{
                APIPublicacaoController::adicionarMovimentacao($object->id, "Prazo adicionado.", null, null);
            }

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TApplication::loadPage('PublicacaoHeaderList', 'onShow');

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

                            TWindow::closeWindow(parent::getId()); 

            TApplication::loadPage('PublicacaoFormView', 'onShow', ['key' => $object->id]);
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

                $object = new Publicacao($key); // instantiates the Active Record 

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

    public static function simAlterarTarefa($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $tarefas = Tarefa::where('publicacao_id','=',$param['publicacao_id'])->load();
            foreach($tarefas as $tarefa){
                $tarefa->prazo_entrega = $param['prazo'];
                $tarefa->store();
            }
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function naoAlterarTarefa($param = null) 
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

