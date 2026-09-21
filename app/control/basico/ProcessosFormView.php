<?php

class ProcessosFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Pessoa';

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

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        TSession::setValue('keyVoltar', $param['key'] ?? null);

        $pessoa = new Pessoa($param['key']);
        // define the form title
        $this->form->setFormTitle("");

        $transformed_pessoa_cpf_cnpj = call_user_func(function($value, $object, $row)
        {
            if(strlen($value)==11){
                return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $value);
            } 

            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $value);

        }, $pessoa->cpf_cnpj, $pessoa, null);    

        $transformed_pessoa_telefone = call_user_func(function($value, $object, $row)
        {
            if($value!=NULL && $value!="" && isset($value) && !empty($value)){
                $number="(".substr($value,0,2).") ".substr($value,2,-4)."-".substr($value,-4);
                // primeiro substr pega apenas o DDD e coloca dentro do (), segundo subtr pega os números do 3º até faltar 4, insere o hifem, e o ultimo pega apenas o 4 ultimos digitos

                return $number;
            }
        }, $pessoa->telefone, $pessoa, null);

        /*
            A apresentação do portal vive em app/lib/include/css/curciol-portal.css.
        */

        $label3 = new TLabel("Nome", '', '12px', '', '100%');
        $text3 = new TTextDisplay($pessoa->nome, '', '12px', '');
        $label11 = new TLabel("Documento", '', '12px', '', '100%');
        $text11 = new TTextDisplay($transformed_pessoa_cpf_cnpj, '', '12px', '');
        $label6 = new TLabel("Telefone", '', '12px', '', '100%');
        $text6 = new TTextDisplay($transformed_pessoa_telefone, '', '12px', '');
        $label5 = new TLabel("E-mail", '', '12px', '', '100%');
        $text5 = new TTextDisplay($pessoa->email, '', '12px', '');
        $processo_view = new BPageContainer();

        $processo_view->setSize('100%');
        $processo_view->setAction(new TAction(['ProcessoViewHeaderList', 'onShow'], ['key' => $pessoa->id]));
        $processo_view->setId('b69d541fa523dc');

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $processo_view->add($loadingContainer);


        $keyVoltar = $param['key'] ?? TSession::getValue('keyVoltar');

        $action_voltar = new TAction(['ProcessosFormView', 'onShow']);
        $action_voltar->setParameter('key', $keyVoltar);

        $btn_voltar = new TButton('btn_voltar');
        $btn_voltar->setLabel('Voltar');
        $btn_voltar->setImage('fas:arrow-left');
        $btn_voltar->setAction($action_voltar, 'Voltar');

        $row0 = $this->form->addFields([$btn_voltar]);
        $row0->layout = [' col-sm-12'];

        $row1 = $this->form->addFields([$label3,$text3],[$label11,$text11],[$label6,$text6],[$label5,$text5]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([$processo_view]);
        $row2->layout = [' col-sm-12'];

        $row0->class = trim(($row0->class ?? '') . ' curciol-row-voltar');
        $row1->class = trim(($row1->class ?? '') . ' curciol-faixa-cliente');
        $row2->class = trim(($row2->class ?? '') . ' curciol-area-processos');

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container curciol-portal';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Básico","Processos"]));
        }
        $container->add($this->form);


        TTransaction::close();
        parent::add($container);

    }

    public function onShow($param = null)
    {     

    }

}

