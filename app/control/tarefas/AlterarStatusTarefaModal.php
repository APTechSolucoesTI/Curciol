<?php

class AlterarStatusTarefaModal extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_AlterarStatusTarefaModal';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(600, null);
        parent::setTitle("Alterar o status da tarefa");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Alterar o status da tarefa");

        $criteria_tarefa_status_id = new TCriteria();

        $tarefa_status_id = new TDBCombo('tarefa_status_id', 'escritorio', 'TarefaStatus', 'id', '{nome}','kanban asc' , $criteria_tarefa_status_id );
        $id = new THidden('id');
        $retorno = new THidden('retorno');
        $origem = new THidden('origem');

        $tarefa_status_id->addValidation("Status", new TRequiredValidator()); 

        $tarefa_status_id->enableSearch();
        $id->setValue($param['key'] ?? null);
        $origem->setValue($param['origem'] ?? null);
        $retorno->setValue($param['retorno'] ?? null);

        $id->setSize(200);
        $origem->setSize(200);
        $retorno->setSize(200);
        $tarefa_status_id->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Status:", '#FF0000', '14px', null)],[$tarefa_status_id]);
        $row2 = $this->form->addFields([$id],[$retorno,$origem],[]);
        $row2->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-success'); 

        parent::add($this->form);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');

            $this->form->validate(); // validate form data

            $data = $this->form->getData(); // get form data as array
            if(!$data->id){
                throw new Exception("Tarefa inválida!");
            }
            $tarefa = Tarefa::find($data->id);

            $tarefa->alterarStatusTarefa($data->tarefa_status_id);

            if(isset($tarefa->publicacao_id) && !empty($tarefa->publicacao_id)){
                $tarefasNFinal = Tarefa::where('publicacao_id','=',$tarefa->publicacao_id)
                                        ->where('tarefa_status_id','not in',[
                                            (TarefaConfiguracao::find(1))->status_final_id,
                                            (TarefaConfiguracao::find(1))->status_cancelado_id
                                        ])->count();
                if($tarefasNFinal == 0){
                    new TQuestion("Deseja adicionar data de entrega na publicação?", new TAction([__CLASS__, 'finalizarPublicacao'], ['publicacao_id'=>$tarefa->publicacao_id]), new TAction([__CLASS__, 'onNo'], $param));
                }
            }

            TWindow::closeWindow(parent::getId());
            $this->form->setData($data);

            TTransaction::close();

            TApplication::loadPage($param['origem'],'onRefresh');

            $retorno = explode(',',$param['retorno']);
            TApplication::loadPage($retorno[0],'onShow',['key'=>$retorno[1]]);

        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

    } 

    public static function finalizarPublicacao($param = null) 
    {
        try 
        {
            TTransaction::open('escritorio');
            $publicacao = Publicacao::find($param['publicacao_id']);
            $publicacao->data_entrega = date('Y-m-d');
            $publicacao->store();
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onNo($param = null) 
    {
        try 
        {
            //code here
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

}

