<?php

class PreferenciaSistemaForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'PreferenciaSistema';
    private static $primaryKey = 'id';
    private static $formName = 'form_PreferenciaSistemaForm';

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
        $this->form->setFormTitle("Preferências do usuário");


        $id = new THidden('id');
        $system_users_id = new THidden('system_users_id');
        $zoom = new TSpinner('zoom');
        $menu_fixado = new TCombo('menu_fixado');

        $zoom->addValidation("Zoom da Tela", new TRequiredValidator()); 
        $menu_fixado->addValidation("Menu Fixado", new TRequiredValidator()); 

        $zoom->setRange(1, 2000, 1);
        $menu_fixado->addItems(["1"=>" Sim","0"=>" Não"]);
        $menu_fixado->enableSearch();
        $zoom->setValue('100');
        $system_users_id->setValue(TSession::getValue('userid'));

        $id->setSize(200);
        $zoom->setSize('100%');
        $menu_fixado->setSize('100%');
        $system_users_id->setSize(200);

        $row1 = $this->form->addFields([$id,$system_users_id],[]);
        $row1->layout = ['col-sm-6','col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Zoom da Tela:", '#ff0000', '14px', null, '100%'),$zoom],[new TLabel("Adicionar Menu Fixado:", '#FF0000', '14px', null),$menu_fixado]);
        $row2->layout = ['col-sm-4',' col-sm-4'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Pessoas","Preferências do sistema"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new PreferenciaSistema(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

            TScript::create('location.reload()');

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
            TTransaction::open(self::$database); // open a transaction

            $key = (PreferenciaSistema::where('system_users_id','=',TSession::getValue('userid'))->first())->id;

            if (isset($key))
            {
                $object = new PreferenciaSistema($key); // instantiates the Active Record 

                $this->form->setData($object); // fill the form 

            }
            else
            {
                $this->form->clear();
            }

            TTransaction::close(); // close the transaction 
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

