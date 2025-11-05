<?php

class DocumentoAvulsoForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_DocumentoAvulsoForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Gerar documento");

        $criteria_tipo_modelo_documento_id = new TCriteria();
        $criteria_modelo_documento_id = new TCriteria();
        $criteria_cliente_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $tipo_modelo_documento_id = new TDBCombo('tipo_modelo_documento_id', 'escritorio', 'TipoModeloDocumento', 'id', '{nome}','nome asc' , $criteria_tipo_modelo_documento_id );
        $modelo_documento_id = new BDBSelectCheck('modelo_documento_id', 'escritorio', 'ModeloDocumento', 'id', '{nome}','nome asc' , $criteria_modelo_documento_id );
        $inf_objeto = new TCheckButton('inf_objeto');
        $objeto = new TText('objeto');
        $cliente_label = new TLabel("Cliente:", null, '14px', null);
        $cliente_id = new TDBMultiSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );

        $tipo_modelo_documento_id->setChangeAction(new TAction([$this,'onChangeTipo']));
        $inf_objeto->setChangeAction(new TAction([$this,'onHabilitaObjeto']));

        $tipo_modelo_documento_id->enableSearch();
        $inf_objeto->setUseSwitch(true, 'blue');
        $inf_objeto->setIndexValue("S");
        $inf_objeto->setInactiveIndexValue("N");
        $cliente_id->setMinLength(3);
        $cliente_id->setMask('{nome}');
        $objeto->setSize('100%', 70);
        $cliente_id->setSize('100%', 70);
        $modelo_documento_id->setSize('99%');
        $tipo_modelo_documento_id->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Tipo:", null, '14px', null)],[$tipo_modelo_documento_id]);
        $row2 = $this->form->addFields([new TLabel("Documentos:", null, '14px', null)],[$modelo_documento_id]);
        $row3 = $this->form->addFields([new TLabel("Informar objeto?", null, '14px', null)],[$inf_objeto]);
        $row4 = $this->form->addFields([new TLabel("Objeto:", null, '14px', null)],[$objeto]);
        $row5 = $this->form->addFields([$cliente_label],[$cliente_id]);

        // create the form actions
        $btn_onanterior = $this->form->addAction("Anterior", new TAction([$this, 'onAnterior']), 'fas:arrow-alt-circle-left #FFFFFF');
        $this->btn_onanterior = $btn_onanterior;
        $btn_onanterior->addStyleClass('btn-primary'); 

        $btnProximo = $this->form->addAction("Próximo", new TAction([$this, 'onProximo']), 'fas:arrow-alt-circle-right #ffffff');
        $this->btnProximo = $btnProximo;
        $btnProximo->addStyleClass('btn-primary'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Documentos","Gerar documento"]));
        }
        $container->add($this->form);

        $cliente_label->name = 'cliente_label';
        $this->form->getField('cliente_id')->setEditable(FALSE);
        BootstrapFormBuilder::showField(self::$formName, 'tipo_modelo_documento_id');

        BootstrapFormBuilder::hideField(self::$formName, 'cliente_label');

        BootstrapFormBuilder::showField(self::$formName, 'modelo_documento_id');
        BootstrapFormBuilder::showField(self::$formName, 'inf_objeto');
        BootstrapFormBuilder::hideField(self::$formName, 'objeto');

        TTransaction::open('escritorio');
        TmpDocumento::where('id','>=',0)->delete();
        TTransaction::close();

        parent::add($container);

    }

    public static function onChangeTipo($param = null) 
    {
        try 
        {
            $criteria = new TCriteria();
            $criteria->add(new TFilter('tipo_modelo_documento_id','=', $param['tipo_modelo_documento_id']));

            BDBSelectCheck::reloadFromModel(
                    self::$formName,         // Nome do formulário
                    'modelo_documento_id',   // Nome do campo no formulário
                    'escritorio',            // Nome do banco de dados
                    'ModeloDocumento',       // Classe do modelo
                    'id',                    // Campo de chave da cidade
                    'nome',                  // Nome da cidade a ser exibido no combo
                    'nome',                  // Ordenar pelo nome da cidade
                    $criteria,               // Nenhum critério adicional
                    true,                    // Iniciar com uma opção vazia
                    true                     // Disparar evento de alteração
                );

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onHabilitaObjeto($param = null) 
    {
        try 
        {
            if($param['inf_objeto']==="S"){
                BootstrapFormBuilder::showField(self::$formName, 'objeto');
            }else{
                $object = new stdClass();
                $object->objeto = null;
                TForm::sendData(self::$formName, $object);
                BootstrapFormBuilder::hideField(self::$formName, 'objeto');
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onAnterior($param = null) 
    {
        try 
        {
            $object = new stdClass();

            $object->cliente_id = null;

            $object->tipo_modelo_documento_id = $param['tipo_modelo_documento_id'] ?? null;
            $object->modelo_documento_id = $param['modelo_documento_id'] ?? null;
            $object->inf_objeto = 'N';
            $object->objeto = $param['objeto'] ?? null;

            TDBMultiSearch::disableField(self::$formName, 'cliente_id');
            BootstrapFormBuilder::hideField(self::$formName, 'cliente_label');
            BootstrapFormBuilder::showField(self::$formName, 'tipo_modelo_documento_id');
            BootstrapFormBuilder::showField(self::$formName, 'modelo_documento_id');
            BootstrapFormBuilder::showField(self::$formName, 'inf_objeto');
            BootstrapFormBuilder::hideField(self::$formName, 'objeto');

            TForm::sendData(self::$formName, $object);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function onProximo($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');
            TmpDocumento::where('id','>=',0)->delete();
            $objeto = null;

            if($param['tipo_modelo_documento_id']){
                $items = array();
                foreach(ModeloDocumento::where('tipo_modelo_documento_id','=', $param['tipo_modelo_documento_id'])->orderby('nome')->load() as $value){
                    $items[$value->id] = $value->nome;
                }

                TCheckGroup::reload(self::$formName, 'modelo_documento_id', $items, true);

                if((isset($param['modelo_documento_id']) && $param['modelo_documento_id']!=null && !empty($param['modelo_documento_id']))){

                    TSession::setValue('modelos_id',$param['modelo_documento_id']);

                    if(isset($param['cliente_id']) && $param['cliente_id']!=null){

                        $erro = array();

                        foreach($param['modelo_documento_id'] as $value_modelo_documento){

                            $modeloDocumento = ModeloDocumento::find((int)$value_modelo_documento);

                            foreach($param['cliente_id'] as $cliente_id){
                                $cliente = Pessoa::find($cliente_id);

                                //VERIFICAR DADOS DOS CLIENTES
                                $dadosCliente = ModeloDocumentoService::onVerificarDadosCliente($cliente,$modeloDocumento, $param['objeto']);

                                if($dadosCliente !== null){
                                    $erro[] = "Não é possível gerar documento para <b>".$dadosCliente['cliente']."</b>, cadastre os seguintes campos para gerar: ".$dadosCliente['dadosFaltantes'].".";
                                }
                            }
                        }

                        if(count($erro)>0){
                            throw new Exception(implode("<br/>", $erro));
                        }

                        foreach($param['modelo_documento_id'] as $value_modelo_documento){

                            $modelo_documento = ModeloDocumento::find((int)$value_modelo_documento);

                            foreach($param['cliente_id'] as $cliente_id){

                                $cliente = Pessoa::find((int)$cliente_id);

                                $serviceParam = array();
                                $serviceParam['objeto'] = $param['objeto'] ?? null;
                                $serviceParam['modelo_documento_id'] = $modelo_documento->id;
                                $serviceParam['cliente_id'] = $cliente->id;

                                $retorno = ModeloDocumentoService::preencherDocumento($serviceParam);

                                $temporario = new TmpDocumento();
                                $temporario->nome = $modelo_documento->nome." ".$cliente->nome;
                                $temporario->filename = $retorno['novo_nome_arquivo'];
                                $temporario->store();
                            }
                        }

                        DocumentoAvulsoForm::onChangeTipo(['tipo_modelo_documento_id' => $modelo_documento->tipo_modelo_documento_id]);
                        TApplication::loadPage('TmpDocumentoSimpleList', 'onShow');
                    }else{
                        //Não tem cliente
                        TDBMultiSearch::enableField(self::$formName, 'cliente_id');
                        BootstrapFormBuilder::showField(self::$formName, 'cliente_label');

                        TToast::show("warning", "Selecione o(s) cliente(s) para gerar.", "topRight", "fas:info-circle");

                        TScript::create("$('label:contains(\"Documentos:\")').show();");
                        BootstrapFormBuilder::showField(self::$formName, 'tipo_modelo_documento_id');
                        BootstrapFormBuilder::showField(self::$formName, 'modelo_documento_id');
                        BootstrapFormBuilder::showField(self::$formName, 'inf_objeto');
                        TDBCombo::disableField(self::$formName, 'tipo_modelo_documento_id');
                        TCheckGroup::disableField(self::$formName, 'modelo_documento_id');
                        TSession::setValue('modelos_id',$param['modelo_documento_id']);
                    }
                }else{
                    //Nao tem modelo
                    TDBMultiSearch::disableField(self::$formName, 'cliente_id');
                    BootstrapFormBuilder::hideField(self::$formName, 'cliente_label');

                    TToast::show("warning", "Selecione o(s) modelo(s) de documento para gerar.", "topRight", "fas:info-circle");

                    TScript::create("$('label:contains(\"Documentos:\")').show();");
                    BootstrapFormBuilder::showField(self::$formName, 'tipo_modelo_documento_id');
                    BootstrapFormBuilder::showField(self::$formName, 'modelo_documento_id');
                    BootstrapFormBuilder::showField(self::$formName, 'inf_objeto');
                    TSession::setValue('modelos_id',null);
                }
            }else{
                //Não tem tipo
                TDBMultiSearch::disableField(self::$formName, 'cliente_id');
                BootstrapFormBuilder::hideField(self::$formName, 'cliente_label');

                TToast::show("warning", "Selecione o tipo de modelo de documento para gerar.", "topRight", "fas:info-circle");

                TScript::create("$('label:contains(\"Documentos:\")').show();");
                BootstrapFormBuilder::showField(self::$formName, 'tipo_modelo_documento_id');
                BootstrapFormBuilder::showField(self::$formName, 'modelo_documento_id');
                BootstrapFormBuilder::showField(self::$formName, 'inf_objeto');
                TSession::setValue('modelos_id',null);
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

        BootstrapFormBuilder::hideField(self::$formName, 'cliente_label');
    } 

}

