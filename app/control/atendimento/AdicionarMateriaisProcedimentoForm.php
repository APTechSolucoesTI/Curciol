<?php

class AdicionarMateriaisProcedimentoForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_AdicionarMateriaisProcedimentoForm';

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
        $this->form->setFormTitle("Adicionar materias padrões do procedimento");

        $criteria_procedimento = new TCriteria();

        if(!empty("SELECT procedimento_id FROM atendimento_procedimento WHERE atendimento_id = {$param['atendimento_id']}"))
        {
            TSession::setValue(__CLASS__.'load_filter_id', "SELECT procedimento_id FROM atendimento_procedimento WHERE atendimento_id = {$param['atendimento_id']}");
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_id');

        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_procedimento->add(new TFilter('id', 'in', "(SELECT id FROM procedimento WHERE id in ($filterVar))")); 

        $procedimento = new TDBCombo('procedimento', 'escritorio', 'Procedimento', 'id', '{nome}','nome asc' , $criteria_procedimento );

        $procedimento->addValidation("Procedimentos do atendimento", new TRequiredValidator()); 

        $procedimento->setSize('100%');
        $procedimento->enableSearch();


        $row1 = $this->form->addFields([new TLabel("Procedimentos do atendimento:", null, '14px', null, '100%'),$procedimento]);
        $row1->layout = [' col-sm-12'];

        // create the form actions
        $btnSave = $this->form->addAction("Adicionar", new TAction([$this, 'onAdd']), 'far:save #ffffff');
        $this->btnSave = $btnSave;
        $btnSave->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        $btnSave->getAction()->setParameter('atendimento_id', $param['atendimento_id']);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AdicionarMateriaisProcedimentoForm]');
        $style->width = '50% !important';   
        $style->show(true);

    }

    public function onAdd($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');

            $this->form->validate();

            $data = $this->form->getData();

            $procedimento = new Procedimento($data->procedimento);

            $procedimentosMateriais = $procedimento->getProcedimentoMaterials();

            if ($procedimentosMateriais)
            {
                foreach ($procedimentosMateriais as $procedimentoMaterial)
                {
                    $atendimentoMaterial = new AtendimentoMaterial();
                    $atendimentoMaterial->atendimento_id = $param['atendimento_id'];
                    $atendimentoMaterial->material_id = $procedimentoMaterial->material_id;
                    $atendimentoMaterial->quantidade = $procedimentoMaterial->quantidade;
                    $atendimentoMaterial->store();

                    AtendimentoService::subtrairEstoque($atendimentoMaterial, TSession::getValue('userid'));
                }
            }
            else
            {
                throw new Exception('Não foram encontrados materiais padrões');
            }

            $loadPageParam = [];

            if(!empty($param['atendimento_id']))
            {
                $loadPageParam["id"] = $param['atendimento_id'];
            }

            if(!empty($param['atendimento_id']))
            {
                $loadPageParam["key"] = $param['atendimento_id'];
            }

            $loadPageParam["current_tab_abas"] = "2";

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');

            TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam);

            TScript::create("Template.closeRightPanel();");

            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

    } 

}

