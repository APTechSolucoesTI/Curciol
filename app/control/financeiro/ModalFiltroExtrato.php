<?php

class ModalFiltroExtrato extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalFiltroExtrato';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.40, null);
        parent::setTitle("Parâmetros de extrato");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Parâmetros de extrato");

        $criteria_conta_caixa_id = new TCriteria();

        $conta_caixa_id = new TDBCombo('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}','nome asc' , $criteria_conta_caixa_id );
        $nCompensados = new TCheckButton('nCompensados');
        $periodo = new TCombo('periodo');
        $data_periodo = new TDate('data_periodo');

        $periodo->setChangeAction(new TAction([$this,'onSelectPeriodo']));

        $conta_caixa_id->addValidation("Conta caixa", new TRequiredValidator()); 
        $periodo->addValidation("Período", new TRequiredValidator()); 

        $nCompensados->setValue('F');
        $nCompensados->setUseSwitch(true, 'blue');
        $nCompensados->setIndexValue("T");
        $nCompensados->setInactiveIndexValue("F");
        $periodo->addItems(["7"=>"7 dias","15"=>"15 dias","30"=>" 30 dias","0"=>"Personalizado"]);
        $data_periodo->setMask('dd/mm/yyyy');
        $data_periodo->setDatabaseMask('yyyy-mm-dd');
        $periodo->enableSearch();
        $conta_caixa_id->enableSearch();

        $periodo->setSize('100%');
        $data_periodo->setSize('100%');
        $conta_caixa_id->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Conta caixa:", '#FF0000', '14px', null, '100%'),$conta_caixa_id]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("Visualizar não compensados?", null, '14px', null, '100%'),$nCompensados],[]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Período:", '#FF0000', '14px', null, '100%'),$periodo],[new TLabel("A partir de:", '#FF0000', '14px', null, '100%'),$data_periodo]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        // create the form actions
        $btnAbrirLista = $this->form->addAction("Ver listagem", new TAction([$this, 'onVisualizar']), 'fas:eye #ffffff');
        $this->btnAbrirLista = $btnAbrirLista;
        $btnAbrirLista->addStyleClass('btn-success'); 


        TScript::create("$('label:contains(\"A partir de:\")').hide();");
        TScript::create("$(\"[name='data_periodo']\").closest('.fb-inline-field-container').hide()");

        parent::add($this->form);

    }

    public static function onSelectPeriodo($param = null) 
    {
        try 
        {
            if($param['periodo']==0){

                TScript::create("$('label:contains(\"A partir de:\")').show();");
                TScript::create("$(\"[name='data_periodo']\").closest('.fb-inline-field-container').show()");

            }else{

                TScript::create("$('label:contains(\"A partir de:\")').hide();");
                TScript::create("$(\"[name='data_periodo']\").closest('.fb-inline-field-container').hide()");

            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onVisualizar($param = null) 
    {
        try
        {
            $this->form->validate(); 
            $data = $this->form->getData();
            $pageParam['conta_caixa_id'] = $data->conta_caixa_id;

            $pageParam['visualizarNCompensados'] = $data->nCompensados;

            if($data->periodo==0){
                if(!$data->data_periodo){
                    throw new Exception('O campo A partir de é obrigatório.');
                }
                $hoje = date('Y-m-d');
                $dtPeriodo = $data->data_periodo;
                if($hoje<$dtPeriodo){
                    throw new Exception('O dia deve ser anterior ao dia de hoje.');
                }
                $diferenca = strtotime($hoje) - strtotime($dtPeriodo);
                $dias = floor($diferenca / (60 * 60 * 24)); 
                $pageParam['periodo'] = $dias;
            }else{
                $pageParam['periodo'] = $data->periodo;
            }

            TApplication::loadPage('ExtratoList', 'onShow', $pageParam);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

        TScript::create("$('label:contains(\"A partir de:\")').hide();");
        TScript::create("$(\"[name='data_periodo']\").closest('.fb-inline-field-container').hide()");

    } 

}

