<?php

class ProfissionalPorParcela extends TWindow
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Lancamento';
    private static $primaryKey = 'id';
    private static $formName = 'form_profissionalPorParcela';

    use BuilderMasterDetailFieldListTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        parent::setSize(0.8, null);
        parent::setTitle("Percentual de Profissional por Parcela");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Percentual de Profissional por Parcela");

        $criteria_lancamento_profissional_lancamento_pessoa_id = new TCriteria();

        $filterVar = [Grupo::PROFISSIONAL, Grupo::PARCEIRO];
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_lancamento_profissional_lancamento_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id in ($filterVar))")); 

        $lancamento_profissional_lancamento_id = new THidden('lancamento_profissional_lancamento_id[]');
        $lancamento_profissional_lancamento___row__id = new THidden('lancamento_profissional_lancamento___row__id[]');
        $lancamento_profissional_lancamento___row__data = new THidden('lancamento_profissional_lancamento___row__data[]');
        $lancamento_profissional_lancamento_pessoa_id = new TDBCombo('lancamento_profissional_lancamento_pessoa_id[]', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_lancamento_profissional_lancamento_pessoa_id );
        $lancamento_profissional_lancamento_valor = new TNumeric('lancamento_profissional_lancamento_valor[]', '2', ',', '.' );
        $lancamento_profissional_lancamento_percentual = new TNumeric('lancamento_profissional_lancamento_percentual[]', '2', ',', '.' );
        $this->fieldList_6a62556da5f24 = new TFieldList();
        $lancamento_id = new THidden('lancamento_id');

        $this->fieldList_6a62556da5f24->addField(null, $lancamento_profissional_lancamento_id, []);
        $this->fieldList_6a62556da5f24->addField(null, $lancamento_profissional_lancamento___row__id, ['uniqid' => true]);
        $this->fieldList_6a62556da5f24->addField(null, $lancamento_profissional_lancamento___row__data, []);
        $this->fieldList_6a62556da5f24->addField(new TLabel("Profissional", null, '14px', null), $lancamento_profissional_lancamento_pessoa_id, ['width' => '50%']);
        $this->fieldList_6a62556da5f24->addField(new TLabel("Valor", null, '14px', null), $lancamento_profissional_lancamento_valor, ['width' => '33%']);
        $this->fieldList_6a62556da5f24->addField(new TLabel("Percentual", null, '14px', null), $lancamento_profissional_lancamento_percentual, ['width' => '33%']);

        $this->fieldList_6a62556da5f24->width = '100%';
        $this->fieldList_6a62556da5f24->setFieldPrefix('lancamento_profissional_lancamento');
        $this->fieldList_6a62556da5f24->name = 'fieldList_6a62556da5f24';

        $this->criteria_fieldList_6a62556da5f24 = new TCriteria();
        $this->default_item_fieldList_6a62556da5f24 = new stdClass();

        $this->form->addField($lancamento_profissional_lancamento_id);
        $this->form->addField($lancamento_profissional_lancamento___row__id);
        $this->form->addField($lancamento_profissional_lancamento___row__data);
        $this->form->addField($lancamento_profissional_lancamento_pessoa_id);
        $this->form->addField($lancamento_profissional_lancamento_valor);
        $this->form->addField($lancamento_profissional_lancamento_percentual);

        $this->fieldList_6a62556da5f24->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $lancamento_profissional_lancamento_pessoa_id->addValidation("Pessoa id", new TRequiredListValidator()); 

        $lancamento_profissional_lancamento_pessoa_id->enableSearch();
        $lancamento_id->setValue($param["key"] ?? "");
        $lancamento_id->setSize(200);
        $lancamento_profissional_lancamento_valor->setSize('100%');
        $lancamento_profissional_lancamento_pessoa_id->setSize('100%');
        $lancamento_profissional_lancamento_percentual->setSize('100%');

        $tituloDistribuicao = new TElement('div');
        $tituloDistribuicao->add("
            <div style='margin-bottom: 12px; padding: 12px 14px; background: #f5f7fa; border-left: 4px solid #3c8dbc; border-radius: 4px;'>
                <div style='font-size: 15px; font-weight: bold;'>Distribuição original da parcela</div>
                <div style='font-size: 12px; color: #666; margin-top: 3px;'>
                    Os valores e percentuais abaixo representam a distribuição original, antes de descontos ou acréscimos.
                </div>
            </div>
        ");

        $rowTituloDistribuicao = $this->form->addFields([$tituloDistribuicao]);
        $rowTituloDistribuicao->layout = [' col-sm-12'];

        $row1 = $this->form->addFields([$this->fieldList_6a62556da5f24]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([$lancamento_id]);
        $row2->layout = [' col-sm-12'];

        $resumoAjustesProfissionais = new TElement('div');
        $resumoAjustesProfissionais->id = 'resumo_ajustes_profissionais';
        $resumoAjustesProfissionais->style = 'display: none; width: 100%;';

        $rowResumoAjustes = $this->form->addFields([$resumoAjustesProfissionais]);
        $rowResumoAjustes->layout = [' col-sm-12'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;


        TScript::create("
            /*
            * PROFISSIONAIS POR PARCELA:
            *
            * - A base do cálculo é o valor_total do lançamento.
            * - Selecionou profissional: divide igualmente.
            * - Alterou percentual: calcula o valor.
            * - Alterou valor: calcula o percentual.
            */

            $(document).off('.profissionalParcela');

            window.profissionalParcelaValorTotal = window.profissionalParcelaValorTotal || 0;
            window.profissionalParcelaAtualizando = false;

            window.profissionalParcelaCampoPessoa = \"[name='lancamento_profissional_lancamento_pessoa_id[]']\";
            window.profissionalParcelaCampoPercentual = \"[name='lancamento_profissional_lancamento_percentual[]']\";
            window.profissionalParcelaCampoValor = \"[name='lancamento_profissional_lancamento_valor[]']\";

            window.profissionalParcelaParaNumero = function(valor) {
                valor = valor || '0';
                valor = valor.toString();
                valor = valor.trim();

                valor = valor.replace(/R\\$/g, '');
                valor = valor.replace(/\\s/g, '');
                valor = valor.replace(/[^0-9,.-]/g, '');

                if(valor === '' || valor === '-' || valor === ',' || valor === '.'){
                    return 0;
                }

                var negativo = false;

                if(valor.charAt(0) === '-'){
                    negativo = true;
                }

                valor = valor.split('-').join('');

                var ultimoPonto = valor.lastIndexOf('.');
                var ultimaVirgula = valor.lastIndexOf(',');

                if(ultimoPonto >= 0 && ultimaVirgula >= 0){
                    if(ultimaVirgula > ultimoPonto){
                        valor = valor.split('.').join('');
                        valor = valor.replace(',', '.');
                    }else{
                        valor = valor.split(',').join('');
                    }
                }else if(ultimaVirgula >= 0){
                    valor = valor.split('.').join('');
                    valor = valor.replace(',', '.');
                }else if(ultimoPonto >= 0){
                    var partes = valor.split('.');

                    if(partes.length == 2){
                        var antes = partes[0];
                        var depois = partes[1];

                        if(depois.length == 3 && antes.length <= 3){
                            valor = antes + depois;
                        }else{
                            valor = antes + '.' + depois;
                        }
                    }else{
                        valor = partes.join('');
                    }
                }

                var numero = parseFloat(valor);

                if(isNaN(numero)){
                    numero = 0;
                }

                if(negativo){
                    numero = numero * -1;
                }

                return numero;
            };

            window.profissionalParcelaParaTela = function(numero) {
                numero = parseFloat(numero || 0);

                return numero.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            window.profissionalParcelaGetValorTotal = function() {
                return window.profissionalParcelaParaNumero(window.profissionalParcelaValorTotal);
            };

            window.profissionalParcelaSetValor = function(campo, valor) {
                window.profissionalParcelaAtualizando = true;

                campo.val(window.profissionalParcelaParaTela(valor));

                setTimeout(function() {
                    window.profissionalParcelaAtualizando = false;
                }, 150);
            };

            window.profissionalParcelaGetRow = function(campo) {
                var row = campo.closest('tr');

                if(!row.length){
                    row = campo.closest('.tfieldlist_row');
                }

                if(!row.length){
                    row = campo.closest('.fb-inline-field-container').closest('.tformrow');
                }

                return row;
            };

            window.profissionalParcelaGetLinhas = function() {
                var linhas = [];

                $(window.profissionalParcelaCampoPessoa).each(function() {
                    var pessoaId = String($(this).val() || '').trim();

                    if(pessoaId !== ''){
                        var row = window.profissionalParcelaGetRow($(this));

                        if(row.length){
                            linhas.push(row);
                        }
                    }
                });

                return linhas;
            };

            window.profissionalParcelaTemValorOuPercentual = function() {
                var tem = false;

                window.profissionalParcelaGetLinhas().forEach(function(row) {
                    var valor = window.profissionalParcelaParaNumero(
                        row.find(window.profissionalParcelaCampoValor).val()
                    );

                    var percentual = window.profissionalParcelaParaNumero(
                        row.find(window.profissionalParcelaCampoPercentual).val()
                    );

                    if(valor > 0 || percentual > 0){
                        tem = true;
                    }
                });

                return tem;
            };

            window.profissionalParcelaDistribuirTudoIgual = function() {
                var totalParcela = window.profissionalParcelaGetValorTotal();
                var linhas = window.profissionalParcelaGetLinhas();

                if(totalParcela <= 0 || linhas.length <= 0){
                    return;
                }

                var quantidade = linhas.length;
                var totalCentavos = Math.round(totalParcela * 100);
                var valorBaseCentavos = Math.floor(totalCentavos / quantidade);
                var percentualBase = Math.floor((100 / quantidade) * 100) / 100;

                var valorAcumuladoCentavos = 0;
                var percentualAcumulado = 0;

                linhas.forEach(function(row, index) {
                    var valorLinhaCentavos = 0;
                    var percentualLinha = 0;

                    if(index == quantidade - 1){
                        valorLinhaCentavos = totalCentavos - valorAcumuladoCentavos;
                        percentualLinha = 100 - percentualAcumulado;
                    }else{
                        valorLinhaCentavos = valorBaseCentavos;
                        percentualLinha = percentualBase;

                        valorAcumuladoCentavos += valorLinhaCentavos;
                        percentualAcumulado += percentualLinha;
                    }

                    window.profissionalParcelaSetValor(
                        row.find(window.profissionalParcelaCampoValor),
                        valorLinhaCentavos / 100
                    );

                    window.profissionalParcelaSetValor(
                        row.find(window.profissionalParcelaCampoPercentual),
                        percentualLinha
                    );
                });
            };

            window.profissionalParcelaDistribuirSeTudoVazio = function() {
                if(window.profissionalParcelaTemValorOuPercentual()){
                    return;
                }

                window.profissionalParcelaDistribuirTudoIgual();
            };

            window.profissionalParcelaRecalcularValorDaLinha = function(row) {
                var totalParcela = window.profissionalParcelaGetValorTotal();

                if(totalParcela <= 0){
                    return;
                }

                var campoPercentual = row.find(window.profissionalParcelaCampoPercentual);
                var campoValor = row.find(window.profissionalParcelaCampoValor);

                var percentual = window.profissionalParcelaParaNumero(campoPercentual.val());
                var valor = 0;

                if(percentual > 0){
                    valor = (percentual / 100) * totalParcela;
                }

                window.profissionalParcelaSetValor(campoValor, valor);
            };

            window.profissionalParcelaRecalcularPercentualDaLinha = function(row) {
                var totalParcela = window.profissionalParcelaGetValorTotal();

                if(totalParcela <= 0){
                    return;
                }

                var campoPercentual = row.find(window.profissionalParcelaCampoPercentual);
                var campoValor = row.find(window.profissionalParcelaCampoValor);

                var valor = window.profissionalParcelaParaNumero(campoValor.val());
                var percentual = 0;

                if(valor > 0){
                    percentual = (valor / totalParcela) * 100;
                }

                window.profissionalParcelaSetValor(campoPercentual, percentual);
            };

            window.profissionalParcelaFormatarCampo = function(campo) {
                var numero = window.profissionalParcelaParaNumero(campo.val());
                window.profissionalParcelaSetValor(campo, numero);
            };

            $(document).on(
                'change.profissionalParcela select2:select.profissionalParcela select2:clear.profissionalParcela blur.profissionalParcela',
                window.profissionalParcelaCampoPessoa,
                function() {
                    setTimeout(function() {
                        window.profissionalParcelaDistribuirTudoIgual();
                    }, 250);

                    setTimeout(function() {
                        window.profissionalParcelaDistribuirTudoIgual();
                    }, 700);
                }
            );

            $(document).on(
                'input.profissionalParcela keyup.profissionalParcela change.profissionalParcela',
                window.profissionalParcelaCampoPercentual,
                function() {
                    if(window.profissionalParcelaAtualizando === true){
                        return;
                    }

                    var row = window.profissionalParcelaGetRow($(this));
                    window.profissionalParcelaRecalcularValorDaLinha(row);
                }
            );

            $(document).on(
                'input.profissionalParcela keyup.profissionalParcela change.profissionalParcela',
                window.profissionalParcelaCampoValor,
                function() {
                    if(window.profissionalParcelaAtualizando === true){
                        return;
                    }

                    var row = window.profissionalParcelaGetRow($(this));
                    window.profissionalParcelaRecalcularPercentualDaLinha(row);
                }
            );

            $(document).on(
                'blur.profissionalParcela',
                window.profissionalParcelaCampoPercentual + ', ' + window.profissionalParcelaCampoValor,
                function() {
                    if(window.profissionalParcelaAtualizando === true){
                        return;
                    }

                    window.profissionalParcelaFormatarCampo($(this));
                }
            );

            $(document).on(
                'click.profissionalParcela',
                '#form_profissionalPorParcela .tfieldlist button, #form_profissionalPorParcela .tfieldlist a',
                function() {
                    setTimeout(function() {
                        window.profissionalParcelaDistribuirTudoIgual();
                    }, 500);
                }
            );

            if(window.profissionalParcelaObserver){
                window.profissionalParcelaObserver.disconnect();
                window.profissionalParcelaObserver = null;
            }

            window.profissionalParcelaObserver = new MutationObserver(function() {
                clearTimeout(window.profissionalParcelaObserverTimer);

                window.profissionalParcelaObserverTimer = setTimeout(function() {
                    window.profissionalParcelaDistribuirSeTudoVazio();
                }, 300);
            });

            var fieldListProfissionalParcela = $(window.profissionalParcelaCampoPessoa)
                .first()
                .closest('table, .tfieldlist, .tformrow')[0];

            if(fieldListProfissionalParcela){
                window.profissionalParcelaObserver.observe(fieldListProfissionalParcela, {
                    childList: true,
                    subtree: true
                });
            }

            setTimeout(function() {
                window.profissionalParcelaDistribuirSeTudoVazio();
            }, 700);

            setTimeout(function() {
                window.profissionalParcelaDistribuirSeTudoVazio();
            }, 1400);
        ");

        parent::add($this->form);

    }

    public function onSave($param = null) 
    {
        try
        {/*
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Lancamento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $lancamento_profissional_lancamento_items = $this->storeItems('LancamentoProfissional', 'lancamento_id', $object, $this->fieldList_6a62556da5f24, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_6a62556da5f24); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            new TMessage('info', "Registro salvo", $messageAction); 

                TWindow::closeWindow(parent::getId());
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

            */
             TTransaction::open(self::$database);

            $this->form->validate();

            $data = $this->form->getData();

            $lancamentoId = (int) ($data->lancamento_id ?? $param['key'] ?? 0);

            if(empty($lancamentoId)){
                throw new Exception('Lançamento não informado.');
            }

            $object = Lancamento::find($lancamentoId);

            if(!$object){
                throw new Exception('Lançamento não encontrado.');
            }

            if(!empty($object->dt_pagamento)){
                throw new Exception('A parcela já foi quitada. A distribuição dos profissionais não pode mais ser alterada.');
            }

            $valorParcela = !empty($object->valor_total) ? $object->valor_total : $object->valor;
            $valorParcela = self::valorBrParaFloat($valorParcela);

            if($valorParcela <= 0){
                throw new Exception('O valor da parcela deve ser maior que zero.');
            }

            $profissionais = $data->lancamento_profissional_lancamento_pessoa_id ?? [];
            $percentuais = $data->lancamento_profissional_lancamento_percentual ?? [];
            $valores = $data->lancamento_profissional_lancamento_valor ?? [];

            if(!is_array($profissionais)){
                $profissionais = [$profissionais];
            }

            if(!is_array($percentuais)){
                $percentuais = [$percentuais];
            }

            if(!is_array($valores)){
                $valores = [$valores];
            }

            $profissionaisSelecionados = [];
            $quantidadeProfissionais = 0;
            $totalPercentualCentavos = 0;
            $totalValorCentavos = 0;
            $valorParcelaCentavos = (int) round($valorParcela * 100);

            foreach($profissionais as $index => $profissionalId){
                if(empty($profissionalId)){
                    continue;
                }

                if(isset($profissionaisSelecionados[$profissionalId])){
                    throw new Exception('O mesmo profissional foi informado mais de uma vez.');
                }

                $profissionaisSelecionados[$profissionalId] = true;
                $quantidadeProfissionais++;

                $percentual = self::valorBrParaFloat($percentuais[$index] ?? 0);
                $valor = self::valorBrParaFloat($valores[$index] ?? 0);

                if($percentual <= 0){
                    throw new Exception('Informe o percentual do profissional.');
                }

                if($percentual > 100){
                    throw new Exception('O percentual de um profissional não pode passar de 100%.');
                }

                if($valor <= 0){
                    throw new Exception('Informe o valor do profissional.');
                }

                $percentualCentavos = (int) round($percentual * 100);
                $valorCentavos = (int) round($valor * 100);

                if($valorCentavos > $valorParcelaCentavos){
                    throw new Exception(
                        'O valor de um profissional não pode ser maior que o valor da parcela de R$ ' .
                        number_format($valorParcela, 2, ',', '.') . '.'
                    );
                }

                $totalPercentualCentavos += $percentualCentavos;
                $totalValorCentavos += $valorCentavos;
            }

            if($quantidadeProfissionais <= 0){
                throw new Exception('Informe pelo menos um profissional.');
            }

           if($totalPercentualCentavos != 10000){
                throw new Exception(
                    'A soma dos percentuais deve ser exatamente 100%. Total informado: ' .
                    number_format($totalPercentualCentavos / 100, 2, ',', '.') . '%.'
                );
            }

            if($totalValorCentavos != $valorParcelaCentavos){
                throw new Exception(
                    'A soma dos valores dos profissionais deve ser exatamente igual ao valor da parcela de R$ ' .
                    number_format($valorParcela, 2, ',', '.') .
                    '. Total informado: R$ ' .
                    number_format($totalValorCentavos / 100, 2, ',', '.') . '.'
                );
            }

            $lancamento_profissional_lancamento_items = $this->storeItems('LancamentoProfissional', 'lancamento_id', $object, $this->fieldList_6a62556da5f24, function($masterObject, $detailObject){ 

                if(isset($detailObject->percentual)){
                    $detailObject->percentual = self::valorBrParaFloat($detailObject->percentual);
                }

                if(isset($detailObject->valor)){
                    $detailObject->valor = self::valorBrParaFloat($detailObject->valor);
                }

                if(empty($detailObject->pessoa_id)){
                    throw new Exception('Informe o profissional.');
                }

                if(empty($detailObject->percentual) || $detailObject->percentual <= 0){
                    throw new Exception('Informe o percentual do profissional.');
                }

                if($detailObject->percentual > 100){
                    throw new Exception('O percentual do profissional não pode passar de 100%.');
                }

                if(empty($detailObject->valor) || $detailObject->valor <= 0){
                    throw new Exception('Informe o valor do profissional.');
                }

            }, $this->criteria_fieldList_6a62556da5f24); 

            $data->lancamento_id = $object->id;

            $this->form->setData($data);

            TTransaction::close();

            TToast::show('success', "Profissionais da parcela salvos", 'topRight', 'far:check-circle');

            TWindow::closeWindow();

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Lancamento($key); // instantiates the Active Record 
               if(empty($object->id)){
                    throw new Exception('Lançamento não encontrado.');
                }

                $object->lancamento_id = $object->id;

                $resumoProfissionais = self::montarResumoProfissionais($object);

                $valorParcela = $resumoProfissionais->valor_base;

                $quitado = !empty($object->dt_pagamento);

                if($quitado){
                    $campoProfissional = $this->form->getField('lancamento_profissional_lancamento_pessoa_id[]');
                    $campoValor = $this->form->getField('lancamento_profissional_lancamento_valor[]');
                    $campoPercentual = $this->form->getField('lancamento_profissional_lancamento_percentual[]');

                    if($campoProfissional){
                        $campoProfissional->setEditable(false);
                    }

                    if($campoValor){
                        $campoValor->setEditable(false);
                    }

                    if($campoPercentual){
                        $campoPercentual->setEditable(false);
                    }
                }

                TScript::create("
                    window.profissionalParcelaValorTotal = " . json_encode($valorParcela) . ";
                ");

                $this->fieldList_6a62556da5f24_items = $this->loadItems('LancamentoProfissional', 'lancamento_id', $object, $this->fieldList_6a62556da5f24, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_6a62556da5f24); 

                $this->form->setData($object); // fill the form 

                $htmlResumo = json_encode($resumoProfissionais->html, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $mostrarResumo = $resumoProfissionais->mostrar ? 'true' : 'false';
                $bloquearEdicao = !empty($object->dt_pagamento) ? 'true' : 'false';

                TScript::create("
                    setTimeout(function(){
                        var resumo = $('#resumo_ajustes_profissionais');
                        resumo.html({$htmlResumo});

                        if({$mostrarResumo}){
                            resumo.show();
                        }else{
                            resumo.hide();
                        }

                        if({$bloquearEdicao}){
                            var form = $('#form_profissionalPorParcela');

                            form.find('.tfieldlist button, .tfieldlist a').hide();

                            form.find('button, a').filter(function(){
                                var texto = $(this).text().replace(/\\s+/g, ' ').trim();

                                return texto === 'Salvar' || texto === 'Limpar formulário';
                            }).hide();
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

        $this->fieldList_6a62556da5f24->addHeader();
        $this->fieldList_6a62556da5f24->addDetail($this->default_item_fieldList_6a62556da5f24);

        $this->fieldList_6a62556da5f24->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_6a62556da5f24->addHeader();
        $this->fieldList_6a62556da5f24->addDetail($this->default_item_fieldList_6a62556da5f24);

        $this->fieldList_6a62556da5f24->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    private static function valorBrParaFloat($valor)
    {
        if($valor === null || $valor === ''){
            return 0;
        }

        if(is_numeric($valor)){
            return (float) $valor;
        }

        $valor = trim((string) $valor);
        $valor = str_replace('R$', '', $valor);
        $valor = str_replace(' ', '', $valor);

        return (float) str_replace(',', '.', str_replace('.', '', $valor));
    }

    private static function montarResumoProfissionais($lancamento)
    {
        $resumo = new stdClass();
        $resumo->html = '';
        $resumo->mostrar = !empty($lancamento->dt_pagamento);
        $resumo->tem_ajustes = false;

        $valorLancamento = !empty($lancamento->valor_total) ? $lancamento->valor_total : $lancamento->valor;
        $resumo->valor_base = self::valorBrParaFloat($valorLancamento);

        $lancamentosProfissionais = LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)->load();

        if(!$lancamentosProfissionais){
            return $resumo;
        }

        $linhas = '';
        $totalPercentual = 0;
        $totalBase = 0;
        $totalAcrescimo = 0;
        $totalDesconto = 0;
        $totalLiquido = 0;

        foreach($lancamentosProfissionais as $lancamentoProfissional){
            $profissional = Pessoa::find($lancamentoProfissional->pessoa_id);

            $nomeProfissional = $profissional ? $profissional->nome : 'Profissional não encontrado';
            $nomeProfissional = htmlspecialchars($nomeProfissional, ENT_QUOTES, 'UTF-8');

            $percentual = self::valorBrParaFloat($lancamentoProfissional->percentual);
            $valorBase = self::valorBrParaFloat($lancamentoProfissional->valor);

            $acrescimo = 0;
            $desconto = 0;

            $ajustes = LancamentoProfissionalAjuste::where(
                'lancamento_profissional_id',
                '=',
                $lancamentoProfissional->id
            )->load();

            foreach($ajustes as $ajuste){
                $valorAjuste = self::valorBrParaFloat($ajuste->valor);

                if($ajuste->tipo == 'A'){
                    $acrescimo += $valorAjuste;
                }

                if($ajuste->tipo == 'D'){
                    $desconto += $valorAjuste;
                }
            }

            if($acrescimo > 0 || $desconto > 0){
                $resumo->tem_ajustes = true;
            }

            $valorLiquido = round($valorBase + $acrescimo - $desconto, 2);

            $totalPercentual += $percentual;
            $totalBase += $valorBase;
            $totalAcrescimo += $acrescimo;
            $totalDesconto += $desconto;
            $totalLiquido += $valorLiquido;

            $acrescimoTela = $acrescimo > 0
                ? "<span style='color: #198754; font-weight: bold;'>+ R$ ".number_format($acrescimo, 2, ',', '.')."</span>"
                : "<span style='color: #999;'>—</span>";

            $descontoTela = $desconto > 0
                ? "<span style='color: #dc3545; font-weight: bold;'>- R$ ".number_format($desconto, 2, ',', '.')."</span>"
                : "<span style='color: #999;'>—</span>";

            $linhas .= "
                <tr>
                    <td style='padding: 9px 10px; border-bottom: 1px solid #e5e5e5;'>{$nomeProfissional}</td>
                    <td style='padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: right;'>".number_format($percentual, 2, ',', '.')."%</td>
                    <td style='padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: right;'>R$ ".number_format($valorBase, 2, ',', '.')."</td>
                    <td style='padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: right;'>{$acrescimoTela}</td>
                    <td style='padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: right;'>{$descontoTela}</td>
                    <td style='padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: right; font-weight: bold;'>R$ ".number_format($valorLiquido, 2, ',', '.')."</td>
                </tr>
            ";
        }

        $resumo->mostrar = $resumo->mostrar || $resumo->tem_ajustes;

        if($totalBase > 0){
            $resumo->valor_base = round($totalBase, 2);
        }

        if(!$resumo->mostrar){
            return $resumo;
        }

        $aviso = '';

        if(!empty($lancamento->dt_pagamento)){
            $aviso = "
                <div style='margin-bottom: 12px; padding: 10px 12px; background: #fff3cd; border: 1px solid #ffe69c; border-radius: 4px; color: #664d03;'>
                    <b>Parcela quitada.</b> A distribuição original foi bloqueada para preservar os valores usados no extrato e nos recibos.
                </div>
            ";
        }

        $resumo->html = "
            <div style='margin-top: 18px;'>
                {$aviso}

                <div style='margin-bottom: 10px; font-size: 15px; font-weight: bold;'>
                    Resumo após a quitação
                </div>

                <div style='overflow-x: auto; border: 1px solid #ddd; border-radius: 5px;'>
                    <table style='width: 100%; border-collapse: collapse; min-width: 780px;'>
                        <thead>
                            <tr style='background: #f5f5f5;'>
                                <th style='padding: 10px; text-align: left;'>Profissional</th>
                                <th style='padding: 10px; text-align: right;'>% base</th>
                                <th style='padding: 10px; text-align: right;'>Valor base</th>
                                <th style='padding: 10px; text-align: right;'>Acréscimo</th>
                                <th style='padding: 10px; text-align: right;'>Desconto</th>
                                <th style='padding: 10px; text-align: right;'>Valor líquido</th>
                            </tr>
                        </thead>

                        <tbody>
                            {$linhas}
                        </tbody>

                        <tfoot>
                            <tr style='background: #f5f5f5; font-weight: bold;'>
                                <td style='padding: 10px;'>Total</td>
                                <td style='padding: 10px; text-align: right;'>".number_format($totalPercentual, 2, ',', '.')."%</td>
                                <td style='padding: 10px; text-align: right;'>R$ ".number_format($totalBase, 2, ',', '.')."</td>
                                <td style='padding: 10px; text-align: right; color: #198754;'>+ R$ ".number_format($totalAcrescimo, 2, ',', '.')."</td>
                                <td style='padding: 10px; text-align: right; color: #dc3545;'>- R$ ".number_format($totalDesconto, 2, ',', '.')."</td>
                                <td style='padding: 10px; text-align: right;'>R$ ".number_format($totalLiquido, 2, ',', '.')."</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        ";

        return $resumo;
    }

}

