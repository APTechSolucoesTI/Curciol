<?php

require_once 'vendor/autoload.php';
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

use RtfHtmlPhp\Document;
use RtfHtmlPhp\Html\HtmlFormatter;

class AtendimentoFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Atendimento';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Atendimento';

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

        $atendimento = new Atendimento($param['key']);
        // define the form title
        $this->form->setFormTitle("Atendimento {$param['key']}");

        $transformed_atendimento_agendamento_observacao = call_user_func(function($value, $object, $row)
        {
            return $value ? $value : '-';

        }, $atendimento->agendamento->observacao, $atendimento, null);    

        $transformed_atendimento_agendamento_online = call_user_func(function($value, $object, $row)
        {
            if ($value == 'T' && empty($object->dt_final))
            {
                $button = new TElement('a');
                $button->class = 'btn btn-block btn-success';
                $button->target = '_blank';
                $button->href = 'https://meet.jit.si/' . $object->agendamento->link_atendimento_online . "#userInfo.displayName=\"{$object->profissional->nome}\"";
                $button->add(TElement::tag('i', '', ['class' => 'fas fa-video']));
                $button->add(TElement::tag('span', 'Entrar no atendimento'));
                return $button;
            }

            return '';

        }, $atendimento->agendamento->online, $atendimento, null);

        TTransaction::open(self::$database);
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ativo', '=', 'S'));
        $tipos = Formulario::getIndexedArray('id', 'nome', $criteria);
        $tipos_formularios = '<span style="font-weight: normal">Formulários disponíveis:</span><br/>' . implode(', ', $tipos);
        TTransaction::close();

        $label2 = new TLabel("Cliente:", '', '12px', 'B', '100%');
        $text4 = new TTextDisplay($atendimento->cliente->nome_formatado, '', '12px', '');
        $label3 = new TLabel("Agenda:", '', '12px', 'B', '100%');
        $text32 = new TTextDisplay($atendimento->agendamento->agenda->nome, '', '12px', '');
        $label4 = new TLabel("Data início:", '', '12px', 'B', '100%');
        $text5 = new TTextDisplay(TDateTime::convertToMask($atendimento->dt_inicio, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '12px', '');
        $label5 = new TLabel("Observações do agendamento", '', '12px', 'B', '100%');
        $text544 = new TTextDisplay($transformed_atendimento_agendamento_observacao, '', '12px', '');
        $labeltipoatend = new TLabel("Tipo de atendimento:", '', '12px', 'B', '100%');
        $text37 = new TTextDisplay($atendimento->tipo_atendimento->nome, '', '12px', '');
        $text3 = new TTextDisplay($transformed_atendimento_agendamento_online, '', '12px', '');
        $actionAdicionarHistorico = new TActionLink("Adicionar histórico", new TAction(['AtendimentoHistoricoForm', 'onShow'], ['atendimento_id'=> $atendimento->id]), '', '12px', '', 'fas:plus #4CAF50');
        $labelFormulario = new TLabel("{$tipos_formularios}", '', '12px', '', '100%');
        $action2aaa = new TActionLink("Preencher novo formulário", new TAction(['RespostaFormularioForm', 'onShow'], ['atendimento_id'=> $atendimento->id]), '', '12px', '', 'fas:plus #4CAF50');
        $actionAnexo = new TActionLink("Adicionar anexo", new TAction(['AnexoForm', 'onShow'], ['atendimento_id'=> $atendimento->id]), '', '12px', '', 'fas:plus #4CAF50');
        $action2Atestado = new TActionLink("Adicionar documento", new TAction(['DocumentoForm', 'onShow'], ['atendimento_id'=> $atendimento->id]), '', '12px', '', 'fas:plus #4CAF50');
        $onGerarDocumentosPadrao = new TActionLink("Gerar documentos", new TAction(['ModalGerarDocumentosPadrao', 'onShow'], ['atendimento_id'=> $atendimento->id]), '', '12px', '', 'fas:cog #000000');
        $action6 = new TActionLink("Adicionar contrato", new TAction(['AtendimentoContratoForm', 'onShow'], ['atendimento_id'=> $atendimento->id]), '#000000', '12px', '', 'fas:plus #4CAF50');
        $action4 = new TActionLink("Novo contrato", new TAction(['ContratoForm', 'onShow'], ['atendimento_id'=> $atendimento->id]), '#000000', '12px', '', 'fas:file-contract #000000');
        $label6 = new TLabel("Criado em:", '', '10px', 'B', '100%');
        $datetimetext6 = new TTextDisplay(TDateTime::convertToMask($atendimento->data_criacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '10px', '');
        $label7 = new TLabel("Criado por:", '', '10px', 'B', '100%');
        $text7 = new TTextDisplay($atendimento->criacao_user->name, '', '10px', '');
        $label8 = new TLabel("Atualizado em:", '', '10px', 'B', '100%');
        $datetimetext8 = new TTextDisplay(TDateTime::convertToMask($atendimento->data_modificacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '10px', '');
        $label9 = new TLabel("Atualizado por:", '', '10px', 'B', '100%');
        $text9 = new TTextDisplay($atendimento->modificacao_user->name, '', '10px', '');

        $action6->class = 'btn btn-default';
        $action4->class = 'btn btn-default';
        $action2aaa->class = 'btn btn-default';
        $actionAnexo->class = 'btn btn-default';
        $action2Atestado->class = 'btn btn-default';
        $onGerarDocumentosPadrao->class = 'btn btn-default';
        $actionAdicionarHistorico->class = 'btn btn-default';


        $action4->setProperty('tela', '{id}');
        $actionAdicionarHistorico->name = "actionAdicionarHistorico";
        $row1 = $this->form->addFields([$label2,$text4],[$label3,$text32],[$label4,$text5]);
        $row1->layout = [' col-sm-6',' col-sm-3','col-sm-3'];

        $row2 = $this->form->addFields([$label5,$text544],[$labeltipoatend,$text37]);
        $row2->layout = [' col-sm-9',' col-sm-3'];

        $row3 = $this->form->addFields([$text3]);
        $row3->layout = [' col-sm-12'];

        $abas = new BootstrapFormBuilder('abas');
        $this->abas = $abas;
        $abas->setProperty('style', 'border:none; box-shadow:none;');

        $abas->appendPage("Histórico");

        $abas->addFields([new THidden('current_tab_abas')]);
        $abas->setTabFunction("$('[name=current_tab_abas]').val($(this).attr('data-current_page'));");

        $row4 = $abas->addFields([$actionAdicionarHistorico]);
        $row4->layout = ['col-sm-3'];

        $this->atendimento_historico_atendimento_id_list = new TQuickGrid;
        $this->atendimento_historico_atendimento_id_list->style = 'width:100%';
        $this->atendimento_historico_atendimento_id_list->disableDefaultClick();

        $action_onEdit = new TDataGridAction(array('AtendimentoHistoricoForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('fas:edit #2196F3');
        $action_onEdit->setField('id');

        $action_onEdit->setParameter('key', '{id}');
        $this->atendimento_historico_atendimento_id_list->addAction($action_onEdit);

        $column_historico = $this->atendimento_historico_atendimento_id_list->addQuickColumn("Histórico", 'historico', 'left' , '50%');
        $column_data_criacao_transformed = $this->atendimento_historico_atendimento_id_list->addQuickColumn("Criado em", 'data_criacao', 'left');
        $column_criacao_user_name = $this->atendimento_historico_atendimento_id_list->addQuickColumn("Criado por", 'criacao_user->name', 'left');
        $column_data_modificacao_transformed = $this->atendimento_historico_atendimento_id_list->addQuickColumn("Atualizado em", 'data_modificacao', 'left');
        $column_modificacao_user_name = $this->atendimento_historico_atendimento_id_list->addQuickColumn("Atualizado por", 'modificacao_user->name', 'left');

        $column_historico->disableHtmlConversion();

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

        $column_data_modificacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $this->atendimento_historico_atendimento_id_list->createModel();

        $criteria_atendimento_historico_atendimento_id = new TCriteria();
        $criteria_atendimento_historico_atendimento_id->add(new TFilter('atendimento_id', '=', $atendimento->id));

        $criteria_atendimento_historico_atendimento_id->setProperty('order', 'id asc');

        $atendimento_historico_atendimento_id_items = AtendimentoHistorico::getObjects($criteria_atendimento_historico_atendimento_id);

        $this->atendimento_historico_atendimento_id_list->addItems($atendimento_historico_atendimento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->atendimento_historico_atendimento_id_list));

        $abas->addContent([$panel]);

        $abas->appendPage("Formulários");
        $row5 = $abas->addFields([$labelFormulario,$action2aaa]);
        $row5->layout = [' col-sm-12'];

        $this->resposta_formulario_atendimento_id_list = new TQuickGrid;
        $this->resposta_formulario_atendimento_id_list->style = 'width:100%';
        $this->resposta_formulario_atendimento_id_list->disableDefaultClick();

        $action_onEdit = new TDataGridAction(array('RespostaFormularioForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('far:edit #2196F3');
        $action_onEdit->setField('id');

        $action_onEdit->setParameter('id', '{id}');
        $this->resposta_formulario_atendimento_id_list->addAction($action_onEdit);

        $action_onRemoverFormulario = new TDataGridAction(array('AtendimentoFormView', 'onRemoverFormulario'));
        $action_onRemoverFormulario->setUseButton(false);
        $action_onRemoverFormulario->setButtonClass('btn btn-default btn-sm');
        $action_onRemoverFormulario->setLabel("Apagar");
        $action_onRemoverFormulario->setImage('far:trash-alt #F44336');
        $action_onRemoverFormulario->setField('id');

        $this->resposta_formulario_atendimento_id_list->addAction($action_onRemoverFormulario);

        $column_formulario_nome = $this->resposta_formulario_atendimento_id_list->addQuickColumn("Formulário", 'formulario->nome', 'left');
        $column_dt_resposta_transformed = $this->resposta_formulario_atendimento_id_list->addQuickColumn("Data ", 'dt_resposta', 'center' , '100px');

        $column_dt_resposta_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $this->resposta_formulario_atendimento_id_list->createModel();

        $criteria_resposta_formulario_atendimento_id = new TCriteria();
        $criteria_resposta_formulario_atendimento_id->add(new TFilter('atendimento_id', '=', $atendimento->id));

        $criteria_resposta_formulario_atendimento_id->setProperty('order', 'id desc');

        $resposta_formulario_atendimento_id_items = RespostaFormulario::getObjects($criteria_resposta_formulario_atendimento_id);

        $this->resposta_formulario_atendimento_id_list->addItems($resposta_formulario_atendimento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->resposta_formulario_atendimento_id_list));

        $abas->addContent([$panel]);

        $abas->appendPage("Anexos");
        $row6 = $abas->addFields([$actionAnexo]);
        $row6->layout = [' col-sm-12'];

        $this->anexo_atendimento_id_list = new TQuickGrid;
        $this->anexo_atendimento_id_list->style = 'width:100%';
        $this->anexo_atendimento_id_list->disableDefaultClick();

        $action_onEdit = new TDataGridAction(array('AnexoForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("");
        $action_onEdit->setImage('far:edit #2196F3');
        $action_onEdit->setField('id');

        $this->anexo_atendimento_id_list->addAction($action_onEdit);

        $action_onRemoveAnexo = new TDataGridAction(array('AtendimentoFormView', 'onRemoveAnexo'));
        $action_onRemoveAnexo->setUseButton(false);
        $action_onRemoveAnexo->setButtonClass('btn btn-default btn-sm');
        $action_onRemoveAnexo->setLabel("");
        $action_onRemoveAnexo->setImage('far:trash-alt #F44336');
        $action_onRemoveAnexo->setField('id');

        $this->anexo_atendimento_id_list->addAction($action_onRemoveAnexo);

        $column_arquivo_transformed = $this->anexo_atendimento_id_list->addQuickColumn("Arquivo", 'arquivo', 'left');
        $column_observacao = $this->anexo_atendimento_id_list->addQuickColumn("Observação", 'observacao', 'left');

        $column_arquivo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $value = explode(',', $value);
            if(count($value) == 0)
            {
                $value = $value[0];
            }

            if(is_array($value))
            {
                $files = $value;
                $divFiles = new TElement('div');
                foreach($files as $file)
                {
                    $fileName = $file;
                    if (strpos($file, '%7B') !== false) 
                    {
                        if (!empty($file)) 
                        {
                            $fileObject = json_decode(urldecode($file));

                            $fileName = $fileObject->fileName;
                        }
                    }

                    $a = new TElement('a');
                    $a->href = "download.php?file={$fileName}";
                    $a->class = 'btn btn-link';
                    $a->add($fileName);
                    $a->target = '_blank';

                    $divFiles->add($a);

                }

                return $divFiles;
            }
            else
            {
                if (strpos($value, '%7B') !== false) 
                {
                    if (!empty($value)) 
                    {
                        $value_object = json_decode(urldecode($value));
                        $value = $value_object->fileName;
                    }
                }

                if($value)
                {
                    $a = new TElement('a');
                    $a->href = "download.php?file={$value}";
                    $a->class = 'btn btn-default';
                    $a->add($value);
                    $a->target = '_blank';

                    return $a;
                }

                return $value;
            }
        });

        $this->anexo_atendimento_id_list->createModel();

        $criteria_anexo_atendimento_id = new TCriteria();
        $criteria_anexo_atendimento_id->add(new TFilter('atendimento_id', '=', $atendimento->id));

        $criteria_anexo_atendimento_id->setProperty('order', 'arquivo desc');

        $anexo_atendimento_id_items = Anexo::getObjects($criteria_anexo_atendimento_id);

        $this->anexo_atendimento_id_list->addItems($anexo_atendimento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->anexo_atendimento_id_list));

        $abas->addContent([$panel]);

        $abas->appendPage("Documentos/Laudos");
        $row7 = $abas->addFields([$action2Atestado],[$onGerarDocumentosPadrao]);
        $row7->layout = [' col-sm-3',' col-sm-3'];

        $this->documento_atendimento_id_list = new TQuickGrid;
        $this->documento_atendimento_id_list->style = 'width:100%';
        $this->documento_atendimento_id_list->disableDefaultClick();

        $action_onInvalidarDocumento = new TDataGridAction(array('AtendimentoFormView', 'onInvalidarDocumento'));
        $action_onInvalidarDocumento->setUseButton(false);
        $action_onInvalidarDocumento->setButtonClass('btn btn-default btn-sm');
        $action_onInvalidarDocumento->setLabel("Invalidar");
        $action_onInvalidarDocumento->setImage('fas:ban #F44336');
        $action_onInvalidarDocumento->setField('id');
        $action_onInvalidarDocumento->setDisplayCondition('AtendimentoFormView::canInvalidar');

        $this->documento_atendimento_id_list->addAction($action_onInvalidarDocumento);

        $action_onPrint = new TDataGridAction(array('AtendimentoFormView', 'onPrint'));
        $action_onPrint->setUseButton(false);
        $action_onPrint->setButtonClass('btn btn-default btn-sm');
        $action_onPrint->setLabel("Imprimir");
        $action_onPrint->setImage('fas:print #000000');
        $action_onPrint->setField('id');
        $action_onPrint->setDisplayCondition('AtendimentoFormView::canPrint');
        $action_onPrint->setParameter('filename', '{filename}');
        $this->documento_atendimento_id_list->addAction($action_onPrint);

        $action_onDownload = new TDataGridAction(array('AtendimentoFormView', 'onDownload'));
        $action_onDownload->setUseButton(false);
        $action_onDownload->setButtonClass('btn btn-default btn-sm');
        $action_onDownload->setLabel("Gerar");
        $action_onDownload->setImage('fas:file-download #9C27B0');
        $action_onDownload->setField('id');
        $action_onDownload->setDisplayCondition('AtendimentoFormView::canGerar');
        $action_onDownload->setParameter('filename', '{filename}');
        $this->documento_atendimento_id_list->addAction($action_onDownload);

        $column_dt_preenchimento_transformed = $this->documento_atendimento_id_list->addQuickColumn("Emissão", 'dt_preenchimento', 'left' , '100px');
        $column_modelo_documento_nome = $this->documento_atendimento_id_list->addQuickColumn("Tipo", 'modelo_documento->nome', 'left');
        $column_autenticador = $this->documento_atendimento_id_list->addQuickColumn("Autenticador", 'autenticador', 'left' , '200px');
        $column_criacao_user_name1 = $this->documento_atendimento_id_list->addQuickColumn("Emitido por", 'criacao_user->name', 'left');

        $column_dt_preenchimento_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $this->documento_atendimento_id_list->createModel();

        $criteria_documento_atendimento_id = new TCriteria();
        $criteria_documento_atendimento_id->add(new TFilter('atendimento_id', '=', $atendimento->id));

        $criteria_documento_atendimento_id->setProperty('order', 'id desc');

        $documento_atendimento_id_items = Documento::getObjects($criteria_documento_atendimento_id);

        $this->documento_atendimento_id_list->addItems($documento_atendimento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->documento_atendimento_id_list));

        $abas->addContent([$panel]);

        $abas->appendPage("Contratos");
        $row8 = $abas->addFields([$action6],[$action4]);
        $row8->layout = [' col-sm-2','col-sm-2'];

        $this->atendimento_contrato_atendimento_id_list = new TQuickGrid;
        $this->atendimento_contrato_atendimento_id_list->style = 'width:100%';
        $this->atendimento_contrato_atendimento_id_list->disableDefaultClick();

        $action_onShow = new TDataGridAction(array('ContratoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #9C27B0');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{contrato_id}');
        $this->atendimento_contrato_atendimento_id_list->addAction($action_onShow);

        $column_id = $this->atendimento_contrato_atendimento_id_list->addQuickColumn("Id", 'id', 'left');
        $column_contrato_numero = $this->atendimento_contrato_atendimento_id_list->addQuickColumn("Número", 'contrato->numero', 'left');
        $column_contrato_objeto = $this->atendimento_contrato_atendimento_id_list->addQuickColumn("Objeto", 'contrato->objeto', 'left');

        $this->atendimento_contrato_atendimento_id_list->createModel();

        $criteria_atendimento_contrato_atendimento_id = new TCriteria();
        $criteria_atendimento_contrato_atendimento_id->add(new TFilter('atendimento_id', '=', $atendimento->id));

        $criteria_atendimento_contrato_atendimento_id->setProperty('order', 'id desc');

        $atendimento_contrato_atendimento_id_items = AtendimentoContrato::getObjects($criteria_atendimento_contrato_atendimento_id);

        $this->atendimento_contrato_atendimento_id_list->addItems($atendimento_contrato_atendimento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->atendimento_contrato_atendimento_id_list));

        $abas->addContent([$panel]);

        $abas->appendPage("Procedimentos");

        $this->atendimento_procedimento_atendimento_id_list = new TQuickGrid;
        $this->atendimento_procedimento_atendimento_id_list->style = 'width:100%';
        $this->atendimento_procedimento_atendimento_id_list->disableDefaultClick();

        $column_procedimento_nome = $this->atendimento_procedimento_atendimento_id_list->addQuickColumn("Procedimento", 'procedimento->nome', 'left' , '60%');
        $column_valor_transformed = $this->atendimento_procedimento_atendimento_id_list->addQuickColumn("Valor", 'valor', 'left');
        $column_quantidade_transformed = $this->atendimento_procedimento_atendimento_id_list->addQuickColumn("Quantidade", 'quantidade', 'center' , '150px');
        $column_valor_total_transformed = $this->atendimento_procedimento_atendimento_id_list->addQuickColumn("Valor total", 'valor_total', 'right');

        $column_valor_total_transformed->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $column_valor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $column_quantidade_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return number_format($value, 2, ',', '.');

        });

        $column_valor_total_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $this->atendimento_procedimento_atendimento_id_list->createModel();

        $criteria_atendimento_procedimento_atendimento_id = new TCriteria();
        $criteria_atendimento_procedimento_atendimento_id->add(new TFilter('atendimento_id', '=', $atendimento->id));

        $criteria_atendimento_procedimento_atendimento_id->setProperty('order', 'id desc');

        $atendimento_procedimento_atendimento_id_items = AtendimentoProcedimento::getObjects($criteria_atendimento_procedimento_atendimento_id);

        $this->atendimento_procedimento_atendimento_id_list->addItems($atendimento_procedimento_atendimento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->atendimento_procedimento_atendimento_id_list));

        $abas->addContent([$panel]);
        $row9 = $this->form->addFields([$abas]);
        $row9->layout = [' col-sm-12'];

        $row10 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row11 = $this->form->addFields([$label6,$datetimetext6],[$label7,$text7],[$label8,$datetimetext8],[$label9,$text9]);
        $row11->layout = [' col-sm-2',' col-sm-2',' col-sm-2',' col-sm-2'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        if(!empty($param['current_tab_abas']))
        {
            $this->abas->setCurrentPage($param['current_tab_abas']);
        }

        $this->abas->class = 'no-padding';
        $row5->layout = [' col-sm-12 d-flex column-space-between'];

        if (empty($atendimento->dt_final))
        {
            $btnFinalizarAction = new TAction([$this, 'onFinalizar'],['static' => 1, 'key'=>$atendimento->id]);
            $btnFinalizarLabel = new TLabel("Finalizar atendimento");
            $btnFinalizarLabel->setFontSize('12px'); 
            $btnFinalizarLabel->setFontColor('#333'); 

            $this->form->addHeaderAction($btnFinalizarLabel, $btnFinalizarAction, 'fas:check #4CAF50'); 
        }

        if (!AtendimentoService::podeManipular($atendimento, TSession::getValue('userid')) && $atendimento->tipo_atendimento_id==TipoAtendimento::AGENDADO)
        {
            $container = new TElement('div');
            $action = new TAction(['AgendamentoList', 'onShow']);
            new TMessage('error', 'Você não tem acesso a esse atendimento', $action);
        }

        $bntVerClienteAtendAction = new TAction(['AtendimentoFormView', 'onVisualizarCliente'],['key'=>$atendimento->id]);
        $bntVerClienteAtendLabel = new TLabel("Visualizar cliente");

        $bntVerClienteAtend = $this->form->addHeaderAction($bntVerClienteAtendLabel, $bntVerClienteAtendAction, 'fas:user-circle #000000'); 
        $bntVerClienteAtendLabel->setFontSize('12px'); 
        $bntVerClienteAtendLabel->setFontColor('#333'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        $bntVerClienteAtendAction->setParameter('cliente_id',$atendimento->cliente_id);

        TTransaction::close();
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AtendimentoFormView]');
        $style->width = '85% !important';   
        $style->show(true);

    }

    public static function onRemoverFormulario($param = null) 
    {

        TTransaction::open(self::$database);

        $respostaFormulario = RespostaFormulario::find($param['id']);

        $id = $respostaFormulario->atendimento_id;

        if ($respostaFormulario)
        {
            $respostaFormulario->deleteComposite('Resposta', 'resposta_formulario_id', $respostaFormulario->id);
            $respostaFormulario->delete();
        }

        TTransaction::close();

        TApplication::loadPage(__CLASS__, 'onShow', ['key' => $id, 'id' => $id, 'current_tab_abas' => 5]);

            //</autoCode>

    }
    public static function onRemoveAnexo($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $anexo = Anexo::find($param['id']);

            $id = $anexo->atendimento_id;

            if ($anexo)
            {
                $anexo->delete();
            }

            TTransaction::close();

            TApplication::loadPage(__CLASS__, 'onShow', ['key' => $id, 'id' => $id, 'current_tab_abas' => 2]);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function onInvalidarDocumento($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $documento = Documento::find($param['id']);

            $id = $documento->atendimento_id;

            if ($documento)
            {
                // Nome do arquivo tipo
                $nome_arquivo = $documento->filename;

                // Ler o conteúdo do arquivo RTF tip
                $conteudo_rtf = file_get_contents("$nome_arquivo");

                $conteudo_rtf = str_replace(
                "Criado por " . $documento->criacao_user->name . " em " . implode('/', array_reverse(explode('-', substr($documento->data_criacao,0,-9)))) . " | " . $documento->autenticador,
                "Cancelado por " . (SystemUsers::find(TSession::getValue('userid')))->name . " em " . date('d/m/Y'),
                $conteudo_rtf);

                // Enviar conteúdo para novo arquivo
                file_put_contents($nome_arquivo, $conteudo_rtf);

                $documento->filename = $nome_arquivo;
                $documento->dt_validade = date('Y-m-d');
                $documento->autenticador = '';
                $documento->modificacao_user_id = TSession::getValue('userid');
                $documento->store();
            }

            TTransaction::close();

            TApplication::loadPage(__CLASS__, 'onShow', ['key' => $id, 'id' => $id, 'current_tab_abas' => 3]);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canInvalidar($object)
    {
        try 
        {
            //if($object->autenticador)
            //{
            //    return true;
            //}

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function onPrint($param = null) 
    {
        try 
        {

            TPage::openFile($param['filename'].".pdf");

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canPrint($object)
    {
        try 
        {
            if($object)
            {
                return true;
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function onDownload($param = null) 
    {
        try 
        {
            TPage::openFile($param['filename']);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canGerar($object)
    {
        try 
        {
            TTransaction::open(self::$database);

            $pessoa = Pessoa::where('system_users_id','=',TSession::getValue('userid'))->first();
            if($pessoa){
                $pessoaGrupo = PessoaGrupo::where('pessoa_id','=',$pessoa->id)->where('grupo_id','=',Grupo::PROFISSIONAL)->count();
                if($pessoaGrupo>0)
                {
                    return true;
                }
            }

            return false;

            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {     

        TTransaction::open(self::$database);

        TScript::create("$(\"[page_name='AgendamentoFormView']\").remove()");

        $historicos = AtendimentoHistorico::where('atendimento_id','=', $param['key'])->count();
        if($historicos>0){
            TScript::create("$(\"[name='actionAdicionarHistorico']\").hide()");
        }

        TTransaction::close();
    }

    public  function onVisualizarCliente($param = null) 
    {
        try 
        {
            TScript::create("$(\"[page_name='ClienteForm']\").remove()");
            TApplication::loadPage('ClienteForm', 'onEdit', ['key' => $param['cliente_id']]);
            TTransaction::open(self::$database);
            $historicos = AtendimentoHistorico::where('atendimento_id','=', $param['key'])->count();
            if($historicos>0){
                TScript::create("$(\"[name='actionAdicionarHistorico']\").hide()");
            }
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onFinalizar($param)
    {
        try 
        {
            TTransaction::open(self::$database);

            $atendimento = Atendimento::find($param['key']);
            $atendimento->dt_final = date('Y-m-d H:i:s');
            $atendimento->store();

            $agendamento = $atendimento->agendamento;

            if ($agendamento->estado_agenda->estado_final == 'S')
            {
                throw new Exception('Esse agendamento já está finalizado!');
            }

            $agendamento->estado_agenda_id = EstadoAgenda::ATENDIDO;
            $agendamento->store();

            $historico = new EstadoAgendamento();
            $historico->agendamento_id = $agendamento->id;
            $historico->estado_agenda_id = $agendamento->estado_agenda_id;
            $historico->system_users_id = TSession::getValue('userid');
            $historico->atribuido_em = date('Y-m-d H:i:s');
            $historico->store();

            TToast::show('success', 'Atendimento finalizado com sucesso');

            TTransaction::close();

            TScript::create("Template.closeRightPanel();");
            TApplication::loadPage(__CLASS__, 'onShow', ['key' => $atendimento->id, 'id' => $atendimento->id]);
            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
}

