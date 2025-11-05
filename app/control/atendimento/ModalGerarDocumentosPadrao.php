<?php

class ModalGerarDocumentosPadrao extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalGerarDocumentosPadrao';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(900, null);
        parent::setTitle("Gerar documentos no atendimento");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Gerar documentos no atendimento");

        $criteria_padrao_atendimento_documento_id = new TCriteria();

        $atendimento_id = new THidden('atendimento_id');
        $padrao_atendimento_documento_id = new TDBCombo('padrao_atendimento_documento_id', 'escritorio', 'PadraoAtendimentoDocumento', 'id', '{nome}','nome asc' , $criteria_padrao_atendimento_documento_id );

        $padrao_atendimento_documento_id->addValidation("Padrão de documentos", new TRequiredValidator()); 

        $atendimento_id->setValue($param['atendimento_id']);
        $padrao_atendimento_documento_id->enableSearch();
        $atendimento_id->setSize(200);
        $padrao_atendimento_documento_id->setSize('100%');


        $row1 = $this->form->addFields([$atendimento_id]);
        $row1->layout = [' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Padrão de documentos:", '#FF0000', '14px', null, '100%'),$padrao_atendimento_documento_id]);
        $row2->layout = [' col-sm-12'];

        // create the form actions
        $btn_ongerar = $this->form->addAction("Gerar", new TAction([$this, 'onGerar']), 'fas:cog #ffffff');
        $this->btn_ongerar = $btn_ongerar;
        $btn_ongerar->addStyleClass('btn-success'); 

        parent::add($this->form);

    }

    public function onGerar($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');

            $object = new Documento(); // create an empty object

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $modelos_documento_padrao = PadraoAtendModeloDoc::where('tipo_padrao_doc_atendimento_id','=',$data->padrao_atendimento_documento_id)->load();
            $atendimento = Atendimento::find($data->atendimento_id);

            /*
            $cliente_endereco = PessoaEndereco::where('principal','=','S')->where('pessoa_id','=',$atendimento->cliente_id)->first();
            if(!$cliente_endereco){
                throw new Exception("Cadastre um endereço principal para gerar um documento.");
            }
            */

            foreach($modelos_documento_padrao as $modelo_documento_padrao){

                $modeloDocumento = ModeloDocumento::find($modelo_documento_padrao->modelo_documento_id);

                //VERIFICAR OBRIGATORIEDADES
                $serviceParam = [
                    'modelo_documento_id' => $modeloDocumento->id,
                    'cliente_id' => $atendimento->cliente_id,
                    'profissional_id' => $atendimento->profissional_id,
                    'atendimento_id' => $atendimento->id
                ];
                $validarDados = ModeloDocumentoService::validarDadosObriatoriosDocumento($serviceParam);

                if($validarDados!==""){
                    throw new Exception($validarDados);
                }

                $returnParam = ModeloDocumentoService::preencherDocumento($serviceParam);

                $documento = new Documento();
                $documento->modelo_documento_id = $modeloDocumento->id;
                $documento->autenticador = $returnParam['autenticador'];
                $documento->atendimento_id = $returnParam['complemento_id'];
                $documento->dt_preenchimento = date('Y-m-d H:i:s');
                $documento->filename = $returnParam['novo_nome_arquivo'];
                $documento->criacao_user_id = TSession::getValue('userid');
                $documento->store();
            }

            TApplication::loadPage('AtendimentoFormView', 'onShow', ['key' => $atendimento->id, 'id' => $atendimento->id, 'current_tab_abas' => 3]);
            TScript::create("$(\"[page_name='ModalGerarDocumentosPadrao']\").remove()");

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

    } 

}

