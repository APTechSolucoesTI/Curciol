<?php

use phputil\extenso\Extenso;

class ContratoFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Contrato';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Contrato';

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

        $contrato = new Contrato($param['key']);
        // define the form title
        $this->form->setFormTitle("Gerar documentos do contrato");

        $transformed_contrato_contrato_status_nome = call_user_func(function($value, $object, $row)
        {
            return "<span class='label' style='width:100%;max-width:200px;background-color:{$object->contrato_status->cor}'> {$value} </span>"; 

        }, $contrato->contrato_status->nome, $contrato, null);

        $label1 = new TLabel("Id:", '', '14px', 'B', '100%');
        $text1 = new TTextDisplay($contrato->id, '', '14px', '');
        $label2 = new TLabel("Número:", '', '14px', 'B', '100%');
        $text2 = new TTextDisplay($contrato->numero, '', '14px', '');
        $label8 = new TLabel("Status:", '', '12px', 'B', '100%');
        $text8 = new TTextDisplay($transformed_contrato_contrato_status_nome, '', '12px', '');
        $action2 = new TActionLink("Alterar Status", new TAction(['ContratoAlterarStatusForm', 'onEdit'], ['key'=> $contrato->id]), '', '12px', '', 'fas:exchange-alt #000000');
        $label3 = new TLabel("Objeto:", '', '14px', 'B', '100%');
        $text3 = new TTextDisplay($contrato->objeto, '', '14px', '');
        $action1 = new TActionLink("Adicionar documento", new TAction(['ContratoDocumentoForm', 'onShow'], ['contrato_id'=> $contrato->id]), '#000000', '12px', '', 'fas:plus #4CAF50');
        $action3 = new TActionLink("Gerar documentos", new TAction(['ContratoFormView', 'onGerarDoc'], ['key'=> $contrato->id]), '', '12px', '', 'fas:cog #000000');
        $label4 = new TLabel("Criado em:", '', '14px', 'B', '100%');
        $text4 = new TTextDisplay(TDateTime::convertToMask($contrato->data_criacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '14px', '');
        $label5 = new TLabel("Criado por:", '', '14px', 'B', '100%');
        $text5 = new TTextDisplay($contrato->criacao_user->name, '', '14px', '');
        $label6 = new TLabel("Atualizado em:", '', '14px', 'B', '100%');
        $text6 = new TTextDisplay(TDateTime::convertToMask($contrato->data_modificacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '14px', '');
        $label7 = new TLabel("Atualizado por:", '', '14px', 'B', '100%');
        $text7 = new TTextDisplay($contrato->modificacao_user->name, '', '14px', '');

        $action2->class = 'btn btn-default';
        $action1->class = 'btn btn-default';
        $action3->class = 'btn btn-default';

        $action3->name = 'btnGerarDocumento';
        $row1 = $this->form->addFields([$label1,$text1],[$label2,$text2],[$label8,$text8,$action2]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([$label3,$text3]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);

        $tab_64e5fbabf05b7 = new BootstrapFormBuilder('tab_64e5fbabf05b7');
        $this->tab_64e5fbabf05b7 = $tab_64e5fbabf05b7;
        $tab_64e5fbabf05b7->setProperty('style', 'border:none; box-shadow:none;');

        $tab_64e5fbabf05b7->appendPage("Documentos");

        $tab_64e5fbabf05b7->addFields([new THidden('current_tab_tab_64e5fbabf05b7')]);
        $tab_64e5fbabf05b7->setTabFunction("$('[name=current_tab_tab_64e5fbabf05b7]').val($(this).attr('data-current_page'));");

        $row4 = $tab_64e5fbabf05b7->addFields([$action1],[$action3],[]);
        $row4->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $this->contrato_documento_contrato_id_list = new TQuickGrid;
        $this->contrato_documento_contrato_id_list->style = 'width:100%';
        $this->contrato_documento_contrato_id_list->disableDefaultClick();

        $action_invalidarDoc = new TDataGridAction(array('ContratoFormView', 'invalidarDoc'));
        $action_invalidarDoc->setUseButton(false);
        $action_invalidarDoc->setButtonClass('btn btn-default btn-sm');
        $action_invalidarDoc->setLabel("Invalidar");
        $action_invalidarDoc->setImage('fas:ban #FF0000');
        $action_invalidarDoc->setField('id');
        $action_invalidarDoc->setDisplayCondition('ContratoFormView::canInvalidar');
        $action_invalidarDoc->setParameter('contrato_id', '{id}');
        $this->contrato_documento_contrato_id_list->addAction($action_invalidarDoc);

        $action_onPrint = new TDataGridAction(array('ContratoFormView', 'onPrint'));
        $action_onPrint->setUseButton(false);
        $action_onPrint->setButtonClass('btn btn-default btn-sm');
        $action_onPrint->setLabel("Imprimir");
        $action_onPrint->setImage('fas:print #000000');
        $action_onPrint->setField('id');
        $action_onPrint->setDisplayCondition('ContratoFormView::canImprimir');
        $action_onPrint->setParameter('filename', '{filename}');
        $this->contrato_documento_contrato_id_list->addAction($action_onPrint);

        $action_onDownload = new TDataGridAction(array('ContratoFormView', 'onDownload'));
        $action_onDownload->setUseButton(false);
        $action_onDownload->setButtonClass('btn btn-default btn-sm');
        $action_onDownload->setLabel("Download");
        $action_onDownload->setImage('fas:file-download #000000');
        $action_onDownload->setField('id');

        $action_onDownload->setParameter('filename', '{filename}');
        $this->contrato_documento_contrato_id_list->addAction($action_onDownload);

        $column_id = $this->contrato_documento_contrato_id_list->addQuickColumn("Código", 'id', 'left');
        $column_modelo_documento_nome = $this->contrato_documento_contrato_id_list->addQuickColumn("Tipo de documento", 'modelo_documento->nome', 'left');
        $column_autenticador = $this->contrato_documento_contrato_id_list->addQuickColumn("Autenticador", 'autenticador', 'left');
        $column_dt_validade_transformed = $this->contrato_documento_contrato_id_list->addQuickColumn("Validade", 'dt_validade', 'left');
        $column_data_criacao_transformed = $this->contrato_documento_contrato_id_list->addQuickColumn("Criado em", 'data_criacao', 'left');
        $column_criacao_user_name = $this->contrato_documento_contrato_id_list->addQuickColumn("Criado por", 'criacao_user->name', 'left');
        $column_data_modificacao_transformed = $this->contrato_documento_contrato_id_list->addQuickColumn("Atualizado em", 'data_modificacao', 'left');
        $column_modificacao_user_name = $this->contrato_documento_contrato_id_list->addQuickColumn("Atualizado por", 'modificacao_user->name', 'left');

        $column_dt_validade_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $this->contrato_documento_contrato_id_list->createModel();

        $criteria_contrato_documento_contrato_id = new TCriteria();
        $criteria_contrato_documento_contrato_id->add(new TFilter('contrato_id', '=', $contrato->id));

        $criteria_contrato_documento_contrato_id->setProperty('order', 'id desc');

        $contrato_documento_contrato_id_items = ContratoDocumento::getObjects($criteria_contrato_documento_contrato_id);

        $this->contrato_documento_contrato_id_list->addItems($contrato_documento_contrato_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->contrato_documento_contrato_id_list));

        $tab_64e5fbabf05b7->addContent([$panel]);

        $tab_64e5fbabf05b7->appendPage("Clientes");

        $this->contrato_pessoa_contrato_id_list = new TQuickGrid;
        $this->contrato_pessoa_contrato_id_list->style = 'width:100%';
        $this->contrato_pessoa_contrato_id_list->disableDefaultClick();

        $column_cliente_nome = $this->contrato_pessoa_contrato_id_list->addQuickColumn("Cliente", 'cliente->nome', 'left');
        $column_percentual = $this->contrato_pessoa_contrato_id_list->addQuickColumn("Percentual", 'percentual', 'left');

        $column_percentual->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $this->contrato_pessoa_contrato_id_list->createModel();

        $criteria_contrato_pessoa_contrato_id = new TCriteria();
        $criteria_contrato_pessoa_contrato_id->add(new TFilter('contrato_id', '=', $contrato->id));

        $criteria_contrato_pessoa_contrato_id->setProperty('order', 'id desc');

        $contrato_pessoa_contrato_id_items = ContratoPessoa::getObjects($criteria_contrato_pessoa_contrato_id);

        $this->contrato_pessoa_contrato_id_list->addItems($contrato_pessoa_contrato_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->contrato_pessoa_contrato_id_list));

        $tab_64e5fbabf05b7->addContent([$panel]);

        $tab_64e5fbabf05b7->appendPage("Pagamentos");

        $this->contrato_pagamento_parcela_contrato_id_list = new TQuickGrid;
        $this->contrato_pagamento_parcela_contrato_id_list->style = 'width:100%';
        $this->contrato_pagamento_parcela_contrato_id_list->disableDefaultClick();

        $action_onAddFinanceiro = new TDataGridAction(array('ContratoFormView', 'onAddFinanceiro'));
        $action_onAddFinanceiro->setUseButton(false);
        $action_onAddFinanceiro->setButtonClass('btn btn-default btn-sm');
        $action_onAddFinanceiro->setLabel("Gerar financeiro");
        $action_onAddFinanceiro->setImage('fas:dollar-sign #000000');
        $action_onAddFinanceiro->setField('id');
        $action_onAddFinanceiro->setDisplayCondition('ContratoFormView::canGerar');
        $action_onAddFinanceiro->setParameter('contrato_parcela_id', '{id}');
        $this->contrato_pagamento_parcela_contrato_id_list->addAction($action_onAddFinanceiro);

        $action_onEdit = new TDataGridAction(array('ContratoPagamentoParcelaDescritivoForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Descritivo");
        $action_onEdit->setImage('fas:align-left #000000');
        $action_onEdit->setField('id');

        $action_onEdit->setParameter('key', '{id}');
        $this->contrato_pagamento_parcela_contrato_id_list->addAction($action_onEdit);

        $column_contrato_opcao_pagamento_nome = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Opção de pagamento", 'contrato_opcao_pagamento->nome', 'left');
        $column_descritivo = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Descritivo", 'descritivo', 'left' , '25%');
        $column_valor_transformed = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Valor", 'valor', 'left');
        $column_saldo_transformed = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Saldo", 'saldo', 'left');
        $column_data_pagamento_transformed = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Data", 'data_pagamento', 'left');
        $column_contrato_evento_nome = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Evento", 'contrato_evento->nome', 'left');
        $column_complemento_indexador = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Número do indexador", 'complemento_indexador', 'left');
        $column_contrato_indexador_nome = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Indexador", 'contrato_indexador->nome', 'left');
        $column_numero_parcelas = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Número de parcelas", 'numero_parcelas', 'left');
        $column_status_contrato_pagamento_id_transformed = $this->contrato_pagamento_parcela_contrato_id_list->addQuickColumn("Status", 'status_contrato_pagamento_id', 'left');

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

        $column_saldo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_data_pagamento_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_status_contrato_pagamento_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
             $valorParcela = $object->valor ?? 0;

            if ($valorParcela === null || $valorParcela === '') {
                return '';
            }

            if (!is_numeric($valorParcela)) {
                $valorParcela = str_replace(['R$', ' '], '', (string) $valorParcela);
                $valorParcela = str_replace('.', '', $valorParcela);
                $valorParcela = str_replace(',', '.', $valorParcela);
            }

            $valorParcela = (float) $valorParcela;

            if ($valorParcela <= 0) {
                return '';
            }

            $statusId = empty($value) ? 1 : (int) $value;

            switch ($statusId) {
                case 1:
                    $texto = 'Em Aberto';
                    $corFundo = '#fff3cd';
                    $corTexto = '#856404';
                    $icone = 'fas fa-clock';
                    break;

                case 2:
                    $texto = 'Gerado com Saldo';
                    $corFundo = '#d1ecf1';
                    $corTexto = '#0c5460';
                    $icone = 'fas fa-adjust';
                    break;

                case 3:
                    $texto = 'Gerado';
                    $corFundo = '#d4edda';
                    $corTexto = '#155724';
                    $icone = 'fas fa-check-circle';
                    break;

                default:
                    $texto = 'Em Aberto';
                    $corFundo = '#fff3cd';
                    $corTexto = '#856404';
                    $icone = 'fas fa-clock';
                    break;
            }

            $label = new TElement('span');
            $label->style = "
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 9px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                background: {$corFundo}
                ;
                color: {$corTexto}
                ;
                white-space: nowrap;
            ";

            $label->add("<i class='{$icone}'></i> {$texto}");

            $contratoId = (int) ($object->contrato_id ?? 0);

            if (in_array($statusId, [2, 3]) && !empty($contratoId)) {
                try {
                    $fecharTransacao = false;

                    if (!TTransaction::get()) {
                        TTransaction::open('escritorio');
                        $fecharTransacao = true;
                    }

                    $conn = TTransaction::get();

                    $sql = "
                        SELECT id
                        FROM conta
                        WHERE contrato_id = :contrato_id
                        ORDER BY id DESC
                        LIMIT 1
                    ";

                    $stmt = $conn->prepare($sql);
                    $stmt->bindValue(':contrato_id', $contratoId);
                    $stmt->execute();

                    $contaId = $stmt->fetchColumn();

                    if ($fecharTransacao) {
                        TTransaction::close();
                    }

                    if (!empty($contaId)) {
                        $contaId = (int) $contaId;

                       $url = "index.php?class=ContaFormView&method=onShow&key={$contaId}&id={$contaId}&target_container=adianti_right_panel";

                        $link = new TElement('a');
                        $link->href = 'javascript:void(0)';
                        $link->style = 'text-decoration: none; cursor: pointer;';
                        $link->title = 'Abrir conta gerada';

                        $link->onclick = "
                            if (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                event.stopImmediatePropagation();
                            }

                            __adianti_load_page('{$url}');

                            return false;
                        ";

                        $link->add($label);

                        return $link;
                    }
                } catch (Exception $e) {
                    if (TTransaction::get()) {
                        TTransaction::rollback();
                    }

                    return $label;
                }
            }

            return $label;

        });

        $this->contrato_pagamento_parcela_contrato_id_list->createModel();

        $criteria_contrato_pagamento_parcela_contrato_id = new TCriteria();
        $criteria_contrato_pagamento_parcela_contrato_id->add(new TFilter('contrato_id', '=', $contrato->id));

        $criteria_contrato_pagamento_parcela_contrato_id->setProperty('order', 'contrato_opcao_pagamento_id asc');

        $contrato_pagamento_parcela_contrato_id_items = ContratoPagamentoParcela::getObjects($criteria_contrato_pagamento_parcela_contrato_id);

        $this->contrato_pagamento_parcela_contrato_id_list->addItems($contrato_pagamento_parcela_contrato_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->contrato_pagamento_parcela_contrato_id_list));

        $tab_64e5fbabf05b7->addContent([$panel]);
        $row5 = $this->form->addFields([$tab_64e5fbabf05b7]);
        $row5->layout = [' col-sm-12'];

        $tab_68e80abe3a91f = new BootstrapFormBuilder('tab_68e80abe3a91f');
        $this->tab_68e80abe3a91f = $tab_68e80abe3a91f;
        $tab_68e80abe3a91f->setProperty('style', 'border:none; box-shadow:none;');

        $tab_68e80abe3a91f->appendPage("Repasse");

        $tab_68e80abe3a91f->addFields([new THidden('current_tab_tab_68e80abe3a91f')]);
        $tab_68e80abe3a91f->setTabFunction("$('[name=current_tab_tab_68e80abe3a91f]').val($(this).attr('data-current_page'));");

        $this->contrato_repasse_contrato_id_list = new TQuickGrid;
        $this->contrato_repasse_contrato_id_list->style = 'width:100%';
        $this->contrato_repasse_contrato_id_list->disableDefaultClick();

        $column_pessoa_nome = $this->contrato_repasse_contrato_id_list->addQuickColumn("Parceiro", 'pessoa->nome', 'left');
        $column_percentual1 = $this->contrato_repasse_contrato_id_list->addQuickColumn("Percentual", 'percentual', 'left');

        $column_percentual1->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $this->contrato_repasse_contrato_id_list->createModel();

        $criteria_contrato_repasse_contrato_id = new TCriteria();
        $criteria_contrato_repasse_contrato_id->add(new TFilter('contrato_id', '=', $contrato->id));

        $criteria_contrato_repasse_contrato_id->setProperty('order', 'id desc');

        $contrato_repasse_contrato_id_items = ContratoRepasse::getObjects($criteria_contrato_repasse_contrato_id);

        $this->contrato_repasse_contrato_id_list->addItems($contrato_repasse_contrato_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->contrato_repasse_contrato_id_list));

        $tab_68e80abe3a91f->addContent([$panel]);
        $row6 = $this->form->addFields([$tab_68e80abe3a91f]);
        $row6->layout = [' col-sm-12'];

        $row7 = $this->form->addFields([$label4,$text4],[$label5,$text5],[$label6,$text6],[$label7,$text7]);
        $row7->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        if(!empty($param['current_tab_tab_64e5fbabf05b7']))
        {
            $this->tab_64e5fbabf05b7->setCurrentPage($param['current_tab_tab_64e5fbabf05b7']);
        }
        if(!empty($param['current_tab_tab_68e80abe3a91f']))
        {
            $this->tab_68e80abe3a91f->setCurrentPage($param['current_tab_tab_68e80abe3a91f']);
        }

        $action_invalidarDoc->setParameter('key', '{contrato_id}');

        $btn_ondeleteAction = new TAction([$this, 'onDelete'],['key'=>$contrato->id]);
        $btn_ondeleteLabel = new TLabel("Excluir");

        $btn_ondelete = $this->form->addHeaderAction($btn_ondeleteLabel, $btn_ondeleteAction, 'fas:trash-alt #FF0000'); 
        $btn_ondeleteLabel->setFontSize('12px'); 
        $btn_ondeleteLabel->setFontColor('#333'); 

        $btnContratoFormOnEditAction = new TAction(['ContratoForm', 'onEdit'],['key'=>$contrato->id]);
        $btnContratoFormOnEditLabel = new TLabel("Editar");

        $btnContratoFormOnEdit = $this->form->addHeaderAction($btnContratoFormOnEditLabel, $btnContratoFormOnEditAction, 'fas:edit #03A9F4'); 
        $btnContratoFormOnEditLabel->setFontSize('12px'); 
        $btnContratoFormOnEditLabel->setFontColor('#333'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TTransaction::close();
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ContratoFormView]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function invalidarDoc($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $documento = ContratoDocumento::find((int)$param['id']);

            $idContrato = $documento->contrato_id;

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

            TApplication::loadPage(__CLASS__, 'onShow', ['key' => $idContrato]);

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
            //if(!$object->dt_validade)
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
    public static function canImprimir($object)
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
    public static function onDownload($param = null) 
    {
        try 
        {
            TPage::openFile($param['filename'].'.docx');

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function onAddFinanceiro($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if (empty($param['contrato_parcela_id'])) {
                throw new Exception('Parcela do contrato não informada.');
            }

            $toFloat = function($valor) {
                if ($valor === null || $valor === '') {
                    return 0;
                }

                if (is_int($valor) || is_float($valor)) {
                    return (float) $valor;
                }

                $valor = trim((string) $valor);
                $valor = str_replace('R$', '', $valor);
                $valor = str_replace(' ', '', $valor);
                $valor = preg_replace('/[^0-9,.\-]/', '', $valor);

                if ($valor === '' || $valor === '-' || $valor === ',' || $valor === '.') {
                    return 0;
                }

                if (strpos($valor, ',') !== false) {
                    $valor = str_replace('.', '', $valor);
                    $valor = str_replace(',', '.', $valor);
                    return (float) $valor;
                }

                return (float) $valor;
            };

            $contratoParcela = ContratoPagamentoParcela::find((int) $param['contrato_parcela_id']);

            if (!$contratoParcela) {
                throw new Exception('Parcela do contrato não encontrada.');
            }

            $contrato = Contrato::find($contratoParcela->contrato_id);

            if (!$contrato) {
                throw new Exception('Contrato não encontrado.');
            }

            $statusId = (int) ($contratoParcela->status_contrato_pagamento_id ?? 1);

            /*
            * 1 = Em Aberto
            * 2 = Gerado com Saldo
            * 3 = Gerado
            */
            if ($statusId === 3) {
                throw new Exception('Esta parcela do contrato já foi gerada integralmente.');
            }

            if (!in_array($statusId, [1, 2])) {
                throw new Exception('Status da parcela não permite gerar financeiro.');
            }

            $valorParcela = $toFloat($contratoParcela->valor ?? 0);
            $saldoParcela = $toFloat($contratoParcela->saldo ?? 0);

            /*
            * Se está em aberto, usa o valor original.
            * Se já foi gerado com saldo, usa o saldo restante.
            */
            if ($statusId === 2) {
                $valorDisponivel = $saldoParcela;
            } else {
                $valorDisponivel = $valorParcela;
            }

            if ($valorDisponivel <= 0) {
                throw new Exception('Adicione um valor inicial antes de gerar o financeiro.');
            }

            $profissionaisRepasse = [];
            $percentuaisRepasse = [];
            $valoresProfissionais = [];

            $repasses = ContratoRepasse::where('contrato_id', '=', $contrato->id)->load();

            foreach ($repasses as $repasse)
            {
                $grupo = PessoaGrupo::where('pessoa_id', '=', $repasse->pessoa_id)
                    ->where('grupo_id', 'in', [Grupo::PARCEIRO, Grupo::FORNECEDOR])
                    ->first();

                if ($grupo)
                {
                    $profissionaisRepasse[] = $repasse->pessoa_id;
                    $percentuaisRepasse[] = $repasse->percentual ?? 0;
                }
            }

            $qtdProfissionais = count($profissionaisRepasse);

            if ($qtdProfissionais > 0)
            {
                $valorBase = floor(($valorDisponivel / $qtdProfissionais) * 100) / 100;
                $valorAcumulado = 0;

                for ($i = 0; $i < $qtdProfissionais; $i++)
                {
                    if ($i == $qtdProfissionais - 1)
                    {
                        $valoresProfissionais[] = $valorDisponivel - $valorAcumulado;
                    }
                    else
                    {
                        $valoresProfissionais[] = $valorBase;
                        $valorAcumulado += $valorBase;
                    }
                }
            }

            // CLIENTE
            $pessoaCount = ContratoPessoa::where('contrato_id', '=', $contrato->id)->count();

            if ($pessoaCount <= 0) {
                throw new Exception('Nenhum cliente vinculado ao contrato.');
            }

            $pessoa = (ContratoPessoa::where('contrato_id', '=', $contrato->id)->orderby('percentual')->load())[$pessoaCount - 1];

            if ($pessoaCount > 1) {
                $pessoasDesc = $pessoa->cliente->nome . " e outros";
            } else {
                $pessoasDesc = $pessoa->cliente->nome;
            }

            $pageParam = [
                'escritorio_id' => $contrato->escritorio_id,
                'contrato_id' => $contrato->id,
                'pessoa_id' => $pessoa->cliente_id,
                'profissional_id' => $profissional->id ?? null,
                'contrato_parcela_id' => $contratoParcela->id,

                /*
                * Aqui agora vai o valor disponível:
                * - status 1: valor original
                * - status 2: saldo
                */
                'valor' => $valorDisponivel,

                'dt' => $contratoParcela->data_pagamento ?? null,
                'desc' => "Contrato ".$contrato->area->nome." #$contrato->numero - $pessoasDesc.",
                'quant_parcela' => $contratoParcela->numero_parcelas,
                'profissionais_json' => json_encode($profissionaisRepasse),
                'valores_profissionais_json' => json_encode($valoresProfissionais),
                'repasses_profissionais_json' => json_encode($percentuaisRepasse)
            ];

            TTransaction::close();

            TApplication::loadPage('ModalContratoGerarFinanceiro', 'onShow', $pageParam);
        }

            //</autoCode>

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

            $podeGerar = false;

            $toFloat = function($valor) {
                if ($valor === null || $valor === '') {
                    return 0;
                }

                if (is_numeric($valor)) {
                    return (float) $valor;
                }

                $valor = str_replace(['R$', ' '], '', (string) $valor);
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);

                return (float) $valor;
            };

            $pessoa = Pessoa::where('system_users_id', '=', TSession::getValue('userid'))->first();

            if ($pessoa) 
            {
                $pessoaGrupo = PessoaGrupo::where('pessoa_id', '=', $pessoa->id)
                    ->where('grupo_id', '=', Grupo::PROFISSIONAL)
                    ->count();

                if ($pessoaGrupo > 0)
                {
                    $statusId = (int) ($object->status_contrato_pagamento_id ?? 1);

                    /*
                    * 1 = Em Aberto
                    * 2 = Gerado com Saldo
                    * 3 = Gerado
                    */
                    if (in_array($statusId, [1, 2]))
                    {
                        $valor = $toFloat($object->valor ?? 0);
                        $saldo = $toFloat($object->saldo ?? 0);

                        if ($statusId === 1 && $valor > 0) {
                            $podeGerar = true;
                        }

                        if ($statusId === 2 && $saldo > 0) {
                            $podeGerar = true;
                        }

                    }
                }
            }

            TTransaction::close();

            return $podeGerar;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onDelete($param = null) 
    {
        if(isset($param['delete']) && $param['delete'] == 1)
        {
            try
            {
                // get the paramseter $key
                $key = (int) $param['key'];
                // open a transaction with database
                TTransaction::open(self::$database);

                // instantiates object
                $object = new Contrato($key, FALSE);

                if($object->loadComposite('ContratoProcesso', 'contrato_id', $object->id)){
                    throw new Exception("Esse contrato está vinculado a um processo e não pode ser removido!");
                }

                $object->deleteComposite('ContratoPessoa', 'contrato_id', $object->id);

                // deletes the object from the database
                $object->delete();

                // close the transaction
                TTransaction::close();

                TApplication::loadPage('ContratoList', 'onShow');
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

    public function onShow($param = null)
    {     

    try {
        $permitidos = [1, 3, 4, 5]; // IDs do SystemUser
        $userid = (int) TSession::getValue('userid');

        if (!in_array($userid, $permitidos, true)) {
            if (isset($this->tab_68e80abe3a91f) && $this->tab_68e80abe3a91f) {
                $this->tab_68e80abe3a91f->style = 'display:none';
            }
        }
    } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
    }

        TTransaction::open(self::$database);
        $contratoDocumento = ContratoDocumento::where('contrato_id','=',$param['key'])->count();
        if($contratoDocumento>0){
            TScript::create("$(\"[name='btnGerarDocumento']\").closest('.fb-inline-field-container').hide()");
        }
        TTransaction::close();
    }

    public static function onGerarDoc($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $contrato = Contrato::find((int)$param['key']);

            $contratoRepres = ContratoRepresentante::where('contrato_id','=',$contrato->id)->orderby('id')->first();
            if(!$contratoRepres){
                $param['repres'] = 0;
            }
            if(isset($param['repres']))
            {

                $contrato = Contrato::find((int)$param['key']);

                if($param['repres']==1){
                    $cliente_id = (ContratoRepresentante::where('contrato_id','=',$contrato->id)->orderby('id')->first())->representante_id;
                }else{
                    $cliente_id = (ContratoPessoa::where('contrato_id','=',$contrato->id)->orderby('id')->first())->cliente_id;
                }

                $repasses = ContratoRepasse::where('contrato_id','=',(int) $contrato->id)->orderby('id')->load();
                foreach($repasses as $repasse){
                    $grupo = PessoaGrupo::where('pessoa_id','=',$repasse->pessoa_id)->where('grupo_id','=',Grupo::PROFISSIONAL)->first();
                    if($grupo){
                        $profissional = Pessoa::find($repasse->pessoa_id);
                        break;
                    }
                }

                $documentosObrigatorios = DocumentoBaseContrato::where('area_id','=',$contrato->area_id)->load();
                foreach($documentosObrigatorios as $documentoObrigatorio){

                    $modeloDocumento = ModeloDocumento::find($documentoObrigatorio->modelo_documento_id);

                    //VERIFICAR OBRIGATORIEDADES
                    $serviceParam = [
                        'modelo_documento_id' => $modeloDocumento->id,
                        'cliente_id' => $cliente_id,
                        'profissional_id' => $profissional->id,
                        'contrato_id' => $contrato->id
                    ];

                    $validarDados = ModeloDocumentoService::validarDadosObriatoriosDocumento($serviceParam);

                    if($validarDados!==""){
                        throw new Exception($validarDados);
                    }

                    $returnParam = ModeloDocumentoService::preencherDocumento($serviceParam);

                    $contratoDocumento = new ContratoDocumento();
                    $contratoDocumento->modelo_documento_id = $modeloDocumento->id;
                    $contratoDocumento->autenticador = $returnParam['autenticador'];
                    $contratoDocumento->contrato_id = $returnParam['complemento_id'];
                    $contratoDocumento->dt_preenchimento = date('Y-m-d H:i:s');
                    $contratoDocumento->filename = $returnParam['novo_nome_arquivo'];
                    $contratoDocumento->criacao_user_id = TSession::getValue('userid');
                    $contratoDocumento->store();
                }

                TApplication::loadPage(__CLASS__, 'onShow', ['key' => $contrato->id]);

                TTransaction::close();
            }
            else
            {

                // define the delete action
                $actionRepres = new TAction(array('ContratoFormView', 'onGerarDoc'));
                $actionRepres->setParameters($param); // pass the key paramseter ahead
                $actionRepres->setParameter('repres', 1);

                $actionPessoa = new TAction(array('ContratoFormView', 'onGerarDoc'));
                $actionPessoa->setParameters($param); // pass the key paramseter ahead
                $actionPessoa->setParameter('repres', 0);
                // shows a dialog to the user
                new TQuestion('Utilizar o representante nos documentos?', $actionRepres, $actionPessoa);   
            }
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

