<?php

class TarefaDashboard extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_TarefaDashboard';

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
        $this->form->setFormTitle("Dashboard de tarefa");

        $criteria_pie = new TCriteria();
        $criteria_bar = new TCriteria();

        $filterVar = NULL;
        $criteria_pie->add(new TFilter('tarefa.data_entrega', 'is', $filterVar)); 
        $filterVar = NULL;
        $criteria_bar->add(new TFilter('tarefa.data_entrega', 'is', $filterVar)); 

        $pie = new BPieChart('pie');
        $bar = new BBarChart('bar');


        $pie->setDatabase('escritorio');
        $pie->setFieldValue("tarefa.tarefa_status_id");
        $pie->setFieldColor("tarefa_status.cor");
        $pie->setFieldGroup("system_users.name");
        $pie->setModel('Tarefa');
        $pie->setTitle("Título do gráfico");
        $pie->setJoins([
             'system_users' => ['tarefa.usuario_destinatario_id', 'system_users.id'],
             'tarefa_status' => ['tarefa.tarefa_status_id', 'tarefa_status.id']
        ]);
        $pie->setTotal('sum');
        $pie->showLegend(true);
        $pie->enableOrderByValue('asc');
        $pie->setCriteria($criteria_pie);
        $pie->setSize('100%', 280);
        $pie->disableZoom();

        $bar->setDatabase('escritorio');
        $bar->setFieldValue("tarefa.id");
        $bar->setFieldColor("tarefa_status.cor");
        $bar->setFieldGroup(["system_users.name", "tarefa_status.nome"]);
        $bar->setModel('Tarefa');
        $bar->setTitle("Título do gráfico");
        $bar->setLayout('vertical');
        $bar->setJoins([
             'system_users' => ['tarefa.usuario_destinatario_id', 'system_users.id'],
             'tarefa_status' => ['tarefa.tarefa_status_id', 'tarefa_status.id']
        ]);
        $bar->setTotal('count');
        $bar->showLegend(true);
        $bar->enableOrderByValue('asc');
        $bar->setCriteria($criteria_bar);
        $bar->setSize('100%', 280);
        $bar->disableZoom();

        $row1 = $this->form->addFields([$pie]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([$bar]);
        $row2->layout = [' col-sm-12'];

        $searchData = $this->form->getData();
        $this->form->setData($searchData);

        BChart::generate($pie, $bar);

        // create the form actions

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Tarefas","Dashboard de tarefa"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onShow($param = null)
    {               

    } 

}

