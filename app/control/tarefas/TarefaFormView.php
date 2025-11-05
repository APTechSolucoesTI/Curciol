<?php

class TarefaFormView extends TPage
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
        $this->form->setFormTitle("Consulta de tarefa");

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

        $label1 = new TLabel("Número do processo:", '', '12px', 'B', '100%');
        $text1 = new TTextDisplay($tarefa->numero_processo, '', '12px', '');
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
        $btnAlterarStatus = new TActionLink("Alterar status", new TAction(['AlterarStatusTarefaModal', 'onShow'], ['key'=> $tarefa->id]), '', '12px', '', 'fas:exchange-alt #000000');
        $btnArquivar = new TActionLink("Arquivar", new TAction(['TarefaFormView', 'onArquivar'], ['key'=> $tarefa->id]), '', '12px', '', 'fas:archive #000000');
        $btnDesarquivar = new TActionLink("Desarquivar", new TAction(['TarefaFormView', 'onDesarquivar'], ['key'=> $tarefa->id]), '', '12px', '', 'fas:backspace #000000');
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
        $btnAddHoras = new TActionLink("Adicionar horas", new TAction(['TarefaHorasTrabalhadasForm', 'onShow'], ['tarefa_id'=> $tarefa->id]), '', '12px', '', 'fas:plus #4CAF50');
        $action2 = new TActionLink("Adicionar comentário", new TAction(['TarefaComentarioForm', 'onShow'], ['tarefa_id'=> $tarefa->id]), '', '12px', '', 'fas:plus #4CAF50');
        $labelNaoSubtarefa = new TLabel("Essa não é uma subtarefa.", '#FF0000', '12px', '');
        $bpagecontainer4 = new BPageContainer();
        $label10 = new TLabel("Criado em:", '', '10px', 'B', '100%');
        $text10 = new TTextDisplay(TDateTime::convertToMask($tarefa->data_criacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '10px', '');
        $label11 = new TLabel("Criado por:", '', '10px', 'B', '100%');
        $text11 = new TTextDisplay($tarefa->criacao_user->name, '', '10px', '');
        $label12 = new TLabel("Atualizado em:", '', '10px', 'B', '100%');
        $text12 = new TTextDisplay(TDateTime::convertToMask($tarefa->data_modificacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '10px', '');
        $label13 = new TLabel("Atualizado por:", '', '10px', 'B', '100%');
        $text13 = new TTextDisplay($tarefa->modificacao_user->name, '', '10px', '');

        $label488->enableToggleVisibility(false);
        $bpagecontainer4->setSize('100%');
        $bpagecontainer4->setAction(new TAction(['TarefaPrincipalFormView', 'onShow'], ['tarefa_id' => $tarefa->id]));
        $bpagecontainer4->setId('b66daec3a272fc');

        $action2->class = 'btn btn-default';
        $btnArquivar->class = 'btn btn-default';
        $btnAddHoras->class = 'btn btn-default';
        $actVerProcesso->class = 'btn btn-default';
        $btnDesarquivar->class = 'btn btn-default';
        $actVerPublicacao->class = 'btn btn-default';
        $btnAlterarStatus->class = 'btn btn-default';

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $bpagecontainer4->add($loadingContainer);


        $btnAlterarStatus = new TActionLink("Alterar status", new TAction(['AlterarStatusTarefaModal', 'onShow'], ['key'=> $tarefa->id, 'retorno'=>self::class.','.$tarefa->id, 'origem'=>$param['origem'] ?? null]), '', '12px', '', 'fas:exchange-alt #000000');

        $labelNaoSubtarefa->name = 'labelNaoSubtarefa';
        $bpagecontainer4->name = 'bpagecontainer4';

        $principal = TarefaVinculo::where('subtarefa_id','=',$tarefa->id)->first();
        if($principal){
            $bpagecontainer4->setSize('100%');
            $bpagecontainer4->setAction(new TAction(['TarefaPrincipalFormView', 'onShow'], ['key' => $principal->tarefa_id]));
            $bpagecontainer4->setId('b66daec3a272fc');
            $bpagecontainer4->add($loadingContainer);
            TScript::create("$(\"[name='labelNaoSubtarefa']\").hide()");
        }else{
            $bpagecontainer4->hide();
        }

        $btnArquivar->name = "btnArquivar";
        $btnDesarquivar->name = "btnDesarquivar";
        $actVerPublicacao->name = "actVerPublicacao";
        $actVerProcesso->name = "actVerProcesso";
        $btnAlterarStatus->name = 'btnAlterarStatus';

        $processo = null;
        if ($tarefa->processo_id) {
            $processo = Processo::find($tarefa->processo_id);
        } elseif ($tarefa->publicacao_id) {
            $publicacao = Publicacao::find($tarefa->publicacao_id);
            if($publicacao->processo_id)
                $processo = Processo::find($publicacao->processo_id);
        }
        if($processo != null){
            $actVerProcesso = new TActionLink("Ver processo", new TAction(['ProcessoFormView', 'onShow'], ['key' => $processo->id]), '', '12px', '', 'fas:search-plus #000000');
        }
        /*if(isset($tarefa->publicacao->processo_id) && !empty($tarefa->publicacao->processo_id)){
            $actVerProcesso = new TActionLink("Ver processo", new TAction(['ProcessoFormView', 'onShow'], ['key'=> $tarefa->publicacao->processo->id]), '', '12px', '', 'fas:search-plus #000000');
        }else if(isset($tarefa->processo_id) && !empty($tarefa->processo_id)){
            $actVerProcesso = new TActionLink("Ver processo", new TAction(['ProcessoFormView', 'onShow'], ['key'=> $tarefa->processo->id]), '', '12px', '', 'fas:search-plus #000000');
        }*/

        if($tarefa->tarefa_status_id == (TarefaConfiguracao::find(1))->status_cancelado_id){
            TScript::create("$(\"[name='btnAlterarStatus']\").hide()");
            TScript::create("$(\"[name='btnArquivar']\").hide()");
            TScript::create("$(\"[name='btnDesarquivar']\").hide()");
        }

        $row1 = $this->form->addFields([$label1,$text1,$actVerProcesso,$label488,$text1188],[$label555,$text99],[$label68,$datetimetext4,$label86,$datetext2],[$actVerPublicacao]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row3 = $this->form->addFields([$label4,$text4]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row5 = $this->form->addFields([$label5,$datetext8],[$label444,$text166],[$label9,$text9],[$label2,$text2,$btnAlterarStatus,$btnArquivar,$btnDesarquivar]);
        $row5->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row6 = $this->form->addFields([$label6,$datetext6],[$label7,$datetext4],[$labelprazoprocessual,$text16],[$label222,$datetimetext7]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row7 = $this->form->addFields([$label8,$text8]);
        $row7->layout = [' col-sm-12'];

        $row8 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);

        $tab_65e1e464a1b00 = new BootstrapFormBuilder('tab_65e1e464a1b00');
        $this->tab_65e1e464a1b00 = $tab_65e1e464a1b00;
        $tab_65e1e464a1b00->setProperty('style', 'border:none; box-shadow:none;');

        $tab_65e1e464a1b00->appendPage("Movimentações");

        $tab_65e1e464a1b00->addFields([new THidden('current_tab_tab_65e1e464a1b00')]);
        $tab_65e1e464a1b00->setTabFunction("$('[name=current_tab_tab_65e1e464a1b00]').val($(this).attr('data-current_page'));");

        $this->tarefa_movimentacao_tarefa_id_list = new TQuickGrid;
        $this->tarefa_movimentacao_tarefa_id_list->style = 'width:100%';
        $this->tarefa_movimentacao_tarefa_id_list->disableDefaultClick();

        $column_data_movimentacao_transformed = $this->tarefa_movimentacao_tarefa_id_list->addQuickColumn("Data da movimentação", 'data_movimentacao', 'left');
        $column_descricao = $this->tarefa_movimentacao_tarefa_id_list->addQuickColumn("Descrição", 'descricao', 'left');
        $column_criacao_user_name = $this->tarefa_movimentacao_tarefa_id_list->addQuickColumn("Autor", 'criacao_user->name', 'left');

        $column_data_movimentacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });

        $this->tarefa_movimentacao_tarefa_id_list->createModel();

        $criteria_tarefa_movimentacao_tarefa_id = new TCriteria();
        $criteria_tarefa_movimentacao_tarefa_id->add(new TFilter('tarefa_id', '=', $tarefa->id));

        $criteria_tarefa_movimentacao_tarefa_id->setProperty('order', 'data_movimentacao desc');

        $tarefa_movimentacao_tarefa_id_items = TarefaMovimentacao::getObjects($criteria_tarefa_movimentacao_tarefa_id);

        $this->tarefa_movimentacao_tarefa_id_list->addItems($tarefa_movimentacao_tarefa_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->tarefa_movimentacao_tarefa_id_list));

        $tab_65e1e464a1b00->addContent([$panel]);

        $tab_65e1e464a1b00->appendPage("Horas trabalhadas");
        $row9 = $tab_65e1e464a1b00->addFields([$btnAddHoras],[],[]);
        $row9->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $this->tarefa_horas_trabalhadas_tarefa_id_list = new TQuickGrid;
        $this->tarefa_horas_trabalhadas_tarefa_id_list->style = 'width:100%';
        $this->tarefa_horas_trabalhadas_tarefa_id_list->disableDefaultClick();

        $column_data_inicio_transformed = $this->tarefa_horas_trabalhadas_tarefa_id_list->addQuickColumn("Início", 'data_inicio', 'left');
        $column_data_fim_transformed = $this->tarefa_horas_trabalhadas_tarefa_id_list->addQuickColumn("Fim", 'data_fim', 'left');
        $column_observacao = $this->tarefa_horas_trabalhadas_tarefa_id_list->addQuickColumn("Observação", 'observacao', 'left');
        $column_criacao_user_name1 = $this->tarefa_horas_trabalhadas_tarefa_id_list->addQuickColumn("Usuário", 'criacao_user->name', 'left');

        $column_data_inicio_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });

        $column_data_fim_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });

        $this->tarefa_horas_trabalhadas_tarefa_id_list->createModel();

        $criteria_tarefa_horas_trabalhadas_tarefa_id = new TCriteria();
        $criteria_tarefa_horas_trabalhadas_tarefa_id->add(new TFilter('tarefa_id', '=', $tarefa->id));

        $criteria_tarefa_horas_trabalhadas_tarefa_id->setProperty('order', 'data_fim desc');

        $tarefa_horas_trabalhadas_tarefa_id_items = TarefaHorasTrabalhadas::getObjects($criteria_tarefa_horas_trabalhadas_tarefa_id);

        $this->tarefa_horas_trabalhadas_tarefa_id_list->addItems($tarefa_horas_trabalhadas_tarefa_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->tarefa_horas_trabalhadas_tarefa_id_list));

        $tab_65e1e464a1b00->addContent([$panel]);

        $tab_65e1e464a1b00->appendPage("Subtarefas");

        $this->tarefa_vinculo_subtarefa_id_list = new TQuickGrid;
        $this->tarefa_vinculo_subtarefa_id_list->style = 'width:100%';
        $this->tarefa_vinculo_subtarefa_id_list->disableDefaultClick();

        $action_onShow = new TDataGridAction(array('TarefaFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{tarefa_id}');
        $this->tarefa_vinculo_subtarefa_id_list->addAction($action_onShow);

        $column_tarefa_titulo = $this->tarefa_vinculo_subtarefa_id_list->addQuickColumn("Tarefa principal", 'tarefa->titulo', 'left');
        $column_tarefa_tarefa_status_nome_transformed = $this->tarefa_vinculo_subtarefa_id_list->addQuickColumn("Status", 'tarefa->tarefa_status->nome', 'left');

        $column_tarefa_tarefa_status_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return "<span class='label' style='background-color:{$object->tarefa->tarefa_status->cor}'> {$value} <span> "; 

        });

        $this->tarefa_vinculo_subtarefa_id_list->createModel();

        $criteria_tarefa_vinculo_subtarefa_id = new TCriteria();
        $criteria_tarefa_vinculo_subtarefa_id->add(new TFilter('subtarefa_id', '=', $tarefa->id));

        $criteria_tarefa_vinculo_subtarefa_id->setProperty('order', 'data_criacao desc');

        $tarefa_vinculo_subtarefa_id_items = TarefaVinculo::getObjects($criteria_tarefa_vinculo_subtarefa_id);

        $this->tarefa_vinculo_subtarefa_id_list->addItems($tarefa_vinculo_subtarefa_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->tarefa_vinculo_subtarefa_id_list));

        $tab_65e1e464a1b00->addContent([$panel]);

        $this->tarefa_vinculo_tarefa_id_list = new TQuickGrid;
        $this->tarefa_vinculo_tarefa_id_list->style = 'width:100%';
        $this->tarefa_vinculo_tarefa_id_list->disableDefaultClick();

        $action_onShow = new TDataGridAction(array('TarefaFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{subtarefa_id}');
        $this->tarefa_vinculo_tarefa_id_list->addAction($action_onShow);

        $column_subtarefa_titulo = $this->tarefa_vinculo_tarefa_id_list->addQuickColumn("Subtarefa", 'subtarefa->titulo', 'left');
        $column_subtarefa_tarefa_status_nome_transformed = $this->tarefa_vinculo_tarefa_id_list->addQuickColumn("Status", 'subtarefa->tarefa_status->nome', 'left');

        $column_subtarefa_tarefa_status_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            return "<span class='label' style='background-color:{$object->subtarefa->tarefa_status->cor}'> {$value} <span> "; 

        });

        $this->tarefa_vinculo_tarefa_id_list->createModel();

        $criteria_tarefa_vinculo_tarefa_id = new TCriteria();
        $criteria_tarefa_vinculo_tarefa_id->add(new TFilter('tarefa_id', '=', $tarefa->id));

        $criteria_tarefa_vinculo_tarefa_id->setProperty('order', 'data_criacao desc');

        $tarefa_vinculo_tarefa_id_items = TarefaVinculo::getObjects($criteria_tarefa_vinculo_tarefa_id);

        $this->tarefa_vinculo_tarefa_id_list->addItems($tarefa_vinculo_tarefa_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->tarefa_vinculo_tarefa_id_list));

        $tab_65e1e464a1b00->addContent([$panel]);

        $tab_65e1e464a1b00->appendPage("Comentários");
        $row10 = $tab_65e1e464a1b00->addFields([$action2],[],[]);
        $row10->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $this->tarefa_comentario_tarefa_id_list = new TQuickGrid;
        $this->tarefa_comentario_tarefa_id_list->style = 'width:100%';
        $this->tarefa_comentario_tarefa_id_list->disableDefaultClick();

        $column_texto_transformed = $this->tarefa_comentario_tarefa_id_list->addQuickColumn("Comentario", 'texto', 'left');
        $column_data_criacao_transformed = $this->tarefa_comentario_tarefa_id_list->addQuickColumn("Criado em", 'data_criacao', 'left');
        $column_criacao_user_name2 = $this->tarefa_comentario_tarefa_id_list->addQuickColumn("Criado por", 'criacao_user->name', 'left');

        $column_texto_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return wordwrap($value, 100, "<br/>", false);

        });

        $column_data_criacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });

        $this->tarefa_comentario_tarefa_id_list->createModel();

        $criteria_tarefa_comentario_tarefa_id = new TCriteria();
        $criteria_tarefa_comentario_tarefa_id->add(new TFilter('tarefa_id', '=', $tarefa->id));

        $criteria_tarefa_comentario_tarefa_id->setProperty('order', 'data_criacao desc');

        $tarefa_comentario_tarefa_id_items = TarefaComentario::getObjects($criteria_tarefa_comentario_tarefa_id);

        $this->tarefa_comentario_tarefa_id_list->addItems($tarefa_comentario_tarefa_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->tarefa_comentario_tarefa_id_list));

        $tab_65e1e464a1b00->addContent([$panel]);

        $tab_65e1e464a1b00->appendPage("Principal");
        $row11 = $tab_65e1e464a1b00->addFields([$labelNaoSubtarefa]);
        $row11->layout = [' col-sm-12'];

        $row12 = $tab_65e1e464a1b00->addFields([$bpagecontainer4]);
        $row12->layout = [' col-sm-12'];

        $row13 = $this->form->addFields([$tab_65e1e464a1b00]);
        $row13->layout = [' col-sm-12'];

        $row14 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row15 = $this->form->addFields([$label10,$text10],[$label11,$text11],[$label12,$text12],[$label13,$text13]);
        $row15->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        if(!empty($param['current_tab_tab_65e1e464a1b00']))
        {
            $this->tab_65e1e464a1b00->setCurrentPage($param['current_tab_tab_65e1e464a1b00']);
        }

        $btn_ondeleteAction = new TAction([$this, 'onDelete'],['key'=>$tarefa->id]);
        $btn_ondeleteLabel = new TLabel("Excluir");

        $btn_ondelete = $this->form->addHeaderAction($btn_ondeleteLabel, $btn_ondeleteAction, 'fas:trash-alt #FF0000'); 
        $btn_ondeleteLabel->setFontSize('12px'); 
        $btn_ondeleteLabel->setFontColor('#333'); 

        $btnTarefaFormOnEditAction = new TAction(['TarefaForm', 'onEdit'],['key'=>$tarefa->id]);
        $btnTarefaFormOnEditLabel = new TLabel("Editar");

        $btnTarefaFormOnEdit = $this->form->addHeaderAction($btnTarefaFormOnEditLabel, $btnTarefaFormOnEditAction, 'fas:edit #03A9F4'); 
        $btnTarefaFormOnEditLabel->setFontSize('12px'); 
        $btnTarefaFormOnEditLabel->setFontColor('#333'); 

        $btnAddSubtarefaAction = new TAction([$this, 'onAddSubtarefa'],['key'=>$tarefa->id]);
        $btnAddSubtarefaLabel = new TLabel("Adicionar Subtarefa");

        $btnAddSubtarefa = $this->form->addHeaderAction($btnAddSubtarefaLabel, $btnAddSubtarefaAction, 'fas:plus #4CAF50'); 
        $btnAddSubtarefaLabel->setFontSize('12px'); 
        $btnAddSubtarefaLabel->setFontColor('#333'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        if($tarefa->publicacao_id == null || !$tarefa->publicacao_id || $tarefa->publicacao_id=="" || !isset($tarefa->publicacao_id) || empty($tarefa->publicacao_id)){
            TScript::create("$(\"[name='actVerPublicacao']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$('label:contains(\"Jornal:\")').hide();");
            TScript::create("$('label:contains(\"Data do tratamento da publicação:\")').hide();");
            TScript::create("$('label:contains(\"Data da disponibilização da publicação:\")').hide();");
        }

        TScript::create("$(\"[name='btnArquivar']\").closest('.fb-inline-field-container').hide()");
        if($tarefa->tarefa_status->fim == 'S' && $tarefa->arquivado!="S"){
            TScript::create("$(\"[name='btnArquivar']\").closest('.fb-inline-field-container').show()");
        }

        TScript::create("$(\"[name='btnDesarquivar']\").closest('.fb-inline-field-container').hide()");
        if($tarefa->arquivado=="S" && ($tarefa->usuario_destinatario_id == TSession::getValue('userid') || $tarefa->criacao_user_id == TSession::getValue('userid'))){
            TScript::create("$(\"[name='btnDesarquivar']\").closest('.fb-inline-field-container').show()");
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

        if($tarefa->numero_processo == null){
            TScript::create("$('label:contains(\"Número do processo:\")').hide();");
        }

        TTransaction::close();
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=TarefaFormView]');
        $style->width = '60% !important';   
        $style->show(true);

    }

    public function onDelete($param = null) 
    {
        if(isset($param['delete']) && $param['delete'] == 1)
        {
            try
            {
                // get the paramseter $key
                $key = $param['key'];
                // open a transaction with database
                TTransaction::open(self::$database);

                // instantiates object
                $object = new Tarefa($key, FALSE);

                $master = TarefaUsuarioMaster::where('usuario_master_id','=',TSession::getValue('userid'))->first();
                if($object->usuario_destinatario_id != TSession::getValue('userid') && $object->criacao_user_id != TSession::getValue('userid') && !$master){
                    throw new Exception("Você não tem permissão para excluir essa tarefa.");
                }

                $countTarefa = 
                    (TarefaMovimentacao::where('tarefa_id','=',$object->id)->count()) +
                    (TarefaHorasTrabalhadas::where('tarefa_id','=',$object->id)->count()) +
                    (TarefaVinculo::where('tarefa_id','=',$object->id)->count()) +
                    (TarefaVinculo::where('subtarefa_id','=',$object->id)->count())+
                    (PublicacaoMovimentacao::where('tarefa_id','=',$object->id)->count())
                    ;
                if($countTarefa>0){
                    throw new Exception("Essa tarefa já possui registros e não pode ser removida!");
                }

                $object->deleteComposite('TarefaCliente', 'tarefa_id', $object->id);
                $object->delete();
                TTransaction::close();

                TToast::show('success', "Registro excluído", 'topRight', 'far:check-circle');
                TScript::create("Template.closeRightPanel();");
            }
            catch (Exception $e) // in case of exception
            {
                // shows the exception error message
                new TMessage('error', $e->getMessage());
                // undo all pending operations
                TTransaction::rollback();
            }
        }
        else
        {
            // define the delete action
            $action = new TAction(array($this, 'onDelete'));
            $action->setParameters($param); // pass the key paramseter ahead
            $action->setParameter('delete', 1);
            // shows a dialog to the user
            new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);   
        }

    }
    public function onAddSubtarefa($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $tarefa = Tarefa::find((int) $param['key']);
            $pageParam['tarefa_principal_id'] = $tarefa->id;
            $pageParam['publicacao_id'] = $tarefa->publicacao_id ?? null;
            $pageParam['processo_id'] = $tarefa->processo_id ?? null;
            $pageParam['prazo'] = date('d/m/Y',strtotime($tarefa->prazo_entrega)) ?? null;
            $pageParam['retorno'] = self::class.','.$param['key'];

            TTransaction::close();

            TApplication::loadPage('TarefaForm', 'onShow', $pageParam);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {     

    }

    public  function onArquivar($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $vinculadas = TarefaVinculo::where('tarefa_id','=',$param['key'])->load();
            foreach($vinculadas as $vinculada){
                $tarefa = Tarefa::find($vinculada->subtarefa->id);
                $tarefa->arquivado = "S";
                $tarefa->store();
            }

            $tarefa = Tarefa::find($param['key']);
            $tarefa->arquivado = "S";
            $tarefa->store();
            TTransaction::close();

            TApplication::loadPage('TarefaFormView','onShow', ['key'=> $param['key'], 'id'=>$param['key']]);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onDesarquivar($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $tarefa = Tarefa::find($param['key']);
            $tarefa->arquivado = "N";
            $tarefa->store();
            TTransaction::close();

            TApplication::loadPage('TarefaFormView','onShow', ['key'=> $param['key'], 'id'=>$param['key']]);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

