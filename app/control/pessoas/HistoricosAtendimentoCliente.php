<?php

class HistoricosAtendimentoCliente extends TPage
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

        $pessoa = new Pessoa($param['key']);
        // define the form title
        $this->form->setFormTitle("Histórico de atendimentos do cliente");

        $bpagecontainer2 = new BPageContainer();

        $bpagecontainer2->setSize('100%');
        $bpagecontainer2->setAction(new TAction(['AtendimentoTimeLine', 'onShow'], ['cliente_id' => $pessoa->id]));
        $bpagecontainer2->setId('b652eaef03aef2');

        $this->form->appendPage("Listagem");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $this->atendimento_cliente_id_list = new TQuickGrid;
        $this->atendimento_cliente_id_list->style = 'width:100%';
        $this->atendimento_cliente_id_list->disableDefaultClick();

        $action_onShow = new TDataGridAction(array('AtendimentoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search #2196F3');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{id}');
        $this->atendimento_cliente_id_list->addAction($action_onShow);

        $column_dt_inicio_transformed = $this->atendimento_cliente_id_list->addQuickColumn("Data", 'dt_inicio', 'left');
        $column_dt_inicio_transformed1 = $this->atendimento_cliente_id_list->addQuickColumn("Início", 'dt_inicio', 'left');
        $column_profissional_nome = $this->atendimento_cliente_id_list->addQuickColumn("Profissional", 'profissional->nome', 'left' , '40%');
        $column_agendamento_especialidade_descricao = $this->atendimento_cliente_id_list->addQuickColumn("Especialidade", 'agendamento->especialidade->descricao', 'left' , '30%');

        $column_dt_inicio_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_dt_inicio_transformed1->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $date = new DateTime($value);
            return $date->format('H:i');

        });

        $this->atendimento_cliente_id_list->enablePopover("Histórico", " {atendimento_historico_historico_to_string} ");

        $this->atendimento_cliente_id_list->createModel();

        $criteria_atendimento_cliente_id = new TCriteria();
        $criteria_atendimento_cliente_id->add(new TFilter('cliente_id', '=', $pessoa->id));

        $criteria_atendimento_cliente_id->setProperty('order', 'dt_inicio desc');

        $atendimento_cliente_id_items = Atendimento::getObjects($criteria_atendimento_cliente_id);

        $this->atendimento_cliente_id_list->addItems($atendimento_cliente_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->atendimento_cliente_id_list));

        $this->form->addContent([$panel]);

        $this->form->appendPage("TimeLine");
        $row1 = $this->form->addFields([$bpagecontainer2]);
        $row1->layout = [' col-sm-12'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoAction = new TAction(['HistoricosAtendimentoCliente', 'addAtendimentoAvulso'],['cliente_id'=>$pessoa->id]);
        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel = new TLabel("Adicionar");

        $btnHistoricosAtendimentoClienteAddAtendimentoAvulso = $this->form->addHeaderAction($btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel, $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoAction, 'fas:plus #4CAF50'); 
        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel->setFontSize('12px'); 
        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel->setFontColor('#333'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Pessoas","Histórico de atendimentos do cliente"]));
        }
        $container->add($this->form);

        TTransaction::close();
        parent::add($container);

    }

    public function onShow($param = null)
    {     

    }

    public static function addAtendimentoAvulso($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $atendimento = new Atendimento(); 

            $login = Pessoa::where('system_users_id', '=', TSession::getValue('userid'))->first();
            if($login){
                $atendimento->profissional_id = $login->id;
            }

            if(isset($param['cliente_id'])){
                if(!PessoaEndereco::where('pessoa_id','=',(int)$param['cliente_id'])->where('principal','=','S')->first()){
                    throw new Exception("Cadastre um endereço principal para criar um atendimento.");
                }
                $atendimento->cliente_id = $param['cliente_id'];
            }
            $atendimento->tipo_atendimento_id = TipoAtendimento::AVULSO;
            $atendimento->dt_inicio = $atendimento->dt_final = date('Y-m-d H:i:s');
            $atendimento->criacao_user_id = TSession::getValue('userid');

            $atendimento->store();

            TScript::create("$(\"[page_name='ClienteForm']\").remove()");

            $pageParam = ['key' => $atendimento->id];
            TApplication::loadPage('AtendimentoFormView', 'onShow', $pageParam);

            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

