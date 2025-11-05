<?php

class TarefaForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Tarefa';
    private static $primaryKey = 'id';
    private static $formName = 'form_TarefaForm';

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
        $this->form->setFormTitle("Cadastro de tarefa");

        $criteria_publicacao_id = new TCriteria();
        $criteria_processo_id = new TCriteria();
        $criteria_tarefa_cliente_tarefa_cliente_id = new TCriteria();
        $criteria_usuario_destinatario_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_tarefa_cliente_tarefa_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = "Y";
        $criteria_usuario_destinatario_id->add(new TFilter('active', '=', $filterVar)); 

        $id = new TEntry('id');
        $tarefa_principal_id = new THidden('tarefa_principal_id');
        $retorno = new THidden('retorno');
        $prazo_processual = new TCheckButton('prazo_processual');
        $publicacao_id = new TDBUniqueSearch('publicacao_id', 'escritorio', 'Publicacao', 'id', 'numero_unico_processo','id asc' , $criteria_publicacao_id );
        $processo_id = new TDBUniqueSearch('processo_id', 'escritorio', 'Processo', 'id', 'numero_cnj_numero','numero_cnj_numero asc' , $criteria_processo_id );
        $tarefa_cliente_tarefa_id = new THidden('tarefa_cliente_tarefa_id[]');
        $tarefa_cliente_tarefa___row__id = new THidden('tarefa_cliente_tarefa___row__id[]');
        $tarefa_cliente_tarefa___row__data = new THidden('tarefa_cliente_tarefa___row__data[]');
        $tarefa_cliente_tarefa_cliente_id = new TDBUniqueSearch('tarefa_cliente_tarefa_cliente_id[]', 'escritorio', 'Pessoa', 'id', 'nome_busca','nome_busca asc' , $criteria_tarefa_cliente_tarefa_cliente_id );
        $this->fieldList_66bb6a7fea9ae = new TFieldList();
        $titulo = new TEntry('titulo');
        $usuario_destinatario_id = new TDBCombo('usuario_destinatario_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_usuario_destinatario_id );
        $prazo_validacao = new TDate('prazo_validacao');
        $prazo_entrega = new TDate('prazo_entrega');
        $observacao = new TText('observacao');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->fieldList_66bb6a7fea9ae->addField(null, $tarefa_cliente_tarefa_id, []);
        $this->fieldList_66bb6a7fea9ae->addField(null, $tarefa_cliente_tarefa___row__id, ['uniqid' => true]);
        $this->fieldList_66bb6a7fea9ae->addField(null, $tarefa_cliente_tarefa___row__data, []);
        $this->fieldList_66bb6a7fea9ae->addField(new TLabel("Cliente", null, '14px', null), $tarefa_cliente_tarefa_cliente_id, ['width' => '100%']);

        $this->fieldList_66bb6a7fea9ae->width = '100%';
        $this->fieldList_66bb6a7fea9ae->setFieldPrefix('tarefa_cliente_tarefa');
        $this->fieldList_66bb6a7fea9ae->name = 'fieldList_66bb6a7fea9ae';

        $this->criteria_fieldList_66bb6a7fea9ae = new TCriteria();
        $this->default_item_fieldList_66bb6a7fea9ae = new stdClass();

        $this->form->addField($tarefa_cliente_tarefa_id);
        $this->form->addField($tarefa_cliente_tarefa___row__id);
        $this->form->addField($tarefa_cliente_tarefa___row__data);
        $this->form->addField($tarefa_cliente_tarefa_cliente_id);

        $this->fieldList_66bb6a7fea9ae->disableRemoveButton();

        $this->fieldList_66bb6a7fea9ae->disableCloneButton();

        $titulo->addValidation("Tarefa", new TRequiredValidator()); 
        $usuario_destinatario_id->addValidation("Destinatário", new TRequiredValidator()); 
        $prazo_entrega->addValidation("Prazo de entrega", new TRequiredValidator()); 

        $prazo_processual->setUseSwitch(true, 'blue');
        $prazo_processual->setIndexValue("S");
        $prazo_processual->setInactiveIndexValue("N");
        $titulo->setMaxLength(255);
        $usuario_destinatario_id->enableSearch();
        $processo_id->setMinLength(2);
        $publicacao_id->setMinLength(2);
        $tarefa_cliente_tarefa_cliente_id->setMinLength(2);

        $publicacao_id->setFilterColumns(["numero_unico_processo"]);
        $tarefa_cliente_tarefa_cliente_id->setFilterColumns(["nome_busca"]);
        $processo_id->setFilterColumns(["numero_cnj_numero","numero_outro"]);

        $prazo_entrega->setDatabaseMask('yyyy-mm-dd');
        $prazo_validacao->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $prazo_processual->setValue('S');
        $retorno->setValue($param['retorno'] ?? null);
        $prazo_entrega->setValue($param['prazo'] ?? null);
        $publicacao_id->setValue($param['publicacao_id'] ?? null);
        $tarefa_principal_id->setValue($param['tarefa_principal_id'] ?? null);

        $prazo_entrega->setMask('dd/mm/yyyy');
        $prazo_validacao->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $tarefa_cliente_tarefa_cliente_id->setMask('{nome}');
        $processo_id->setMask('{numero_outro} {numero_cnj_numero}');
        $publicacao_id->setMask('{numero_unico_processo} - {numero_arquivo} -{numero_publicacao}');

        $id->setSize(100);
        $retorno->setSize(200);
        $titulo->setSize('100%');
        $processo_id->setSize('100%');
        $data_criacao->setSize('100%');
        $publicacao_id->setSize('100%');
        $prazo_entrega->setSize('100%');
        $observacao->setSize('100%', 70);
        $prazo_validacao->setSize('100%');
        $tarefa_principal_id->setSize(200);
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $usuario_destinatario_id->setSize('100%');
        $tarefa_cliente_tarefa_cliente_id->setSize('100%');

        $this->form->appendPage("Tarefa");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id],[$tarefa_principal_id,$retorno],[new TLabel("Prazo processual?", null, '14px', null, '100%'),$prazo_processual]);
        $row1->layout = ['col-sm-4',' col-sm-4','col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Publicação:", null, '14px', null, '100%'),$publicacao_id],[new TLabel("Processo:", null, '14px', null, '100%'),$processo_id],[$this->fieldList_66bb6a7fea9ae]);
        $row2->layout = ['col-sm-4','col-sm-4',' col-sm-4'];

        $row3 = $this->form->addFields([new TLabel("Tarefa:", '#ff0000', '14px', null, '100%'),$titulo]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([new TLabel("Destinatário:", '#ff0000', '14px', null, '100%'),$usuario_destinatario_id],[new TLabel("Prazo de validação:", null, '14px', null, '100%'),$prazo_validacao],[new TLabel("Prazo de entrega:", '#FF0000', '14px', null, '100%'),$prazo_entrega]);
        $row4->layout = ['col-sm-6',' col-sm-3',' col-sm-3'];

        $row5 = $this->form->addFields([new TLabel("Observação:", null, '14px', null, '100%'),$observacao]);
        $row5->layout = [' col-sm-12'];

        $this->form->appendPage("Dados Cadastrais");
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['TarefaList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TTransaction::open(self::$database);

        $configuracao = TarefaConfiguracao::find(1);
        //tem_dtvalidacao
        //dtvalidacao_obrigatoria
        if($configuracao->tem_dtvalidacao != "S"){
            TScript::create("$(\"[name='prazo_validacao']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$('label:contains(\"Prazo de validação:\")').hide();");
        }else{
            TScript::create("$(\"[name='prazo_validacao']\").closest('.fb-inline-field-container').show()");
            TScript::create("$('label:contains(\"Prazo de validação:\")').show();");
            if($configuracao->dtvalidacao_obrigatoria != "N"){
                TScript::create("$('label:contains(\"Prazo de validação:\")').html('<span style=\"color:#ff0000;\">Prazo de validação:</span>')");
                $prazo_validacao->addValidation("Prazo de validação", new TRequiredValidator()); 
            }
        }

        TTransaction::close();

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=TarefaForm]');
        $style->width = '60% !important';   
        $style->show(true);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Tarefa(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if($object->publicacao_id!=null){
                if($object->publicacao->prazo < $object->prazo_entrega){
                    throw new Exception("O prazo de entrega da tarefa não pode ser maior que o prazo de entrega da publicação.");
                }
            }

            $object->data_disponibilizacao = date('Y-m-d H:m:s');

            $object->tarefa_status_id = (TarefaConfiguracao::find(1))->status_inicial_id;

            $object->store(); // save the object 

            if(!empty($data->tarefa_principal_id)){
                if($data->id==null){
                    $subtarefa = new TarefaVinculo();
                    $subtarefa->tarefa_id = $data->tarefa_principal_id;
                    $subtarefa->subtarefa_id = $object->id;
                    $subtarefa->store();

                    $movimentacao = new TarefaMovimentacao();
                    $movimentacao->tarefa_id = $data->tarefa_principal_id;
                    $movimentacao->descricao = "Subtarefa #$subtarefa->id criada em ".date('d/m/Y H:i:s');
                    $movimentacao->data_movimentacao = date('Y-m-d H:i:s');
                    $movimentacao->store();
                }
            }

//<generatedAutoCode>
            $this->criteria_fieldList_66bb6a7fea9ae->setProperty('order', 'id asc');
//</generatedAutoCode>
            $tarefa_cliente_tarefa_items = $this->storeItems('TarefaCliente', 'tarefa_id', $object, $this->fieldList_66bb6a7fea9ae, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_66bb6a7fea9ae); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            if($object->publicacao_id!=null){
                APIPublicacaoController::adicionarMovimentacao($object->publicacao_id, "Tarefa criada.", $object->id, null);
            }

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

                        TScript::create("Template.closeRightPanel();");
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

            sleep(1);

            if($data->retorno){
                $retorno = explode(',',$data->retorno);
                if(isset($retorno[1])) $pageParam['key'] = $retorno[1];
                TApplication::loadPage($retorno[0],'onShow',$pageParam ?? null);
            }else{
                TApplication::loadPage('TarefaList','onShow');
            }

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

                $object = new Tarefa($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->criteria_fieldList_66bb6a7fea9ae->setProperty('order', 'id asc');
                $this->fieldList_66bb6a7fea9ae_items = $this->loadItems('TarefaCliente', 'tarefa_id', $object, $this->fieldList_66bb6a7fea9ae, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_66bb6a7fea9ae); 

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

        $this->fieldList_66bb6a7fea9ae->addHeader();
        $this->fieldList_66bb6a7fea9ae->addDetail($this->default_item_fieldList_66bb6a7fea9ae);

    }

    public function onShow($param = null)
    {
        $this->fieldList_66bb6a7fea9ae->addHeader();
        $this->fieldList_66bb6a7fea9ae->addDetail($this->default_item_fieldList_66bb6a7fea9ae);

        $retorno = $param['retorno'] ?? null;

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

