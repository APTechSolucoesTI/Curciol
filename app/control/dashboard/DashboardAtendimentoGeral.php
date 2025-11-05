<?php

class DashboardAtendimentoGeral extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_DashboardAtendimentoGeral';

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
        $this->form->setFormTitle("Dashboard de ATENDIMENTOS");

        $criteria_agenda_id = new TCriteria();
        $criteria_qtd_atendimentos = new TCriteria();
        $criteria_total_bruto = new TCriteria();
        $criteria_total_por_agenda = new TCriteria();
        $criteria_total_por_procedimento = new TCriteria();
        $criteria_atendimento_mes = new TCriteria();
        $criteria_total_convenio = new TCriteria();

        $filterVar = TSession::getValue("userunitid");
        $criteria_agenda_id->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_qtd_atendimentos->add(new TFilter('atendimento.agendamento_id', 'in', "(SELECT id FROM agendamento WHERE agenda_id in ((SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}'))))")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_total_bruto->add(new TFilter('atendimento.agendamento_id', 'in', "(SELECT id FROM agendamento WHERE agenda_id in ((SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}'))))")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_total_por_agenda->add(new TFilter('atendimento.agendamento_id', 'in', "(SELECT id FROM agendamento WHERE agenda_id in ((SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}'))))")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_total_por_procedimento->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE agendamento_id in ((SELECT id FROM agendamento WHERE agenda_id in ((SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}'))))))")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_atendimento_mes->add(new TFilter('atendimento.agendamento_id', 'in', "(SELECT id FROM agendamento WHERE agenda_id in ((SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}'))))")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_total_convenio->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE agendamento_id in ((SELECT id FROM agendamento WHERE agenda_id in ((SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}'))))))")); 

        $mes = new TCombo('mes');
        $ano = new TCombo('ano');
        $agenda_id = new TDBCombo('agenda_id', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_agenda_id );
        $button_buscar = new TButton('button_buscar');
        $qtd_atendimentos = new BIndicator('qtd_atendimentos');
        $total_bruto = new BIndicator('total_bruto');
        $total_por_agenda = new BBarChart('total_por_agenda');
        $total_por_procedimento = new BPieChart('total_por_procedimento');
        $atendimento_mes = new BLineChart('atendimento_mes');
        $total_convenio = new BDonutChart('total_convenio');


        $button_buscar->setAction(new TAction(['DashboardAtendimentoGeral', 'onShow']), "Buscar");
        $button_buscar->addStyleClass('btn-default');
        $button_buscar->setImage('fas:search #00BCD4');
        $ano->addItems(TempoService::getAnos());
        $mes->addItems(TempoService::getMeses());

        $mes->setSize('100%');
        $ano->setSize('100%');
        $agenda_id->setSize('100%');

        $qtd_atendimentos->setDatabase('escritorio');
        $qtd_atendimentos->setFieldValue("atendimento.id");
        $qtd_atendimentos->setModel('Atendimento');
        $qtd_atendimentos->setTotal('count');
        $qtd_atendimentos->setColors('#00CEC9', '#ffffff', '#81ECEC', '#ffffff');
        $qtd_atendimentos->setTitle("atendimentos", '#ffffff', '20', '');
        $qtd_atendimentos->setCriteria($criteria_qtd_atendimentos);
        $qtd_atendimentos->setIcon(new TImage('fas:calendar-alt #ffffff'));
        $qtd_atendimentos->setValueSize("30");
        $qtd_atendimentos->setValueColor("#ffffff", 'B');
        $qtd_atendimentos->setSize('100%', 95);
        $qtd_atendimentos->setLayout('horizontal', 'left');

        $total_bruto->setDatabase('escritorio');
        $total_bruto->setFieldValue("atendimento.valor_total");
        $total_bruto->setModel('Atendimento');
        $total_bruto->setTransformerValue(function($value)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $total_bruto->setTotal('sum');
        $total_bruto->setColors('#27AE60', '#FFFFFF', '#2ECC71', '#FFFFFF');
        $total_bruto->setTitle("total bruto", '#FFFFFF', '20', '');
        $total_bruto->setCriteria($criteria_total_bruto);
        $total_bruto->setIcon(new TImage('far:money-bill-alt #FFFFFF'));
        $total_bruto->setValueSize("28");
        $total_bruto->setValueColor("#FFFFFF", 'B');
        $total_bruto->setSize('100%', 95);
        $total_bruto->setLayout('horizontal', 'left');

        $total_por_agenda->setDatabase('escritorio');
        $total_por_agenda->setFieldValue("atendimento.valor_total");
        $total_por_agenda->setFieldGroup(["agenda.nome"]);
        $total_por_agenda->setModel('Atendimento');
        $total_por_agenda->setTitle("Total por agenda");
        $total_por_agenda->setTransformerValue(function($value, $row, $data)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $total_por_agenda->setLayout('horizontal');
        $total_por_agenda->setJoins([
             'agendamento' => ['atendimento.agendamento_id', 'agendamento.id'],
             'agenda' => ['agendamento.agenda_id', 'agenda.id']
        ]);
        $total_por_agenda->setTotal('sum');
        $total_por_agenda->showLegend(false);
        $total_por_agenda->enableOrderByValue('asc');
        $total_por_agenda->setCriteria($criteria_total_por_agenda);
        $total_por_agenda->setLabelValue("Valor Total");
        $total_por_agenda->setSize('100%', 280);
        $total_por_agenda->disableZoom();

        $total_por_procedimento->setDatabase('escritorio');
        $total_por_procedimento->setFieldValue("atendimento_procedimento.valor_total");
        $total_por_procedimento->setFieldGroup("procedimento.nome");
        $total_por_procedimento->setModel('AtendimentoProcedimento');
        $total_por_procedimento->setTitle("Total por pocedimento");
        $total_por_procedimento->setTransformerValue(function($value, $row, $data)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $total_por_procedimento->setJoins([
             'procedimento' => ['atendimento_procedimento.procedimento_id', 'procedimento.id']
        ]);
        $total_por_procedimento->setTotal('sum');
        $total_por_procedimento->showLegend(false);
        $total_por_procedimento->showPercentage();
        $total_por_procedimento->enableOrderByValue('asc');
        $total_por_procedimento->setCriteria($criteria_total_por_procedimento);
        $total_por_procedimento->setSize('100%', 280);
        $total_por_procedimento->disableZoom();

        $atendimento_mes->setDatabase('escritorio');
        $atendimento_mes->setFieldValue("atendimento.id");
        $atendimento_mes->setFieldGroup(["atendimento.ano_mes_inicial"]);
        $atendimento_mes->setModel('Atendimento');
        $atendimento_mes->setTitle("Atendimento mês");
        $atendimento_mes->setTransformerLegend(function($value, $row, $data)
            {
                return TempoService::getMesAno($value);

            });
        $atendimento_mes->setTotal('count');
        $atendimento_mes->showLegend(true);
        $atendimento_mes->setCriteria($criteria_atendimento_mes);
        $atendimento_mes->setLabelValue("Quantidade");
        $atendimento_mes->setSize('100%', 280);
        $atendimento_mes->showArea(true);
        $atendimento_mes->disableZoom();

        $total_convenio->setDatabase('escritorio');
        $total_convenio->setFieldValue("atendimento_procedimento.valor_total");
        $total_convenio->setFieldGroup("parceiro.nome");
        $total_convenio->setModel('AtendimentoProcedimento');
        $total_convenio->setTitle("Total por convênio");
        $total_convenio->setJoins([
             'parceiro' => ['atendimento_procedimento.parceiro_id', 'parceiro.id']
        ]);
        $total_convenio->setTotal('sum');
        $total_convenio->showLegend(true);
        $total_convenio->showPercentage();
        $total_convenio->enableOrderByValue('asc');
        $total_convenio->setCriteria($criteria_total_convenio);
        $total_convenio->setSize('100%', 280);
        $total_convenio->disableZoom();

        $row1 = $this->form->addFields([new TLabel("Mês:", null, '14px', null, '100%'),$mes],[new TLabel("Ano:", null, '14px', null, '100%'),$ano],[new TLabel("Agenda:", null, '14px', null, '100%'),$agenda_id],[new TLabel(" ", null, '14px', null, '100%'),$button_buscar]);
        $row1->layout = ['col-sm-2','col-sm-2','col-sm-3','col-sm-2'];

        $row2 = $this->form->addFields([$qtd_atendimentos],[$total_bruto]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([$total_por_agenda],[$total_por_procedimento]);
        $row3->layout = ['col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([$atendimento_mes],[$total_convenio]);
        $row4->layout = [' col-sm-6',' col-sm-6'];

        $searchData = $this->form->getData();
        $this->form->setData($searchData);

        $filterVar = $searchData->mes;
        if($filterVar)
        {
            $criteria_qtd_atendimentos->add(new TFilter('atendimento.mes_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->ano;
        if($filterVar)
        {
            $criteria_qtd_atendimentos->add(new TFilter('atendimento.ano_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->agenda_id;
        if($filterVar)
        {
            $criteria_qtd_atendimentos->add(new TFilter('atendimento.agendamento_id', 'in', "(SELECT id FROM agendamento WHERE agenda_id = '$filterVar')")); 
        }
        $filterVar = $searchData->mes;
        if($filterVar)
        {
            $criteria_total_bruto->add(new TFilter('atendimento.mes_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->ano;
        if($filterVar)
        {
            $criteria_total_bruto->add(new TFilter('atendimento.ano_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->agenda_id;
        if($filterVar)
        {
            $criteria_total_bruto->add(new TFilter('atendimento.agendamento_id', 'in', "(SELECT id FROM agendamento WHERE agenda_id = '$filterVar')")); 
        }
        $filterVar = $searchData->mes;
        if($filterVar)
        {
            $criteria_total_por_agenda->add(new TFilter('atendimento.mes_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->ano;
        if($filterVar)
        {
            $criteria_total_por_agenda->add(new TFilter('atendimento.ano_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->mes;
        if($filterVar)
        {
            $criteria_total_por_procedimento->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE mes_inicial = '$filterVar')")); 
        }
        $filterVar = $searchData->ano;
        if($filterVar)
        {
            $criteria_total_por_procedimento->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE ano_inicial = '$filterVar')")); 
        }
        $filterVar = $searchData->agenda_id;
        if($filterVar)
        {
            $criteria_total_por_procedimento->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE agendamento_id in (SELECT id FROM agendamento WHERE agenda_id = '$filterVar'))")); 
        }
        $filterVar = $searchData->ano;
        if($filterVar)
        {
            $criteria_atendimento_mes->add(new TFilter('atendimento.ano_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->ano;
        if($filterVar)
        {
            $criteria_atendimento_mes->add(new TFilter('atendimento.mes_inicial', '=', $filterVar)); 
        }
        $filterVar = $searchData->agenda_id;
        if($filterVar)
        {
            $criteria_atendimento_mes->add(new TFilter('atendimento.agendamento_id', 'in', "(SELECT id FROM agendamento WHERE agenda_id = '$filterVar')")); 
        }
        $filterVar = $searchData->ano;
        if($filterVar)
        {
            $criteria_total_convenio->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE ano_inicial = '$filterVar')")); 
        }
        $filterVar = $searchData->mes;
        if($filterVar)
        {
            $criteria_total_convenio->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE mes_inicial = '$filterVar')")); 
        }
        $filterVar = $searchData->agenda_id;
        if($filterVar)
        {
            $criteria_total_convenio->add(new TFilter('atendimento_procedimento.atendimento_id', 'in', "(SELECT id FROM atendimento WHERE agendamento_id in ((SELECT id FROM agendamento WHERE agenda_id in (SELECT id FROM agenda WHERE id = '$filterVar'))))")); 
        }

        BChart::generate($qtd_atendimentos, $total_bruto, $total_por_agenda, $total_por_procedimento, $atendimento_mes, $total_convenio);

        // create the form actions

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Dashboard","Dashboard"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onShow($param = null)
    {               

    } 

}

