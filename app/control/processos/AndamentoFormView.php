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

        $transformed_andamento_id = call_user_func(function($value, $object, $row)
        {
         $id = (int) $value;

            $verificada = strtoupper(trim($object->etapa_verificada ?? 'N'));

            if ($verificada !== 'S') {
                $verificada = 'N';
            }

            $btnId = 'btn_etapa_verificada_' . $id;

            $label = ($verificada == 'S') ? 'Verificada' : 'Não verificada';
            $cor   = ($verificada == 'S') ? '#059669' : '#dc2626';

            $classe = __CLASS__;

            return "
                <button
                    type='button'
                    id='{$btnId}'
                    onclick=\"
                        if (event) {
                            event.preventDefault();
                            event.stopPropagation();
                        }

                        this.disabled = true;

                        __adianti_ajax_exec('class={$classe}&method=onAlternarEtapaVerificada&static=1&id={$id}');

                        return false;
                    \"
                    style='
                        background: {$cor}
                        ; 
                        color: #fff;
                        border: none;
                        border-radius: 4px;
                        padding: 3px 7px;
                        font-size: 11px;
                        font-weight: 700;
                        line-height: 14px;
                        cursor: pointer;
                        white-space: nowrap;
                        box-shadow: none;
                    '
                >
                    {$label}
                </button>
            ";
        }, $andamento->id, $andamento, null);    

        $transformed_andamento_tipo_andamento_criacao_user_system_unit_name = call_user_func(function($value, $object, $row)
        {
           if(empty($object->andamento_id)){
                return '-';
            }

            $processoPublicacao = ProcessoPublicacao::where('andamento_id', '=', $object->andamento_id)->first();

            if(!$processoPublicacao || empty($processoPublicacao->complemento)){
                return '-';
            }

            return $processoPublicacao->complemento;

        }, $andamento->tipo_andamento->criacao_user->system_unit->name, $andamento, null);    

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
        $Etapa = new TLabel("Etapa:", '', '12px', 'B', '100%');
        $etapa_nome = new TTextDisplay($andamento->publicacao_etapa->etapa_nome, '', '12px', '');
        $labelvazia = new TLabel(" ", '', '12px', '', '100%');
        $tbuttonalteretapa = new TButton('tbuttonalteretapa');
        $tbuttonaddcomplemento = new TButton('tbuttonaddcomplemento');
        $labelstatus = new TLabel("Status da etapa:", '', '12px', 'B', '100%');
        $text26 = new TTextDisplay($transformed_andamento_id, '', '12px', '');
        $complementoLabel = new TLabel("Complemento:", '', '12px', 'B', '100%');
        $text39 = new TTextDisplay($transformed_andamento_tipo_andamento_criacao_user_system_unit_name, '', '12px', '');
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

        $tbuttonalteretapa->setAction(new TAction(['AndamentoAlterEtapaForm', 'onEdit'],['key' => 'id']), "Alterar Etapa");
        $tbuttonaddcomplemento->setAction(new TAction(['ProcessoPublicacoesForm', 'onEdit'],['key' => 'id']), "Complemento");

        $tbuttonalteretapa->addStyleClass('btn-default');
        $tbuttonaddcomplemento->addStyleClass('btn-default');

        $tbuttonaddcomplemento->setImage('fas:plus #000000');
        $tbuttonalteretapa->setImage('fas:pencil-alt #000000');

        if ($andamento) {
            if ($andamento->publicacao_etapa_id) {
                $tbuttonalteretapa->setAction(new TAction(['AndamentoAlterEtapaForm', 'onEdit'],['key' => $andamento->id]), "Alterar Etapa");                
            }else {
                TScript::create("$(\"[name='tbuttonalteretapa']\").hide()");
            }

            $comp = ProcessoPublicacoes::where('andamento_id', '=', $andamento->id)->first();
            if ($comp) {
                $tbuttonaddcomplemento->setAction(new TAction(['ProcessoPublicacoesForm', 'onEdit'],['key' => $comp->id]), "Complemento");                
            }else {            
                TScript::create("$(\"[name='tbuttonaddcomplemento']\").hide()");
            }
        }         

        $btnAddTarefa = new TActionLink("Adicionar tarefa", new TAction(['AndamentoFormView', 'onAddTarefa'], ['andamento_id'=> $andamento->id, 'key' => $andamento->id]), '', '12px', '', 'fas:plus #4CAF50');
        $row1 = $this->form->addFields([$label2,$text2],[$label3,$text3],[$label4,$text4]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([$Etapa,$etapa_nome,$labelvazia,$tbuttonalteretapa,$tbuttonaddcomplemento],[$labelstatus,$text26],[$complementoLabel,$text39]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);

        $abas = new BootstrapFormBuilder('abas');
        $this->abas = $abas;
        $abas->setProperty('style', 'border:none; box-shadow:none;');

        $abas->appendPage("Informações");

        $abas->addFields([new THidden('current_tab_abas')]);
        $abas->setTabFunction("$('[name=current_tab_abas]').val($(this).attr('data-current_page'));");

        $row4 = $abas->addFields([$label5,$text5]);
        $row4->layout = [' col-sm-12'];

        $row5 = $abas->addFields([$label6,$text6]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addFields([$abas]);
        $row6->layout = [' col-sm-12'];

        $row7 = $this->form->addFields([$label7,$text7],[$label8,$text8],[$label9,$text9],[$label10,$text10]);
        $row7->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

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

    public static function onAlternarEtapaVerificada($param = null)
    {
        try {
            TTransaction::open(self::$database);

            $id = $param['id'] ?? null;

            if (!$id) {
                throw new Exception('Publicação não encontrada');
            }

            $andamento = new Andamento($id);

            $atual = strtoupper(trim($andamento->etapa_verificada ?? 'N'));
            $novo  = ($atual == 'S') ? 'N' : 'S';

            $andamento->etapa_verificada = $novo;
            $andamento->store();

            TTransaction::close();

            $btnId = 'btn_etapa_verificada_' . $id;

            if ($novo == 'S') {
                TScript::create("
                    var btn = document.getElementById('{$btnId}');
                    if (btn) {
                        btn.innerHTML = 'Verificada';
                        btn.style.background = '#16a34a';
                        btn.disabled = false;
                    }
                ");
            } else {
                TScript::create("
                    var btn = document.getElementById('{$btnId}');
                    if (btn) {
                        btn.innerHTML = 'Não Verificada';
                        btn.style.background = '#dc2626';
                        btn.disabled = false;
                    }
                ");
            }
        } catch (Exception $e) {
            TTransaction::rollback();

            TScript::create("
                var btn = document.getElementById('btn_etapa_verificada_" . ($param['id'] ?? '') . "');
                if (btn) {
                    btn.disabled = false;
                }
            ");

            new TMessage('error', $e->getMessage());
        }
    }
}

