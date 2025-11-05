<?php

class AtendimentoAvulsoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Atendimento';
    private static $primaryKey = 'id';
    private static $formName = 'form_AtendimentoAvulsoForm';

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
        $this->form->setFormTitle("Cadastro de anotação");

        $criteria_cliente_id = new TCriteria();
        $criteria_profissional_id = new TCriteria();
        $criteria_atendimento_procedimento_atendimento_parceiro_id = new TCriteria();
        $criteria_atendimento_procedimento_atendimento_procedimento_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::PROFISSIONAL;
        $criteria_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $id = new TEntry('id');
        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $profissional_id = new TDBCombo('profissional_id', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_profissional_id );
        $dt_inicio = new TDateTime('dt_inicio');
        $atendimento_procedimento_atendimento_id = new THidden('atendimento_procedimento_atendimento_id[]');
        $atendimento_procedimento_atendimento___row__id = new THidden('atendimento_procedimento_atendimento___row__id[]');
        $atendimento_procedimento_atendimento___row__data = new THidden('atendimento_procedimento_atendimento___row__data[]');
        $atendimento_procedimento_atendimento_parceiro_id = new TDBCombo('atendimento_procedimento_atendimento_parceiro_id[]', 'escritorio', 'Parceiro', 'id', '{nome}','nome asc' , $criteria_atendimento_procedimento_atendimento_parceiro_id );
        $atendimento_procedimento_atendimento_procedimento_id = new TDBCombo('atendimento_procedimento_atendimento_procedimento_id[]', 'escritorio', 'Procedimento', 'id', '{nome}','nome asc' , $criteria_atendimento_procedimento_atendimento_procedimento_id );
        $atendimento_procedimento_atendimento_valor = new TNumeric('atendimento_procedimento_atendimento_valor[]', '2', ',', '.' );
        $atendimento_procedimento_atendimento_quantidade = new TNumeric('atendimento_procedimento_atendimento_quantidade[]', '2', ',', '.' );
        $atendimento_procedimento_atendimento_valor_total = new TNumeric('atendimento_procedimento_atendimento_valor_total[]', '2', ',', '.' );
        $this->fieldList_64ecd2caa2771 = new TFieldList();
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->fieldList_64ecd2caa2771->addField(null, $atendimento_procedimento_atendimento_id, []);
        $this->fieldList_64ecd2caa2771->addField(null, $atendimento_procedimento_atendimento___row__id, ['uniqid' => true]);
        $this->fieldList_64ecd2caa2771->addField(null, $atendimento_procedimento_atendimento___row__data, []);
        $this->fieldList_64ecd2caa2771->addField(new TLabel("Parceiro", null, '14px', null), $atendimento_procedimento_atendimento_parceiro_id, ['width' => '20%']);
        $this->fieldList_64ecd2caa2771->addField(new TLabel("Procedimento", null, '14px', null), $atendimento_procedimento_atendimento_procedimento_id, ['width' => '20%']);
        $this->fieldList_64ecd2caa2771->addField(new TLabel("Valor", null, '14px', null), $atendimento_procedimento_atendimento_valor, ['width' => '20%']);
        $this->fieldList_64ecd2caa2771->addField(new TLabel("Quantidade", null, '14px', null), $atendimento_procedimento_atendimento_quantidade, ['width' => '20%']);
        $this->fieldList_64ecd2caa2771->addField(new TLabel("Valor total", null, '14px', null), $atendimento_procedimento_atendimento_valor_total, ['width' => '20%','sum' => true]);

        $this->fieldList_64ecd2caa2771->width = '100%';
        $this->fieldList_64ecd2caa2771->setFieldPrefix('atendimento_procedimento_atendimento');
        $this->fieldList_64ecd2caa2771->name = 'fieldList_64ecd2caa2771';

        $this->criteria_fieldList_64ecd2caa2771 = new TCriteria();
        $this->default_item_fieldList_64ecd2caa2771 = new stdClass();

        $this->form->addField($atendimento_procedimento_atendimento_id);
        $this->form->addField($atendimento_procedimento_atendimento___row__id);
        $this->form->addField($atendimento_procedimento_atendimento___row__data);
        $this->form->addField($atendimento_procedimento_atendimento_parceiro_id);
        $this->form->addField($atendimento_procedimento_atendimento_procedimento_id);
        $this->form->addField($atendimento_procedimento_atendimento_valor);
        $this->form->addField($atendimento_procedimento_atendimento_quantidade);
        $this->form->addField($atendimento_procedimento_atendimento_valor_total);

        $this->fieldList_64ecd2caa2771->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $atendimento_procedimento_atendimento_parceiro_id->setChangeAction(new TAction([$this,'onChangeParceiro']));
        $atendimento_procedimento_atendimento_procedimento_id->setChangeAction(new TAction([$this,'onChangeProcedimento']));

        $atendimento_procedimento_atendimento_valor->setExitAction(new TAction([$this,'onExitValor']));
        $atendimento_procedimento_atendimento_quantidade->setExitAction(new TAction([$this,'onExitQuantidade']));

        $cliente_id->addValidation("Cliente id", new TRequiredValidator()); 
        $profissional_id->addValidation("Profissional", new TRequiredValidator()); 

        $cliente_id->setMinLength(3);
        $dt_inicio->setValue(date('d/m/Y H:i:s'));
        $cliente_id->setValue($param['cliente_id'] ?? null);

        $profissional_id->enableSearch();
        $atendimento_procedimento_atendimento_parceiro_id->enableSearch();
        $atendimento_procedimento_atendimento_procedimento_id->enableSearch();

        $dt_inicio->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $cliente_id->setMask('{nome}');
        $dt_inicio->setMask('dd/mm/yyyy hh:ii');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);
        $atendimento_procedimento_atendimento_valor_total->setEditable(false);

        $id->setSize(100);
        $dt_inicio->setSize(160);
        $cliente_id->setSize('100%');
        $data_criacao->setSize('100%');
        $profissional_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $atendimento_procedimento_atendimento_valor->setSize('100%');
        $atendimento_procedimento_atendimento_quantidade->setSize('100%');
        $atendimento_procedimento_atendimento_parceiro_id->setSize('100%');
        $atendimento_procedimento_atendimento_valor_total->setSize('100%');
        $atendimento_procedimento_atendimento_procedimento_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Cliente:", '#ff0000', '14px', null, '100%'),$cliente_id],[new TLabel("Profissional:", '#ff0000', '14px', null, '100%'),$profissional_id]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([$dt_inicio]);
        $row3->layout = ['col-sm-3'];

        $row4 = $this->form->addContent([new TFormSeparator("Procedimentos", '#797979', '18', '#797979')]);
        $row5 = $this->form->addFields([$this->fieldList_64ecd2caa2771]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row7 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row7->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
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

        $style = new TStyle('right-panel > .container-part[page-name=AtendimentoAvulsoForm]');
        $style->width = '60% !important';   
        $style->show(true);

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

    public static function onExitQuantidade($param = null) 
    {
        try 
        {
            // Código gerado pelo snippet: "Cálculos com campos"
            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);
            $field_data = json_decode($param['_field_data_json']);

            if(!empty($param['atendimento_procedimento_atendimento_valor'][$field_data->row]) && !empty($param['atendimento_procedimento_atendimento_quantidade'][$field_data->row]))
            {
                $atendimento_procedimento_atendimento_valor = (double) str_replace(',', '.', str_replace('.', '', $param['atendimento_procedimento_atendimento_valor'][$field_data->row]));
                $atendimento_procedimento_atendimento_quantidade = (double) str_replace(',', '.', str_replace('.', '', $param['atendimento_procedimento_atendimento_quantidade'][$field_data->row]));

                $atendimento_procedimento_atendimento_valor_total = $atendimento_procedimento_atendimento_valor * $atendimento_procedimento_atendimento_quantidade ;
                $object = new stdClass();
                $object->{"atendimento_procedimento_atendimento_valor_total_{$field_id}"} = number_format($atendimento_procedimento_atendimento_valor_total, 2, ',', '.');
                TForm::sendData(self::$formName, $object);
                // -----    
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeParceiro($param = null) 
    {
        try 
        {
            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);
            $row = json_decode($param['_field_data_json'], true)['row'];

            $param['key'] = $param["atendimento_procedimento_atendimento_procedimento_id"][$row];
            self::onChangeProcedimento($param);

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
            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);

            $row = json_decode($param['_field_data_json'], true)['row'];

            if(empty($param["atendimento_procedimento_atendimento_parceiro_id"][$row]))
            {
                $object = new stdClass();
                $object->{"atendimento_procedimento_atendimento_procedimento_id_{$field_id}"} = '';

                TForm::sendData(self::$formName, $object, false, false, false);

                throw new Exception('Antes de escolher uma procedimento, preencha o Convênio');
            }

            if(!empty($param['key']))
            {
                TTransaction::open(self::$database);

                $procedimentoPreco = ProcedimentoPreco::where('parceiro_id', '=', $param["atendimento_procedimento_atendimento_parceiro_id"][$row])
                                                      ->where('procedimento_id', '=', $param['key'])
                                                      ->first();
                $object = new stdClass();
                $object->{"atendimento_procedimento_atendimento_quantidade_{$field_id}"} = 1;
                $object->{"atendimento_procedimento_atendimento_valor_{$field_id}"} = TextService::toBRL($procedimentoPreco->valor);

                TForm::sendData(self::$formName, $object, false, false, false);

                $object = new stdClass();
                $object->{"atendimento_procedimento_atendimento_valor_total_{$field_id}"} = TextService::toBRL($procedimentoPreco->valor);

                TForm::sendData(self::$formName, $object);

                TTransaction::close();
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
            $object->tipo_atendimento_id = TipoAtendimento::AVULSO;
            $object->dt_final = date('Y-m-d H:i:s');

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(!empty($object->id))
            {
                $loadPageParam["key"] = $object->id;
            }

            $object->valor_total = 0.00;

            $atendimento_procedimento_atendimento_items = $this->storeItems('AtendimentoProcedimento', 'atendimento_id', $object, $this->fieldList_64ecd2caa2771, function($masterObject, $detailObject){ 

                $masterObject->valor_total = $masterObject->valor_total + $detailObject->valor_total;

            }, $this->criteria_fieldList_64ecd2caa2771); 

            $object->store();
            $atendimento = Atendimento::find( $object->id );
            if($object->valor_total>0){
                AtendimentoService::gerarContaReceber($atendimento);
            }

            $object->store();
            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TScript::create("Template.closeRightPanel();");
            TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam);

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam); 

            TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam);

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

                $this->fieldList_64ecd2caa2771_items = $this->loadItems('AtendimentoProcedimento', 'atendimento_id', $object, $this->fieldList_64ecd2caa2771, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_64ecd2caa2771); 

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

        $this->fieldList_64ecd2caa2771->addHeader();
        $this->fieldList_64ecd2caa2771->addDetail($this->default_item_fieldList_64ecd2caa2771);

        $this->fieldList_64ecd2caa2771->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_64ecd2caa2771->addHeader();
        $this->fieldList_64ecd2caa2771->addDetail($this->default_item_fieldList_64ecd2caa2771);

        $this->fieldList_64ecd2caa2771->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        TScript::create("$(\"[page_name='ClienteForm']\").remove()");
        TTransaction::open(self::$database);
        TScript::create("$(\"[name='dt_inicio']\").closest('.fb-inline-field-container').hide()");
        $objeto = Pessoa::where('system_users_id', '=', TSession::getValue('userid'))->first();
        if($objeto){
            if($objeto->tipo_profissional_id == TipoProfissional::ADVOGADO){

                $object = new stdClass();
                $object->profissional_id = $objeto->id;
                TForm::sendData(self::$formName, $object);

            }
        }
        if(isset($param['cliente_id'])){
            $object = new stdClass();
            $object->cliente_id = $param['cliente_id'];
            TForm::sendData(self::$formName, $object);
        }
        TTransaction::close();

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

