<?php

class ModalBuscarCNPJ extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalBuscarCNPJ';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.40, null);
        parent::setTitle("Modal Buscar CNPJ");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setProperty('class','panel panel-default form-view-wrapper');
        // define the form title
        $this->form->setFormTitle("Modal Buscar CNPJ");


        $cnpj_buscar = new TEntry('cnpj_buscar');
        $campo = new THidden('campo');
        $form = new THidden('form');


        $cnpj_buscar->setMask('##.###.###/####-##', true);
        $form->setValue($param['form']);
        $campo->setValue($param['campo']);

        $form->setSize(200);
        $campo->setSize(200);
        $cnpj_buscar->setSize('100%');

        $cnpj_buscar->autofocus = 'autofocus';


        $row1 = $this->form->addFields([new TLabel("Digite o CNPJ para buscar:", null, '14px', null, '100%')],[$cnpj_buscar,$campo,$form]);
        $row1->layout = [' col-sm-4 control-label',' col-sm-8'];

        // create the form actions
        $btn_onbuscar = $this->form->addAction("Buscar", new TAction([$this, 'onBuscar'],['static' => 1]), 'fas:search #ffffff');
        $this->btn_onbuscar = $btn_onbuscar;
        $btn_onbuscar->addStyleClass('btn-primary'); 

        parent::add($this->form);

    }

    public static function onBuscar($param = null) 
    {
        try{

            TTransaction::open('escritorio');
            $dados = CNPJService::get($param['cnpj_buscar']);
            $dadosFull = CNPJService::getFull($param['cnpj_buscar']);

            if(!$dados)
            {
                throw new Exception('CNPJ não encontrado');
            }

            if(!$dadosFull)
            {
                throw new Exception('CNPJ Full não encontrado');
            }

            // iremos recarregar a combo de estado, pois pode ser que o estado encontrado para aquele CNPJ
            // ainda não foi cadastrado no sistema
            TCombo::reload(self::$formName, 'pessoa_endereco_pessoa_cidade_estado_id', Estado::getIndexedArray('id', 'nome'), true);

            TTransaction::close();

            $object = new stdClass();

            //Dados principais
            $object->nome = $dados->razao_social;
            $object->telefone = $dados->ddd_telefone_1 ?? NULL;
            $object->email = $dadosFull->estabelecimento->email ?? NULL;

            $data = $dadosFull->estabelecimento->data_inicio_atividade;
            $data = date('d/m/Y',strtotime($data));

            //Documentos
            $object->cpf_cnpj = $dados->cnpj;
            $object->dt_nascimento_abertura = $data ?? NULL;
            $object->rg_ie = $dadosFull->estabelecimento->inscricoes_estaduais[0]->inscricao_estadual ?? NULL;

            //Endereço
            $object->pessoa_endereco_pessoa_cep = $dados->cep;
            $object->pessoa_endereco_pessoa_rua = $dados->logradouro;
            $object->pessoa_endereco_pessoa_bairro = $dados->bairro;
            $object->pessoa_endereco_pessoa_numero = $dados->numero;
            $object->pessoa_endereco_pessoa_complemento = $dados->complemento ?? NULL;
            $object->pessoa_endereco_pessoa_cidade_estado_id = $dados->estado_id ?? NULL;
            $object->pessoa_endereco_pessoa_cidade_id = $dados->cidade_id ?? null;

            if (!empty($param['form']))
            {
                TScript::create("$(\"[page_name='ModalBuscarCNPJ']\").remove()");
                TToast::show("show", "Sucesso ao buscar!", "topRight", "fas:check-circle");
                return TForm::sendData($param['form'], $object);
            }else{
                throw new Exception('Não foi possível carregar os dados, tente novamente!');
            }

        }catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {               

    } 

}

