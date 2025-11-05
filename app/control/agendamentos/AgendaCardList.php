<?php

class AgendaCardList extends TPage
{
    private $form; // form
    public $cardView; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Agenda';
    private static $primaryKey = 'id';
    private static $formName = 'form_AgendaCardList';
    private $showMethods = ['onReload', 'onSearch'];

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        // define the form title
        $this->form->setFormTitle("Agendas");

        $criteria_escritorio_id = new TCriteria();
        $criteria_profissional_id = new TCriteria();
        $criteria_especialidade_id = new TCriteria();

        $filterVar = Grupo::PROFISSIONAL;
        $criteria_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $escritorio_id = new TDBCombo('escritorio_id', 'escritorio', 'Escritorio', 'id', '{nome}','nome asc' , $criteria_escritorio_id );
        $nome = new TEntry('nome');
        $profissional_id = new TDBUniqueSearch('profissional_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_profissional_id );
        $especialidade_id = new TDBCombo('especialidade_id', 'escritorio', 'Especialidade', 'id', '{descricao}','descricao asc' , $criteria_especialidade_id );

        $nome->forceUpperCase();
        $profissional_id->setMinLength(3);
        $profissional_id->setMask('{nome}');
        $profissional_id->setFilterColumns(["cpf_cnpj","email","nome"]);
        $escritorio_id->enableSearch();
        $especialidade_id->enableSearch();

        $nome->setSize('100%');
        $escritorio_id->setSize('100%');
        $profissional_id->setSize('100%');
        $especialidade_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Escritorio:", null, '14px', null, '100%'),$escritorio_id],[new TLabel("Agenda:", null, '14px', null, '100%'),$nome],[new TLabel("Profissional:", null, '14px', null, '100%'),$profissional_id],[new TLabel("Especialidade:", null, '14px', null, '100%'),$especialidade_id]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $startHidden = true;

        if(TSession::getValue('AgendaCardList_expand_start_hidden') === false)
        {
            $startHidden = false;
        }
        elseif(TSession::getValue('AgendaCardList_expand_start_hidden') === true)
        {
            $startHidden = true; 
        }
        $expandButton = $this->form->addExpandButton("Ver filtros", 'fas:filter #000000', $startHidden);
        $expandButton->addStyleClass('btn-default');
        $expandButton->setAction(new TAction([$this, 'onExpandForm'], ['static'=>1]), "Ver filtros");
        $this->form->addField($expandButton);

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary'); 

        $this->cardView = new TCardView;

        $this->cardView->setContentHeight(170);
        $this->cardView->setTitleTemplate('{nome}');
        $this->cardView->setColorAttribute('cor');
        $this->cardView->setItemTemplate("<div style=\"margin: -15px 0px;min-height: 170px;cursor: pointer;\" generator=\"adianti\" href=\"agenda-{id}\">
<div class=\"agenda\">
  <div><div data-img=\"{profissional->foto_icone}\" style=\"background-image: url({profissional->foto_icone})\" class=\"imagem-profissional\"></div></div>

  <div>
<div>
<i class=\"fa fa-user-md\"></i><span>{profissional->nome_formatado}</span>
</div>
<div> 
<i class=\"far fa-hospital\"></i><span>{escritorio->nome}</span></div>
</div>
 {aceita_agendamento_online} 
</div>
<br/>
<div>
 <small class=\"title-especialidade\">Especialidades</small><br/>
<small>
{profissional->pessoa_especialidade_especialidade_to_string} 
</small>
</div>
</div>");

        $this->cardView->setItemDatabase(self::$database);

        $this->filter_criteria = new TCriteria;

        $filterVar = 'T';
        $this->filter_criteria->add(new TFilter('publica', '=', $filterVar));

        $action_AgendamentoClienteCalendarForm_onShow = new TAction(['AgendamentoClienteCalendarForm', 'onShow'], ['key'=> '{id}']);
        $action_AgendamentoClienteCalendarForm_onShow->setParameter('agenda_id', '{id}');
        $this->cardView->addAction($action_AgendamentoClienteCalendarForm_onShow, "Agendar", 'fas:calendar-day #2196F3', null, "Agendar", false); 

        $panel = new TPanelGroup;
        $panel->add($this->cardView);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);

        $this->cardView->setUseButton();

        parent::add($container);

    }

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        // get the search form data
        $data = $this->form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->escritorio_id) AND ( (is_scalar($data->escritorio_id) AND $data->escritorio_id !== '') OR (is_array($data->escritorio_id) AND (!empty($data->escritorio_id)) )) )
        {

            $filters[] = new TFilter('escritorio_id', '=', $data->escritorio_id);// create the filter 
        }

        if (isset($data->nome) AND ( (is_scalar($data->nome) AND $data->nome !== '') OR (is_array($data->nome) AND (!empty($data->nome)) )) )
        {

            $filters[] = new TFilter('nome', 'like', "%{$data->nome}%");// create the filter 
        }

        if (isset($data->profissional_id) AND ( (is_scalar($data->profissional_id) AND $data->profissional_id !== '') OR (is_array($data->profissional_id) AND (!empty($data->profissional_id)) )) )
        {

            $filters[] = new TFilter('profissional_id', '=', $data->profissional_id);// create the filter 
        }

        $param = array();
        $param['offset']     = 0;
        $param['first_page'] = 1;

        if (!empty($data->especialidade_id))
        {
            $filters[] = new TFilter($data->especialidade_id, 'IN', "(SELECT especialidade_id FROM pessoa_especialidade WHERE pessoa_id = profissional_id)");// create the filter 
        }

        // fill the form with data again
        $this->form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        $this->onReload($param);
    }

    public function onReload($param = NULL)
    {
        try
        {

            // open a transaction with database 'escritorio'
            TTransaction::open(self::$database);

            // creates a repository for Agenda
            $repository = new TRepository(self::$activeRecord);
            $limit = 0;

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'nome';    
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'asc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $filter) 
                {
                    $criteria->add($filter);       
                }
            }

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->cardView->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $object->aceita_agendamento_online = call_user_func(function($value, $object, $row)
                    {
                        if ($value == 'T')
                        {
                            return "<i class='fa fa-video green aceita-atendimento-online' title='Agenda permite atendimento por video chamada'></i>";
                        }

                        return '';
                    }, $object->aceita_agendamento_online, $object, null);

                    $this->cardView->addItem($object);

                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);

            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  $this->showMethods))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }

    public static function onExpandForm($param = null)
    {
        try
        {
            $startHidden = true;

            if(TSession::getValue('AgendaCardList_expand_start_hidden') === false)
            {
                TSession::setValue('AgendaCardList_expand_start_hidden', true);
            }
            elseif(TSession::getValue('AgendaCardList_expand_start_hidden') === true)
            {
                TSession::setValue('AgendaCardList_expand_start_hidden', false);
            }
            else
            {
                TSession::setValue('AgendaCardList_expand_start_hidden', !$startHidden);
            }

        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

}

