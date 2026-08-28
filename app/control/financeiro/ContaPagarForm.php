<?php

class ContaPagarForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Conta';
    private static $primaryKey = 'id';
    private static $formName = 'form_Conta';

    use BuilderMasterDetailFieldListTrait;

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

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Cadastro de conta a pagar");

        $criteria_tipo_documento_financeiro_id = new TCriteria();
        $criteria_escritorio_id = new TCriteria();
        $criteria_pessoa_id = new TCriteria();
        $criteria_cliente_id = new TCriteria();
        $criteria_categoria_conta_id = new TCriteria();
        $criteria_tipo_pagamento = new TCriteria();
        $criteria_lancamento_conta_tipo_pagamento_id = new TCriteria();

        $filterVar = [TipoConta::AMBOS,TipoConta::PAGAR];
        $criteria_tipo_documento_financeiro_id->add(new TFilter('tipo_conta_id', 'in', $filterVar)); 
        $filterVar = TipoDocFinanceiroPadrao::ATENDIMENTO;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('padrao_id', '!=', $filterVar)); 
        $filterVar = TipoDocFinanceiroPadrao::CONTRATO;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('padrao_id', '!=', $filterVar)); 
        $filterVar = TipoDocFinanceiroPadrao::PROCESSO;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('padrao_id', '!=', $filterVar)); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_escritorio_id->add(new TFilter('system_unit_id', '=', $filterVar)); 
        $filterVar = Grupo::FORNECEDOR;
        $criteria_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = TipoConta::PAGAR;
        $criteria_categoria_conta_id->add(new TFilter('tipo_conta_id', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_lancamento_conta_tipo_pagamento_id->add(new TFilter('ativo', '=', $filterVar)); 

        if (!empty($param['key']))
        {                        
            $id = (int) $param['key'];
            $criteria_categoria_conta_id->add(new TFilter("tipo_conta_id", '=', "(SELECT tipo_conta_id FROM conta WHERE id = {$id})"));
        }
        else if ($param['method'] == 'onAddReceber')
        {
            $criteria_categoria_conta_id->add(new TFilter("tipo_conta_id", '=', TipoConta::RECEBER));
        }                                                                                                                                                                             
        else if ($param['method'] == 'onAddPagar')
        {
            $criteria_categoria_conta_id->add(new TFilter("tipo_conta_id", '=', TipoConta::PAGAR));
        }

        $id = new TEntry('id');
        $tipo_conta_id = new THidden('tipo_conta_id');
        $tipo_documento_financeiro_id = new TDBCombo('tipo_documento_financeiro_id', 'escritorio', 'TipoDocumentoFinanceiro', 'id', '{nome}','nome asc' , $criteria_tipo_documento_financeiro_id );
        $numero_documento = new TEntry('numero_documento');
        $escritorio_id = new TDBCombo('escritorio_id', 'escritorio', 'Escritorio', 'id', '{nome}','nome asc' , $criteria_escritorio_id );
        $pessoa_id = new TDBUniqueSearch('pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_pessoa_id );
        $btnAddNewFornecedor = new TButton('btnAddNewFornecedor');
        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $categoria_conta_id = new TDBCombo('categoria_conta_id', 'escritorio', 'CategoriaConta', 'id', '{nome}','nome asc' , $criteria_categoria_conta_id );
        $descricao = new TEntry('descricao');
        $tipo = new TRadioGroup('tipo');
        $valor = new TNumeric('valor', '2', ',', '.', true, true );
        $tipo_pagamento = new TDBCombo('tipo_pagamento', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_tipo_pagamento );
        $data_vencimento = new TDate('data_vencimento');
        $repetir_ate_final_ano = new TCheckButton('repetir_ate_final_ano');
        $total_conta = new TNumeric('total_conta', '2', ',', '.', true, true );
        $dataVencPadrao = new TDate('dataVencPadrao');
        $total_parcelas = new TSpinner('total_parcelas');
        $btnGerarParcelas = new TButton('btnGerarParcelas');
        $btnEditarParcelas = new TButton('btnEditarParcelas');
        $btnCancelarParcelas = new TButton('btnCancelarParcelas');
        $lancamento_conta_id = new TEntry('lancamento_conta_id[]');
        $lancamento_conta___row__id = new THidden('lancamento_conta___row__id[]');
        $lancamento_conta___row__data = new THidden('lancamento_conta___row__data[]');
        $lancamento_conta_parcela = new TEntry('lancamento_conta_parcela[]');
        $lancamento_conta_valor_total = new TNumeric('lancamento_conta_valor_total[]', '2', ',', '.', true, true );
        $lancamento_conta_dt_vencimento = new TDate('lancamento_conta_dt_vencimento[]');
        $lancamento_conta_tipo_pagamento_id = new TDBCombo('lancamento_conta_tipo_pagamento_id[]', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_lancamento_conta_tipo_pagamento_id );
        $lancamento_conta_dt_pagamento = new TDate('lancamento_conta_dt_pagamento[]');
        $this->parcelas = new TFieldList();
        $total_valor_parcelas = new TNumeric('total_valor_parcelas', '2', ',', '.' );
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->parcelas->addField(null, $lancamento_conta_id, []);
        $this->parcelas->addField(null, $lancamento_conta___row__id, ['uniqid' => true]);
        $this->parcelas->addField(null, $lancamento_conta___row__data, []);
        $this->parcelas->addField(new TLabel("", null, '12px', null), $lancamento_conta_id, ['width' => '10%']);
        $this->parcelas->addField(new TLabel("Parcela", null, '12px', null), $lancamento_conta_parcela, ['width' => '10%']);
        $this->parcelas->addField(new TLabel("Valor", null, '12px', null), $lancamento_conta_valor_total, ['width' => '22%']);
        $this->parcelas->addField(new TLabel("Data vencimento", null, '12px', null), $lancamento_conta_dt_vencimento, ['width' => '22%']);
        $this->parcelas->addField(new TLabel("Tipo de pagamento", null, '12px', null), $lancamento_conta_tipo_pagamento_id, ['width' => '22%']);
        $this->parcelas->addField(new TLabel("Data de pagamento", null, '12px', null), $lancamento_conta_dt_pagamento, ['width' => '100%']);

        $this->parcelas->width = '100%';
        $this->parcelas->setFieldPrefix('lancamento_conta');
        $this->parcelas->name = 'parcelas';

        $this->criteria_parcelas = new TCriteria();
        $this->default_item_parcelas = new stdClass();

        $this->form->addField($lancamento_conta_id);
        $this->form->addField($lancamento_conta___row__id);
        $this->form->addField($lancamento_conta___row__data);
        $this->form->addField($lancamento_conta_id);
        $this->form->addField($lancamento_conta_parcela);
        $this->form->addField($lancamento_conta_valor_total);
        $this->form->addField($lancamento_conta_dt_vencimento);
        $this->form->addField($lancamento_conta_tipo_pagamento_id);
        $this->form->addField($lancamento_conta_dt_pagamento);

        $this->parcelas->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $tipo_documento_financeiro_id->setChangeAction(new TAction([$this,'onSelectTipoDoc']));
        $tipo->setChangeAction(new TAction([$this,'onChange']));

        $lancamento_conta_valor_total->setExitAction(new TAction([$this,'onChangeValor']));

        $categoria_conta_id->addValidation("Categoria", new TRequiredValidator()); 
        $descricao->addValidation("Descrição", new TRequiredValidator()); 
        $total_parcelas->addValidation("Total de parcelas", new TRequiredValidator()); 

        $descricao->forceUpperCase();
        $tipo->addItems(["S"=>"Simples","P"=>"Parcelada","R"=>"Recorrente"]);
        $tipo->setLayout('horizontal');
        $tipo->setUseButton();
        $repetir_ate_final_ano->setUseSwitch(true, 'blue');
        $repetir_ate_final_ano->setIndexValue("1");
        $total_parcelas->setRange(1, 2000, 1);
        $lancamento_conta_tipo_pagamento_id->setDefaultOption(false);
        $pessoa_id->setMinLength(3);
        $cliente_id->setMinLength(3);

        $escritorio_id->enableSearch();
        $tipo_pagamento->enableSearch();
        $tipo_documento_financeiro_id->enableSearch();

        $tipo->setValue('S');
        $total_parcelas->setValue('1');
        $total_valor_parcelas->setValue('0,00');

        $btnGerarParcelas->setAction(new TAction([$this, 'onGerarParcelas']), "Atualizar parcelas");
        $btnAddNewFornecedor->setAction(new TAction(['FornecedorForm', 'onShow'],['page' => 'ContaPagar']), "");
        $btnCancelarParcelas->setAction(new TAction(['ModalCancelarParcelas', 'onShow'],['conta_id' => $param['key'] ?? null,"page" => "ContaPagarForm"]), "Cancelar parcelas");
        $btnEditarParcelas->setAction(new TAction(['ModalEditarParcelasAberto', 'onShow'],['conta_id' => $param['key'] ?? null,"total_conta" => $param['total_conta'] ?? null,"page" => "ContaPagarForm"]), "Editar Parcelas");

        $btnGerarParcelas->addStyleClass('btn-default');
        $btnEditarParcelas->addStyleClass('btn-default');
        $btnAddNewFornecedor->addStyleClass('btn-default');
        $btnCancelarParcelas->addStyleClass('btn-default');

        $btnGerarParcelas->setImage('fas:cog #03A9F4');
        $btnEditarParcelas->setImage('fas:edit #000000');
        $btnAddNewFornecedor->setImage('fas:plus #000000');
        $btnCancelarParcelas->setImage('fas:times-circle #000000');

        $dataVencPadrao->setDatabaseMask('yyyy-mm-dd');
        $data_vencimento->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $lancamento_conta_dt_pagamento->setDatabaseMask('yyyy-mm-dd');
        $lancamento_conta_dt_vencimento->setDatabaseMask('yyyy-mm-dd');

        $cliente_id->setMask('{nome}');
        $dataVencPadrao->setMask('dd/mm/yyyy');
        $pessoa_id->setMask('{nome_formatado}');
        $data_vencimento->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $lancamento_conta_dt_pagamento->setMask('dd/mm/yyyy');
        $lancamento_conta_dt_vencimento->setMask('dd/mm/yyyy');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $lancamento_conta_id->setEditable(false);
        $total_valor_parcelas->setEditable(false);
        $modificacao_user_name->setEditable(false);
        $lancamento_conta_parcela->setEditable(false);
        $lancamento_conta_valor_total->setEditable(false);

        $id->setSize('100%');
        $tipo->setSize('100%');
        $valor->setSize('100%');
        $descricao->setSize('100%');
        $tipo_conta_id->setSize(200);
        $cliente_id->setSize('100%');
        $total_conta->setSize('100%');
        $dataVencPadrao->setSize(210);
        $data_vencimento->setSize(150);
        $data_criacao->setSize('100%');
        $escritorio_id->setSize('100%');
        $tipo_pagamento->setSize('100%');
        $numero_documento->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $categoria_conta_id->setSize('100%');
        $lancamento_conta_id->setSize('100%');
        $total_valor_parcelas->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $pessoa_id->setSize('calc(100% - 50px)');
        $lancamento_conta_parcela->setSize('100%');
        $tipo_documento_financeiro_id->setSize('100%');
        $total_parcelas->setSize('calc(100% - 150px)');
        $lancamento_conta_valor_total->setSize('100%');
        $lancamento_conta_dt_pagamento->setSize('100%');
        $lancamento_conta_dt_vencimento->setSize('100%');
        $lancamento_conta_tipo_pagamento_id->setSize('100%');


        $this->parcelas->class = ' tfieldlist';

        $lancamento_conta_valor_total->setEditable(true);
        $lancamento_conta_parcela->setEditable(false);

        // Deixa editável no PHP, porque o JS trava só a linha vazia
        $lancamento_conta_dt_vencimento->setEditable(true);
        $lancamento_conta_tipo_pagamento_id->setEditable(true);

        $total_parcelas->style = 'text-align: right';

        // O mesmo botão decide se atualiza parcelas ou recorrências
        $btnGerarParcelas->setAction(new TAction([$this, 'onAtualizarParcelasOuRecorrencias']), "Atualizar");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '12px', null, '100%'),$id,$tipo_conta_id],[new TLabel("Tipo de documento:", '#FF0000', '12px', null, '100%'),$tipo_documento_financeiro_id],[new TLabel("Número do documento:", '#FF0000', '12px', null, '100%'),$numero_documento],[new TLabel("Escritório:", null, '12px', null, '100%'),$escritorio_id]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Fornecedor:", '#ff0000', '12px', null, '100%'),$pessoa_id,$btnAddNewFornecedor]);
        $row2->layout = [' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Cliente:", '#FF0000', '12px', null, '100%'),$cliente_id]);
        $row3->layout = [' col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Categoria:", '#ff0000', '12px', null, '100%'),$categoria_conta_id],[new TLabel("Descrição:", '#ff0000', '12px', null, '100%'),$descricao]);
        $row4->layout = [' col-sm-6',' col-sm-6'];

        $row5 = $this->form->addFields([new TLabel("Tipo de conta:", null, '12px', null, '100%'),$tipo]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addFields([new TLabel("Valor:", '#FF0000', '12px', null, '100%'),$valor],[new TLabel("Tipo de pagamento:", '#FF0000', '12px', null, '100%'),$tipo_pagamento],[new TLabel("Data de vencimento:", '#FF0000', '12px', null, '100%'),$data_vencimento,new TLabel("Repetir até o final do ano:", null, '12px', null),$repetir_ate_final_ano]);
        $row6->layout = ['col-sm-3',' col-sm-3',' col-sm-6'];

        $row7 = $this->form->addFields([new TLabel("Total:", '#ff0000', '12px', null, '100%'),$total_conta],[new TLabel("Data de Vencimento", null, '14px', null, '100%'),$dataVencPadrao],[new TLabel("Total de parcelas:", '#ff0000', '12px', null, '100%'),$total_parcelas,$btnGerarParcelas],[new TLabel(" ", null, '12px', null, '100%'),$btnEditarParcelas,$btnCancelarParcelas]);
        $row7->layout = ['col-sm-3','col-sm-2','col-sm-3','col-sm-3'];

        $bcontainer_62cf238b6bca9 = new BContainer('bcontainer_62cf238b6bca9');
        $this->bcontainer_62cf238b6bca9 = $bcontainer_62cf238b6bca9;

        $bcontainer_62cf238b6bca9->setTitle("Parcelas", '#333', '12px', '', '#fff');
        $bcontainer_62cf238b6bca9->setBorderColor('#c0c0c0');

        $row8 = $bcontainer_62cf238b6bca9->addFields([new TLabel("Motivo do cancelamento das parcelas:", null, '10px', null, '100%'),new TLabel("MOTIVO CANCELAMENTO", null, '12px', null, '100%')],[$this->parcelas],[$total_valor_parcelas]);
        $row8->layout = ['col-sm-6','col-sm-12','col-sm-3'];

        $row9 = $this->form->addFields([$bcontainer_62cf238b6bca9]);
        $row9->layout = [' col-sm-9'];

        $row10 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row11 = $this->form->addFields([new TLabel("Criado em:", null, '12px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '12px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '12px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '12px', null, '100%'),$modificacao_user_name]);
        $row11->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];


        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ContaPagarList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TScript::create("$(\"[name='total_valor_parcelas']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='btnEditarParcelas']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='btnCancelarParcelas']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$('label:contains(\"Motivo do cancelamento das parcelas:\")').hide();");
        TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').hide();");

        TScript::create("$(\"[name='cliente_id']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$('label:contains(\"Cliente:\")').hide();");

       TScript::create("
        window.contaPagarSetTextoBotaoAtualizar = function(texto) {
            var btn = $('[name=\"btnGerarParcelas\"]');

            if (!btn.length) {
                return;
            }

            var icon = btn.find('i').first().clone();

            btn.empty();

            if (icon.length) {
                btn.append(icon).append(' ');
            }

            btn.append(texto);
        };

            window.contaPagarValorTelaParaNumero = function(valor) {
            valor = valor || '0';
            valor = valor.toString();

            valor = valor.replace(/R\$/g, '');
            valor = valor.replace(/\s/g, '');
            valor = valor.replace(/\./g, '');
            valor = valor.replace(',', '.');

            var numero = parseFloat(valor);

            if (isNaN(numero)) {
                return 0;
            }

            return numero;
        };

        window.contaPagarNumeroParaValorTela = function(numero) {
            numero = parseFloat(numero || 0);

            return numero.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        window.contaPagarAtualizarTotalRecorrenteTela = function() {
            var tipo = $('[name=\"tipo\"]:checked').val();

            if (tipo != 'R' || window.contaPagarModoEdicao !== true) {
                return;
            }

            var total = 0;

            $('[name=\"lancamento_conta_valor_total[]\"]').each(function() {
                total += window.contaPagarValorTelaParaNumero($(this).val());
            });

            var totalFormatado = window.contaPagarNumeroParaValorTela(total);

            $('[name=total_conta]').val(totalFormatado);
            $('[name=total_valor_parcelas]').val(totalFormatado);
        };

        $(document).off('keyup.totalRecorrentePagar change.totalRecorrentePagar blur.totalRecorrentePagar');

        $(document).on(
            'keyup.totalRecorrentePagar change.totalRecorrentePagar blur.totalRecorrentePagar',
            '[name=\"lancamento_conta_valor_total[]\"]',
            function() {
                setTimeout(function() {
                    if (typeof window.contaPagarAtualizarTotalRecorrenteTela === 'function') {
                        window.contaPagarAtualizarTotalRecorrenteTela();
                    }
                }, 80);
            }
        );

        window.contaPagarAjustarTextosGrid = function(tipo) {
            var rowGrid = $('[name=\"lancamento_conta_valor_total[]\"]').closest('.bContainer-fieldset').closest('.tformrow');
            var fieldset = $('[name=\"lancamento_conta_valor_total[]\"]').closest('.bContainer-fieldset');

            if (tipo == 'R') {
                fieldset.find('legend').first().text('Recorrência');

                rowGrid.find('label, th, span').each(function() {
                    var txt = $.trim($(this).text());

                    if (txt == 'Parcela') {
                        $(this).text('Conta');
                    }
                });
            } else {
                fieldset.find('legend').first().text('Parcelas');

                rowGrid.find('label, th, span').each(function() {
                    var txt = $.trim($(this).text());

                    if (txt == 'Conta') {
                        $(this).text('Parcela');
                    }
                });
            }
        };

        window.ajustarTelaTipoContaPagar = function(tipo) {
            if (!tipo) {
                tipo = $('[name=\"tipo\"]:checked').val();
            }

            var rowSimples = $('[name=\"valor\"]').closest('.tformrow');
            var rowParcelada = $('[name=\"total_conta\"]').closest('.tformrow');
            var rowGrid = $('[name=\"lancamento_conta_valor_total[]\"]').closest('.bContainer-fieldset').closest('.tformrow');
            var rowTotalGrid = $('[name=\"total_valor_parcelas\"]').closest('.tformrow');

            var repetirBox = $('[name=\"repetir_ate_final_ano\"]').closest('.fb-inline-field-container');

            var repetirLabel = $('label, span').filter(function() {
                var txt = $.trim($(this).text());
                return txt == 'Repetir até o final do ano:';
            });

            var colTotalConta = $('[name=\"total_conta\"]').closest('div[class*=\"col-\"]');
            var colDataPadrao = $('[name=\"dataVencPadrao\"]').closest('div[class*=\"col-\"]');
            var colTotalParcelas = $('[name=\"total_parcelas\"]').closest('div[class*=\"col-\"]');
            var colBotoesExtras = $('[name=\"btnEditarParcelas\"]').closest('div[class*=\"col-\"]');

            var boxBotaoAtualizar = $('[name=\"btnGerarParcelas\"]').closest('.fb-inline-field-container');
            var boxTotalParcelas = $('[name=\"total_parcelas\"]').closest('.fb-inline-field-container');
            var boxTotalValorParcelas = $('[name=\"total_valor_parcelas\"]').closest('.fb-inline-field-container');

            boxTotalValorParcelas.hide();

            if (window.contaPagarMostrarBotoesEdicao !== true) {
                colBotoesExtras.hide();
            }

            if (tipo == 'S') {
                rowSimples.show();
                rowParcelada.hide();
                rowGrid.hide();
                rowTotalGrid.hide();

                repetirBox.show();
                repetirLabel.show();
                boxBotaoAtualizar.hide();

                window.contaPagarAjustarTextosGrid('S');
                window.contaPagarSetTextoBotaoAtualizar('Atualizar parcelas');
            }

            if (tipo == 'P') {
                rowSimples.hide();
                rowParcelada.show();
                rowGrid.show();
                rowTotalGrid.show();

                repetirBox.hide();
                repetirLabel.hide();

                colTotalConta.show();
                colDataPadrao.show();
                colTotalParcelas.show();

                boxBotaoAtualizar.show();

                if (colDataPadrao.length && colTotalParcelas.length) {
                    colDataPadrao.insertAfter(colTotalParcelas);
                }

                if (boxTotalParcelas.length && boxBotaoAtualizar.length) {
                    boxBotaoAtualizar.insertAfter(boxTotalParcelas);
                }

                colTotalParcelas.find('label').first().text('Total de parcelas:');

                window.contaPagarAjustarTextosGrid('P');
                window.contaPagarSetTextoBotaoAtualizar('Atualizar parcelas');
            }

            if (tipo == 'R') {
                rowSimples.show();
                rowParcelada.show();
                rowGrid.show();
                rowTotalGrid.show();

                repetirBox.hide();
                repetirLabel.hide();

               if (window.contaPagarModoEdicao === true) {
                colTotalConta.show();

                $('[name=valor]')
                    .closest('.fb-inline-field-container')
                    .find('label')
                    .first()
                    .text('Valor único:');

                $('[name=total_conta]')
                    .closest('.fb-inline-field-container')
                    .find('label')
                    .first()
                    .text('Valor total:');

                $('[name=btnEditarParcelas]').closest('.fb-inline-field-container').hide();

                setTimeout(function() {
                    if (typeof window.contaPagarAtualizarTotalRecorrenteTela === 'function') {
                        window.contaPagarAtualizarTotalRecorrenteTela();
                    }
                }, 150);
            } else {
                colTotalConta.hide();

                $('[name=valor]')
                    .closest('.fb-inline-field-container')
                    .find('label')
                    .first()
                    .text('Valor:');

                $('[name=total_conta]')
                    .closest('.fb-inline-field-container')
                    .find('label')
                    .first()
                    .text('Total:');
            }

            colDataPadrao.hide();
            colTotalParcelas.show();

                boxBotaoAtualizar.show();

                if (boxTotalParcelas.length && boxBotaoAtualizar.length) {
                    boxBotaoAtualizar.insertAfter(boxTotalParcelas);
                }

                colTotalParcelas.find('label').first().text('Total de repetições:');

                window.contaPagarAjustarTextosGrid('R');
                window.contaPagarSetTextoBotaoAtualizar('Atualizar recorrência');
            }
        };

        $(document).off('change.tipoContaPagarVisual');
        $(document).on('change.tipoContaPagarVisual', '[name=\"tipo\"]', function() {
            var tipoSelecionado = $(this).val();

            setTimeout(function() {
                window.ajustarTelaTipoContaPagar(tipoSelecionado);
            }, 300);

            setTimeout(function() {
                window.ajustarTelaTipoContaPagar(tipoSelecionado);
            }, 900);
        });

        setTimeout(function() {
            window.ajustarTelaTipoContaPagar();
        }, 500);

    ");

        TScript::create("
            window.ajustarEdicaoParcelasContaPagar = function() {
                setTimeout(function() {
                    $('[name=\"lancamento_conta_dt_vencimento[]\"]').each(function() {
                        var row = $(this).closest('tr');

                        var id = row.find('[name=\"lancamento_conta_id[]\"]').val();
                        var parcela = row.find('[name=\"lancamento_conta_parcela[]\"]').val();
                        var valor = row.find('[name=\"lancamento_conta_valor_total[]\"]').val();

                        var dtPagamento = row.find('[name=\"lancamento_conta_dt_pagamento[]\"]').val();
                        var linhaQuitada = dtPagamento && $.trim(dtPagamento) !== '';

                        var idsQuitados = window.contaPagarLancamentosQuitados || [];
                        var idNumerico = parseInt(id, 10);

                        if (!isNaN(idNumerico) && idsQuitados.indexOf(idNumerico) !== -1) {
                            linhaQuitada = true;
                        }

                        var linhaReal = false;

                        if (id || parcela || valor) {
                            linhaReal = true;
                        }

                        var campoVencimento = row.find('[name=\"lancamento_conta_dt_vencimento[]\"]');
                        var campoTipo = row.find('[name=\"lancamento_conta_tipo_pagamento_id[]\"]');
                        var campoValor = row.find('[name=\"lancamento_conta_valor_total[]\"]');

                        var grupoVencimento = campoVencimento.closest('.input-group');
                        var botaoCalendario = grupoVencimento.find('button, .input-group-addon, .input-group-text');

                        var containerSelect2 = campoTipo.next('.select2-container');

                        if (linhaReal && !linhaQuitada) {
                         campoValor
                                .prop('readonly', false)
                                .prop('disabled', false)
                                .removeAttr('readonly')
                                .removeAttr('disabled')
                                .css({
                                    'background-color': '',
                                    'pointer-events': '',
                                    'cursor': ''
                                });
                            campoVencimento
                                .prop('readonly', false)
                                .prop('disabled', false)
                                .removeAttr('readonly')
                                .removeAttr('disabled')
                                .css({
                                    'background-color': '',
                                    'pointer-events': '',
                                    'cursor': ''
                                });

                            botaoCalendario.css({
                                'pointer-events': 'auto',
                                'opacity': '1',
                                'cursor': 'pointer'
                            });

                            campoTipo
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .trigger('change');

                            containerSelect2
                                .removeClass('select2-container--disabled')
                                .css({
                                    'pointer-events': 'auto',
                                    'opacity': '1',
                                    'cursor': ''
                                });

                            containerSelect2.find('.select2-selection').css({
                                'background-color': '',
                                'cursor': ''
                            });
                        } else {
                            campoValor
                                .prop('readonly', true)
                                .css({
                                    'background-color': '#f5f5f5',
                                    'pointer-events': 'none',
                                    'cursor': 'not-allowed'
                                });
                            campoVencimento
                                .prop('readonly', true)
                                .css({
                                    'background-color': '#f5f5f5',
                                    'pointer-events': 'none',
                                    'cursor': 'not-allowed'
                                });

                            botaoCalendario.css({
                                'pointer-events': 'none',
                                'opacity': '0.6',
                                'cursor': 'not-allowed'
                            });

                            campoTipo
                                .prop('disabled', false)
                                .removeAttr('disabled')
                                .trigger('change');

                            containerSelect2
                                .addClass('select2-container--disabled')
                                .css({
                                    'pointer-events': 'none',
                                    'opacity': '0.8',
                                    'cursor': 'not-allowed'
                                });

                            containerSelect2.find('.select2-selection').css({
                                'background-color': '#f5f5f5',
                                'cursor': 'not-allowed'
                            });
                        }
                    });
                }, 300);
            };

            ajustarEdicaoParcelasContaPagar();
        ");

        TScript::create("
            window.contaPagarPrepararRecorrenteParaSalvar = function(e) {
                var tipo = $('[name=\"tipo\"]:checked').val();

                if (tipo != 'R') {
                    return true;
                }

                var temLinhaReal = false;

                $('[name=\"lancamento_conta_parcela[]\"]').each(function() {
                    if ($(this).val()) {
                        temLinhaReal = true;
                    }
                });

                if (!temLinhaReal) {
                    alert('Clique em Atualizar recorrência antes de salvar.');
                    if (e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                    return false;
                }

                return true;
            };

            document.addEventListener('click', function(e) {
                var el = e.target.closest('button, a, input[type=\"button\"], input[type=\"submit\"]');

                if (!el) {
                    return true;
                }

                var texto = '';

                if (el.innerText) {
                    texto = el.innerText.trim();
                } else if (el.value) {
                    texto = el.value.trim();
                }

                if (texto == 'Salvar') {
                    return window.contaPagarPrepararRecorrenteParaSalvar(e);
                }

                return true;
            }, true);
        ");

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ContaPagarForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChangeValor($param = null) 
    {
        try 
        {
            //code here

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onSelectTipoDoc($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if($param['tipo_documento_financeiro_id']){
                $tipoDoc = TipoDocumentoFinanceiro::find((int) $param['tipo_documento_financeiro_id']);

                $data = new stdClass();

                if($tipoDoc->gera_codigo == 'S'){
                    //Pesquisa a ultima conta com esse tipo de documento
                    $conta = Conta::where('tipo_documento_financeiro_id','=',$tipoDoc->id)
                                    ->orderBy('numero_documento')
                                    ->last();
                    //Se existir
                    if($conta){
                        //popula o numero com o ultimo somado de 1
                        $data->numero_documento = $conta->numero_documento +1;
                    }else{
                        //caso contrario, popula como 1
                        $data->numero_documento = 1;
                    }
                    //Desabilita a edição de número
                    TEntry::disableField(self::$formName, 'numero_documento');
                }else{
                    $data->numero_documento = $param['numero_documento'] ?? null;
                    TEntry::enableField(self::$formName, 'numero_documento');
                }

                if($tipoDoc->codigo == 'Cust'  || $tipoDoc->codigo == 'Rep'){

                    $data->pessoa_id = null;

                    TScript::create("$(\"[name='pessoa_id']\").closest('.fb-inline-field-container').hide()");
                    TScript::create("$(\"[name='btnAddNewFornecedor']\").hide()");
                    TScript::create("$('label:contains(\"Fornecedor:\")').hide();");

                    TScript::create("$(\"[name='cliente_id']\").closest('.fb-inline-field-container').show()");
                    TScript::create("$('label:contains(\"Cliente:\")').show();");
                }else{

                    $data->cliente_id = null;

                    TScript::create("$(\"[name='pessoa_id']\").closest('.fb-inline-field-container').show()");
                    TScript::create("$(\"[name='btnAddNewFornecedor']\").show()");
                    TScript::create("$('label:contains(\"Fornecedor:\")').show();");

                    TScript::create("$(\"[name='cliente_id']\").closest('.fb-inline-field-container').hide()");
                    TScript::create("$('label:contains(\"Cliente:\")').hide();");
                }

                TForm::sendData(self::$formName, $data);
            }
            TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChange($param = null) 
    {
        try 
        {
            if (empty($param['tipo']) || $param['tipo'] == 'S')
            {
                TScript::create("$('[name=valor]').closest('.tformrow').show();");
                TScript::create("$('[name=total_conta]').closest('.tformrow').hide();");
                TScript::create("$('[name*=lancamento_conta_valor').closest('.bContainer-fieldset').closest('.tformrow').hide();");
                TScript::create("$('[name=total_valor_parcelas').closest('.tformrow').hide();");
            }
            else
            {
                TScript::create("$('[name=valor]').closest('.tformrow').hide();");
                TScript::create("$('[name=total_conta]').closest('.tformrow').show();");
                TScript::create("$('[name*=lancamento_conta_valor').closest('.bContainer-fieldset').closest('.tformrow').show();");
                TScript::create("$('[name=total_valor_parcelas').closest('.tformrow').show();");
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onGerarParcelas($param = null) 
    {
    try 
    {
        TTransaction::open(self::$database);

        $id = (int) ($param['id'] ?? 0);

        if (!empty($id)) {
            if (self::existeLancamentoCancelado($id)) {
                TToast::show("error", "Lançamento cancelado, não é possível editar.", "topRight", "fas:info-circle");
                TTransaction::close();
                return;
            }

            if (self::contaEstaQuitada($id)) {
                throw new Exception('Conta quitada, não é possível editar.');
            }
        }

        if (empty($param['total_parcelas']) || empty($param['total_conta'])) {
            throw new Exception('Total da conta e total de parcelas são obrigatórios.');
        }

        $totalInformado = self::valorBrParaFloat($param['total_conta']);
        $totalParcelasInformado = (int) $param['total_parcelas'];

        if ($totalInformado <= 0) {
            throw new Exception('Total da conta deve ser maior que zero.');
        }

        if ($totalParcelasInformado <= 0) {
            throw new Exception('Total de parcelas deve ser maior que zero.');
        }

        $ids = [];
        $parcelas = [];
        $valores = [];
        $vencimentos = [];
        $tipos = [];
        $dtPagamentos = [];

        $totalQuitado = 0;
        $quantidadeQuitada = 0;

        if (!empty($id)) {
            $lancamentos = Lancamento::where('conta_id', '=', $id)
                ->orderBy('parcela')
                ->load();

            foreach ($lancamentos as $lancamento) {
                if ($lancamento->cancelado == 'S') {
                    continue;
                }

                if(!empty($lancamento->dt_pagamento)){
                    $quantidadeQuitada++;
                    $totalQuitado += self::valorBrParaFloat($lancamento->valor_total);

                    $ids[] = $lancamento->id;
                    $parcelas[] = $lancamento->parcela;
                    $valores[] = number_format(self::valorBrParaFloat($lancamento->valor_total), 2, ',', '.');
                    $vencimentos[] = self::dataParaTela($lancamento->dt_vencimento);
                    $tipos[] = $lancamento->tipo_pagamento_id;
                    $dtPagamentos[] = self::dataParaTela($lancamento->dt_pagamento);
                } else {
                    $lancamento->delete();
                }
            }
        }

        $totalAberto = $totalInformado - $totalQuitado;

        if ($totalAberto < 0) {
            throw new Exception('O total informado é menor que o valor das parcelas já quitadas.');
        }

        $parcelasAbertas = $totalParcelasInformado - $quantidadeQuitada;

        if ($parcelasAbertas <= 0) {
            throw new Exception('Não é possível alterar parcelas quitadas. Aumente o número de parcelas.');
        }

        if (!empty($param['dataVencPadrao'])) {
            $dataBaseVencimento = DateTime::createFromFormat('d/m/Y', $param['dataVencPadrao']);

            if (!$dataBaseVencimento) {
                $dataBaseVencimento = DateTime::createFromFormat('Y-m-d', $param['dataVencPadrao']);
            }

            if (!$dataBaseVencimento) {
                throw new Exception('Data de vencimento padrão inválida.');
            }
        } else {
            $dataBaseVencimento = new DateTime();
        }

        $valorParcela = round(($totalAberto / $parcelasAbertas), 2);
        $proximaParcela = empty($parcelas) ? 1 : (max($parcelas) + 1);

        $tipoPagamentoPadrao = $param['tipo_pagamento'] ?? null;

        if (empty($tipoPagamentoPadrao)) {
            $tipoPagamentoPadrao = TipoPagamento::BOLETO;
        }

        for ($i = 1; $i <= $parcelasAbertas; $i++) {
            $dataParcela = clone $dataBaseVencimento;
            $dataParcela->modify('+' . ($i - 1) . ' month');

            $valorAtual = ($i == $parcelasAbertas)
                ? ($totalAberto - ($valorParcela * ($i - 1)))
                : $valorParcela;

            $ids[] = '';
            $parcelas[] = $proximaParcela++;
            $valores[] = number_format($valorAtual, 2, ',', '.');
            $vencimentos[] = $dataParcela->format('d/m/Y');
            $tipos[] = $tipoPagamentoPadrao;
            $dtPagamentos[] = '';
        }

        if (!empty($id)) {
            $totalItens = count($parcelas);

            for ($i = 0; $i < $totalItens; $i++) {
                if (empty($vencimentos[$i]) || empty($valores[$i]) || empty($tipos[$i])) {
                    continue;
                }

                if (!empty($ids[$i])) {
                    $object = Lancamento::find($ids[$i]);

                    if ($object && !empty($object->dt_pagamento)) {
                        continue;
                    }
                }

                $object = new Lancamento();
                $object->conta_id = $id;
                $object->parcela = $parcelas[$i];
                $object->dt_vencimento = self::dataParaBanco($vencimentos[$i]);
                $object->valor = self::valorBrParaFloat($valores[$i]);
                $object->valor_total = $object->valor;
                $object->tipo_pagamento_id = $tipos[$i];
                $object->store();

                $ids[$i] = $object->id;
            }

            $totaisConta = self::recalcularContaPelosLancamentos($id);

            $totalTela = $totaisConta['total'];
            $totalParcelasTela = $totaisConta['quantidade'];
        } else {
            $totalTela = $totalInformado;
            $totalParcelasTela = $totalParcelasInformado;
        }

        $data = new stdClass;
        $data->tipo = 'P';
        $data->lancamento_conta_id = $ids;
        $data->lancamento_conta_parcela = $parcelas;
        $data->lancamento_conta_dt_vencimento = $vencimentos;
        $data->lancamento_conta_valor_total = $valores;
        $data->lancamento_conta_tipo_pagamento_id = $tipos;
        $data->lancamento_conta_dt_pagamento = $dtPagamentos;
        $data->total_conta = number_format($totalTela, 2, ',', '.');
        $data->total_parcelas = $totalParcelasTela;
        $data->total_valor_parcelas = number_format($totalTela, 2, ',', '.');
        $data->dataVencPadrao = $dataBaseVencimento->format('d/m/Y');

        TFieldList::clearRows('parcelas');

        if (count($parcelas) > 1) {
            TFieldList::addRows('parcelas', count($parcelas) - 1);
        }

        TForm::sendData(self::$formName, $data, false, false, 50 * count($parcelas));

        TScript::create("
            setTimeout(function() {
                $('[name=\"tipo\"][value=\"P\"]').prop('checked', true);

                if (typeof ajustarTelaTipoContaPagar === 'function') {
                    ajustarTelaTipoContaPagar('P');
                }

                if (typeof ajustarEdicaoParcelasContaPagar === 'function') {
                    ajustarEdicaoParcelasContaPagar();
                }
            }, " . ((50 * count($parcelas)) + 700) . ");
        ");

        TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;
            $cancelado = false;

            $this->form->validate(); // validate form data

            $object = new Conta(); // create an empty object 

            $data = $this->form->getData(); // get form data as array

            if(!$data->pessoa_id && $data->cliente_id){
                $data->pessoa_id = $data->cliente_id;
            }

            if (!empty($data->id) && self::contaEstaQuitada($data->id)) {
                throw new Exception('Conta quitada, não é possível editar.');
            }

            $object->fromArray( (array) $data); // load the object with data

            if(!$object->id)
            {
                $object->tipo_conta_id = TipoConta::PAGAR;
                $object->data_emissao = date('Y-m-d H:i:s');

                if(!$object->tipo_documento_financeiro_id){
                    throw new Exception("O campo Tipo de documento é obrigatório.");
                }

                if(!$object->numero_documento){
                    throw new Exception("O campo Número do documento é obrigatório.");
                }
                if(!$object->pessoa_id && !$object->cliente_id){
                    $tipoDoc = TipoDocumentoFinanceiro::find($object->tipo_documento_financeiro_id);
                    if($tipoDoc->codigo == 'Cust'  || $tipoDoc->codigo == 'Rep'){
                       throw new Exception("O campo Cliente é obrigatório."); 
                    }else{
                        throw new Exception("O campo Fornecedor é obrigatório.");
                    }
                }

            }else{
                $contaOriginal = new Conta($object->id);

                $lancamentos = Lancamento::where('conta_id','=',$object->id)->load();
                foreach($lancamentos as $lancamento){
                    if($lancamento->cancelado=='S'){
                        $cancelado = true;
                    }
                }

                /*$objeto = Conta::find($object->id);
                $object->tipo_documento_financeiro_id = $objeto->tipo_documento_financeiro_id;
                $object->numero_documento = $objeto->numero_documento;
                $object->atendimento_id = $objeto->atendimento_id;
                $object->contrato_id = $objeto->contrato_id;
                $object->pessoa_id = $objeto->pessoa_id;*/
                if($cancelado){
                    $object->total_conta = $contaOriginal->total_conta;
                    $object->total_parcelas = $contaOriginal->total_parcelas;
                }
            }

            if ($data->tipo == 'S' && !$cancelado)
            {
                $object->total_conta = $data->valor;
                $object->total_parcelas = 1;
            }

            if (empty($object->total_conta))
            {
                throw new Exception('Valor não foi preenchido');
            }

           if(!$data->id){
                if (($data->tipo == 'S' || $data->tipo == 'R') && !$cancelado){
                    $object->proximo_vencimento_lancamento = $data->data_vencimento;
                }else if ($data->tipo == 'P'){
                    if (!empty($data->dataVencPadrao)) {
                        $dataBase = DateTime::createFromFormat('d/m/Y', $data->dataVencPadrao);

                        if (!$dataBase) {
                            $dataBase = DateTime::createFromFormat('Y-m-d', $data->dataVencPadrao);
                        }

                        if ($dataBase) {
                            $object->proximo_vencimento_lancamento = $dataBase->format('Y-m-d');
                        } else {
                            $object->proximo_vencimento_lancamento = date('Y-m-d');
                        }
                    } else {
                        $object->proximo_vencimento_lancamento = date('Y-m-d');
                    }
                }
            }else{
                $lancamentosConta = Lancamento::where('conta_id','=',$data->id)->orderBy('dt_vencimento')->load();

                foreach($lancamentosConta as $lancamentoConta){
                    if(empty($lancamentoConta->dt_pagamento)){
                        $object->proximo_vencimento_lancamento = $lancamentoConta->dt_vencimento;
                        break;
                    }
                }
            }

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            if (!empty($data->tipo) && in_array($data->tipo, ['S', 'P', 'R'])) {
                $object->tipo_lancamento = $data->tipo;
            }

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            $objetos = Lancamento::where('conta_id','=',$object->id)->load();
            if($objetos)
            {
                foreach($objetos as $objeto){
                    if(!$objeto->dt_pagamento && $objeto->cancelado!='S'){
                        $objeto->delete();  
                    }
                }
            }

           if (($data->tipo == 'P' || $data->tipo == 'R') && !$cancelado) {
//<generatedAutoCode>
            $this->criteria_parcelas->setProperty('order', 'parcela asc');
//</generatedAutoCode>
            $lancamento_conta_items = $this->storeItems('Lancamento', 'conta_id', $object, $this->parcelas, function($masterObject, $detailObject){ 

                if (!empty($detailObject->id)) {
                    $lancamentoBanco = Lancamento::find($detailObject->id);

                    if($lancamentoBanco && ($lancamentoBanco->cancelado == 'S' || !empty($lancamentoBanco->dt_pagamento))){
                        $detailObject->parcela = $lancamentoBanco->parcela;
                        $detailObject->valor = $lancamentoBanco->valor;
                        $detailObject->valor_total = $lancamentoBanco->valor_total;
                        $detailObject->dt_vencimento = $lancamentoBanco->dt_vencimento;
                        $detailObject->tipo_pagamento_id = $lancamentoBanco->tipo_pagamento_id;
                        $detailObject->dt_pagamento = $lancamentoBanco->dt_pagamento;
                        $detailObject->cancelado = $lancamentoBanco->cancelado;
                        return;
                    }
                }

               if(isset($detailObject->valor_total)){
                    $detailObject->valor_total = self::valorBrParaFloat($detailObject->valor_total);

                    // parcela aberta ainda nao teve desconto nem acrescimo
                    $detailObject->valor = $detailObject->valor_total;
                }

                if (isset($detailObject->dt_vencimento)) {
                    $detailObject->dt_vencimento = self::dataParaBanco($detailObject->dt_vencimento);
                }

                if (isset($detailObject->dt_pagamento)) {
                    $detailObject->dt_pagamento = self::dataParaBanco($detailObject->dt_pagamento);
                }

            }, $this->criteria_parcelas); 
            }
            $this->total_valor_parcelas = 0;
            $this->count_parcelas = 1;

            if ($data->tipo == 'S' && !$cancelado)
            {
                if ($data->id)
                {
                    Lancamento::where('conta_id', '=', $data->id)->delete();
                }

                if (empty($data->valor) || empty($data->data_vencimento) || empty($data->tipo_pagamento))
                {
                    throw new Exception('Valor, data de vencimento e tipo de pagamento são obrigatórios');
                }

                $object->total_conta = $data->valor;
                $object->total_parcelas = 1;

                $lancamento = new Lancamento();
                $lancamento->dt_vencimento = $data->data_vencimento;
                $lancamento->valor = self::valorBrParaFloat($data->valor);
                $lancamento->valor_total = $lancamento->valor;
                $lancamento->tipo_pagamento_id = $data->tipo_pagamento;
                $lancamento->conta_id = $object->id;
                $lancamento->store();

                if (empty($data->id) && $data->repetir_ate_final_ano)
                {
                    $mes = date('m', strtotime($lancamento->dt_vencimento));
                    $qtde = 12 - $mes;

                    for($i = 1; $i <= $qtde; $i++)
                    {
                        $conta = clone $object;
                        unset($conta->id);
                        $conta->store();

                        $newlancamento = clone $lancamento;
                        unset($newlancamento->id);
                        unset($newlancamento->dt_vencimento);
                        $newlancamento->conta_id = $conta->id;
                        $newlancamento->dt_vencimento = date('Y-m-d', strtotime("+$i months", strtotime($lancamento->dt_vencimento)));
                        $newlancamento->store();
                    }
                }
            }

            if (!$cancelado) {
                self::recalcularContaPelosLancamentos($object->id);
            }
            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ContaPagarList', 'onShow', $loadPageParam); 

                        TScript::create("Template.closeRightPanel();");
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            $this->onSelectTipoDoc($param);
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $this->form->getField('pessoa_id')->setEditable(FALSE);
                $this->form->getField('tipo_documento_financeiro_id')->setEditable(FALSE);

                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Conta($key); // instantiates the Active Record 
                if($object->tipo_documento_financeiro_id != TipoDocumentoFinanceiro::NOTA){
                    TEntry::disableField(self::$formName, 'numero_documento');
                }
                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->criteria_parcelas->setProperty('order', 'parcela asc');
                $this->parcelas_items = $this->loadItems('Lancamento', 'conta_id', $object, $this->parcelas, function($masterObject, $detailObject, $objectItems){ 
                    if($detailObject->dt_pagamento=='' || $detailObject->dt_pagamento==null){
                        $masterObject->em_aberto = true;
                    }
                    if($detailObject->cancelado=='S'){
                        $masterObject->cancelado = true;
                        $masterObject->motivo_cancelamento = $detailObject->motivo_cancelamento;
                    }

                }, $this->criteria_parcelas); 
                if($object->em_aberto){
                    if(!$object->cancelado){
                        TScript::create("
                            window.contaPagarMostrarBotoesEdicao = true;

                            setTimeout(function() {
                                $('[name=\"btnEditarParcelas\"]').closest('div[class*=\"col-\"]').show();
                                $('[name=\"btnEditarParcelas\"]').closest('.fb-inline-field-container').show();

                                $('[name=\"btnCancelarParcelas\"]').closest('div[class*=\"col-\"]').show();
                                $('[name=\"btnCancelarParcelas\"]').closest('.fb-inline-field-container').show();
                            }, 700);
                        ");
                    }else{
                        TScript::create("$('label:contains(\"Motivo do cancelamento das parcelas:\")').show();");
                        TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').show();");
                        TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').html('".$object->motivo_cancelamento."')");
                    }
                }

                $object->total_valor_parcelas = 0;

                $tipoAoEditar = !empty($object->tipo_lancamento) ? $object->tipo_lancamento : 'P';

                $lancamentosEdicao = Lancamento::where('conta_id', '=', $object->id)
                    ->orderBy('dt_vencimento')
                    ->load();

                $idsLancamentosQuitados = [];

                foreach ($lancamentosEdicao as $lancamentoEdicao) {
                    if ($lancamentoEdicao->cancelado != 'S' && !empty($lancamentoEdicao->dt_pagamento)) {
                        $idsLancamentosQuitados[] = (int) $lancamentoEdicao->id;
                    }
                }

                TScript::create("
                    window.contaPagarLancamentosQuitados = " . json_encode($idsLancamentosQuitados) . ";
                ");

               if (!empty($lancamentosEdicao)) {
                    $primeiroLancamentoValido = null;

                    foreach ($lancamentosEdicao as $lancamentoEdicao) {
                        if ($lancamentoEdicao->cancelado != 'S') {
                            $primeiroLancamentoValido = $lancamentoEdicao;
                            break;
                        }
                    }

                    if ($primeiroLancamentoValido) {
                        if ($tipoAoEditar == 'S' || $tipoAoEditar == 'R') {
                            $object->valor = $primeiroLancamentoValido->valor;
                            $object->data_vencimento = $primeiroLancamentoValido->dt_vencimento;
                            $object->tipo_pagamento = $primeiroLancamentoValido->tipo_pagamento_id;
                        }

                        if ($tipoAoEditar == 'R') {
                            $totaisConta = self::recalcularContaPelosLancamentos($object->id);

                            $object->total_parcelas = $totaisConta['quantidade'];
                            $object->total_conta = $totaisConta['total'];
                            $object->total_valor_parcelas = $totaisConta['total'];
                            $object->dataVencPadrao = date('d/m/Y', strtotime($primeiroLancamentoValido->dt_vencimento));
                        }
                    }
                }

                $object->tipo = $tipoAoEditar;

                $this->form->setData($object); // fill the form 

                if ($tipoAoEditar == 'R') {
                    TScript::create("
                        setTimeout(function() {
                            if (typeof window.contaPagarAtualizarTotalRecorrenteTela === 'function') {
                                window.contaPagarAtualizarTotalRecorrenteTela();
                            }
                        }, 900);

                        setTimeout(function() {
                            if (typeof window.contaPagarAtualizarTotalRecorrenteTela === 'function') {
                                window.contaPagarAtualizarTotalRecorrenteTela();
                            }
                        }, 1400);
                    ");
                }

                $this->parcelas->getFoot()->style = 'display: none';

                self::onChange(['tipo' => $tipoAoEditar]);

                TScript::create("
                    window.contaPagarModoEdicao = true;
                ");

                TScript::create("
                    setTimeout(function() {
                        window.contaPagarModoEdicao = true;

                        $('[name=\"tipo\"][value=\"{$tipoAoEditar}\"]').prop('checked', true).trigger('change');

                        if (typeof ajustarTelaTipoContaPagar === 'function') {
                            ajustarTelaTipoContaPagar('{$tipoAoEditar}');
                        }

                        if (typeof ajustarEdicaoParcelasContaPagar === 'function') {
                            ajustarEdicaoParcelasContaPagar();
                        }

                        if (window.contaPagarMostrarBotoesEdicao === true && '{$tipoAoEditar}' == 'P') {
                            $('[name=\"btnEditarParcelas\"]').closest('div[class*=\"col-\"]').show();
                            $('[name=\"btnEditarParcelas\"]').closest('.fb-inline-field-container').show();

                            $('[name=\"btnCancelarParcelas\"]').closest('div[class*=\"col-\"]').show();
                            $('[name=\"btnCancelarParcelas\"]').closest('.fb-inline-field-container').show();
                        }

                        if (window.contaPagarMostrarBotoesEdicao === true && '{$tipoAoEditar}' == 'R') {
                            $('[name=\"btnEditarParcelas\"]').closest('.fb-inline-field-container').hide();

                            $('[name=\"btnCancelarParcelas\"]').closest('div[class*=\"col-\"]').show();
                            $('[name=\"btnCancelarParcelas\"]').closest('.fb-inline-field-container').show();

                            $('[name=\"valor\"]')
                                .closest('.fb-inline-field-container')
                                .find('label')
                                .first()
                                .text('Valor único:');

                            $('[name=\"total_conta\"]')
                                .closest('.fb-inline-field-container')
                                .find('label')
                                .first()
                                .text('Valor total:');

                            if (typeof window.contaPagarAtualizarTotalRecorrenteTela === 'function') {
                                window.contaPagarAtualizarTotalRecorrenteTela();
                            }
                        }
                    }, 500);
                ");

                TTransaction::close(); // close the transaction 
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);

        $this->parcelas->addHeader();
        $this->parcelas->addDetail($this->default_item_parcelas);

        $this->parcelas->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        TTransaction::open(self::$database);

        $escritorio = Escritorio::where('system_unit_id', '=', TSession::getValue('userunitid'))->first();

        $data = new stdClass;
        $data->escritorio_id = $escritorio->id;
        $data->tipo = 'S';

        $this->form->setData($data);

        TTransaction::close();

        self::onChange(['tipo' => 'S']);

    }

    public function onShow($param = null)
    {
        $this->parcelas->addHeader();
        $this->parcelas->addDetail($this->default_item_parcelas);

        $this->parcelas->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        TTransaction::open(self::$database);
        $data = new stdClass;

        $escritorio = Escritorio::where('system_unit_id', '=', TSession::getValue('userunitid'))->first();

        $data->escritorio_id = $escritorio->id;
        $data->tipo = 'S';

        //Pesquisa um tipo de documento financeiro configurado como padrão para inserção manual
        $tiposDocFinanceiro = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::MANUAL)
                                                    ->load();

        foreach($tiposDocFinanceiro as $tipoDocFinanceiro){
            //Se for de contas a pagar ou ambos
            if($tipoDocFinanceiro->tipo_conta_id == TipoConta::PAGAR || $tipoDocFinanceiro->tipo_conta_id == TipoConta::AMBOS){
                //Envia id para o campo de tipo de documento
                $data->tipo_documento_financeiro_id = $tipoDocFinanceiro->id;

                //Se gera codigo automaticamente
                if($tipoDocFinanceiro->gera_codigo == 'S'){
                    //Pesquisa a ultima conta com esse tipo de documento
                    $conta = Conta::where('tipo_documento_financeiro_id','=',$tipoDocFinanceiro->id)
                                    ->orderBy('numero_documento')
                                    ->last();
                    //Se existir
                    if($conta){
                        //popula o numero com o ultimo somado de 1
                        $data->numero_documento = $conta->numero_documento +1;
                    }else{
                        //caso contrario, popula como 1
                        $data->numero_documento = 1;
                    }
                    //Desabilita a edição de número
                    TEntry::disableField(self::$formName, 'numero_documento');
                }
            }
        }

        $this->form->setData($data);

        TTransaction::close();

        self::onChange(['tipo' => 'S']);

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    public  function onEditarParcelas($param = null) 
    {
        try 
        {
            ContaPagarForm::onSave();

            $pageParam = $param;
            $pageParam['id'] = null;
            $pageParam['conta_id'] = (int) $param['id'] ?? null;
            $pageParam['total_conta'] = $param['total_conta'] ?? null;

            TApplication::loadPage('ModalEditarParcelasAberto', 'onShow', $pageParam);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    private static function valorBrParaFloat($valor)
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $valor = trim((string) $valor);
        $valor = str_replace('R$', '', $valor);
        $valor = str_replace(' ', '', $valor);

        return (float) str_replace(',', '.', str_replace('.', '', $valor));
    }

    private static function dataParaBanco($valor)
    {
        if (empty($valor)) {
            return null;
        }

        $valor = trim((string) $valor);

        $data = DateTime::createFromFormat('d/m/Y', $valor);

        if (!$data) {
            $data = DateTime::createFromFormat('Y-m-d', $valor);
        }

        if (!$data) {
            return $valor;
        }

        return $data->format('Y-m-d');
    }

    private static function dataParaTela($valor)
    {
        if (empty($valor)) {
            return '';
        }

        try {
            $data = new DateTime($valor);
            return $data->format('d/m/Y');
        } catch (Exception $e) {
            return $valor;
        }
    }

    private static function existeLancamentoCancelado($contaId)
    {
        if (empty($contaId)) {
            return false;
        }

        $lancamentos = Lancamento::where('conta_id', '=', $contaId)->load();

        foreach ($lancamentos as $lancamento) {
            if ($lancamento->cancelado == 'S') {
                return true;
            }
        }

        return false;
    }

    private static function contaEstaQuitada($contaId)
    {
        if (empty($contaId)) {
            return false;
        }

        $lancamentos = Lancamento::where('conta_id', '=', $contaId)->load();

        $temLancamentoValido = false;

        foreach ($lancamentos as $lancamento) {
            if ($lancamento->cancelado == 'S') {
                continue;
            }

            $temLancamentoValido = true;

            if (empty($lancamento->dt_pagamento)) {
                return false;
            }
        }

        return $temLancamentoValido;
    }

    private static function recalcularContaPelosLancamentos($contaId)
    {
        $retorno = [
            'total' => 0,
            'quantidade' => 0,
            'proximo_vencimento' => null
        ];

        if (empty($contaId)) {
            return $retorno;
        }

        $conta = new Conta($contaId);

        $lancamentos = Lancamento::where('conta_id', '=', $contaId)
            ->orderBy('dt_vencimento')
            ->load();

        foreach ($lancamentos as $lancamento) {
            if ($lancamento->cancelado == 'S') {
                continue;
            }

            $retorno['quantidade']++;
            $retorno['total'] += self::valorBrParaFloat($lancamento->valor_total);

            if (empty($lancamento->dt_pagamento) && empty($retorno['proximo_vencimento'])) {
                $retorno['proximo_vencimento'] = $lancamento->dt_vencimento;
            }
        }

        $conta->total_conta = $retorno['total'];
        $conta->total_parcelas = $retorno['quantidade'];
        $conta->proximo_vencimento_lancamento = $retorno['proximo_vencimento'];
        $conta->store();

        return $retorno;
    }
    public static function onAtualizarParcelasOuRecorrencias($param = null)
    {
        if (($param['tipo'] ?? null) == 'R') {
            self::onGerarRecorrencias($param);
            return;
        }

        self::onGerarParcelas($param);
    }

    public static function onGerarRecorrencias($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $id = (int) ($param['id'] ?? 0);

            if (!empty($id)) {
                if (self::existeLancamentoCancelado($id)) {
                    TToast::show("error", "Lançamento cancelado, não é possível editar.", "topRight", "fas:info-circle");
                    TTransaction::close();
                    return;
                }

                if (self::contaEstaQuitada($id)) {
                    throw new Exception('Conta quitada, não é possível editar.');
                }
            }

            if (empty($param['valor']) || empty($param['data_vencimento']) || empty($param['tipo_pagamento'])) {
                throw new Exception('Valor, data de vencimento e tipo de pagamento são obrigatórios para gerar recorrências.');
            }

            if (empty($param['total_parcelas']) || (int) $param['total_parcelas'] <= 0) {
                throw new Exception('Informe o total de repetições.');
            }

            $totalRepeticoes = (int) $param['total_parcelas'];

            $dataBase = DateTime::createFromFormat('d/m/Y', $param['data_vencimento']);

            if (!$dataBase) {
                $dataBase = DateTime::createFromFormat('Y-m-d', $param['data_vencimento']);
            }

            if (!$dataBase) {
                throw new Exception('Data de vencimento inválida.');
            }

            $valorNumerico = self::valorBrParaFloat($param['valor']);
            $valorFormatado = number_format($valorNumerico, 2, ',', '.');

            $ids = [];
            $parcelas = [];
            $valores = [];
            $vencimentos = [];
            $tipos = [];
            $dtPagamentos = [];

            $quantidadeQuitada = 0;

            if (!empty($id)) {
                $lancamentos = Lancamento::where('conta_id', '=', $id)
                    ->orderBy('parcela')
                    ->load();

                foreach ($lancamentos as $lancamento) {
                    if ($lancamento->cancelado == 'S') {
                        continue;
                    }

                    if (!empty($lancamento->dt_pagamento)) {
                        $quantidadeQuitada++;

                        $ids[] = $lancamento->id;
                        $parcelas[] = $lancamento->parcela;
                        $valores[] = number_format(self::valorBrParaFloat($lancamento->valor_total), 2, ',', '.');
                        $vencimentos[] = self::dataParaTela($lancamento->dt_vencimento);
                        $tipos[] = $lancamento->tipo_pagamento_id;
                        $dtPagamentos[] = self::dataParaTela($lancamento->dt_pagamento);
                    } else {
                        $lancamento->delete();
                    }
                }
            }

            $repeticoesAbertas = $totalRepeticoes - $quantidadeQuitada;

            if ($repeticoesAbertas <= 0) {
                throw new Exception('Não é possível alterar recorrências já quitadas.');
            }

            $dataAtual = clone $dataBase;

            if ($quantidadeQuitada > 0) {
                $dataAtual->modify('+' . $quantidadeQuitada . ' month');
            }

            $proximaParcela = empty($parcelas) ? 1 : (max($parcelas) + 1);

            for ($i = 1; $i <= $repeticoesAbertas; $i++) {
                $ids[] = '';
                $parcelas[] = $proximaParcela++;
                $valores[] = $valorFormatado;
                $vencimentos[] = $dataAtual->format('d/m/Y');
                $tipos[] = $param['tipo_pagamento'];
                $dtPagamentos[] = '';

                $dataAtual->modify('+1 month');
            }

            if (!empty($id)) {
                $totalItens = count($parcelas);

                for ($i = 0; $i < $totalItens; $i++) {
                    if (empty($vencimentos[$i]) || empty($valores[$i]) || empty($tipos[$i])) {
                        continue;
                    }

                    if (!empty($ids[$i])) {
                        $object = Lancamento::find($ids[$i]);

                        if ($object && !empty($object->dt_pagamento)) {
                            continue;
                        }
                    }

                    $object = new Lancamento();
                    $object->conta_id = $id;
                    $object->parcela = $parcelas[$i];
                    $object->dt_vencimento = self::dataParaBanco($vencimentos[$i]);
                    $object->valor = self::valorBrParaFloat($valores[$i]);
                    $object->valor_total = $object->valor;
                    $object->tipo_pagamento_id = $tipos[$i];
                    $object->store();

                    $ids[$i] = $object->id;
                }

                $totaisConta = self::recalcularContaPelosLancamentos($id);
                $totalTela = $totaisConta['total'];
                $totalParcelasTela = $totaisConta['quantidade'];
            } else {
                $totalTela = 0;

                foreach ($valores as $valor) {
                    $totalTela += self::valorBrParaFloat($valor);
                }

                $totalParcelasTela = count($parcelas);
            }

            $data = new stdClass;
            $data->tipo = 'R';

            $data->valor = $param['valor'];
            $data->tipo_pagamento = $param['tipo_pagamento'];
            $data->data_vencimento = $param['data_vencimento'];
            $data->dataVencPadrao = $dataBase->format('d/m/Y');

            $data->lancamento_conta_id = $ids;
            $data->lancamento_conta_parcela = $parcelas;
            $data->lancamento_conta_valor_total = $valores;
            $data->lancamento_conta_dt_vencimento = $vencimentos;
            $data->lancamento_conta_tipo_pagamento_id = $tipos;
            $data->lancamento_conta_dt_pagamento = $dtPagamentos;

            $data->total_parcelas = $totalParcelasTela;
            $data->total_conta = number_format($totalTela, 2, ',', '.');
            $data->total_valor_parcelas = number_format($totalTela, 2, ',', '.');

            TFieldList::clearRows('parcelas');

            if (count($parcelas) > 1) {
                TFieldList::addRows('parcelas', count($parcelas) - 1);
            }

            TForm::sendData(self::$formName, $data, false, false, 50 * count($parcelas));

            TScript::create("
                setTimeout(function() {
                    $('[name=\"tipo\"][value=\"R\"]').prop('checked', true);

                    if (typeof ajustarTelaTipoContaPagar === 'function') {
                        ajustarTelaTipoContaPagar('R');
                    }

                    if (typeof ajustarEdicaoParcelasContaPagar === 'function') {
                        ajustarEdicaoParcelasContaPagar();
                    }

                    if (typeof window.contaPagarAtualizarTotalRecorrenteTela === 'function') {
                        window.contaPagarAtualizarTotalRecorrenteTela();
                    }
                }, " . ((50 * count($parcelas)) + 700) . ");
            ");

            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

}

