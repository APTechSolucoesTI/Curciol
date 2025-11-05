<?php

class LoginClienteForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_LoginClienteForm';

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
        $this->form->setFormTitle("Bem vindo");


        $usuario = new TEntry('usuario');
        $senha = new TPassword('senha');


        $senha->setSize('100%');
        $usuario->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Login:", null, '14px', null, '100%'),$usuario]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("Senha:", null, '14px', null, '100%'),$senha]);
        $row2->layout = [' col-sm-12'];

        // create the form actions
        $btnEntrar = $this->form->addAction("Entrar", new TAction([$this, 'onLogin']), ' #ffffff');
        $this->btnEntrar = $btnEntrar;
        $btnEntrar->addStyleClass('btn-primary'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        }
        $container->add($this->form);

        $this->form->style = 'width:100%; max-width:450px;margin:auto;';
        $container->style = 'width:100%; max-width:450px;margin:auto;margin-top:100px;';

        parent::add($container);

    }

    public static function onLogin($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');
            $cliente = Pessoa::where('usuario', '=', $param['usuario'])->where('senha', '=', $param['senha'])->first();

            if($cliente)
            {
                TSession::setValue('cliente_id', $cliente->id);

                $pageParam = []; // ex.: = ['key' => 10]

                TApplication::loadPage('ClienteAtendimentoList', 'onShow', $pageParam);
            }
            else
            {
                throw new Exception('Usuário ou senha incorretos');
            }

            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::close();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

    } 

}

