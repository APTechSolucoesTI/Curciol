<?php

class ModalSelecionarContaCaixa extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalSelecionarContaCaixa';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.40, null);
        parent::setTitle("Selecionar Conta Caixa");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Selecionar Conta Caixa");

        $criteria_conta_caixa_id = new TCriteria();

        $conta_caixa_id = new BDBSelectCheck('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}','nome asc' , $criteria_conta_caixa_id );
        $data_periodo = new BDateRange('data_periodo', 'data_periodo_final');
        $periodo = new TCombo('periodo');

        $periodo->setChangeAction(new TAction([$this,'onSelectPeriodo']));

        $conta_caixa_id->addValidation("Conta caixa", new TRequiredValidator()); 

        $data_periodo->setMask('dd/mm/yyyy');
        $data_periodo->setDatabaseMask('yyyy-mm-dd');
        $periodo->addItems(["7"=>"7 dias","15"=>"15 dias","30"=>" 30 dias"]);
        $periodo->enableSearch();
        $periodo->setSize('100%');
        $data_periodo->setSize('98%');
        $conta_caixa_id->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Conta caixa:", '#FF0000', '14px', null, '100%'),$conta_caixa_id]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("Período Personalizado:", '#2E2E2E', '14px', null, '100%'),$data_periodo]);
        $row2->layout = [' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Período Fixo:", '#2E2E2E', '14px', null, '100%'),$periodo]);
        $row3->layout = ['col-sm-6'];

        // create the form actions
        $btnVisualizar = $this->form->addAction("Visualizar extrato", new TAction([$this, 'onVisualizar']), 'fas:eye #ffffff');
        $this->btnVisualizar = $btnVisualizar;
        $btnVisualizar->addStyleClass('btn-info'); 

        parent::add($this->form);

    }

    public static function onSelectPeriodo($param = null) 
    {

    }

    public function onVisualizar($param = null) 
    {
        try
        {
            $this->form->validate(); 
            $data = $this->form->getData();

            $contas = $data->conta_caixa_id;

            if(!is_array($contas)){
                $contas = preg_split('/[^0-9]+/', (string) $contas, -1, PREG_SPLIT_NO_EMPTY);
            }

            $contasSelecionadas = [];

            foreach($contas as $contaId){
                $contaId = (int) $contaId;

                if($contaId > 0){
                    $contasSelecionadas[$contaId] = $contaId;
                }
            }

            $contasSelecionadas = array_values($contasSelecionadas);

            if(!$contasSelecionadas){
                throw new Exception('Selecione pelo menos uma conta caixa.');
            }

            $hoje = date('Y-m-d');

            $temDataInicial = !empty($data->data_periodo);
            $temDataFinal = !empty($data->data_periodo_final);
            $temPeriodoPersonalizado = $temDataInicial || $temDataFinal;
            $temPeriodoFixo = isset($data->periodo) && (string) $data->periodo !== '';

            if($temPeriodoPersonalizado && $temPeriodoFixo){
                throw new Exception('Escolha somente um período: personalizado ou fixo.');
            }

            if(!$temPeriodoPersonalizado && !$temPeriodoFixo){
                throw new Exception('Informe o período personalizado ou selecione um período fixo.');
            }

            if($temPeriodoPersonalizado){
                if(!$temDataInicial || !$temDataFinal){
                    throw new Exception('Informe a data inicial e a data final do período personalizado.');
                }

                $dataInicial = $data->data_periodo;
                $dataFinal = $data->data_periodo_final;

                if($dataInicial > $dataFinal){
                    throw new Exception('A data inicial deve ser menor ou igual à data final.');
                }

            }else{
                $dias = (int) $data->periodo;

                if(!in_array($dias, [7, 15, 30])){
                    throw new Exception('O período fixo selecionado é inválido.');
                }

                $dataFinal = $hoje;
                $dataInicial = date('Y-m-d', strtotime("-{$dias} days", strtotime($dataFinal)));
            }

            $pageParam['contas'] = implode(',', $contasSelecionadas);
            $pageParam['data_inicial'] = $dataInicial;
            $pageParam['data_final'] = $dataFinal;

            TApplication::loadPage('ContaCaixaFormView', 'onShow', $pageParam);
            TScript::create("$(\"[page_name='ModalSelecionarContaCaixa']\").remove()");
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

