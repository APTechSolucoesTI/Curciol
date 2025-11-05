<?php

class TarefaPrincipalFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Tarefa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Tarefa';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $tarefa = new Tarefa($param['key']);
        // define the form title
        $this->form->setFormTitle("Tarefa Principal");

        $transformed_tarefa_publicacao_id = call_user_func(function($value, $object, $row)
        {

            if($object->processo_id){
                return $object->processo->numero_cnj_numero;

            }
            if($object->publicacao_id){
                if($object->publicacao->processo_id)
                    return $object->publicacao->processo->numero_cnj_numero;
                else if($object->publicacao->numero_unico_processo)
                    return $object->publicacao->numero_unico_processo;

            }
        }, $tarefa->publicacao_id, $tarefa, null);    

        $transformed_tarefa_tarefa_status_nome = call_user_func(function($value, $object, $row)
        {
            $retorno = "<span class='label' style='width:100%;max-width:200px;background-color:{$object->tarefa_status->cor}'> {$value} </span>"; 

            if($object->tarefa_status->fim == 'N'){
                if($object->prazo_entrega >= date('Y-m-d') && $object->prazo_entrega <= date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d'))))){
                    $retorno .= "<br/><span class='label' style='width:100%;max-width:200px;background-color:orange'> Prazo a expirar </span>";
                }elseif ($object->prazo_entrega < date('Y-m-d')) {
                    $retorno .= "<br/><span class='label' style='width:100%;max-width:200px;background-color:red'> Prazo expirado </span>";
                }
            }

            return $retorno;
        }, $tarefa->tarefa_status->nome, $tarefa, null);    

        $transformed_tarefa_prazo_processual = call_user_func(function($value, $object, $row) 
        {
            if($value === true || $value == 't' || $value === 1 || $value == '1' || $value == 's' || $value == 'S' || $value == 'T')
            {
                return 'Sim';
            }
            elseif($value === false || $value == 'f' || $value === 0 || $value == '0' || $value == 'n' || $value == 'N' || $value == 'F')   
            {
                return 'Não';
            }

            return $value;

        }, $tarefa->prazo_processual, $tarefa, null);

        $belement2 = new BElement('hr');
        $label1 = new TLabel("Número do processo:", '', '12px', 'B', '100%');
        $text1 = new TTextDisplay($transformed_tarefa_publicacao_id, '', '12px', '');
        $actVerProcesso = new TActionLink("Ver processo", new TAction(['ProcessoFormView', 'onShow'], ['key'=> $tarefa->publicacao->processo->id]), '', '12px', '', 'fas:search-plus #000000');
        $label488 = new TLabel("Número do processo principal:", '', '12px', 'B', '100%');
        $text1188 = new TTextDisplay($tarefa->publicacao->numero_processo_principal, '', '12px', '');
        $label555 = new TLabel("Jornal:", '', '12px', 'B', '100%');
        $text99 = new TTextDisplay($tarefa->publicacao->jornal->nome, '', '12px', '');
        $label68 = new TLabel("Data do tratamento da publicação:", '', '12px', 'B', '100%');
        $datetimetext4 = new TTextDisplay(TDateTime::convertToMask($tarefa->publicacao->data_tratamento, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '12px', '');
        $label86 = new TLabel("Data da disponibilização da publicação:", '', '12px', 'B', '100%');
        $datetext2 = new TTextDisplay(TDate::convertToMask($tarefa->publicacao->data_disponibilizacao, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '12px', '');
        $actVerPublicacao = new TActionLink("Ver publicação", new TAction(['PublicacaoFormView', 'onShow'], ['key'=> $tarefa->publicacao_id]), '', '12px', '', 'fas:search-plus #000000');
        $label4 = new TLabel("Titulo:", '', '12px', 'B', '100%');
        $text4 = new TTextDisplay($tarefa->titulo, '', '12px', '');
        $label5 = new TLabel("Data da disponibilização:", '', '12px', 'B', '100%');
        $datetext8 = new TTextDisplay(TDate::convertToMask($tarefa->data_disponibilizacao, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '12px', '');
        $label444 = new TLabel("Disponibilizado por:", '', '12px', 'B', '100%');
        $text166 = new TTextDisplay($tarefa->criacao_user->name, '', '12px', '');
        $label9 = new TLabel("Destinatário:", '', '12px', 'B', '100%');
        $text9 = new TTextDisplay($tarefa->usuario_destinatario->name, '', '12px', '');
        $label2 = new TLabel("Status:", '', '12px', 'B', '100%');
        $text2 = new TTextDisplay($transformed_tarefa_tarefa_status_nome, '', '12px', '');
        $label6 = new TLabel("Prazo de validação:", '', '12px', 'B', '100%');
        $datetext6 = new TTextDisplay(TDate::convertToMask($tarefa->prazo_validacao, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '12px', '');
        $label7 = new TLabel("Prazo de entrega:", '', '12px', 'B', '100%');
        $datetext4 = new TTextDisplay(TDate::convertToMask($tarefa->prazo_entrega, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '12px', '');
        $labelprazoprocessual = new TLabel("Prazo processual:", '', '12px', 'B', '100%');
        $text16 = new TTextDisplay($transformed_tarefa_prazo_processual, '', '12px', '');
        $label222 = new TLabel("Data de entrega:", '', '12px', 'B', '100%');
        $datetimetext7 = new TTextDisplay(TDateTime::convertToMask($tarefa->data_entrega, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '12px', '');
        $label8 = new TLabel("Observação:", '', '12px', 'B', '100%');
        $text8 = new TTextDisplay($tarefa->observacao, '', '12px', '');
        $belement4 = new BElement('hr');

        $label488->enableToggleVisibility(false);

        $belement2->width = '100%';
        $belement4->width = '100%';
        $belement2->height = '10px';
        $belement4->height = '10px';
        $actVerProcesso->class = 'btn btn-default';
        $actVerPublicacao->class = 'btn btn-default';
        $belement2->style = "color: red; background-color: red;";
        $belement4->style = "color: red; background-color: red;";

        $this->belement2 = $belement2;
        $this->belement4 = $belement4;

        $actVerPublicacao->name = "actVerPublicacao";
        $actVerProcesso->name = "actVerProcesso";

        if(isset($tarefa->publicacao->processo_id) && !empty($tarefa->publicacao->processo_id)){
            $actVerProcesso = new TActionLink("Ver processo", new TAction(['ProcessoFormView', 'onShow'], ['key'=> $tarefa->publicacao->processo->id]), '', '12px', '', 'fas:search-plus #000000');
        }else if(isset($tarefa->processo_id) && !empty($tarefa->processo_id)){
            $actVerProcesso = new TActionLink("Ver processo", new TAction(['ProcessoFormView', 'onShow'], ['key'=> $tarefa->processo->id]), '', '12px', '', 'fas:search-plus #000000');
        }
        $row1 = $this->form->addFields([$belement2]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([$label1,$text1,$actVerProcesso,$label488,$text1188],[$label555,$text99],[$label68,$datetimetext4,$label86,$datetext2],[$actVerPublicacao]);
        $row2->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row4 = $this->form->addFields([$label4,$text4]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row6 = $this->form->addFields([$label5,$datetext8],[$label444,$text166],[$label9,$text9],[$label2,$text2]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row7 = $this->form->addFields([$label6,$datetext6],[$label7,$datetext4],[$labelprazoprocessual,$text16],[$label222,$datetimetext7]);
        $row7->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row8 = $this->form->addFields([$label8,$text8]);
        $row8->layout = [' col-sm-12'];

        $row9 = $this->form->addFields([$belement4]);
        $row9->layout = [' col-sm-12'];

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Tarefas","Consulta de tarefa principal"]));
        }
        $container->add($this->form);

        if($tarefa->publicacao_id == null || !$tarefa->publicacao_id || $tarefa->publicacao_id=="" || !isset($tarefa->publicacao_id) || empty($tarefa->publicacao_id)){
            TScript::create("$(\"[name='actVerPublicacao']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$('label:contains(\"Jornal:\")').hide();");
            TScript::create("$('label:contains(\"Data do tratamento da publicação:\")').hide();");
            TScript::create("$('label:contains(\"Data da disponibilização da publicação:\")').hide();");
        }

        if(!$tarefa->prazo_validacao){
            TScript::create("$('label:contains(\"Prazo de validação\")').hide();");
        }

        if(!$tarefa->publicacao->processo_id && !$tarefa->processo_id){
            TScript::create("$(\"[name='actVerProcesso']\").closest('.fb-inline-field-container').hide()");
        }

        if(!$tarefa->publicacao->numero_processo_principal){
            TScript::create("$('label:contains(\"Número do processo principal:\")').hide();");
        }

        if($transformed_tarefa_publicacao_id == null){
            TScript::create("$('label:contains(\"Número do processo:\")').hide();");
        }

        TTransaction::close();
        parent::add($container);

    }

    public function onShow($param = null)
    {     

    }

}

