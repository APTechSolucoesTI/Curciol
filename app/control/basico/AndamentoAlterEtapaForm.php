<?php

class AndamentoAlterEtapaForm extends TWindow
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Andamento';
    private static $primaryKey = 'id';
    private static $formName = 'form_AndamentoAlterEtapaForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        parent::setSize(0.30, null);
        parent::setTitle("Alterar etapa do andamento");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Alterar etapa do andamento");

        $criteria_publicacao_etapa_id = new TCriteria();

        $id = new THidden('id');
        $publicacao_etapa_id = new TDBCombo('publicacao_etapa_id', 'escritorio', 'PublicacaoEtapa', 'id', '{etapa_nome}','ordem_prioridade asc' , $criteria_publicacao_etapa_id );

        $publicacao_etapa_id->addValidation("Etapa", new TRequiredValidator()); 

        $publicacao_etapa_id->enableSearch();
        $id->setSize(200);
        $publicacao_etapa_id->setSize('100%');

        $row1 = $this->form->addFields([$id]);
        $row1->layout = ['col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Etapa:", '#000000', '14px', null, '100%')],[$publicacao_etapa_id]);
        $row2->layout = [' col-sm-2',' col-sm-10'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        parent::add($this->form);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Andamento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $andamento = Andamento::where('id', '=', $object->id)->first();
            $pp = ProcessoPublicacoes::where('andamento_id', '=', $object->id)->first();

            if (!empty($pp)) {                   
                $pp->publicacao_etapa_id = $object->publicacao_etapa_id;
                $pp->date_log = date('Y-m-d H:i:s');
            }
            else {
                $pp = new ProcessoPublicacoes();
                $pp->processo_id = $andamento->processo_id;
                $pp->andamento_id = $object->id;
                $pp->publicacao_etapa_id = $object->publicacao_etapa_id;
                $pp->date_log = date('Y-m-d H:i:s');
            }      
            $pp->store();

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

                TWindow::closeWindow(parent::getId()); 

    TApplication::loadPage('AndamentoFormView', 'onShow', ['key' => $object->id]);
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

                $object = new Andamento($key); // instantiates the Active Record 

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

