<?php

class TarefaKanbanHeader extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_TarefaKanbanHeader';

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
        $this->form->setFormTitle("");

        $criteria_usuario_destinatario_id = new TCriteria();

        $filterVar = "Y";
        $criteria_usuario_destinatario_id->add(new TFilter('active', '=', $filterVar)); 

        $filters = TSession::getValue('TarefaKanbanView_filters');
        $this->form->setData(TSession::getValue('TarefaKanbanView_data'));

        $data_disponibilizacao_de = new TDate('data_disponibilizacao_de');
        $data_disponibilizacao_ate = new TDate('data_disponibilizacao_ate');
        $prazo_validacao_de = new TDate('prazo_validacao_de');
        $label_ate_prazo_validacao = new TLabel("até", null, '12px', null);
        $prazo_validacao_ate = new TDate('prazo_validacao_ate');
        $prazo_entrega_de = new TDate('prazo_entrega_de');
        $prazo_entrega_ate = new TDate('prazo_entrega_ate');
        $data_entrega_de = new TDate('data_entrega_de');
        $label_ate_dt_entrega = new TLabel("até", null, '12px', null);
        $data_entrega_ate = new TDate('data_entrega_ate');
        $arquivado = new TCheckGroup('arquivado');
        $usuario_destinatario_id = new TDBUniqueSearch('usuario_destinatario_id', 'escritorio', 'SystemUsers', 'id', 'name','name asc' , $criteria_usuario_destinatario_id );
        $button_buscar = new TButton('button_buscar');
        $button_limpar_filtros = new TButton('button_limpar_filtros');
        $kanbanPage = new BPageContainer();


        $arquivado->addItems(["N"=>"Não arquivadas","S"=>"Arquivadas"]);
        $arquivado->setLayout('horizontal');
        $arquivado->setUseButton();
        $usuario_destinatario_id->setMinLength(3);
        $usuario_destinatario_id->setValue(TSession::getValue("userid"));
        $button_buscar->addStyleClass('btn-primary');
        $button_limpar_filtros->addStyleClass('btn-default');

        $button_buscar->setImage('fas:search #FFFFFF');
        $button_limpar_filtros->setImage('fas:eraser #F44336');

        $kanbanPage->setId('tarefaKanban');
        $label_ate_dt_entrega->setId("label_ate_dt_entrega");
        $label_ate_prazo_validacao->setId("label_ate_prazo_validacao");

        $kanbanPage->setAction(new TAction(['TarefaKanbanView', 'onShow']));
        $button_buscar->setAction(new TAction([$this, 'onBuscar']), "Buscar");
        $button_limpar_filtros->setAction(new TAction([$this, 'onClearFilters']), "Limpar Filtros");

        $data_entrega_de->setDatabaseMask('yyyy-mm-dd');
        $prazo_entrega_de->setDatabaseMask('yyyy-mm-dd');
        $data_entrega_ate->setDatabaseMask('yyyy-mm-dd');
        $prazo_entrega_ate->setDatabaseMask('yyyy-mm-dd');
        $prazo_validacao_de->setDatabaseMask('yyyy-mm-dd');
        $prazo_validacao_ate->setDatabaseMask('yyyy-mm-dd');
        $data_disponibilizacao_de->setDatabaseMask('yyyy-mm-dd');
        $data_disponibilizacao_ate->setDatabaseMask('yyyy-mm-dd');

        $data_entrega_de->setMask('dd/mm/yyyy');
        $prazo_entrega_de->setMask('dd/mm/yyyy');
        $data_entrega_ate->setMask('dd/mm/yyyy');
        $prazo_entrega_ate->setMask('dd/mm/yyyy');
        $prazo_validacao_de->setMask('dd/mm/yyyy');
        $prazo_validacao_ate->setMask('dd/mm/yyyy');
        $usuario_destinatario_id->setMask('{name}');
        $data_disponibilizacao_de->setMask('dd/mm/yyyy');
        $data_disponibilizacao_ate->setMask('dd/mm/yyyy');

        $arquivado->setSize('100%');
        $kanbanPage->setSize('100%');
        $prazo_entrega_de->setSize('35%');
        $prazo_entrega_ate->setSize('35%');
        $prazo_validacao_de->setSize('35%');
        $prazo_validacao_ate->setSize('35%');
        $data_disponibilizacao_de->setSize('35%');
        $usuario_destinatario_id->setSize('100%');
        $data_disponibilizacao_ate->setSize('35%');
        $data_entrega_de->setSize('calc(50% - 50px)');
        $data_entrega_ate->setSize('calc(50% - 50px)');

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $kanbanPage->add($loadingContainer);
        $kanbanPage->setParameter("filters", $filters ?? null);

        $this->kanbanPage = $kanbanPage;

        $row1 = $this->form->addFields([new TLabel("Data da disponibilização:", null, '12px', null, '100%'),$data_disponibilizacao_de,new TLabel("até", null, '12px', null),$data_disponibilizacao_ate],[new TLabel("Prazo de validação:", null, '12px', null, '100%'),$prazo_validacao_de,$label_ate_prazo_validacao,$prazo_validacao_ate],[new TLabel("Prazo de entrega:", null, '12px', null, '100%'),$prazo_entrega_de,new TLabel("até", null, '12px', null),$prazo_entrega_ate],[new TLabel("Data de entrega:", null, '12px', null, '100%'),$data_entrega_de,$label_ate_dt_entrega,$data_entrega_ate]);
        $row1->layout = ['col-sm-3','col-sm-3','col-sm-3','col-sm-3'];

        $row2 = $this->form->addFields([new TLabel(" ", null, '14px', null, '100%'),$arquivado],[new TLabel("Destinatário:", null, '14px', null, '100%'),$usuario_destinatario_id],[new TLabel(" ", null, '14px', null, '100%'),$button_buscar,$button_limpar_filtros]);
        $row2->layout = ['col-sm-3','col-sm-6','col-sm-3'];

        $row3 = $this->form->addFields([$kanbanPage]);
        $row3->layout = [' col-sm-12'];

        // create the form actions

        $btnVerListagem = $this->form->addHeaderAction("Lista", new TAction(['TarefaList', 'onShow']), 'fas:list #000000');
        $this->btnVerListagem = $btnVerListagem;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Tarefas","Cabeçalho do kanban"]));
        }
        $container->add($this->form);

        TTransaction::open('escritorio');

        $configuracao = TarefaConfiguracao::find(1);
        if($configuracao->tem_dtvalidacao!="S"){
            TScript::create("$('label:contains(\"Prazo de validação:\")').hide();");
            TScript::create("$(\"#label_ate_prazo_validacao\").hide()");

            TScript::create("$(\"[name='prazo_validacao_de']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='prazo_validacao_ate']\").closest('.fb-inline-field-container').hide()");
        }else{
            TScript::create("$('label:contains(\"Prazo de validação:\")').show();");
            TScript::create("$(\"#label_ate_prazo_validacao\").show()");

            TScript::create("$(\"[name='prazo_validacao_de']\").closest('.fb-inline-field-container').show()");
            TScript::create("$(\"[name='prazo_validacao_ate']\").closest('.fb-inline-field-container').show()");
        }

        TTransaction::close();

        if(TSession::getValue('TarefaKanbanView_filters') == null){
            $data = TSession::getValue('TarefaKanbanView_data') ?? new stdClass;
            $filters = TSession::getValue('TarefaKanbanView_filters') ?? [];

            $param['arquivado'] = $data->arquivado = ["N"];
            $param['usuario_destinatario_id'] = $data->usuario_destinatario_id = TSession::getValue('userid');

            $filters[] = new TFilter('arquivado', 'in', ["N"]);
            $filters[] = new TFilter('usuario_destinatario_id', '=', TSession::getValue('userid'));

            TSession::setValue('TarefaKanbanView_filters',$filters);
            TSession::setValue('TarefaKanbanView_data', $data);

            TForm::sendData(self::$formName,$data);
        }

        parent::add($container);

    }

    public  function onBuscar($param = null) 
    {
        try 
        {
            $data = $this->form->getData();
            $filters = [];

            if(!(isset($data->arquivado) AND ((is_scalar($data->arquivado) AND $data->arquivado !== '') OR (is_array($data->arquivado) AND (!empty($data->arquivado)))))){
                $filters[] = new TFilter('arquivado', 'in', ["N"]);
                $data->arquivado = ["N"];
            }else{
                $filters[] = new TFilter('arquivado', 'in', $data->arquivado);
            }

            //Data disponibilização
            if (isset($data->data_disponibilizacao_de) AND ( (is_scalar($data->data_disponibilizacao_de) AND $data->data_disponibilizacao_de !== '') OR (is_array($data->data_disponibilizacao_de) AND (!empty($data->data_disponibilizacao_de)) )) )
            {
                $date = new DateTime($data->data_disponibilizacao_de);
                $data->data_disponibilizacao_de = $date->format('Y-m-d 00:00:00');
                $filters[] = new TFilter('data_disponibilizacao', '>=', $data->data_disponibilizacao_de);
            }
            if (isset($data->data_disponibilizacao_ate) AND ( (is_scalar($data->data_disponibilizacao_ate) AND $data->data_disponibilizacao_ate !== '') OR (is_array($data->data_disponibilizacao_ate) AND (!empty($data->data_disponibilizacao_ate)) )) )
            {
                $date = new DateTime($data->data_disponibilizacao_ate);
                $data->data_disponibilizacao_ate = $date->format('Y-m-d 23:59:59');
                $filters[] = new TFilter('data_disponibilizacao', '<=', $data->data_disponibilizacao_ate);
            }

            //Prazo validação
            if (isset($data->prazo_validacao_de) AND ( (is_scalar($data->prazo_validacao_de) AND $data->prazo_validacao_de !== '') OR (is_array($data->prazo_validacao_de) AND (!empty($data->prazo_validacao_de)) )) )
            {
                $date = new DateTime($data->prazo_validacao_de);
                $data->prazo_validacao_de = $date->format('Y-m-d 00:00:00');
                $filters[] = new TFilter('prazo_validacao', '>=', $data->prazo_validacao_de);
            }
            if (isset($data->prazo_validacao_ate) AND ( (is_scalar($data->prazo_validacao_ate) AND $data->prazo_validacao_ate !== '') OR (is_array($data->prazo_validacao_ate) AND (!empty($data->prazo_validacao_ate)) )) )
            {
                $date = new DateTime($data->prazo_validacao_ate);
                $data->prazo_validacao_ate = $date->format('Y-m-d 23:59:59');
                $filters[] = new TFilter('prazo_validacao', '<=', $data->prazo_validacao_ate);
            }

            //Prazo Entrega
            if (isset($data->prazo_entrega_de) AND ( (is_scalar($data->prazo_entrega_de) AND $data->prazo_entrega_de !== '') OR (is_array($data->prazo_entrega_de) AND (!empty($data->prazo_entrega_de)) )) )
            {
                $date = new DateTime($data->prazo_entrega_de);
                $data->prazo_entrega_de = $date->format('Y-m-d 00:00:00');
                $filters[] = new TFilter('prazo_entrega', '>=', $data->prazo_entrega_de);
            }
            if (isset($data->prazo_entrega_ate) AND ( (is_scalar($data->prazo_entrega_ate) AND $data->prazo_entrega_ate !== '') OR (is_array($data->prazo_entrega_ate) AND (!empty($data->prazo_entrega_ate)) )) )
            {
                $date = new DateTime($data->prazo_entrega_ate);
                $data->prazo_entrega_ate = $date->format('Y-m-d 23:59:59');
                $filters[] = new TFilter('prazo_entrega', '<=', $data->prazo_entrega_ate);
            }

            //Data Entrega
            if (isset($data->data_entrega_de) AND ( (is_scalar($data->data_entrega_de) AND $data->data_entrega_de !== '') OR (is_array($data->data_entrega_de) AND (!empty($data->data_entrega_de)) )) )
            {
                $date = new DateTime($data->data_entrega_de);
                $data->data_entrega_de = $date->format('Y-m-d 00:00:00');
                $filters[] = new TFilter('data_entrega', '>=', $data->data_entrega_de);
            }
            if (isset($data->data_entrega_ate) AND ( (is_scalar($data->data_entrega_ate) AND $data->data_entrega_ate !== '') OR (is_array($data->data_entrega_ate) AND (!empty($data->data_entrega_ate)) )) )
            {
                $date = new DateTime($data->data_entrega_ate);
                $data->data_entrega_ate = $date->format('Y-m-d 23:59:59');
                $filters[] = new TFilter('data_entrega', '<=', $data->data_entrega_ate);
            }

            if (isset($data->usuario_destinatario_id) AND ( (is_scalar($data->usuario_destinatario_id) AND $data->usuario_destinatario_id !== '') OR (is_array($data->usuario_destinatario_id) AND (!empty($data->usuario_destinatario_id)) )) )
            {
                $filters[] = new TFilter('usuario_destinatario_id', '=', $data->usuario_destinatario_id);// create the filter 
            }

            TSession::setValue('TarefaKanbanView_filters',$filters);
            TSession::setValue('TarefaKanbanView_data', $data);

            $this->form->setData($data);

            TTransaction::open('escritorio');

            $configuracao = TarefaConfiguracao::find(1);
            if($configuracao->tem_dtvalidacao!="S"){
                TScript::create("$('label:contains(\"Prazo de validação:\")').hide();");
                TScript::create("$(\"#label_ate_prazo_validacao\").hide()");

                TScript::create("$(\"[name='prazo_validacao_de']\").closest('.fb-inline-field-container').hide()");
                TScript::create("$(\"[name='prazo_validacao_ate']\").closest('.fb-inline-field-container').hide()");
            }else{
                TScript::create("$('label:contains(\"Prazo de validação:\")').show();");
                TScript::create("$(\"#label_ate_prazo_validacao\").show()");

                TScript::create("$(\"[name='prazo_validacao_de']\").closest('.fb-inline-field-container').show()");
                TScript::create("$(\"[name='prazo_validacao_ate']\").closest('.fb-inline-field-container').show()");
            }

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onClearFilters($param = null) 
    {
        try 
        {
            $data = new stdClass;
            $filters = [];

            $data->arquivado = ["N"];
            $data->usuario_destinatario_id = TSession::getValue('userid');

            $filters[] = new TFilter('arquivado', 'in', ["N"]);
            $filters[] = new TFilter('usuario_destinatario_id', '=', TSession::getValue('userid'));

            $data->data_entrega_ate = null;
            $data->data_entrega_de = null;

            TSession::setValue('TarefaKanbanView_filters',$filters);
            TSession::setValue('TarefaKanbanView_data', $data);

            TForm::sendData(self::$formName,$data);

            TApplication::loadPage('TarefaKanbanHeader', 'onShow');

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

