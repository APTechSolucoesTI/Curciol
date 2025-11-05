<?php

class AndamentoFormView extends TWindow
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Andamento';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Andamento';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        parent::setSize(0.8, null);
        parent::setTitle("Consulta de andamento");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $andamento = new Andamento($param['key']);
        // define the form title
        $this->form->setFormTitle("Consulta de andamento");

        $transformed_andamento_titulo = call_user_func(function($value, $object, $row)
        {

            return str_replace(";","<br/>",$value);

        }, $andamento->titulo, $andamento, null);    

        $transformed_andamento_texto = call_user_func(function($value, $object, $row)
        {

            return str_replace(";","<br/>",$value);

        }, $andamento->texto, $andamento, null);

        $label2 = new TLabel("Número do processo:", '', '12px', 'B', '100%');
        $text2 = new TTextDisplay($andamento->processo->numero_cnj_numero, '', '12px', '');
        $label3 = new TLabel("Tipo de andamento:", '', '12px', 'B', '100%');
        $text3 = new TTextDisplay($andamento->tipo_andamento->nome, '', '12px', '');
        $label4 = new TLabel("Data do andamento:", '', '12px', 'B', '100%');
        $text4 = new TTextDisplay(TDateTime::convertToMask($andamento->data_andamento, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '12px', '');
        $label5 = new TLabel("Titulo:", '', '12px', 'B', '100%');
        $text5 = new TTextDisplay($transformed_andamento_titulo, '', '12px', '');
        $label6 = new TLabel("Descrição:", '', '12px', 'B', '100%');
        $text6 = new TTextDisplay($transformed_andamento_texto, '', '12px', '');
        $label7 = new TLabel("Criado em:", '', '12px', '', '100%');
        $text7 = new TTextDisplay(TDateTime::convertToMask($andamento->data_criacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '12px', '');
        $label8 = new TLabel("Criado por:", '', '12px', '', '100%');
        $text8 = new TTextDisplay($andamento->criacao_user->name, '', '12px', '');
        $label9 = new TLabel("Atualizado em:", '', '12px', '', '100%');
        $text9 = new TTextDisplay(TDateTime::convertToMask($andamento->data_modificacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '12px', '');
        $label10 = new TLabel("Atualizado por:", '', '12px', '', '100%');
        $text10 = new TTextDisplay($andamento->modificacao_user->name, '', '12px', '');

        $btnAddTarefa = new TActionLink("Adicionar tarefa", new TAction(['AndamentoFormView', 'onAddTarefa'], ['andamento_id'=> $andamento->id, 'key' => $andamento->id]), '', '12px', '', 'fas:plus #4CAF50');
        $row1 = $this->form->addFields([$label2,$text2],[$label3,$text3],[$label4,$text4]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);

        $abas = new BootstrapFormBuilder('abas');
        $this->abas = $abas;
        $abas->setProperty('style', 'border:none; box-shadow:none;');

        $abas->appendPage("Informações");

        $abas->addFields([new THidden('current_tab_abas')]);
        $abas->setTabFunction("$('[name=current_tab_abas]').val($(this).attr('data-current_page'));");

        $row3 = $abas->addFields([$label5,$text5]);
        $row3->layout = [' col-sm-12'];

        $row4 = $abas->addFields([$label6,$text6]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([$abas]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addFields([$label7,$text7],[$label8,$text8],[$label9,$text9],[$label10,$text10]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        if(!empty($param['current_tab_abas']))
        {
            $this->abas->setCurrentPage($param['current_tab_abas']);
        }


        TTransaction::close();
        parent::add($this->form);

    }

    public function onShow($param = null)
    {     

    }

    public  function onAddTarefa($param = null) 
    {
        try 
        {
            TWindow::closeWindow(parent::getId());

            $pageParam['andamento_id'] = $param['key'];
            $pageParam['retorno'] = self::class.','.$param['key'];

            TApplication::loadPage('TarefaForm', 'onShow', $pageParam);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

