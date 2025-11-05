<?php

class AtendimentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Atendimento';
    private static $primaryKey = 'id';
    private static $formName = 'form_Atendimento';

    use BuilderMasterDetailFieldListTrait;

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
        $this->form->setFormTitle("Atendimento #{$param['key']}");

        $criteria_atendimento_procedimento_atendimento_procedimento_id = new TCriteria();
        $criteria_atendimento_material_atendimento_material_id = new TCriteria();

        $filterVar = "S";
        $criteria_atendimento_procedimento_atendimento_procedimento_id->add(new TFilter('ativo', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_atendimento_material_atendimento_material_id->add(new TFilter('ativo', '=', $filterVar)); 

        $id = new TEntry('id');
        $atendimento_procedimento_atendimento_id = new THidden('atendimento_procedimento_atendimento_id[]');
        $atendimento_procedimento_atendimento___row__id = new THidden('atendimento_procedimento_atendimento___row__id[]');
        $atendimento_procedimento_atendimento___row__data = new THidden('atendimento_procedimento_atendimento___row__data[]');
        $atendimento_procedimento_atendimento_procedimento_id = new TDBCombo('atendimento_procedimento_atendimento_procedimento_id[]', 'escritorio', 'Procedimento', 'id', '{nome}','nome asc' , $criteria_atendimento_procedimento_atendimento_procedimento_id );
        $atendimento_procedimento_atendimento_quantidade = new TNumeric('atendimento_procedimento_atendimento_quantidade[]', '2', ',', '.' );
        $atendimento_procedimento_atendimento_valor = new TNumeric('atendimento_procedimento_atendimento_valor[]', '2', ',', '.' );
        $atendimento_procedimento_atendimento_valor_total = new TNumeric('atendimento_procedimento_atendimento_valor_total[]', '2', ',', '.' );
        $this->procedimentos = new TFieldList();
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');
        $atendimento_material_atendimento_id = new THidden('atendimento_material_atendimento_id[]');
        $atendimento_material_atendimento___row__id = new THidden('atendimento_material_atendimento___row__id[]');
        $atendimento_material_atendimento___row__data = new THidden('atendimento_material_atendimento___row__data[]');
        $atendimento_material_atendimento_material_id = new TDBCombo('atendimento_material_atendimento_material_id[]', 'escritorio', 'Material', 'id', '{nome} - ({unidade_medida->sigla})','nome asc' , $criteria_atendimento_material_atendimento_material_id );
        $atendimento_material_atendimento_quantidade = new TNumeric('atendimento_material_atendimento_quantidade[]', '2', ',', '.' );
        $this->fieldList_60fb02dae1078 = new TFieldList();

        $this->procedimentos->addField(null, $atendimento_procedimento_atendimento_id, []);
        $this->procedimentos->addField(null, $atendimento_procedimento_atendimento___row__id, ['uniqid' => true]);
        $this->procedimentos->addField(null, $atendimento_procedimento_atendimento___row__data, []);
        $this->procedimentos->addField(new TLabel("Procedimento", null, '14px', null), $atendimento_procedimento_atendimento_procedimento_id, ['width' => '40%']);
        $this->procedimentos->addField(new TLabel("Quantidade", null, '14px', null), $atendimento_procedimento_atendimento_quantidade, ['width' => '20%','sum' => true]);
        $this->procedimentos->addField(new TLabel("Valor", null, '14px', null), $atendimento_procedimento_atendimento_valor, ['width' => '20%']);
        $this->procedimentos->addField(new TLabel("Total", null, '14px', null), $atendimento_procedimento_atendimento_valor_total, ['width' => '100%']);

        $this->procedimentos->width = '100%';
        $this->procedimentos->setFieldPrefix('atendimento_procedimento_atendimento');
        $this->procedimentos->name = 'procedimentos';

        $this->criteria_procedimentos = new TCriteria();
        $this->default_item_procedimentos = new stdClass();

        $this->form->addField($atendimento_procedimento_atendimento_id);
        $this->form->addField($atendimento_procedimento_atendimento___row__id);
        $this->form->addField($atendimento_procedimento_atendimento___row__data);
        $this->form->addField($atendimento_procedimento_atendimento_procedimento_id);
        $this->form->addField($atendimento_procedimento_atendimento_quantidade);
        $this->form->addField($atendimento_procedimento_atendimento_valor);
        $this->form->addField($atendimento_procedimento_atendimento_valor_total);

        $this->procedimentos->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $this->fieldList_60fb02dae1078->addField(null, $atendimento_material_atendimento_id, []);
        $this->fieldList_60fb02dae1078->addField(null, $atendimento_material_atendimento___row__id, ['uniqid' => true]);
        $this->fieldList_60fb02dae1078->addField(null, $atendimento_material_atendimento___row__data, []);
        $this->fieldList_60fb02dae1078->addField(new TLabel("Material", null, '14px', null), $atendimento_material_atendimento_material_id, ['width' => '50%']);
        $this->fieldList_60fb02dae1078->addField(new TLabel("Quantidade", null, '14px', null), $atendimento_material_atendimento_quantidade, ['width' => '20%']);

        $this->fieldList_60fb02dae1078->width = '100%';
        $this->fieldList_60fb02dae1078->setFieldPrefix('atendimento_material_atendimento');
        $this->fieldList_60fb02dae1078->name = 'fieldList_60fb02dae1078';

        $this->criteria_fieldList_60fb02dae1078 = new TCriteria();
        $this->default_item_fieldList_60fb02dae1078 = new stdClass();

        $this->form->addField($atendimento_material_atendimento_id);
        $this->form->addField($atendimento_material_atendimento___row__id);
        $this->form->addField($atendimento_material_atendimento___row__data);
        $this->form->addField($atendimento_material_atendimento_material_id);
        $this->form->addField($atendimento_material_atendimento_quantidade);

        $this->fieldList_60fb02dae1078->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $atendimento_procedimento_atendimento_procedimento_id->setChangeAction(new TAction([$this,'onChangeProcedimento']));

        $atendimento_procedimento_atendimento_quantidade->setExitAction(new TAction([$this,'onExitQuantidade']));
        $atendimento_procedimento_atendimento_valor->setExitAction(new TAction([$this,'onExitValor']));

        $atendimento_procedimento_atendimento_procedimento_id->addValidation("Procedimento", new TRequiredListValidator()); 
        $atendimento_procedimento_atendimento_quantidade->addValidation("Quantidade", new TRequiredListValidator()); 
        $atendimento_procedimento_atendimento_valor->addValidation("Valor", new TRequiredListValidator()); 
        $atendimento_material_atendimento_material_id->addValidation("Material", new TRequiredListValidator()); 
        $atendimento_material_atendimento_quantidade->addValidation("Quantidade", new TRequiredListValidator()); 

        $atendimento_procedimento_atendimento_procedimento_id->enableSearch();
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $data_criacao->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $atendimento_procedimento_atendimento_valor->setSize('100%');
        $atendimento_material_atendimento_quantidade->setSize('100%');
        $atendimento_material_atendimento_material_id->setSize('100%');
        $atendimento_procedimento_atendimento_quantidade->setSize('100%');
        $atendimento_procedimento_atendimento_valor_total->setSize('100%');
        $atendimento_procedimento_atendimento_procedimento_id->setSize('100%');


        $this->form->appendPage("Procedimentos");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[]);
        $row1->layout = ['col-sm-6',' col-sm-4'];

        $row2 = $this->form->addFields([$this->procedimentos]);
        $row2->layout = ['col-sm-12'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row4 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row4->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        $this->form->appendPage("Materiais");
        $row5 = $this->form->addFields([$this->fieldList_60fb02dae1078]);
        $row5->layout = [' col-sm-12'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Atendimento","Cadastro de Atendimento"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public static function onExitQuantidade($param = null) 
    {
        try 
        {

            // Código gerado pelo snippet: "Cálculos com campos"
            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);
            $field_data = json_decode($param['_field_data_json']);

            $atendimento_procedimento_atendimento_quantidade = (double) str_replace(',', '.', str_replace('.', '', $param['atendimento_procedimento_atendimento_quantidade'][$field_data->row]));
            $atendimento_procedimento_atendimento_valor = (double) str_replace(',', '.', str_replace('.', '', $param['atendimento_procedimento_atendimento_valor'][$field_data->row]));

            $atendimento_procedimento_atendimento_valor_total = $atendimento_procedimento_atendimento_quantidade * $atendimento_procedimento_atendimento_valor ;
            $object = new stdClass();
            $object->{"atendimento_procedimento_atendimento_valor_total_{$field_id}"} = number_format($atendimento_procedimento_atendimento_valor_total, 2, ',', '.');
            TForm::sendData(self::$formName, $object);
            // -----

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onExitValor($param = null) 
    {
        try 
        {

            self::onExitQuantidade($param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeProcedimento($param = null) 
    {
        try 
        {
            if(!empty($param['key']))
            {
                TTransaction::open(self::$database);

                $procedimento = new Procedimento($param['key']);

                TTransaction::close();

                // Código gerado pelo snippet: "Enviar dados para campo"

                $field_id = explode('_', $param['_field_id']);
                $field_id = end($field_id);

                $object = new stdClass();
                $object->{"atendimento_procedimento_atendimento_valor_{$field_id}"} = TextService::toBRL($procedimento->valor);
                $object->{"atendimento_procedimento_atendimento_quantidade_{$field_id}"} = 1;
                $object->{"atendimento_procedimento_atendimento_valor_total_{$field_id}"} = TextService::toBRL($procedimento->valor);
                //$object->fieldName = 'insira o novo valor aqui'; //sample

                TForm::sendData(self::$formName, $object);
                // -----

            }

        }
        catch (Exception $e) 
        {
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

            $object = new Atendimento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            $atendimento_material_atendimento_items = $this->storeItems('AtendimentoMaterial', 'atendimento_id', $object, $this->fieldList_60fb02dae1078, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_60fb02dae1078); 

            $atendimento_procedimento_atendimento_items = $this->storeItems('AtendimentoProcedimento', 'atendimento_id', $object, $this->procedimentos, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_procedimentos); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

            TForm::sendData(self::$formName, (object)['id' => $object->id]);

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

                $object = new Atendimento($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->fieldList_60fb02dae1078_items = $this->loadItems('AtendimentoMaterial', 'atendimento_id', $object, $this->fieldList_60fb02dae1078, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_60fb02dae1078); 

                $this->procedimentos_items = $this->loadItems('AtendimentoProcedimento', 'atendimento_id', $object, $this->procedimentos, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_procedimentos); 

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

        $this->procedimentos->addHeader();
        $this->procedimentos->addDetail($this->default_item_procedimentos);

        $this->procedimentos->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_60fb02dae1078->addHeader();
        $this->fieldList_60fb02dae1078->addDetail($this->default_item_fieldList_60fb02dae1078);

        $this->fieldList_60fb02dae1078->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->procedimentos->addHeader();
        $this->procedimentos->addDetail($this->default_item_procedimentos);

        $this->procedimentos->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_60fb02dae1078->addHeader();
        $this->fieldList_60fb02dae1078->addDetail($this->default_item_fieldList_60fb02dae1078);

        $this->fieldList_60fb02dae1078->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

