<?php

class PessoaRepresentanteLegalTimeLine extends TPage
{
    private static $database = 'escritorio';
    private static $activeRecord = 'PessoaRepresentantesLegais';
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

            if(!empty($param['key']))
        {
            TSession::setValue(__CLASS__.'load_filter_representante_id', $param['key']);
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_representante_id');
            $this->timelineCriteria->add(new TFilter('representante_id', '=', $filterVar));
            $filterVar = TipoPessoa::JURIDICA;
            $this->timelineCriteria->add(new TFilter('pessoa_juridica_id', 'in', "(SELECT id FROM pessoa WHERE tipo_pessoa_id in (SELECT id FROM tipo_pessoa WHERE id = '{$filterVar}'))"));
            $filterVar = TipoPessoa::FISICA;
            $this->timelineCriteria->add(new TFilter('representante_id', 'in', "(SELECT id FROM pessoa WHERE tipo_pessoa_id in (SELECT id FROM tipo_pessoa WHERE id = '{$filterVar}'))"));

            $limit = 0;

            $this->timelineCriteria->setProperty('limit', $limit);
            $this->timelineCriteria->setProperty('order', 'created_at desc');

            $objects = PessoaRepresentantesLegais::getObjects($this->timelineCriteria);

            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $id = $object->id;
                    $title = "{pessoa_juridica->nome_formatado}";
                    $htmlTemplate = "<B>Definido como </B> {descricao} 
<HR/>
<B>CNPJ: </B> {pessoa_juridica->cpf_cnpj} 
<br/>
<B>Telefone: </B>  {pessoa_juridica->telefone} <br/>
<B>Email: </B>  {pessoa_juridica->email}";
                    $date = $object->created_at;
                    $icon = 'fa:arrow-left bg-green';
                    $position = 'left';

                    if(empty($positionValue[$object->id]))
                    {
                        $lastPosition = (empty($lastPosition) || $lastPosition == 'right') ? 'left' : 'right';
                        $bg = ($lastPosition == 'left') ? 'bg-green' : 'bg-blue';

                        $positionValue[$object->id]['position'] = $lastPosition;
                        $positionValue[$object->id]['bg'] = $bg;
                        $position = $positionValue[$object->id]['position'];
                        $icon = "fa:arrow-{$lastPosition} {$bg}";
                    }
                    else
                    {
                        $position = $positionValue[$object->id]['position'];
                        $lastPosition = $position;
                        $icon = "fa:arrow-{$lastPosition} {$positionValue[$object->id]['bg']}";
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
                $container->add(TBreadCrumb::create(["Pessoas","Linha do tempo de representante legal"]));
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

