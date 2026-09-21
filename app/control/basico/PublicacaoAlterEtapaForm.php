<?php

class PublicacaoAlterEtapaForm extends TWindow
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Publicacao';
    private static $primaryKey = 'id';
    private static $formName = 'form_PublicacaoAlterEtapaForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        parent::setSize(0.30, null);
        parent::setTitle("Alterar etapa da publicação");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Alterar etapa da publicação");

        $criteria_publicacao_etapa_id = new TCriteria();

        $key = $param['key'];
        $tipo = null;

        if (!empty($key)) {
            TTransaction::open(self::$database);
            $pub = Publicacao::where('id', '=', $key)->first();
            if (!empty($pub->processo_id)) {
                $processo = Processo::where('id', '=', $pub->processo_id)->first();
                if (!empty($processo->tipo_processo_id)) {
                    if ($processo->tipo_processo_id == 1) {
                        $tipo = 'J';
                    }
                    if ($processo->tipo_processo_id == 2) {
                        $tipo = 'E';
                    }
                }
            }
            TTransaction::close();
        }

        if ($tipo != null) {
            if ($tipo == 'J') {
                TSession::setValue(__CLASS__.'load_filter_judicial', 'S' ?? "");            
                $filterVar = TSession::getValue(__CLASS__.'load_filter_judicial');
                $criteria_publicacao_etapa_id->add(new TFilter('judicial', '=', $filterVar)); 
            }

            if ($tipo == 'E') {
                TSession::setValue(__CLASS__.'load_filter_extrajudicial', 'S' ?? "");            
                $filterVar = TSession::getValue(__CLASS__.'load_filter_extrajudicial');
                $criteria_publicacao_etapa_id->add(new TFilter('extrajudicial', '=', $filterVar));    
            }            
        }

        $id = new THidden('id');
        $publicacao_etapa_id = new TDBCombo('publicacao_etapa_id', 'escritorio', 'PublicacaoEtapa', 'id', '{etapa_nome}','ordem_prioridade asc' , $criteria_publicacao_etapa_id );

        $publicacao_etapa_id->addValidation("Etapa", new TRequiredValidator()); 

        $publicacao_etapa_id->enableSearch();
        $id->setSize(200);
        $publicacao_etapa_id->setSize('100%');

        $row1 = $this->form->addFields([$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Etapa:", '#000000', '14px', null, '100%')],[$publicacao_etapa_id]);
        $row2->layout = [' col-sm-2 control-label',' col-sm-10'];

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

            $object = new Publicacao(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            if (!empty($object->id) && !empty($object->publicacao_etapa_id))
            {
                $conn = TTransaction::get();

                $stmt = $conn->prepare("
                    UPDATE processo_publicacoes
                    SET publicacao_etapa_id = :publicacao_etapa_id
                    WHERE publicacao_id = :publicacao_id
                ");

                $stmt->execute([
                    ':publicacao_etapa_id' => $object->publicacao_etapa_id,
                    ':publicacao_id'       => $object->id
                ]);
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

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

}

