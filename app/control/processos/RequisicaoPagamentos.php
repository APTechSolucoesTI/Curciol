<?php

class RequisicaoPagamentos extends TPage
{
    protected $form;

    private static $database = 'escritorio';
    private static $formName = 'form_RequisicaoPagamentos';

    const GRUPO_ENTIDADE_DEVEDORA = 5;

    public function __construct($param = null)
    {
        parent::__construct();

        if (!empty($param['target_container'])) {
            $this->adianti_target_container = $param['target_container'];
        }

        if (!empty($param['key']) && empty($param['requisicao_pagamento_id'])) {
            $param['requisicao_pagamento_id'] = $param['key'];
        }

        if (!empty($param['requisicao_pagamento_id'])) {
            $param['modo_edicao'] = 1;

            if (empty($param['step'])) {
                $param['step'] = 'selecionar_clientes';
            }

            try {
                $param = self::prepararParametrosEdicao($param);
            } catch (Exception $e) {
                new TMessage('error', $e->getMessage());
            }
        }

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setClientValidation(true);
        $this->form->setFieldSizes('100%');

        $this->aplicarCss();

        if (!empty($param['step']) && $param['step'] == 'selecionar_clientes') {
            $this->montarEtapaSelecaoClientes($param);
        } elseif (!empty($param['step']) && $param['step'] == 'clientes') {
            $this->montarEtapaClientes($param);
        } else {
            $this->montarEtapaInicial($param);
        }

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onShow($param = null)
    {
    }

    private function montarEtapaInicial($param = null)
    {
        $this->form->setFormTitle('Nova requisição de pagamento');

        $info = new TElement('div');
        $info->setProperty('class', 'req-info-box');
        $info->add('Selecione o tipo da requisição, o processo, a entidade devedora/devedor padrão e os dados padrão de levantamento para continuar.');

        $this->form->addContent([$info]);

        $criteria_processo = new TCriteria;
        $criteria_processo->add(
            new TFilter(
                'id',
                'in',
                "(SELECT id FROM processo WHERE COALESCE(numero_cnj_numero, numero_outro, '') !~ '/[0-9]+$')"
            )
        );

        $tipo_requisicao = new TDBCombo(
            'tipos_requisicao_pagamento_id',
            self::$database,
            'TiposRequisicaoPagamento',
            'id',
            'nome',
            'nome'
        );

        $processo_id = new TDBUniqueSearch(
            'processo_id',
            self::$database,
            'Processo',
            'id',
            'numero_cnj_numero',
            'numero_cnj_numero',
            $criteria_processo
        );

        $entidade_devedora_padrao_id = self::criarCampoEntidadeDevedora('entidade_devedora_padrao_id');

        $conta_indicada_mle_padrao = new TEntry('conta_indicada_mle_padrao');
        $conta_indicada_mle_padrao->setSize('100%');
        $conta_indicada_mle_padrao->setValue($param['conta_indicada_mle_padrao'] ?? '');

        $data_requerimento_padrao = new TDate('data_requerimento_padrao');
        $data_requerimento_padrao->setMask('dd/mm/yyyy');
        $data_requerimento_padrao->setDatabaseMask('yyyy-mm-dd');
        $data_requerimento_padrao->setSize('100%');
        $data_requerimento_padrao->setValue(self::formatarDataBR($param['data_requerimento_padrao'] ?? null));

        $data_base_padrao = new TDate('data_base_padrao');
        $data_base_padrao->setMask('dd/mm/yyyy');
        $data_base_padrao->setDatabaseMask('yyyy-mm-dd');
        $data_base_padrao->setSize('100%');
        $data_base_padrao->setValue(self::formatarDataBR($param['data_base_padrao'] ?? null));

        $tipo_requisicao->addValidation('Tipo da requisição', new TRequiredValidator);
        $processo_id->addValidation('Processo', new TRequiredValidator);
        $entidade_devedora_padrao_id->addValidation('Entidade devedora/devedor', new TRequiredValidator);
        $conta_indicada_mle_padrao->addValidation('Conta Indicada para Levantamento', new TRequiredValidator);
        $data_requerimento_padrao->addValidation('Data do requerimento', new TRequiredValidator);
        $data_base_padrao->addValidation('Data base do cálculo', new TRequiredValidator);

        $tipo_requisicao->setSize('100%');

        $processo_id->setSize('100%');
        $processo_id->setMinLength(0);
        $processo_id->setMask('{numero_cnj_numero}');

        $entidade_devedora_padrao_id->setSize('100%');
        $entidade_devedora_padrao_id->setMinLength(1);
        $entidade_devedora_padrao_id->setMask('{nome}');

        if (!empty($param['tipos_requisicao_pagamento_id'])) {
            $tipo_requisicao->setValue($param['tipos_requisicao_pagamento_id']);
        }

        if (!empty($param['processo_id'])) {
            $processo_id->setValue($param['processo_id']);
        }

        if (!empty($param['entidade_devedora_padrao_id'])) {
            $entidade_devedora_padrao_id->setValue($param['entidade_devedora_padrao_id']);
        }

        $this->form->addField($tipo_requisicao);
        $this->form->addField($processo_id);
        $this->form->addField($entidade_devedora_padrao_id);
        $this->form->addField($conta_indicada_mle_padrao);
        $this->form->addField($data_requerimento_padrao);
        $this->form->addField($data_base_padrao);

        $grid = new TElement('div');
        $grid->setProperty('class', 'req-initial-grid');

        $grid->add(self::criarCampoVisual('Tipo da requisição', $tipo_requisicao, true));
        $grid->add(self::criarCampoVisual('Processo', $processo_id, true));
        $grid->add(self::criarCampoVisual('Entidade devedora/devedor', $entidade_devedora_padrao_id, true));
        $grid->add(self::criarCampoVisual('Conta Indicada para Levantamento', $conta_indicada_mle_padrao, true));
        $grid->add(self::criarCampoVisual('Data do requerimento', $data_requerimento_padrao, true));
        $grid->add(self::criarCampoVisual('Data base do cálculo', $data_base_padrao, true));

        $this->form->addContent([$grid]);

        $btn = $this->form->addAction(
            'Seguir',
            new TAction([__CLASS__, 'onSeguir']),
            'fa:arrow-right #ffffff'
        );

        $btn->class = 'btn btn-primary';
    }

    private function montarEtapaSelecaoClientes($param)
    {
        $tipo_id = (int) ($param['tipos_requisicao_pagamento_id'] ?? 0);
        $processo_id = (int) ($param['processo_id'] ?? 0);
        $entidade_devedora_padrao_id = (int) ($param['entidade_devedora_padrao_id'] ?? 0);
        $conta_indicada_mle_padrao = $param['conta_indicada_mle_padrao'] ?? '';
        $data_requerimento_padrao = $param['data_requerimento_padrao'] ?? '';
        $data_base_padrao = $param['data_base_padrao'] ?? '';

        $requisicao_id = (int) ($param['requisicao_pagamento_id'] ?? $param['key'] ?? 0);
        $modo_edicao = !empty($param['modo_edicao']) || !empty($requisicao_id);

        $clientes_marcados_param = $param['clientes_ids'] ?? '';

        if (is_array($clientes_marcados_param)) {
            $clientes_marcados = $clientes_marcados_param;
        } else {
            $clientes_marcados = explode(',', $clientes_marcados_param);
        }

        $clientes_marcados = array_filter(array_map('intval', $clientes_marcados));

        if (empty($tipo_id) || empty($processo_id) || empty($entidade_devedora_padrao_id)) {
            $this->montarEtapaInicial($param);
            return;
        }

        try {
            TTransaction::open(self::$database);

            $tipo = new TiposRequisicaoPagamento($tipo_id);
            $processo = new Processo($processo_id);
            $entidade = new Pessoa($entidade_devedora_padrao_id);

            $clientes = self::buscarClientesDoProcesso($processo_id);

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();

            new TMessage('error', $e->getMessage());

            $this->montarEtapaInicial($param);
            return;
        }

        if (empty($clientes)) {
            new TMessage('warning', 'Nenhum cliente foi encontrado para esse processo.');

            $this->montarEtapaInicial($param);
            return;
        }

        $this->form->setFormTitle('Selecionar clientes da requisição');

        $hidden_tipo = new THidden('tipos_requisicao_pagamento_id');
        $hidden_tipo->setValue($tipo_id);

        $hidden_processo = new THidden('processo_id');
        $hidden_processo->setValue($processo_id);

        $hidden_entidade = new THidden('entidade_devedora_padrao_id');
        $hidden_entidade->setValue($entidade_devedora_padrao_id);

        $hidden_conta_indicada_mle_padrao = new THidden('conta_indicada_mle_padrao');
        $hidden_conta_indicada_mle_padrao->setValue($conta_indicada_mle_padrao);

        $hidden_data_requerimento_padrao = new THidden('data_requerimento_padrao');
        $hidden_data_requerimento_padrao->setValue($data_requerimento_padrao);

        $hidden_data_base_padrao = new THidden('data_base_padrao');
        $hidden_data_base_padrao->setValue($data_base_padrao);

        $hidden_requisicao = new THidden('requisicao_pagamento_id');
        $hidden_requisicao->setValue($requisicao_id);

        $hidden_modo = new THidden('modo_edicao');
        $hidden_modo->setValue($modo_edicao ? 1 : null);

        $this->form->addField($hidden_requisicao);
        $this->form->addField($hidden_modo);

        $this->form->addContent([$hidden_requisicao, $hidden_modo]);

        $this->form->addField($hidden_tipo);
        $this->form->addField($hidden_processo);
        $this->form->addField($hidden_entidade);
        $this->form->addField($hidden_conta_indicada_mle_padrao);
        $this->form->addField($hidden_data_requerimento_padrao);
        $this->form->addField($hidden_data_base_padrao);

        $this->form->addContent([
            $hidden_tipo,
            $hidden_processo,
            $hidden_entidade,
            $hidden_conta_indicada_mle_padrao,
            $hidden_data_requerimento_padrao,
            $hidden_data_base_padrao
        ]);

        $numero_processo = $processo->numero_cnj_numero ?? $processo->numero_outro ?? $processo_id;

        $resumo = new TElement('div');
        $resumo->setProperty('class', 'req-summary-box');
        $resumo->add('<b>Tipo:</b> ' . self::h($tipo->nome) . '<br>');
        $resumo->add('<b>Processo:</b> ' . self::h($numero_processo) . '<br>');
        $resumo->add('<b>Entidade devedora/devedor:</b> ' . self::h($entidade->nome) . '<br>');
        $resumo->add('<b>Conta Indicada para Levantamento:</b> ' . self::h($conta_indicada_mle_padrao) . '<br>');
        $resumo->add('<b>Data do requerimento:</b> ' . self::h(self::formatarDataBR($data_requerimento_padrao)) . '<br>');
        $resumo->add('<b>Data base do cálculo:</b> ' . self::h(self::formatarDataBR($data_base_padrao)) . '<br>');
        $resumo->add('<b>Clientes encontrados:</b> ' . count($clientes));

        $this->form->addContent([$resumo]);

        $check = new TCheckGroup('clientes_selecionados');

        $items = [];
        $valores_marcados = [];

        foreach ($clientes as $cliente) {
            $label = $cliente->nome;

            if (!empty($cliente->cpf_cnpj)) {
                $label .= ' - ' . $cliente->cpf_cnpj;
            }

            $items[$cliente->id] = $label;

            if (!empty($clientes_marcados)) {
                if (in_array((int) $cliente->id, $clientes_marcados)) {
                    $valores_marcados[] = $cliente->id;
                }
            } else {
                $valores_marcados[] = $cliente->id;
            }
        }

        $check->addItems($items);
        $check->setValue($valores_marcados);
        $check->setLayout('vertical');

        $this->form->addField($check);

        $box = new TElement('div');
        $box->setProperty('class', 'req-client-select-box');

        $label = new TElement('div');
        $label->setProperty('class', 'req-client-select-title');
        $label->add('Marque os clientes que farão parte desta requisição');

        $selectActions = new TElement('div');
        $selectActions->setProperty('class', 'req-select-actions');

        $btnMarcarTodos = new TElement('button');
        $btnMarcarTodos->setProperty('type', 'button');
        $btnMarcarTodos->setProperty('class', 'req-select-action-btn');
        $btnMarcarTodos->setProperty('onclick', "this.closest('.req-client-select-box').querySelectorAll(\"input[type='checkbox']\").forEach(function(c){ c.checked = true; });");
        $btnMarcarTodos->add('Marcar todos');

        $btnLimpar = new TElement('button');
        $btnLimpar->setProperty('type', 'button');
        $btnLimpar->setProperty('class', 'req-select-action-btn');
        $btnLimpar->setProperty('onclick', "this.closest('.req-client-select-box').querySelectorAll(\"input[type='checkbox']\").forEach(function(c){ c.checked = false; });");
        $btnLimpar->add('Limpar seleção');

        $selectActions->add($btnMarcarTodos);
        $selectActions->add($btnLimpar);

        $list = new TElement('div');
        $list->setProperty('class', 'req-client-select-list');
        $list->add($check);

        $box->add($label);
        $box->add($selectActions);
        $box->add($list);

        $this->form->addContent([$box]);

        $btnVoltar = $this->form->addAction(
            'Voltar',
            new TAction([__CLASS__, 'onVoltar']),
            'fa:arrow-left #333333'
        );
        $btnVoltar->class = 'btn btn-default';

        $btnSeguir = $this->form->addAction(
            'Seguir',
            new TAction([__CLASS__, 'onSeguirClientes']),
            'fa:arrow-right #ffffff'
        );
        $btnSeguir->class = 'btn btn-primary';
    }

    private function montarEtapaClientes($param)
    {
        $tipo_id = (int) ($param['tipos_requisicao_pagamento_id'] ?? 0);
        $processo_id = (int) ($param['processo_id'] ?? 0);
        $entidade_devedora_padrao_id = (int) ($param['entidade_devedora_padrao_id'] ?? 0);
        $conta_indicada_mle_padrao = $param['conta_indicada_mle_padrao'] ?? '';
        $data_requerimento_padrao = $param['data_requerimento_padrao'] ?? '';
        $data_base_padrao = $param['data_base_padrao'] ?? '';

        $requisicao_id = (int) ($param['requisicao_pagamento_id'] ?? $param['key'] ?? 0);
        $modo_edicao = !empty($param['modo_edicao']) || !empty($requisicao_id);
        $itens_requisicao = [];

        $clientes_ids_param = $param['clientes_ids'] ?? '';

        if (is_array($clientes_ids_param)) {
            $clientes_ids = $clientes_ids_param;
        } else {
            $clientes_ids = explode(',', $clientes_ids_param);
        }

        $clientes_ids = array_filter(array_map('intval', $clientes_ids));

        if (empty($tipo_id) || empty($processo_id) || empty($entidade_devedora_padrao_id) || empty($clientes_ids)) {
            $this->montarEtapaInicial($param);
            return;
        }

        try {
            TTransaction::open(self::$database);

            $conn = TTransaction::get();

            $tipo = new TiposRequisicaoPagamento($tipo_id);
            $processo = new Processo($processo_id);
            $entidade = new Pessoa($entidade_devedora_padrao_id);

            $clientes = self::buscarClientesDoProcesso($processo_id, $clientes_ids);

            if (!empty($requisicao_id)) {
                $itens_requisicao = self::buscarItensDaRequisicaoIndexados($conn, $requisicao_id);
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();

            new TMessage('error', $e->getMessage());

            $this->montarEtapaInicial($param);
            return;
        }

        if (empty($clientes)) {
            new TMessage('warning', 'Nenhum cliente selecionado foi encontrado para esse processo.');

            $this->montarEtapaInicial($param);
            return;
        }

        $this->form->setFormTitle('Dados dos clientes da requisição');

        $hidden_tipo = new THidden('tipos_requisicao_pagamento_id');
        $hidden_tipo->setValue($tipo_id);

        $hidden_processo = new THidden('processo_id');
        $hidden_processo->setValue($processo_id);

        $hidden_entidade = new THidden('entidade_devedora_padrao_id');
        $hidden_entidade->setValue($entidade_devedora_padrao_id);

        $hidden_conta_indicada_mle_padrao = new THidden('conta_indicada_mle_padrao');
        $hidden_conta_indicada_mle_padrao->setValue($conta_indicada_mle_padrao);

        $hidden_data_requerimento_padrao = new THidden('data_requerimento_padrao');
        $hidden_data_requerimento_padrao->setValue($data_requerimento_padrao);

        $hidden_data_base_padrao = new THidden('data_base_padrao');
        $hidden_data_base_padrao->setValue($data_base_padrao);

        $hidden_clientes = new THidden('clientes_ids');
        $hidden_clientes->setValue(implode(',', $clientes_ids));

        $hidden_requisicao = new THidden('requisicao_pagamento_id');
        $hidden_requisicao->setValue($requisicao_id);

        $hidden_modo = new THidden('modo_edicao');
        $hidden_modo->setValue($modo_edicao ? 1 : null);

        $this->form->addField($hidden_requisicao);
        $this->form->addField($hidden_modo);

        $this->form->addContent([$hidden_requisicao, $hidden_modo]);

        $this->form->addField($hidden_tipo);
        $this->form->addField($hidden_processo);
        $this->form->addField($hidden_entidade);
        $this->form->addField($hidden_conta_indicada_mle_padrao);
        $this->form->addField($hidden_data_requerimento_padrao);
        $this->form->addField($hidden_data_base_padrao);
        $this->form->addField($hidden_clientes);

        $this->form->addContent([
            $hidden_tipo,
            $hidden_processo,
            $hidden_entidade,
            $hidden_conta_indicada_mle_padrao,
            $hidden_data_requerimento_padrao,
            $hidden_data_base_padrao,
            $hidden_clientes
        ]);

        $numero_processo = $processo->numero_cnj_numero ?? $processo->numero_outro ?? $processo_id;

        $resumo = new TElement('div');
        $resumo->setProperty('class', 'req-summary-box');
        $resumo->add('<b>Tipo:</b> ' . self::h($tipo->nome) . '<br>');
        $resumo->add('<b>Processo:</b> ' . self::h($numero_processo) . '<br>');
        $resumo->add('<b>Entidade devedora/devedor padrão:</b> ' . self::h($entidade->nome) . '<br>');
        $resumo->add('<b>Conta Indicada para Levantamento:</b> ' . self::h($conta_indicada_mle_padrao) . '<br>');
        $resumo->add('<b>Data do requerimento:</b> ' . self::h(self::formatarDataBR($data_requerimento_padrao)) . '<br>');
        $resumo->add('<b>Data base do cálculo:</b> ' . self::h(self::formatarDataBR($data_base_padrao)) . '<br>');
        $resumo->add('<b>Clientes selecionados:</b> ' . count($clientes));

        $this->form->addContent([$resumo]);

        $notebook = new TNotebook;
        $notebook->setSize('100%', 500);

        foreach ($clientes as $cliente) {
            $cliente_id = (int) $cliente->id;

            $item_cadastrado = $itens_requisicao[$cliente_id] ?? null;

            $hidden_pessoa = new THidden("cliente_{$cliente_id}_pessoa_id");
            $hidden_pessoa->setValue($cliente_id);

            $nome = new TEntry("cliente_{$cliente_id}_nome");
            $nome->setValue($cliente->nome);
            $nome->setEditable(false);
            $nome->setSize('100%');

            $cpf = new TEntry("cliente_{$cliente_id}_cpf");
            $cpf->setValue($cliente->cpf_cnpj ?? '');
            $cpf->setEditable(false);
            $cpf->setSize('100%');

            $data_nascimento = new TEntry("cliente_{$cliente_id}_data_nascimento");
            $data_nascimento->setValue(self::formatarDataBR($cliente->dt_nascimento_abertura ?? null));
            $data_nascimento->setEditable(false);
            $data_nascimento->setSize('100%');

            $processo_campo = new TEntry("cliente_{$cliente_id}_processo");
            $processo_campo->setValue($numero_processo);
            $processo_campo->setEditable(false);
            $processo_campo->setSize('100%');

            $entidade_devedora = self::criarCampoEntidadeDevedora(
                "cliente_{$cliente_id}_entidade_devedora_id",
                $item_cadastrado->entidade_devedora_id ?? $entidade_devedora_padrao_id
            );

            $valor = new TNumeric("cliente_{$cliente_id}_valor", 2, ',', '.', true);
            $valor->setSize('100%');

            if (!empty($item_cadastrado)) {
                $valor->setValue(self::formatarValorBR($item_cadastrado->valor));
            }



            $data_base = new TDate("cliente_{$cliente_id}_data_base");
            $data_base->setMask('dd/mm/yyyy');
            $data_base->setDatabaseMask('yyyy-mm-dd');
            $data_base->setSize('100%');

            if (!empty($item_cadastrado)) {
                $data_base->setValue(self::formatarDataBR($item_cadastrado->data_base));
            } else {
                $data_base->setValue(self::formatarDataBR($data_base_padrao));
            }

            $data_requerimento = new TDate("cliente_{$cliente_id}_data_requerimento");
            $data_requerimento->setMask('dd/mm/yyyy');
            $data_requerimento->setDatabaseMask('yyyy-mm-dd');
            $data_requerimento->setSize('100%');

            if (!empty($item_cadastrado)) {
                $data_requerimento->setValue(self::formatarDataBR($item_cadastrado->data_requerimento));
            } else {
                $data_requerimento->setValue(self::formatarDataBR($data_requerimento_padrao));
            }

            $conta_indicada_mle = new TEntry("cliente_{$cliente_id}_conta_indicada_mle");
            $conta_indicada_mle->setSize('100%');
            $conta_indicada_mle->addValidation('Conta Indicada para Levantamento', new TRequiredValidator);

            if (!empty($item_cadastrado)) {
                $conta_indicada_mle->setValue($item_cadastrado->conta_indicada_mle ?? '');
            } else {
                $conta_indicada_mle->setValue($conta_indicada_mle_padrao);
            }

            $obs = new TText("cliente_{$cliente_id}_obs");
            $obs->setSize('100%', 90);

            if (!empty($item_cadastrado)) {
                $obs->setValue($item_cadastrado->obs);
            }

          $fields = [
                $hidden_pessoa,
                $nome,
                $cpf,
                $data_nascimento,
                $processo_campo,
                $entidade_devedora,
                $conta_indicada_mle,
                $valor,
                $data_base,
                $data_requerimento,
                $obs
            ];

            foreach ($fields as $field) {
                $this->form->addField($field);
            }

        $labelDataRequerimento = 'Data do requerimento';

        $labelValor = ($tipo_id == 3)
            ? 'Valor do MLE'
            : 'Valor';

        $labelDataBase = 'Data base do cálculo';

        $labelContaIndicada = 'Conta Indicada para Levantamento';

            $box = new TVBox;
            $box->style = 'width: 100%; padding: 10px;';

            $box->add($hidden_pessoa);

            $grid = new TElement('div');
            $grid->setProperty('class', 'req-client-grid');

            $grid->add(self::criarCampoVisual('Nome', $nome));
            $grid->add(self::criarCampoVisual('CPF', $cpf));
            $grid->add(self::criarCampoVisual('Data de nascimento', $data_nascimento));
            $grid->add(self::criarCampoVisual('Processo', $processo_campo));
            
            $grid->add(self::criarCampoVisual('Entidade devedora/devedor', $entidade_devedora, true));
            $grid->add(self::criarCampoVisual($labelContaIndicada, $conta_indicada_mle, true));
            $grid->add(self::criarCampoVisual($labelDataRequerimento, $data_requerimento, true));
            $grid->add(self::criarCampoVisual($labelValor, $valor, true));
            $grid->add(self::criarCampoVisual($labelDataBase, $data_base, true));

            $obsWrapper = new TElement('div');
            $obsWrapper->setProperty('class', 'req-client-field req-client-field-full');

            $obsLabel = new TLabel('Observação');
            $obsLabel->setProperty('class', 'req-client-label');

            $obsWrapper->add($obsLabel);
            $obsWrapper->add($obs);

            $grid->add($obsWrapper);

            $box->add($grid);

            $titulo_aba = $cliente->nome;

            if (mb_strlen($titulo_aba) > 24) {
                $titulo_aba = mb_substr($titulo_aba, 0, 24) . '...';
            }

            $notebook->appendPage($titulo_aba, $box);
        }

        $tabsWrapper = new TElement('div');
        $tabsWrapper->setProperty('class', 'req-tabs-wrapper');

        $tabsWrapper->add($notebook);

        $this->form->addContent([$tabsWrapper]);

        $btnVoltar = $this->form->addAction(
            'Voltar para seleção',
            new TAction([__CLASS__, 'onVoltarSelecao']),
            'fa:arrow-left #333333'
        );
        $btnVoltar->class = 'btn btn-default';

        $btnSalvar = $this->form->addAction(
            'Salvar requisição',
            new TAction([__CLASS__, 'onSalvar']),
            'fa:save #ffffff'
        );
        $btnSalvar->class = 'btn btn-success';
    }

    public static function onSeguir($param = null)
    {
        try {
            $tipo_id = (int) ($param['tipos_requisicao_pagamento_id'] ?? 0);
            $processo_id = (int) ($param['processo_id'] ?? 0);
            $entidade_devedora_padrao_id = (int) ($param['entidade_devedora_padrao_id'] ?? 0);
            $conta_indicada_mle_padrao = trim((string) ($param['conta_indicada_mle_padrao'] ?? ''));
            $data_requerimento_padrao = $param['data_requerimento_padrao'] ?? '';
            $data_base_padrao = $param['data_base_padrao'] ?? '';

            if (empty($tipo_id)) {
                throw new Exception('Selecione o tipo da requisição.');
            }

            if (empty($processo_id)) {
                throw new Exception('Selecione o processo.');
            }

            if (empty($entidade_devedora_padrao_id)) {
                throw new Exception('Selecione a entidade devedora/devedor.');
            }

            if ($conta_indicada_mle_padrao === '') {
                throw new Exception('Informe a Conta Indicada para Levantamento.');
            }

            if (empty($data_requerimento_padrao)) {
                throw new Exception('Informe a Data do requerimento.');
            }

            if (empty($data_base_padrao)) {
                throw new Exception('Informe a Data base do cálculo.');
            }

            TTransaction::open(self::$database);

            $processo = new Processo($processo_id);

            $numero = $processo->numero_cnj_numero ?? $processo->numero_outro ?? '';

            if (!empty($numero) && preg_match('/\/[0-9]+$/', $numero)) {
                throw new Exception('O processo selecionado não parece ser um processo principal. Selecione um processo sem final /01, /02, /03 etc.');
            }

            $conn = TTransaction::get();
            self::validarEntidadeDevedora($conn, $entidade_devedora_padrao_id);

            TTransaction::close();

            TApplication::loadPage(__CLASS__, 'onShow', [
                'step' => 'selecionar_clientes',
                'tipos_requisicao_pagamento_id' => $tipo_id,
                'processo_id' => $processo_id,
                'entidade_devedora_padrao_id' => $entidade_devedora_padrao_id,
                'conta_indicada_mle_padrao' => $conta_indicada_mle_padrao,
                'data_requerimento_padrao' => $data_requerimento_padrao,
                'data_base_padrao' => $data_base_padrao,
                'register_state' => 'false'
            ]);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onSeguirClientes($param = null)
    {
        try {
            $tipo_id = (int) ($param['tipos_requisicao_pagamento_id'] ?? 0);
            $processo_id = (int) ($param['processo_id'] ?? 0);
            $entidade_devedora_padrao_id = (int) ($param['entidade_devedora_padrao_id'] ?? 0);
            $conta_indicada_mle_padrao = trim((string) ($param['conta_indicada_mle_padrao'] ?? ''));
            $data_requerimento_padrao = $param['data_requerimento_padrao'] ?? '';
            $data_base_padrao = $param['data_base_padrao'] ?? '';

            $requisicao_id = (int) ($param['requisicao_pagamento_id'] ?? $param['key'] ?? 0);
            $modo_edicao = !empty($param['modo_edicao']) || !empty($requisicao_id);

            $selecionados = $param['clientes_selecionados'] ?? [];

            if (!is_array($selecionados)) {
                $selecionados = [$selecionados];
            }

            $selecionados = array_filter(array_map('intval', $selecionados));

            if (empty($tipo_id)) {
                throw new Exception('Tipo da requisição não informado.');
            }

            if (empty($processo_id)) {
                throw new Exception('Processo não informado.');
            }

            if (empty($entidade_devedora_padrao_id)) {
                throw new Exception('Entidade devedora/devedor não informada.');
            }

            if (empty($selecionados)) {
                throw new Exception('Selecione pelo menos um cliente para continuar.');
            }

            TApplication::loadPage(__CLASS__, 'onShow', [
                'step' => 'clientes',
                'requisicao_pagamento_id' => $requisicao_id,
                'key' => $requisicao_id,
                'modo_edicao' => $modo_edicao ? 1 : null,
                'tipos_requisicao_pagamento_id' => $tipo_id,
                'processo_id' => $processo_id,
                'entidade_devedora_padrao_id' => $entidade_devedora_padrao_id,
                'conta_indicada_mle_padrao' => $conta_indicada_mle_padrao,
                'data_requerimento_padrao' => $data_requerimento_padrao,
                'data_base_padrao' => $data_base_padrao,
                'clientes_ids' => implode(',', $selecionados),
                'register_state' => 'false'
            ]);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onSalvar($param = null)
    {
        try {
            $requisicao_id = (int) ($param['requisicao_pagamento_id'] ?? $param['key'] ?? 0);
            $modo_edicao = !empty($param['modo_edicao']) || !empty($requisicao_id);

            $tipo_id = (int) ($param['tipos_requisicao_pagamento_id'] ?? 0);
            $processo_id = (int) ($param['processo_id'] ?? 0);
            $clientes_raw = $param['clientes_ids'] ?? '';

            if (empty($tipo_id)) {
                throw new Exception('Tipo da requisição não informado.');
            }

            if (empty($processo_id)) {
                throw new Exception('Processo não informado.');
            }

            if (empty($clientes_raw)) {
                throw new Exception('Nenhum cliente foi encontrado para salvar.');
            }

            $clientes_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $clientes_raw)))));

            if (empty($clientes_ids)) {
                throw new Exception('Nenhum cliente foi encontrado para salvar.');
            }

            TTransaction::open(self::$database);

            $conn = TTransaction::get();

            $status_id = self::buscarStatusInicialId($conn);
            $user_id = TSession::getValue('userid');

            if ($modo_edicao) {
                $requisicao = new RequisicaoPagamento($requisicao_id);
                $requisicao->processo_id = $processo_id;
                $requisicao->tipos_requisicao_pagamento_id = $tipo_id;
                $requisicao->store();
            } else {
                $requisicao = new RequisicaoPagamento;
                $requisicao->processo_id = $processo_id;
                $requisicao->tipos_requisicao_pagamento_id = $tipo_id;
                $requisicao->data_criacao = date('Y-m-d H:i:s');
                $requisicao->criacao_user_id = $user_id;
                $requisicao->store();

                $requisicao_id = $requisicao->id;
            }

            $itens_atuais = [];

            if ($modo_edicao) {
                $itens_atuais = self::buscarItensDaRequisicaoIndexados($conn, $requisicao_id);

                foreach ($itens_atuais as $pessoa_id_atual => $item_atual) {
                    $pessoa_id_atual = (int) $pessoa_id_atual;

                    if (!in_array($pessoa_id_atual, $clientes_ids, true)) {
                        self::excluirClienteDaRequisicao($conn, $requisicao_id, $item_atual->id);

                        unset($itens_atuais[$pessoa_id_atual]);
                    }
                }
            }

            foreach ($clientes_ids as $cliente_id) {
                $cliente_id = (int) $cliente_id;

                $entidade_devedora_id = $param["cliente_{$cliente_id}_entidade_devedora_id"] ?? null;
                $valor = $param["cliente_{$cliente_id}_valor"] ?? null;
                $data_base = $param["cliente_{$cliente_id}_data_base"] ?? null;
                $data_requerimento = $param["cliente_{$cliente_id}_data_requerimento"] ?? null;
                $conta_indicada_mle = $param["cliente_{$cliente_id}_conta_indicada_mle"] ?? null;
                $obs = $param["cliente_{$cliente_id}_obs"] ?? null;

                if (empty($entidade_devedora_id)) {
                    throw new Exception('Informe a entidade devedora/devedor para todos os clientes.');
                }

                if ($valor === null || $valor === '') {
                    throw new Exception('Informe o valor para todos os clientes.');
                }

                if (empty($data_base)) {
                    throw new Exception('Informe a Data base do cálculo para todos os clientes.');
                }

                if (empty($data_requerimento)) {
                    throw new Exception('Informe a data do requerimento para todos os clientes.');
                }

                if ($conta_indicada_mle === null || trim((string) $conta_indicada_mle) === '') {
                    throw new Exception('Informe a Conta Indicada para Levantamento para todos os clientes.');
                }

                self::validarEntidadeDevedora($conn, $entidade_devedora_id);

                if (!empty($itens_atuais[$cliente_id])) {
                    $item = new RequisicaoPagamentoCliente($itens_atuais[$cliente_id]->id);
                } else {
                    $item = new RequisicaoPagamentoCliente;
                    $item->requisicao_pagamento_id = $requisicao_id;
                    $item->status_requisicao_pagamento_id = $status_id;
                    $item->pessoa_id = $cliente_id;
                    $item->data_criacao = date('Y-m-d');
                    $item->criacao_user_id = $user_id;
                }

                if (empty($item->status_requisicao_pagamento_id)) {
                    $item->status_requisicao_pagamento_id = $status_id;
                }

                $item->entidade_devedora_id = $entidade_devedora_id;
                $item->valor = self::converterValorBanco($valor);
                $item->data_base = self::converterDataBanco($data_base);
                $item->data_requerimento = self::converterDataBanco($data_requerimento);
                $item->obs = $obs;
                $item->conta_indicada_mle = $conta_indicada_mle;
                
                $item->store();

                if ($tipo_id == 3) {
                    self::salvarLancamentoInicialMle(
                        $conn,
                        $item->id,
                        $processo_id,
                        $data_requerimento,
                        $data_base,
                        $valor,
                        $conta_indicada_mle
                    );
                }
            }

            TTransaction::close();

            $action = new TAction([__CLASS__, 'onFinalizarSalvar']);
            $action->setParameter('key', $requisicao_id);

            new TMessage(
                'info',
                $modo_edicao ? 'Requisição de pagamento atualizada com sucesso.' : 'Requisição de pagamento cadastrada com sucesso.',
                $action
            );
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onFinalizarSalvar($param = null)
    {
        $requisicao_id = $param['key'] ?? null;

        TApplication::loadPage('RequisicaoPagamentoListagemHeaderList', 'onShow', [
            'register_state' => 'false'
        ]);

        if (!empty($requisicao_id)) {
            TScript::create("
                setTimeout(function() {
                    __adianti_load_page('index.php?class=RequisicaoPagamentoVisualizacao&method=onShow&key={$requisicao_id}&register_state=false');
                }, 500);
            ");
        }
    }

    public static function onVoltar($param = null)
    {
        TApplication::loadPage(__CLASS__, 'onShow', [
            'tipos_requisicao_pagamento_id' => $param['tipos_requisicao_pagamento_id'] ?? null,
            'processo_id' => $param['processo_id'] ?? null,
            'entidade_devedora_padrao_id' => $param['entidade_devedora_padrao_id'] ?? null,
            'conta_indicada_mle_padrao' => $param['conta_indicada_mle_padrao'] ?? null,
            'data_requerimento_padrao' => $param['data_requerimento_padrao'] ?? null,
            'data_base_padrao' => $param['data_base_padrao'] ?? null,
            'register_state' => 'false'
        ]);
    }

    public static function onVoltarSelecao($param = null)
    {
        $requisicao_id = (int) ($param['requisicao_pagamento_id'] ?? $param['key'] ?? 0);

        TApplication::loadPage(__CLASS__, 'onShow', [
            'step' => 'selecionar_clientes',
            'requisicao_pagamento_id' => $requisicao_id,
            'key' => $requisicao_id,
            'modo_edicao' => !empty($requisicao_id) ? 1 : null,
            'tipos_requisicao_pagamento_id' => $param['tipos_requisicao_pagamento_id'] ?? null,
            'processo_id' => $param['processo_id'] ?? null,
            'entidade_devedora_padrao_id' => $param['entidade_devedora_padrao_id'] ?? null,
            'conta_indicada_mle_padrao' => $param['conta_indicada_mle_padrao'] ?? null,
            'data_requerimento_padrao' => $param['data_requerimento_padrao'] ?? null,
            'data_base_padrao' => $param['data_base_padrao'] ?? null,
            'clientes_ids' => $param['clientes_ids'] ?? null,
            'register_state' => 'false'
        ]);
    }

    private static function buscarClientesDoProcesso($processo_id, $clientes_ids = null)
    {
        $conn = TTransaction::get();

        $filtro_clientes = '';
        $params = [$processo_id];

        if (!empty($clientes_ids)) {
            $clientes_ids = array_filter(array_map('intval', $clientes_ids));

            if (!empty($clientes_ids)) {
                $placeholders = implode(',', array_fill(0, count($clientes_ids), '?'));
                $filtro_clientes = " AND pe.id IN ({$placeholders}) ";

                foreach ($clientes_ids as $id) {
                    $params[] = $id;
                }
            }
        }

        $sql = "
            SELECT DISTINCT
                pe.id,
                pe.nome,
                pe.cpf_cnpj,
                pe.dt_nascimento_abertura
            FROM contrato_processo cp
            JOIN contrato_pessoa cpe
                ON cpe.contrato_id = cp.contrato_id
            JOIN pessoa pe
                ON pe.id = cpe.cliente_id
            WHERE cp.processo_id = ?
            {$filtro_clientes}
            ORDER BY pe.nome
        ";

        $sth = $conn->prepare($sql);
        $sth->execute($params);

        return $sth->fetchAll(PDO::FETCH_OBJ);
    }

    private static function prepararParametrosEdicao($param)
    {
        $requisicao_id = (int) ($param['requisicao_pagamento_id'] ?? $param['key'] ?? 0);

        if (empty($requisicao_id)) {
            return $param;
        }

        TTransaction::open(self::$database);

        $conn = TTransaction::get();

        $sql = "
            SELECT
                id,
                processo_id,
                tipos_requisicao_pagamento_id
            FROM requisicao_pagamento
            WHERE id = ?
            LIMIT 1
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([$requisicao_id]);

        $requisicao = $sth->fetch(PDO::FETCH_OBJ);

        if (empty($requisicao)) {
            throw new Exception('Requisição de pagamento não encontrada.');
        }

        $itens = self::buscarItensDaRequisicao($conn, $requisicao_id);

        $clientes_ids = [];
        $entidade_padrao_id = null;
        $conta_indicada_mle_padrao = null;
        $data_requerimento_padrao = null;
        $data_base_padrao = null;

        foreach ($itens as $item) {
            $clientes_ids[] = (int) $item->pessoa_id;

            if (empty($entidade_padrao_id) && !empty($item->entidade_devedora_id)) {
                $entidade_padrao_id = $item->entidade_devedora_id;
            }

            if (empty($conta_indicada_mle_padrao) && !empty($item->conta_indicada_mle)) {
                $conta_indicada_mle_padrao = $item->conta_indicada_mle;
            }

            if (empty($data_requerimento_padrao) && !empty($item->data_requerimento)) {
                $data_requerimento_padrao = self::formatarDataBR($item->data_requerimento);
            }

            if (empty($data_base_padrao) && !empty($item->data_base)) {
                $data_base_padrao = self::formatarDataBR($item->data_base);
            }
        }

        TTransaction::close();

        $param['requisicao_pagamento_id'] = $requisicao_id;
        $param['tipos_requisicao_pagamento_id'] = $param['tipos_requisicao_pagamento_id'] ?? $requisicao->tipos_requisicao_pagamento_id;
        $param['processo_id'] = $param['processo_id'] ?? $requisicao->processo_id;
        $param['entidade_devedora_padrao_id'] = $param['entidade_devedora_padrao_id'] ?? $entidade_padrao_id;
        $param['conta_indicada_mle_padrao'] = $param['conta_indicada_mle_padrao'] ?? $conta_indicada_mle_padrao;
        $param['data_requerimento_padrao'] = $param['data_requerimento_padrao'] ?? $data_requerimento_padrao;
        $param['data_base_padrao'] = $param['data_base_padrao'] ?? $data_base_padrao;
        $param['clientes_ids'] = $param['clientes_ids'] ?? implode(',', $clientes_ids);

        return $param;
    }

    private static function buscarItensDaRequisicao($conn, $requisicao_id)
    {
        $sql = "
            SELECT
                id,
                requisicao_pagamento_id,
                pessoa_id,
                status_requisicao_pagamento_id,
                entidade_devedora_id,
                valor,
                data_base,
                data_requerimento,
                obs,
                conta_indicada_mle
            FROM requisicao_pagamento_cliente
            WHERE requisicao_pagamento_id = ?
            ORDER BY id
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([(int) $requisicao_id]);

        return $sth->fetchAll(PDO::FETCH_OBJ);
    }

    private static function buscarItensDaRequisicaoIndexados($conn, $requisicao_id)
    {
        $itens = self::buscarItensDaRequisicao($conn, $requisicao_id);

        $indexados = [];

        foreach ($itens as $item) {
            $indexados[(int) $item->pessoa_id] = $item;
        }

        return $indexados;
    }

    private static function formatarValorBR($valor)
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return number_format((float) $valor, 2, ',', '.');
    }

    private static function excluirClienteDaRequisicao($conn, $requisicao_id, $requisicao_pagamento_cliente_id)
    {
        $requisicao_id = (int) $requisicao_id;
        $requisicao_pagamento_cliente_id = (int) $requisicao_pagamento_cliente_id;

        if (empty($requisicao_id) || empty($requisicao_pagamento_cliente_id)) {
            return;
        }

        self::excluirDependenciasDoClienteDaRequisicao($conn, $requisicao_pagamento_cliente_id);

        $sql = "
            DELETE FROM requisicao_pagamento_cliente
            WHERE id = ?
            AND requisicao_pagamento_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $requisicao_pagamento_cliente_id,
            $requisicao_id
        ]);
    }

    private static function excluirDependenciasDoClienteDaRequisicao($conn, $requisicao_pagamento_cliente_id)
    {
        $requisicao_pagamento_cliente_id = (int) $requisicao_pagamento_cliente_id;

        if (empty($requisicao_pagamento_cliente_id)) {
            return;
        }

        $sql = "
            SELECT
                table_schema,
                table_name
            FROM information_schema.columns
            WHERE column_name = 'requisicao_pagamento_cliente_id'
            AND table_schema = current_schema()
            AND table_name <> 'requisicao_pagamento_cliente'
        ";

        $tabelas = $conn->query($sql)->fetchAll(PDO::FETCH_OBJ);

        foreach ($tabelas as $tabela) {
            $schema = str_replace('"', '""', $tabela->table_schema);
            $nomeTabela = str_replace('"', '""', $tabela->table_name);

            $sqlDelete = "
                DELETE FROM \"{$schema}\".\"{$nomeTabela}\"
                WHERE requisicao_pagamento_cliente_id = ?
            ";

            $stmt = $conn->prepare($sqlDelete);
            $stmt->execute([$requisicao_pagamento_cliente_id]);
        }
    }

    private static function buscarStatusInicialId($conn)
    {
        $sql = "
            SELECT id
            FROM status_requisicao_pagamento
            ORDER BY
                CASE
                    WHEN UPPER(nome) IN ('CADASTRADO', 'CADASTRADA') THEN 1
                    WHEN UPPER(nome) = 'RASCUNHO' THEN 2
                    ELSE 3
                END,
                id
            LIMIT 1
        ";

        $row = $conn->query($sql)->fetch(PDO::FETCH_OBJ);

        if (empty($row)) {
            throw new Exception('Cadastre pelo menos um status em status_requisicao_pagamento.');
        }

        return $row->id;
    }

    private static function criarCampoEntidadeDevedora($name, $value = null)
    {
        $criteria_entidade = new TCriteria;
        $criteria_entidade->add(
            new TFilter(
                'id',
                'in',
                "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = " . self::GRUPO_ENTIDADE_DEVEDORA . ")"
            )
        );

        $field = new TDBUniqueSearch(
            $name,
            self::$database,
            'Pessoa',
            'id',
            'nome',
            'nome',
            $criteria_entidade
        );

        $field->setSize('100%');
        $field->setMinLength(1);
        $field->setMask('{nome}');

        if (method_exists($field, 'setOperator')) {
            $field->setOperator('ilike');
        }

        if (!empty($value)) {
            $field->setValue($value);
        }

        return $field;
    }

    private static function validarEntidadeDevedora($conn, $pessoa_id)
    {
        $sql = "
            SELECT 1
            FROM pessoa_grupo
            WHERE pessoa_id = ?
              AND grupo_id = " . self::GRUPO_ENTIDADE_DEVEDORA . "
            LIMIT 1
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([$pessoa_id]);

        $existe = $sth->fetchColumn();

        if (!$existe) {
            throw new Exception('A entidade devedora/devedor selecionada não pertence ao grupo de Parte Contrária.');
        }
    }

    private static function converterValorBanco($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = trim($valor);
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

        return $valor;
    }

    private static function converterDataBanco($data)
    {
        if (empty($data)) {
            return null;
        }

        if (strpos($data, '/') !== false) {
            $partes = explode('/', $data);

            if (count($partes) == 3) {
                return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
            }
        }

        return substr($data, 0, 10);
    }

    private static function formatarDataBR($data)
    {
        if (empty($data)) {
            return '';
        }

        if (strpos($data, '-') !== false) {
            $partes = explode('-', substr($data, 0, 10));

            if (count($partes) == 3) {
                return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
            }
        }

        return $data;
    }

    private static function criarCampoVisual($label, $field, $required = false)
    {
        $wrapper = new TElement('div');
        $wrapper->setProperty('class', 'req-client-field');

        $labelElement = new TLabel($label);
        $labelElement->setProperty('class', $required ? 'req-client-label req-required-label' : 'req-client-label');

        $wrapper->add($labelElement);
        $wrapper->add($field);

        return $wrapper;
    }

    private static function salvarLancamentoInicialMle(
        $conn,
        $requisicaoPagamentoClienteId,
        $processoId,
        $dataPedidoMle,
        $dataDeposito,
        $valorMle,
        $contaIndicadaMle
    ) {
        $requisicaoPagamentoClienteId = (int) $requisicaoPagamentoClienteId;
        $processoId = (int) $processoId;

        if (empty($requisicaoPagamentoClienteId) || empty($processoId)) {
            return;
        }

        $dataPedidoMle = self::converterDataBanco($dataPedidoMle);
        $dataDeposito = self::converterDataBanco($dataDeposito);
        $valorMle = self::converterValorBanco($valorMle);

        $sqlBusca = "
            SELECT id
            FROM requisicao_pagamento_etapa3
            WHERE requisicao_pagamento_cliente_id = ?
            ORDER BY COALESCE(numero_ciclo, 1) ASC, id ASC
            LIMIT 1
        ";

        $sthBusca = $conn->prepare($sqlBusca);
        $sthBusca->execute([$requisicaoPagamentoClienteId]);

        $etapa3Id = $sthBusca->fetchColumn();

        if (!empty($etapa3Id)) {
            $sql = "
                UPDATE requisicao_pagamento_etapa3
                SET
                    processo_filho_id = ?,
                    numero_ciclo = 1,
                    data_deposito = ?,
                    valor_mle = ?,
                    conta_indicada_mle = ?,
                    data_pedido_mle = ?
                WHERE id = ?
                AND requisicao_pagamento_cliente_id = ?
            ";

            $sth = $conn->prepare($sql);
            $sth->execute([
                $processoId,
                $dataDeposito,
                $valorMle,
                $contaIndicadaMle,
                $dataPedidoMle,
                $etapa3Id,
                $requisicaoPagamentoClienteId
            ]);

            return;
        }

        $sql = "
            INSERT INTO requisicao_pagamento_etapa3
            (
                requisicao_pagamento_cliente_id,
                processo_filho_id,
                numero_ciclo,
                data_deposito,
                valor_bruto_depositado,
                valor_mle,
                conta_indicada_mle,
                data_pedido_mle,
                data_deferimento_mle,
                saldo_bruto,
                data_base_saldo,
                possui_saldo
            )
            VALUES (?, ?, 1, ?, NULL, ?, ?, ?, NULL, NULL, NULL, 'N')
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([
            $requisicaoPagamentoClienteId,
            $processoId,
            $dataDeposito,
            $valorMle,
            $contaIndicadaMle,
            $dataPedidoMle
        ]);
    }

    private static function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function aplicarCss()
    {
        $style = new TElement('style');

        $style->add("
            .req-info-box {
                background: #eef5ff;
                border: 1px solid #cfe2ff;
                color: #0D4069;
                padding: 12px 14px;
                border-radius: 10px;
                margin-bottom: 15px;
                font-size: 14px;
            }

            .req-summary-box {
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                color: #1E2843;
                padding: 12px 14px;
                border-radius: 10px;
                margin-bottom: 15px;
                font-size: 14px;
            }

            .req-initial-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 14px;
                max-width: 850px;
                width: 100%;
                margin-bottom: 15px;
            }

            .req-client-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
                width: 100%;
            }

            .req-client-field {
                display: flex;
                flex-direction: column;
                gap: 5px;
                width: 100%;
                text-align: left;
            }

            .req-client-field-full {
                grid-column: 1 / -1;
            }

            .req-client-label {
                font-weight: 600;
                color: #1E2843;
                font-size: 13px;
                margin-bottom: 2px;
                text-align: left;
            }

            .req-required-label {
                color: #dc2626 !important;
            }

            .req-client-select-box {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 14px;
                margin-bottom: 15px;
            }

            .req-client-select-title {
                font-weight: 600;
                color: #1E2843;
                margin-bottom: 10px;
                font-size: 14px;
            }

            .req-client-select-list {
                max-height: calc(100vh - 360px);
                min-height: 250px;
                overflow-y: auto;
                overflow-x: hidden;
                padding-right: 8px;
                overscroll-behavior: contain;
            }

            .req-client-select-list label {
                color: #1E2843;
                font-weight: 500;
                margin-bottom: 8px;
                display: block;
            }

            .req-client-select-list input[type='checkbox'] {
                margin-right: 8px;
            }

            .req-select-actions {
                display: flex;
                gap: 8px;
                margin-bottom: 12px;
                flex-wrap: wrap;
            }

            .req-select-action-btn {
                border: 1px solid #d7dce3;
                background: #ffffff;
                color: #1E2843;
                border-radius: 8px;
                padding: 6px 12px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .req-select-action-btn:hover {
                background: #f3f4f6;
            }

            .req-tabs-wrapper {
                width: 100%;
            }

            .req-tabs-controls {
                display: flex;
                justify-content: flex-end;
                gap: 6px;
                margin-bottom: 8px;
            }

            .req-tab-arrow {
                border: 1px solid #d7dce3;
                background: #ffffff;
                color: #1E2843;
                border-radius: 8px;
                width: 36px;
                height: 32px;
                font-size: 20px;
                font-weight: 700;
                line-height: 1;
                cursor: pointer;
            }

            .req-tab-arrow:hover {
                background: #f3f4f6;
            }

            .req-tabs-wrapper .nav-tabs {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                white-space: nowrap !important;
                scrollbar-width: thin;
                scroll-behavior: smooth;
                border-bottom: 1px solid #dee2e6;
            }

            .req-tabs-wrapper .nav-tabs > li {
                flex: 0 0 auto !important;
                float: none !important;
                width: auto !important;
            }

            .req-tabs-wrapper .nav-tabs > li > a {
                max-width: 210px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            @media (max-width: 768px) {
                .req-client-grid {
                    grid-template-columns: 1fr;
                }

                .req-initial-grid {
                    max-width: 100%;
                }

                .req-client-select-list {
                    max-height: calc(100vh - 330px);
                    min-height: 260px;
                }

                .req-tabs-wrapper .nav-tabs {
                    flex-direction: row !important;
                    flex-wrap: nowrap !important;
                    overflow-x: auto !important;
                }

                .req-tabs-wrapper .nav-tabs > li {
                    width: auto !important;
                }

                .req-tabs-wrapper .nav-tabs > li > a {
                    width: auto !important;
                    max-width: 220px;
                    border-radius: 8px 8px 0 0 !important;
                    color: #1E2843;
                    font-weight: 600;
                }

                .tab-content {
                    border-top: 1px solid #e5e7eb;
                    margin-top: 10px;
                    padding-top: 10px;
                }
            }
        ");

        $this->form->addContent([$style]);
    }
}