<?php

class MessageTimeLine extends TPage
{
    private static $database = 'escritorio';
    private static $activeRecord = 'Mensagem';
    private static $primaryKey = 'id';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null )
    {
        try
        {
            parent::__construct();

            TTransaction::open(self::$database);

            if(!empty($param['target_container']))
            {
                $this->adianti_target_container = $param['target_container'];
            }

            $this->timeline = new TTimeline;
            $this->timeline->setItemDatabase(self::$database);
            $this->timelineCriteria = new TCriteria;

            if(!empty($param['agendamento_id']))
        {
            TSession::setValue(__CLASS__.'load_filter_agendamento_id', $param['agendamento_id']);
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_agendamento_id');
            $this->timelineCriteria->add(new TFilter('agendamento_id', '=', $filterVar));

            $limit = 0;

            $this->timelineCriteria->setProperty('limit', $limit);
            $this->timelineCriteria->setProperty('order', 'id asc');

            $objects = Mensagem::getObjects($this->timelineCriteria);

            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $id = $object->id;
                    $title = "{tipo_mensagem}";
                    $htmlTemplate = " {template_formatado}<br />
 {acoes_formatada} <br />
<br />
Enviado por:  {system_user->name} ";
                    $date = $object->enviado_em;
                    $icon = 'fa:arrow-left bg-green';
                    $position = 'left';

                    if(empty($positionValue[$object->enviado_em]))
                    {
                        $lastPosition = (empty($lastPosition) || $lastPosition == 'right') ? 'left' : 'right';
                        $bg = ($lastPosition == 'left') ? 'bg-green' : 'bg-blue';

                        $positionValue[$object->enviado_em]['position'] = $lastPosition;
                        $positionValue[$object->enviado_em]['bg'] = $bg;
                        $position = $positionValue[$object->enviado_em]['position'];
                        $icon = "fa:arrow-{$lastPosition} {$bg}";
                    }
                    else
                    {
                        $position = $positionValue[$object->enviado_em]['position'];
                        $lastPosition = $position;
                        $icon = "fa:arrow-{$lastPosition} {$positionValue[$object->enviado_em]['bg']}";
                    }

                    $this->timeline->addItem($id, $title, $htmlTemplate, $date, $icon, $position, $object);

                }
            }

            $this->timeline->setUseBothSides();
            $this->timeline->setTimeDisplayMask('dd/mm/yyyy');
            $this->timeline->setFinalIcon( 'fas:flag-checkered #ffffff #de1414' );

            $container = new TVBox;

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Agendamentos","Mensagens"]));
            }
            $container->add($this->timeline);

            TTransaction::close();

            parent::add($container);
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {

    } 

}

