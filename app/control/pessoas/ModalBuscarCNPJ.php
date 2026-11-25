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
        try
        {
            $form = $param['form'] ?? '';
            if (!in_array($form, ['form_Pessoa', 'form_Parceiro'], true))
            {
                throw new Exception('Abra a busca de CNPJ pelo formulário de cadastro de pessoa.');
            }
            TTransaction::open('escritorio');
            $dados = CNPJService::get($param['cnpj_buscar'] ?? '');
            $object = self::dadosFormulario($dados);
            TTransaction::close();

            TForm::sendData($form, $object);
            TWindow::closeWindowByName(__CLASS__);
            TToast::show('success', 'Dados do CNPJ preenchidos. Confira antes de salvar.', 'topRight', 'fas:check-circle');
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /** Preenche os dados disponíveis. Inscrição estadual e contatos ausentes ficam para conferência manual. */
    public static function dadosFormulario(stdClass $dados): stdClass
    {
        $object = new stdClass;
        $object->nome = trim((string) $dados->razao_social);
        $object->cpf_cnpj = $dados->cnpj;
        foreach (['ddd_telefone_1', 'ddd_telefone_2'] as $campo)
        {
            $telefone = PessoaContatoService::telefoneValido($dados->$campo ?? '');
            if ($telefone !== null)
            {
                $object->telefone = $telefone;
                break;
            }
        }
        $email = mb_strtolower(trim((string) ($dados->email ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) { $object->email = $email; }
        $data = (string) ($dados->data_inicio_atividade ?? '');
        $abertura = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        if ($abertura && $abertura->format('Y-m-d') === $data)
        {
            $object->dt_nascimento_abertura = $abertura->format('d/m/Y');
        }
        $object->pessoa_endereco_pessoa_cep = preg_replace('/\D/', '', (string) ($dados->cep ?? ''));
        foreach (['logradouro' => 'rua', 'bairro' => 'bairro', 'numero' => 'numero', 'complemento' => 'complemento'] as $origem => $destino)
        {
            $object->{'pessoa_endereco_pessoa_' . $destino} = trim((string) ($dados->$origem ?? ''));
        }
        $object->pessoa_endereco_pessoa_cidade_id = $dados->cidade_id ?? null;
        return $object;
    }

    public function onShow($param = null)
    {               

    } 

}

