<?php

class ContaReceberList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Conta';
    private static $primaryKey = 'id';
    private static $formName = 'formList_Conta';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    use BuilderDatagridTrait;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        // define the form title
        $this->form->setFormTitle("Contas a receber");
        $this->limit = 20;

        $criteria_pessoa_id = new TCriteria();
        $criteria_categoria_conta_id = new TCriteria();
        $criteria_escritorio_id = new TCriteria();
        $criteria_profissional_id = new TCriteria();
        $criteria_tipo_documento_financeiro_id = new TCriteria();
        $criteria_pessoa_col = new TCriteria();
        $criteria_id = new TCriteria();
        $criteria_tipo_documento_financeiro_nome = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = TipoConta::RECEBER;
        $criteria_categoria_conta_id->add(new TFilter('tipo_conta_id', '=', $filterVar)); 
        $filterVar = TSession::getValue("userunitids");
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_escritorio_id->add(new TFilter('system_unit_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id in ($filterVar))")); 
        $filterVar = [Grupo::PARCEIRO, Grupo::PROFISSIONAL];
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id in ($filterVar))")); 
        $filterVar = TipoConta::RECEBER;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('tipo_conta_id', '=', $filterVar)); 
        $filterVar = [Grupo::PARCEIRO, Grupo::PROFISSIONAL];
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id in ($filterVar))")); 

        $this->showMethods = array_merge($this->showMethods, ['onVencidas', 'onVencer', 'onEmAberto']);

        $pessoa_id = new TDBUniqueSearch('pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_pessoa_id );
        $descricao = new TEntry('descricao');
        $filtro_rapido = new TCombo('filtro_rapido');
        $categoria_conta_id = new TDBCombo('categoria_conta_id', 'escritorio', 'CategoriaConta', 'id', '{nome}','nome asc' , $criteria_categoria_conta_id );
        $escritorio_id = new TDBCombo('escritorio_id', 'escritorio', 'Escritorio', 'id', '{nome}','nome asc' , $criteria_escritorio_id );
        $profissional_id = new TDBMultiSearch('profissional_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_profissional_id );
        $tipo_documento_financeiro_id = new TDBCombo('tipo_documento_financeiro_id', 'escritorio', 'TipoDocumentoFinanceiro', 'id', '{nome}','nome asc' , $criteria_tipo_documento_financeiro_id );
        $numero_documento = new TEntry('numero_documento');
        $quitada = new TRadioGroup('quitada');
        $data_emissao_ini = new TDate('data_emissao_ini');
        $data_emissao_fim = new TDate('data_emissao_fim');
        $dt_vencimento_ini = new TDate('dt_vencimento_ini');
        $data_vencimento_fim = new TDate('data_vencimento_fim');
        $pessoa_col = new TDBUniqueSearch('pessoa_col', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_pessoa_col );
        $id = new BDBSelectCheck('id', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_id );
        $tipo_documento_financeiro_nome = new TDBCombo('tipo_documento_financeiro_nome', 'escritorio', 'TipoDocumentoFinanceiro', 'id', '{nome}','nome asc' , $criteria_tipo_documento_financeiro_nome );
        $descricao_col = new TEntry('descricao_col');
        $data_emissao = new TDate('data_emissao');
        $quitada22 = new TCombo('quitada22');

        $descricao_col->exitOnEnter();

        $descricao_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $data_emissao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $pessoa_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $id->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $tipo_documento_financeiro_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $quitada22->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $descricao->forceUpperCase();
        $quitada->setLayout('horizontal');
        $quitada->setUseButton();
        $pessoa_col->setFilterColumns(["nome"]);
        $profissional_id->setFilterColumns(["nome"]);

        $pessoa_id->setMinLength(3);
        $pessoa_col->setMinLength(2);
        $profissional_id->setMinLength(0);

        $quitada22->addItems(["S"=>"Sim","N"=>"Não"]);
        $quitada->addItems(["S"=>"Sim","N"=>"Não",""=>"Ambos"]);
        $filtro_rapido->addItems(["1"=>"Vencidas","2"=>"À vencer"]);

        $quitada22->enableSearch();
        $filtro_rapido->enableSearch();
        $tipo_documento_financeiro_id->enableSearch();
        $tipo_documento_financeiro_nome->enableSearch();

        $data_emissao->setDatabaseMask('yyyy-mm-dd');
        $data_emissao_ini->setDatabaseMask('yyyy-mm-dd');
        $data_emissao_fim->setDatabaseMask('yyyy-mm-dd');
        $dt_vencimento_ini->setDatabaseMask('yyyy-mm-dd');
        $data_vencimento_fim->setDatabaseMask('yyyy-mm-dd');

        $pessoa_col->setMask('{nome}');
        $profissional_id->setMask('{nome}');
        $data_emissao->setMask('dd/mm/yyyy');
        $pessoa_id->setMask('{nome_formatado}');
        $data_emissao_ini->setMask('dd/mm/yyyy');
        $data_emissao_fim->setMask('dd/mm/yyyy');
        $dt_vencimento_ini->setMask('dd/mm/yyyy');
        $data_vencimento_fim->setMask('dd/mm/yyyy');

        $id->setSize('100%');
        $quitada->setSize(100);
        $pessoa_id->setSize('100%');
        $descricao->setSize('100%');
        $data_emissao->setSize(110);
        $quitada22->setSize('100%');
        $pessoa_col->setSize('100%');
        $filtro_rapido->setSize('100%');
        $escritorio_id->setSize('100%');
        $data_emissao_ini->setSize(150);
        $data_emissao_fim->setSize(150);
        $descricao_col->setSize('100%');
        $dt_vencimento_ini->setSize(150);
        $numero_documento->setSize('100%');
        $data_vencimento_fim->setSize(150);
        $categoria_conta_id->setSize('100%');
        $profissional_id->setSize('100%', 70);
        $tipo_documento_financeiro_id->setSize('100%');
        $tipo_documento_financeiro_nome->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Cliente:", null, '14px', null, '100%'),$pessoa_id],[new TLabel("Descrição:", null, '14px', null, '100%'),$descricao],[new TLabel("Filtro rápido:", null, '14px', null),$filtro_rapido]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Categoria:", null, '14px', null, '100%'),$categoria_conta_id],[new TLabel("Escritório:", null, '14px', null),$escritorio_id],[new TLabel("Profissional: ", null, '14px', null),$profissional_id]);
        $row2->layout = ['col-sm-4','col-sm-4','col-sm-4'];

        $row3 = $this->form->addFields([new TLabel("Tipo de documento:", null, '14px', null),$tipo_documento_financeiro_id],[new TLabel("Número do documento:", null, '14px', null),$numero_documento],[new TLabel("Quitada:", null, '14px', null, '100%'),$quitada]);
        $row3->layout = ['col-sm-4','col-sm-4','col-sm-4'];

        $row4 = $this->form->addFields([new TLabel("Data de emissão:", null, '14px', null, '100%'),$data_emissao_ini,new TLabel("até", null, '14px', null),$data_emissao_fim],[new TLabel("Data de vencimento", null, '14px', null, '100%'),$dt_vencimento_ini,new TLabel("até", null, '14px', null),$data_vencimento_fim],[]);
        $row4->layout = ['col-sm-4','col-sm-4','col-sm-4'];

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary'); 

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->enableUserProperties('fa fa-cog', 'btn btn-default', new TAction([$this, 'setDatagridProperties']));
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $filterVar = TSession::getValue("userunitid");
        $this->filter_criteria->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')"));
        $filterVar = TipoConta::RECEBER;
        $this->filter_criteria->add(new TFilter('tipo_conta_id', '=', $filterVar));

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);

        $column_pessoa_nome_transformed = new TDataGridColumn('pessoa->nome', "Pessoa", 'left' , '30%');
        $column_id_transformed = new TDataGridColumn('id', "Profissional", 'left');
        $column_tipo_documento_financeiro_nome = new TDataGridColumn('tipo_documento_financeiro->nome', "Tipo de documento", 'left');
        $column_numero_documentoatendimento_idcontrato_idprocesso_id = new TDataGridColumn('{numero_documento}{atendimento_id}{contrato_id}{processo_id}', "Número do documento", 'left');
        $column_descricao = new TDataGridColumn('descricao', "Descrição", 'left' , '20%');
        $column_total_conta_transformed = new TDataGridColumn('total_conta', "Total", 'center' , '100px');
        $column_data_emissao_transformed = new TDataGridColumn('data_emissao', "Emissão", 'center' , '100px');
        $column_quitada_transformed = new TDataGridColumn('quitada', "Quitada", 'center' , '100px');

        $column_pessoa_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {   
            TTransaction::open('escritorio');

            $table = new TElement('div');
            $table->style = 'display: flex; flex-direction: row; flex-wrap: wrap; gap: 4px;';

            $filterData = TSession::getValue(__CLASS__.'_filter_data');

            $normalizarData = function($valor){
                if(empty($valor)){
                    return null;
                }

                $valor = trim((string) $valor);

                if(strpos($valor, '/') !== false){
                    $partes = explode('/', $valor);

                    if(count($partes) == 3){
                        return $partes[2].'-'.str_pad($partes[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($partes[0], 2, '0', STR_PAD_LEFT);
                    }
                }

                return substr($valor, 0, 10);
            }
            ;

            $dataFiltroInicio = null;
            $dataFiltroFim = null;

            if($filterData){
                if(!empty($filterData->dt_vencimento_ini) || !empty($filterData->data_vencimento_fim)){
                    $dataFiltroInicio = $normalizarData($filterData->dt_vencimento_ini ?? null);
                    $dataFiltroFim = $normalizarData($filterData->data_vencimento_fim ?? null);
                }elseif(!empty($filterData->data_emissao_ini) || !empty($filterData->data_emissao_fim)){
                    $dataFiltroInicio = $normalizarData($filterData->data_emissao_ini ?? null);
                    $dataFiltroFim = $normalizarData($filterData->data_emissao_fim ?? null);
                }elseif(!empty($filterData->data_emissao)){
                    $dataFiltroInicio = $normalizarData($filterData->data_emissao);
                    $dataFiltroFim = $normalizarData($filterData->data_emissao);
                }
            }

            $lancamentos = Lancamento::where('conta_id', '=', $object->id)
                ->orderBy('dt_vencimento', 'asc')
                ->load();

            foreach($lancamentos as $lancamento){
                $dataVencimento = $normalizarData($lancamento->dt_vencimento);

                if($dataFiltroInicio && $dataVencimento < $dataFiltroInicio){
                    continue;
                }

                if($dataFiltroFim && $dataVencimento > $dataFiltroFim){
                    continue;
                }

                $valorBase = round((float) ($lancamento->valor ?? 0), 2);
                $acrescimo = round((float) ($lancamento->acrescimo ?? 0), 2);
                $desconto = round((float) ($lancamento->desconto ?? 0), 2);

                $valorTotal = $lancamento->valor_total !== null && $lancamento->valor_total !== ''
                    ? round((float) $lancamento->valor_total, 2)
                    : round($valorBase + $acrescimo - $desconto, 2);

                $tableDetail = new TElement('div');
                $tableDetail->style = 'display: flex; flex-direction: column';
                $tableDetail->add(TElement::tag('small', TDate::date2br($lancamento->dt_vencimento)));
                $tableDetail->add(TElement::tag('span', 'R$ '.number_format($valorTotal, 2, ',', '.')));

               if($lancamento->dt_pagamento){
                    $clazz = 'label-success';
                    $title = 'Quitada';

                    if($acrescimo > 0 || $desconto > 0){
                        $title .= ' - Valor base: R$ '.number_format($valorBase, 2, ',', '.');

                        if($acrescimo > 0){
                            $title .= ' - Acréscimo: R$ '.number_format($acrescimo, 2, ',', '.');
                        }

                        if($desconto > 0){
                            $title .= ' - Desconto: R$ '.number_format($desconto, 2, ',', '.');
                        }

                        $title .= ' - Valor total: R$ '.number_format($valorTotal, 2, ',', '.');
                    }
                }elseif($lancamento->cancelado == 'S'){
                    $clazz = 'label-blue';
                    $title = 'Cancelada';
                }elseif((float) $lancamento->saldo > 0){
                    $clazz = 'label-parcial';
                    $title = 'Pagamento parcial - Saldo restante: R$ '.number_format($lancamento->saldo, 2, ',', '.');
                }elseif($lancamento->dt_vencimento < date('Y-m-d')){
                    $clazz = 'label-danger';
                    $title = 'Atrasada';
                }else{
                    $clazz = 'label-default';
                    $title = 'Em aberto';
                }
                $tableDetail->class = 'card card-lancamento '.$clazz;
                $tableDetail->title = $title;

                if($clazz == 'label-blue'){
                    $tableDetail->style = 'display: flex; flex-direction: column; background-color: #2E9AFE; color: #FFF;';
                }

                if($clazz == 'label-parcial'){
                    $tableDetail->style = 'display: flex; flex-direction: column; background-color: #f59e0b; color: #FFF;';
                }

                $table->add($tableDetail);
            }

           /*
            * Se a conta veio de contrato, mostra TODOS os clientes
            * vinculados ao contrato.
            *
            * Se não tiver contrato, mantém exatamente o funcionamento antigo.
            */
            $clientesNomes = [];

            if (!empty($object->contrato_id))
            {
                $clientesContrato = ContratoPessoa::where(
                    'contrato_id',
                    '=',
                    $object->contrato_id
                )
                ->orderBy('id')
                ->load();

                foreach ($clientesContrato as $contratoPessoa)
                {
                    if (!empty($contratoPessoa->cliente_id))
                    {
                        $nomeCliente = $contratoPessoa->cliente->nome ?? null;

                        if (!empty($nomeCliente)) {
                            $clientesNomes[] = $nomeCliente;
                        }
                    }
                }
            }

            $clientesNomes = array_values(array_unique($clientesNomes));

            /*
            * Sem contrato ou sem clientes vinculados:
            * mantém o $value original da conta.
            */
            $nomeExibicao = !empty($clientesNomes)
                ? implode(', ', $clientesNomes)
                : $value;

            $div = new TElement('div');

            $div->add(
                TElement::tag(
                    'span',
                    $nomeExibicao,
                    [
                        'style' => '
                            color: var(--text-color-strong);
                            text-transform: uppercase;
                            font-size: 110%;
                        '
                    ]
                )
            );

            $div->add(
                TElement::tag(
                    'div',
                    'Lançamentos',
                    ['class' => 'title-lancamentos']
                )
            );

            $div->add($table);

            TTransaction::close();

            return $div;

        });

        $column_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
           try
            {
                if (empty($value)) {
                    return '';
                }

                static $cache = [];

                $contaId = (int) $value;

                if (isset($cache[$contaId])) {
                    return $cache[$contaId];
                }

                TTransaction::open('escritorio');

                $nomes = [];

                $profissionais = ContaProfissional::where('conta_id', '=', $contaId)->load();

                foreach ($profissionais as $contaProfissional) {
                    if (!empty($contaProfissional->pessoa_id)) {
                        $pessoa = Pessoa::find($contaProfissional->pessoa_id);

                        if ($pessoa) {
                            $nomes[] = $pessoa->nome;
                        }
                    }
                }

                TTransaction::close();

                $cache[$contaId] = implode(', ', $nomes);

                return $cache[$contaId];
            }
            catch (Exception $e)
            {
                if (TTransaction::get()) {
                    TTransaction::rollback();
                }

                return '';
            }

        });

        $column_total_conta_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_data_emissao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_quitada_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            $label = new TElement('span');
            $label->{'class'} = 'label label-';

            if ($value == 'S' || $value == 'T') {
                $label->{'class'} .= 'success';
                $label->add('Sim');    

                return $label;
            }

            $label->{'class'} .= 'danger';
            $label->add('Não');

            return $label;
        });        

        $order_descricao = new TAction(array($this, 'onReload'));
        $order_descricao->setParameter('order', 'descricao');
        $column_descricao->setAction($order_descricao);
        $order_total_conta_transformed = new TAction(array($this, 'onReload'));
        $order_total_conta_transformed->setParameter('order', 'total_conta');
        $column_total_conta_transformed->setAction($order_total_conta_transformed);
        $order_data_emissao_transformed = new TAction(array($this, 'onReload'));
        $order_data_emissao_transformed->setParameter('order', 'data_emissao');
        $column_data_emissao_transformed->setAction($order_data_emissao_transformed);
        $order_quitada_transformed = new TAction(array($this, 'onReload'));
        $order_quitada_transformed->setParameter('order', 'quitada');
        $column_quitada_transformed->setAction($order_quitada_transformed);

        $this->datagrid->addColumn($column_pessoa_nome_transformed);
        $this->datagrid->addColumn($column_id_transformed);
        $this->datagrid->addColumn($column_tipo_documento_financeiro_nome);
        $this->datagrid->addColumn($column_numero_documentoatendimento_idcontrato_idprocesso_id);
        $this->datagrid->addColumn($column_descricao);
        $this->datagrid->addColumn($column_total_conta_transformed);
        $this->datagrid->addColumn($column_data_emissao_transformed);
        $this->datagrid->addColumn($column_quitada_transformed);

        $action_onShow = new TDataGridAction(array('ContaFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar parcelas");
        $action_onShow->setImage('fas:search #00BCD4');
        $action_onShow->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onShow);

        $this->applyDatagridProperties();
        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onShow->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $td_pessoa_col = TElement::tag('td', $pessoa_col);
        $tr->add($td_pessoa_col);
        $td_id = TElement::tag('td', $id);
        $tr->add($td_id);
        $td_tipo_documento_financeiro_nome = TElement::tag('td', $tipo_documento_financeiro_nome);
        $tr->add($td_tipo_documento_financeiro_nome);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_descricao_col = TElement::tag('td', $descricao_col);
        $tr->add($td_descricao_col);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_data_emissao = TElement::tag('td', $data_emissao);
        $tr->add($td_data_emissao);
        $td_quitada22 = TElement::tag('td', $quitada22);
        $tr->add($td_quitada22);
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($pessoa_col);
        $this->datagrid_form->addField($id);
        $this->datagrid_form->addField($tipo_documento_financeiro_nome);
        $this->datagrid_form->addField($descricao_col);
        $this->datagrid_form->addField($data_emissao);
        $this->datagrid_form->addField($quitada22);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Contas a receber");
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;

        $panel->add($this->datagrid_form);

        $panel->addFooter($this->pageNavigation);

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';
        $headerActions->style = 'justify-content: space-between;';

        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';

        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';

        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);

        $this->datagrid_form->add($headerActions);

        $button_cadastrar = new TButton('button_button_cadastrar');
        $button_cadastrar->setAction(new TAction(['ContaForm', 'onShow']), "Cadastrar");
        $button_cadastrar->addStyleClass('btn-default');
        $button_cadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_cadastrar);

        $btnShowCurtainFilters = new TButton('button_btnShowCurtainFilters');
        $btnShowCurtainFilters->setAction(new TAction(['ContaReceberList', 'onShowCurtainFilters']), "Filtros");
        $btnShowCurtainFilters->addStyleClass('btn-default');
        $btnShowCurtainFilters->setImage('fas:filter #000000');

        $this->datagrid_form->addField($btnShowCurtainFilters);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['ContaReceberList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['ContaReceberList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $mostrar_vencidas = new TButton('button_mostrar_vencidas');
        $mostrar_vencidas->setAction(new TAction(['ContaReceberList', 'onVencidas']), "Vencidas");
        $mostrar_vencidas->addStyleClass('btn-default');
        $mostrar_vencidas->setImage('fas:calendar-times #FFC107');

        $this->datagrid_form->addField($mostrar_vencidas);

        $vencer = new TButton('button_vencer');
        $vencer->setAction(new TAction(['ContaReceberList', 'onVencer']), "À vencer");
        $vencer->addStyleClass('btn-default');
        $vencer->setImage('fas:calendar-day #4CAF50');

        $this->datagrid_form->addField($vencer);

        $em_aberto = new TButton('button_em_aberto');
        $em_aberto->setAction(new TAction(['ContaReceberList', 'onEmAberto']), "Em aberto");
        $em_aberto->addStyleClass('btn-default');
        $em_aberto->setImage('fas:calendar-check #3F51B5');

        $this->datagrid_form->addField($em_aberto);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['ContaReceberList', 'onExportCsv'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:table #00b894' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['ContaReceberList', 'onExportPdf'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['ContaReceberList', 'onExportXls']), 'datagrid_'.self::$formName, 'fas:file-excel #4CAF50' );

        $head_left_actions->add($button_cadastrar);
        $head_left_actions->add($btnShowCurtainFilters);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($mostrar_vencidas);
        $head_left_actions->add($vencer);
        $head_left_actions->add($em_aberto);

        $head_right_actions->add($dropdown_button_exportar);

        $this->datagrid_form->add($this->datagrid);

        $this->btnShowCurtainFilters = $btnShowCurtainFilters;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Financeiro","Contas a receber"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public function onExportCsv($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.csv';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $objects = $this->onReload();

                if ($objects)
                {
                    $handler = fopen($output, 'w');
                    TTransaction::open(self::$database);

                    foreach ($objects as $object)
                    {
                        $row = [];
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();

                            if (isset($object->$column_name))
                            {
                                $row[] = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos($column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $row[] = $object->render($column_name);
                            }
                        }

                        fputcsv($handler, $row);
                    }

                    fclose($handler);
                    TTransaction::close();
                }
                else
                {
                    throw new Exception(_t('No records found'));
                }

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onExportPdf($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.pdf';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $this->datagrid->prepareForPrinting();
                $this->onReload();

                $html = clone $this->datagrid;
                $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();

                $dompdf = new \Dompdf\Dompdf;
                $dompdf->loadHtml($contents);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                file_put_contents($output, $dompdf->output());

                $window = TWindow::create('PDF', 0.8, 0.8);
                $object = new TElement('object');
                $object->data  = $output;
                $object->type  = 'application/pdf';
                $object->style = "width: 100%; height:calc(100% - 10px)";

                $window->add($object);
                $window->show();
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onExportXls($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.xls';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $widths = [];
                $titles = [];

                foreach ($this->datagrid->getColumns() as $column)
                {
                    $titles[] = $column->getLabel();
                    $width    = 100;

                    if (is_null($column->getWidth()))
                    {
                        $width = 100;
                    }
                    else if (strpos((string)$column->getWidth(), '%') !== false)
                    {
                        $width = ((int) $column->getWidth()) * 5;
                    }
                    else if (is_numeric($column->getWidth()))
                    {
                        $width = $column->getWidth();
                    }

                    $widths[] = $width;
                }

                $table = new \TTableWriterXLS($widths);
                $table->addStyle('title',  'Helvetica', '10', 'B', '#ffffff', '#617FC3');
                $table->addStyle('data',   'Helvetica', '10', '',  '#000000', '#FFFFFF', 'LR');

                $table->addRow();

                foreach ($titles as $title)
                {
                    $table->addCell($title, 'center', 'title');
                }

                $this->limit = 0;
                $objects = $this->onReload();

                TTransaction::open(self::$database);
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $table->addRow();
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();
                            $value = '';
                            if (isset($object->$column_name))
                            {
                                $value = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $value = $object->render($column_name);
                            }

                            $table->addCell($value, 'center', 'data');
                        }
                    }
                }
                $table->save($output);
                TTransaction::close();

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public static function onShowCurtainFilters($param = null) 
    {
        try 
        {
            $object = new stdClass();
            $object->pessoa_id = null;
            $object->descricao = null;
            $object->filtro_rapido = null;
            $object->categoria_conta_id = null;
            $object->escritorio_id = null;
            $object->profissional_id = null;
            $object->tipo_documento_financeiro_id = null;
            $object->numero_documento = null;
            $object->quitada = null;
            $object->data_emissao_ini = null;
            $object->data_emissao_fim = null;
            $object->dt_vencimento_ini = null;
            $object->data_vencimento_fim = null;

            TForm::sendData(self::$formName, $object);

                        $filter = new self([]);

            $btnClose = new TButton('closeCurtain');
            $btnClose->class = 'btn btn-sm btn-default';
            $btnClose->style = 'margin-right:10px;';
            $btnClose->onClick = "Template.closeRightPanel();";
            $btnClose->setLabel("Fechar");
            $btnClose->setImage('fas:times');

            $filter->form->addHeaderWidget($btnClose);

            $page = new TPage();
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('page-name', 'ContaReceberListSearch');
            $page->setProperty('page_name', 'ContaReceberListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();

            $style = new TStyle('right-panel > .container-part[page-name=ContaReceberListSearch]');
            $style->width = '80% !important';
            $style->show(true);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onClearFilters($param = null) 
    {
        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $this->onReload(['offset' => 0, 'first_page' => 1]);

    }
    public function onRefresh($param = null) 
    {
        $this->onReload([]);
    }
    public function onVencidas($param = null) 
    {
        try 
        {
            $filters = [new TFilter('id', 'in', "(SELECT conta_id FROM lancamento WHERE dt_vencimento < '". date('Y-m-d 00:00') ."' and quitada = 'N')")];

            $data = new stdClass;
            $data->filtro_rapido = '1';

            TSession::setValue(__CLASS__.'_filter_data', $data);
            TSession::setValue(__CLASS__.'_filters', $filters);

            $this->onReload([]);
            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onVencer($param = null) 
    {
        try 
        {
            $filters = [new TFilter('id', 'in', "(SELECT conta_id FROM lancamento WHERE dt_vencimento >= '". date('Y-m-d 00:00') ."' and quitada = 'N')")];

            $data = new stdClass;
            $data->filtro_rapido = '2';

            TSession::setValue(__CLASS__.'_filter_data', $data);
            TSession::setValue(__CLASS__.'_filters', $filters);

            $this->onReload([]);
            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onEmAberto($param = null) 
    {
        try 
        {
            $filters = [new TFilter('quitada', '=', 'N')];

            $data = new stdClass;
            $data->quitada = 'N';

            TSession::setValue(__CLASS__.'_filter_data', $data);
            TSession::setValue(__CLASS__.'_filters', $filters);

            $this->onReload([]);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        if ((isset($param['static']) && ($param['static'] == '1')) || !empty($param['globalSearch']))
        {
            $data = $this->datagrid_form->getData();
        }
        else
        {
            $data = $this->form->getData();
        }
        $filters = [];

        $clienteFiltroPrincipal = $data->pessoa_id ?? null;
        $clienteFiltroColuna = $data->pessoa_col ?? null;
        $adicionarFiltroClienteContrato = function($clienteId) use (&$filters)
        {
            if (empty($clienteId)) {
                return;
            }

            $clienteId = (int) $clienteId;

            $subquery = "(SELECT c.id FROM conta c WHERE c.pessoa_id = {$clienteId} OR (c.contrato_id IS NOT NULL AND c.contrato_id IN (SELECT cp.contrato_id FROM contrato_pessoa cp WHERE cp.cliente_id = {$clienteId})))";

            $filters[] = new TFilter('id', 'in', $subquery);
        };
        if (!empty($clienteFiltroPrincipal))
        {
            $adicionarFiltroClienteContrato($clienteFiltroPrincipal);

            /*
            * Limpa temporariamente para impedir que o código automático
            * abaixo aplique também pessoa_id = X.
            */
            $data->pessoa_id = null;
        }

        if (!empty($clienteFiltroColuna))
        {
            $adicionarFiltroClienteContrato($clienteFiltroColuna);

            /*
            * Mesma coisa para o filtro da coluna.
            */
            $data->pessoa_col = null;
        } 

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->pessoa_id) AND ( (is_scalar($data->pessoa_id) AND $data->pessoa_id !== '') OR (is_array($data->pessoa_id) AND (!empty($data->pessoa_id)) )) )
        {

            $filters[] = new TFilter('pessoa_id', '=', $data->pessoa_id);// create the filter 
        }

        if (isset($data->descricao) AND ( (is_scalar($data->descricao) AND $data->descricao !== '') OR (is_array($data->descricao) AND (!empty($data->descricao)) )) )
        {

            $filters[] = new TFilter('descricao', 'like', "%{$data->descricao}%");// create the filter 
        }

        if (isset($data->categoria_conta_id) AND ( (is_scalar($data->categoria_conta_id) AND $data->categoria_conta_id !== '') OR (is_array($data->categoria_conta_id) AND (!empty($data->categoria_conta_id)) )) )
        {

            $filters[] = new TFilter('categoria_conta_id', '=', $data->categoria_conta_id);// create the filter 
        }

        if (isset($data->escritorio_id) AND ( (is_scalar($data->escritorio_id) AND $data->escritorio_id !== '') OR (is_array($data->escritorio_id) AND (!empty($data->escritorio_id)) )) )
        {

            $filters[] = new TFilter('escritorio_id', 'in', "(SELECT id FROM conta WHERE escritorio_id = '{$data->escritorio_id}')");// create the filter 
        }

        if (isset($data->profissional_id) AND ( (is_scalar($data->profissional_id) AND $data->profissional_id !== '') OR (is_array($data->profissional_id) AND (!empty($data->profissional_id)) )) )
        {
            $filterVar = (is_array($data->profissional_id) && $data->profissional_id) ? "'".implode("','", $data->profissional_id)."'" : $data->profissional_id;
            $filters[] = new TFilter('id', 'in', "(SELECT conta_id FROM conta_profissional WHERE pessoa_id in ($filterVar))");// create the filter 
        }

        if (isset($data->tipo_documento_financeiro_id) AND ( (is_scalar($data->tipo_documento_financeiro_id) AND $data->tipo_documento_financeiro_id !== '') OR (is_array($data->tipo_documento_financeiro_id) AND (!empty($data->tipo_documento_financeiro_id)) )) )
        {

            $filters[] = new TFilter('tipo_documento_financeiro_id', '=', $data->tipo_documento_financeiro_id);// create the filter 
        }

        if (isset($data->numero_documento) AND ( (is_scalar($data->numero_documento) AND $data->numero_documento !== '') OR (is_array($data->numero_documento) AND (!empty($data->numero_documento)) )) )
        {

            $filters[] = new TFilter('numero_documento', 'like', "%{$data->numero_documento}%");// create the filter 
        }

        if (isset($data->quitada) AND ( (is_scalar($data->quitada) AND $data->quitada !== '') OR (is_array($data->quitada) AND (!empty($data->quitada)) )) )
        {

            $filters[] = new TFilter('quitada', 'like', "%{$data->quitada}%");// create the filter 
        }

        if (isset($data->data_emissao_ini) AND ( (is_scalar($data->data_emissao_ini) AND $data->data_emissao_ini !== '') OR (is_array($data->data_emissao_ini) AND (!empty($data->data_emissao_ini)) )) )
        {

            $filters[] = new TFilter('data_emissao', '>=', $data->data_emissao_ini);// create the filter 
        }

        if (isset($data->data_emissao_fim) AND ( (is_scalar($data->data_emissao_fim) AND $data->data_emissao_fim !== '') OR (is_array($data->data_emissao_fim) AND (!empty($data->data_emissao_fim)) )) )
        {

            $filters[] = new TFilter('data_emissao', '<=', $data->data_emissao_fim);// create the filter 
        }

        if (isset($data->dt_vencimento_ini) AND ( (is_scalar($data->dt_vencimento_ini) AND $data->dt_vencimento_ini !== '') OR (is_array($data->dt_vencimento_ini) AND (!empty($data->dt_vencimento_ini)) )) )
        {

            $filters[] = new TFilter('id', 'in', "(SELECT conta_id FROM lancamento WHERE dt_vencimento >= '{$data->dt_vencimento_ini}')");// create the filter 
        }

        if (isset($data->data_vencimento_fim) AND ( (is_scalar($data->data_vencimento_fim) AND $data->data_vencimento_fim !== '') OR (is_array($data->data_vencimento_fim) AND (!empty($data->data_vencimento_fim)) )) )
        {

            $filters[] = new TFilter('id', 'in', "(SELECT conta_id FROM lancamento WHERE dt_vencimento <= '{$data->data_vencimento_fim}')");// create the filter 
        }

        if (isset($data->pessoa_col) AND ( (is_scalar($data->pessoa_col) AND $data->pessoa_col !== '') OR (is_array($data->pessoa_col) AND (!empty($data->pessoa_col)) )) )
        {

            $filters[] = new TFilter('pessoa_id', '=', $data->pessoa_col);// create the filter 
        }

        if (isset($data->id) AND ( (is_scalar($data->id) AND $data->id !== '') OR (is_array($data->id) AND (!empty($data->id)) )) )
        {
            $filterVar = (is_array($data->id) && $data->id) ? "'".implode("','", $data->id)."'" : $data->id;
            $filters[] = new TFilter('id', 'in', "(SELECT conta_id FROM conta_profissional WHERE pessoa_id in ($filterVar))");// create the filter 
        }

        if (isset($data->tipo_documento_financeiro_nome) AND ( (is_scalar($data->tipo_documento_financeiro_nome) AND $data->tipo_documento_financeiro_nome !== '') OR (is_array($data->tipo_documento_financeiro_nome) AND (!empty($data->tipo_documento_financeiro_nome)) )) )
        {

            $filters[] = new TFilter('tipo_documento_financeiro_id', '=', $data->tipo_documento_financeiro_nome);// create the filter 
        }

        if (isset($data->descricao_col) AND ( (is_scalar($data->descricao_col) AND $data->descricao_col !== '') OR (is_array($data->descricao_col) AND (!empty($data->descricao_col)) )) )
        {

            $filters[] = new TFilter('unaccent(descricao)', 'ilike', "%{$data->descricao_col}%");// create the filter 
        }

        if (isset($data->data_emissao) AND ( (is_scalar($data->data_emissao) AND $data->data_emissao !== '') OR (is_array($data->data_emissao) AND (!empty($data->data_emissao)) )) )
        {

            $filters[] = new TFilter('data_emissao', '=', $data->data_emissao);// create the filter 
        }

        if (isset($data->quitada22) AND ( (is_scalar($data->quitada22) AND $data->quitada22 !== '') OR (is_array($data->quitada22) AND (!empty($data->quitada22)) )) )
        {

            $filters[] = new TFilter('quitada', '=', $data->quitada22);// create the filter 
        }

        if (!empty($data->filtro_rapido) && $data->filtro_rapido == 1)
        {
            $filters[] = new TFilter('id', 'in', "(SELECT conta_id FROM lancamento WHERE dt_vencimento < '". date('Y-m-d 00:00') ."' and quitada = 'N')");// vencidas
        }

        if (!empty($data->filtro_rapido) && $data->filtro_rapido == 2)
        {
            $filters[] = new TFilter('id', 'in', "(SELECT conta_id FROM lancamento WHERE dt_vencimento >= '". date('Y-m-d 00:00') ."' and quitada = 'N')");
        }

        /*
        * Devolve os valores aos campos para o filtro continuar
        * aparecendo selecionado na tela.
        */
        if (!empty($clienteFiltroPrincipal)) {
            $data->pessoa_id = $clienteFiltroPrincipal;
        }

        if (!empty($clienteFiltroColuna)) {
            $data->pessoa_col = $clienteFiltroColuna;
        }

        // fill the form with data again
        if ((isset($param['static']) && ($param['static'] == '1')) || !empty($param['globalSearch']))
        {
            $this->datagrid_form->setData($data);
        }
        else
        {
            $this->form->setData($data);
        }

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            $onReloadParam = ['offset' => 0, 'first_page' => 1, 'target_container' => $param['target_container'] ?? null];
            AdiantiCoreApplication::loadPage($class, 'onReload', $onReloadParam);
            TScript::create('$(".select2").prev().select2("close");');
        }
        else
        {
            $this->onReload(['offset' => 0, 'first_page' => 1]);
        }
    }

    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'escritorio'
            TTransaction::open(self::$database);

            // creates a repository for Conta
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'proximo_vencimento_lancamento';    
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'asc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $this->limit);

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $filter) 
                {
                    $criteria->add($filter);       
                }
            }

            //</blockLine><btnShowCurtainFiltersAutoCode>
            if(!empty($this->btnShowCurtainFilters))
            {
                $this->btnShowCurtainFilters->style = 'position: relative';
                $countFilters = count($filters ?? []);
                $this->btnShowCurtainFilters->setLabel("Filtros<span class='badge badge-success' style='position: absolute'>{$countFilters}<span>");
            }
            //</blockLine></btnShowCurtainFiltersAutoCode>

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $row = $this->datagrid->addItem($object);
                    $row->id = "row_{$object->id}";

                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);

            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($this->limit); // limit

            $this->datagrid->initPopoverHeaderFilters();

            // close the transaction
            TTransaction::close();
            $this->loaded = true;

            return $objects;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

        $this->onClearFilters($param);
        $this->onEmAberto($param);

    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  $this->showMethods))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new Conta($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

