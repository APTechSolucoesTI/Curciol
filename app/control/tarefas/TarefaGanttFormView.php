<?php

class TarefaGanttFormView extends TPage
{
    private $gantt;
    private $loaded;
    private static $database = 'escritorio';

    function __construct($param = [])
    {
        parent::__construct();

        $this->gantt = new TGantt(TGantt::MODE_DAYS, 'sm');
        $this->gantt->enableStripedMonths();
        $this->gantt->enableStripedRows();
        $this->gantt->setReloadAction(new TAction([$this, 'onReload']));
        $this->gantt->setStartDate($param['start'] ?? date('Y-m-01'));
        $this->gantt->setInterval('1 months');
        $this->gantt->setTitle("Tarefas");
        $this->gantt->enableFullHours();
        $this->gantt->enableViewModeButton(true, true, "Visão", 'fas:eye #333333');
        $this->gantt->enableSizeModeButton(true, true, "Zoom", 'fas:search-plus #333333');

        if (!empty(TSession::getValue(__CLASS__.'_gantt_view_mode')))
        {
            $this->gantt->setViewMode(TSession::getValue(__CLASS__.'_gantt_view_mode'));
        }

        if (!empty(TSession::getValue('gantt_size_mode')))
        {
            $this->gantt->setSizeMode(TSession::getValue('gantt_size_mode'));
        }

        $this->criteria_events = new TCriteria();

        $criteria = new TCriteria();

        $criteria->setProperty('order', 'kanban asc');

        TTransaction::open('escritorio');

        $categories = TarefaStatus::getObjects($criteria);

        if($categories)
        {
            foreach($categories as $category)
            {

                $this->gantt->addRow($category->id, $category->render("{nome}"));

            }
        }

        TTransaction::close();

        parent::add($this->gantt);
    }

    public function onReload($param = [])
    {
        try
        {
            if (! empty($param['start_time']))
            {
                $this->gantt->setStartDate($param['start_time']);
            }

            if (!empty($param['view_mode']))
            {
                TSession::setValue(__CLASS__.'_gantt_view_mode', $param['view_mode']);
                $this->gantt->setViewMode($param['view_mode']);
            }

            if (!empty($param['size_mode']))
            {
                TSession::setValue(__CLASS__.'_gantt_size_mode', $param['size_mode']);
                $this->gantt->setSizeMode($param['size_mode']);
            }

            $this->gantt->clearEvents();

            TTransaction::open('escritorio');

            $criteria = clone $this->criteria_events;

            $criteria->add(new TFilter('data_disponibilizacao', '<=', $this->gantt->getEndDate()));
            $criteria->add(new TFilter('prazo_entrega', '>=', $this->gantt->getStartDate()));

            $events = Tarefa::getObjects($criteria);

            if ($events)
            {
                foreach ($events as $event)
                {
                    $percent = null;
                    $color = $event->tarefa_status->cor;
                    $title = $event->render("{titulo}");
                    $popover_content = $event->render("aa");
                    $title = TGantt::renderPopover($title, "", $popover_content);

                    $data_disponibilizacao = new DateTime($event->data_disponibilizacao);
                    $data_disponibilizacao = $data_disponibilizacao->format('d/m/Y H:i');

                    $data_validacao = new DateTime($event->data_validacao);
                    $data_validacao = $data_validacao->format('d/m/Y H:i');

                    $data_entrega = new DateTime($event->data_disponibilizacao);
                    $data_entrega = $data_entrega->format('d/m/Y H:i');

                    $popover_content = $event->render("
                    <b>Disponibilizado em:</b> $data_disponibilizacao <br/>
                         <b>Destinado a:</b> {usuario_destinatario->name} <br/>
                         <b>Validação em:</b> $data_validacao <br/>
                         <b>Entrega em:</b> $data_entrega
                    ");
                    $title = TGantt::renderPopover($title, "", $popover_content);

                    $this->gantt->addEvent($event->id, $event->tarefa_status_id, $title, $event->data_disponibilizacao, $event->prazo_entrega, $color, $percent);

                }
            }

            TTransaction::close();

            $this->loaded = TRUE;
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    // show gantt
    public function show()
    {
        // check if the gantt is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) || $_GET['method'] !== 'onReload'))
        {
            $this->onReload( func_get_arg(0) );
        }

        parent::show();
    }

    public function onShow($param = null)
    {

    }

}

