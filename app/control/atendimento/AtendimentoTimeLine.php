<?php

class AtendimentoTimeLine extends TPage
{
    private static $database = 'escritorio';
    private static $activeRecord = 'Atendimento';
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

            if(!empty($param['cliente_id']??''))
        {
            TSession::setValue(__CLASS__.'load_filter_cliente_id', $param['cliente_id']??'');
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_cliente_id');
            $this->timelineCriteria->add(new TFilter('cliente_id', '=', $filterVar));

            $limit = 0;

            $this->timelineCriteria->setProperty('limit', $limit);
            $this->timelineCriteria->setProperty('order', 'dt_inicio desc');

            $objects = Atendimento::getObjects($this->timelineCriteria);

            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $object->dt_inicio = call_user_func(function($value, $object, $row)
                    {
                        if(!empty(trim((string) $value)))
                        {
                            try
                            {
                                $date = new DateTime($value);
                                return $date->format('d/m/Y H:i');
                            }
                            catch (Exception $e)
                            {
                                return $value;
                            }
                        }
                    }, $object->dt_inicio, $object, null);

                    $id = $object->id;
                    $title = "Atendimento #{id}";
                    $htmlTemplate = "Agenda: <b>{agendamento->agenda->nome}</b><br/>
Parceiro: <b>{agendamento->agendamento_procedimento_convenio_to_string} </b><br/>
Profissional: <b>{profissional->nome_formatado}</b><br/>
Escritório: <b>{agendamento->agenda->escritorio->nome}</b><br/>
Tipo: <b>{tipo_atendimento->nome}</b>";
                    $date = $object->dt_inicio;
                    $icon = 'fa:arrow-left bg-green';
                    $position = 'left';

                    if(empty($positionValue[$object->dt_inicio]))
                    {
                        $lastPosition = (empty($lastPosition) || $lastPosition == 'right') ? 'left' : 'right';
                        $bg = ($lastPosition == 'left') ? 'bg-green' : 'bg-blue';

                        $positionValue[$object->dt_inicio]['position'] = $lastPosition;
                        $positionValue[$object->dt_inicio]['bg'] = $bg;
                        $position = $positionValue[$object->dt_inicio]['position'];
                        $icon = "fa:arrow-{$lastPosition} {$bg}";
                    }
                    else
                    {
                        $position = $positionValue[$object->dt_inicio]['position'];
                        $lastPosition = $position;
                        $icon = "fa:arrow-{$lastPosition} {$positionValue[$object->dt_inicio]['bg']}";
                    }

                    $this->timeline->addItem($id, $title, $htmlTemplate, $date, $icon, $position, $object);

                }
            }

            $this->timeline->setUseBothSides();
            $this->timeline->setTimeDisplayMask('dd/mm/yyyy');
            $this->timeline->setFinalIcon( 'fas:flag-checkered #ffffff #528BB7' );

            $action_AtendimentoFormView_onShow = new TAction(['AtendimentoFormView', 'onShow'], ['key'=> '{id}']);
            $action_AtendimentoFormView_onShow->setParameter('id', '{id}');
            $action_AtendimentoFormView_onShow->setProperty('btn-class', 'btn btn-default');
            $this->timeline->addAction($action_AtendimentoFormView_onShow, "Acessar atendimento", 'fas:dot-circle #FF9800','AtendimentoTimeLine::podeExibir'); 

            $container = new TVBox;

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Atendimento","TimeLine de Atendimentos"]));
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

    public static function podeExibir($object)
    {
        try 
        {
            TTransaction::open(self::$database);
            if($object->tipo_atendimento_id == TipoAtendimento::AGENDADO){
                return AtendimentoService::podeManipular($object, TSession::getValue('userid'));
            }elseif ($object->tipo_atendimento_id == TipoAtendimento::AVULSO){
                return true;
            }
            TTransaction::close();
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

