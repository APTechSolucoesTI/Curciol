<?php

/**
 * Integracao Contatos APChat (Configuracoes > Geral).
 *
 * Clientes que autorizaram WhatsApp (apchat_contato_view) com o resultado da
 * ultima sincronizacao, e o "Sincronizar todos", que aplica a cada um a
 * mesma regra do salvamento do cliente (APChatContactService). O
 * "Sincronizar todos" roda em lotes - uma requisicao por lote - para nao
 * esbarrar no tempo maximo de uma requisicao.
 *
 * Estrutura igual as demais listagens (ClienteList): botoes no topo,
 * cortina de filtros, filtro nas colunas, busca global e exportacao.
 */
class ApchatContatoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'ApchatContatoView';
    private static $primaryKey = 'id';
    private static $formName = 'formList_ApchatContatoView';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    const TAMANHO_LOTE = 10;

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
        $this->form->setFormTitle("Integração Contatos APChat");
        $this->limit = 20;

        $nome = new TEntry('nome');
        $cpf_cnpj = new TEntry('cpf_cnpj');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $situacao = new TCombo('situacao');
        $nome_col = new TEntry('nome_col');
        $cpf_cnpj_col = new TEntry('cpf_cnpj_col');
        $telefone_col = new TEntry('telefone_col');
        $email_col = new TEntry('email_col');
        $situacao_col = new TCombo('situacao_col');
        $global_filter = new TEntry('global_filter');

        $situacao->addItems(self::situacoes());
        $situacao_col->addItems(self::situacoes());

        $nome_col->exitOnEnter();
        $cpf_cnpj_col->exitOnEnter();
        $telefone_col->exitOnEnter();
        $email_col->exitOnEnter();

        $global_filter->setEnterAction(new TAction([$this, 'onGlobalSearch']));

        $nome_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $cpf_cnpj_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $telefone_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $email_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $situacao_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $cpf_cnpj->setMaxLength(18);
        $email->forceLowerCase();
        $global_filter->setInnerIcon(new TImage('fas:search #9E9E9E'), 'left');

        $nome->forceUpperCase();
        $nome_col->forceUpperCase();
        $global_filter->forceUpperCase();

        $nome->setSize('100%');
        $email->setSize('100%');
        $cpf_cnpj->setSize('100%');
        $telefone->setSize('100%');
        $situacao->setSize('100%');
        $nome_col->setSize('100%');
        $email_col->setSize('100%');
        $global_filter->setSize(200);
        $cpf_cnpj_col->setSize('100%');
        $telefone_col->setSize('100%');
        $situacao_col->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Nome:", null, '14px', null, '100%'),$nome],[new TLabel("Situação no APChat:", null, '14px', null),$situacao]);
        $row1->layout = [' col-sm-7',' col-sm-5'];

        $row2 = $this->form->addFields([new TLabel("Documento (CPF ou CNPJ):", null, '14px', null),$cpf_cnpj],[new TLabel("Email:", null, '14px', null),$email],[new TLabel("Telefone:", null, '14px', null, '100%'),$telefone]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

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

        $this->datagrid_form->addField($global_filter);
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(400);

        $column_id = new TDataGridColumn('id', "Código", 'center' , '70px');
        $column_nome = new TDataGridColumn('nome', "Nome", 'left');
        $column_cpf_cnpj_transformed = new TDataGridColumn('cpf_cnpj', "CPF ou CNPJ", 'left');
        $column_telefone_transformed = new TDataGridColumn('telefone', "Telefone", 'left');
        $column_email = new TDataGridColumn('email', "Email", 'left');
        $column_situacao_transformed = new TDataGridColumn('situacao', "Situação no APChat", 'center');
        $column_ultima_sincronizacao_transformed = new TDataGridColumn('ultima_sincronizacao', "Última sincronização", 'center');
        $column_mensagem_transformed = new TDataGridColumn('mensagem', "Detalhe", 'left');

        $column_cpf_cnpj_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(strlen((string) $value)==11){
                return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $value);
            }

            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", (string) $value);

        });

        $column_telefone_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if($value!=NULL && $value!="" && isset($value) && !empty($value)){
                return "(".substr($value,0,2).") ".substr($value,2,-4)."-".substr($value,-4);
            }
        });

        $column_situacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return self::seloSituacao($value);
        });

        $column_ultima_sincronizacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return empty($value) ? '-' : TDateTime::convertToMask($value, 'yyyy-mm-dd hh:ii:ss', 'dd/mm/yyyy hh:ii');
        });

        $column_mensagem_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $texto = trim((string) $value);

            if ($texto === '')
            {
                return '';
            }

            $curto = mb_strlen($texto) > 70 ? mb_substr($texto, 0, 70) . '…' : $texto;

            return "<span title='" . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($curto, ENT_QUOTES, 'UTF-8') . "</span>";
        });

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        $order_cpf_cnpj_transformed = new TAction(array($this, 'onReload'));
        $order_cpf_cnpj_transformed->setParameter('order', 'cpf_cnpj');
        $column_cpf_cnpj_transformed->setAction($order_cpf_cnpj_transformed);
        $order_telefone_transformed = new TAction(array($this, 'onReload'));
        $order_telefone_transformed->setParameter('order', 'telefone');
        $column_telefone_transformed->setAction($order_telefone_transformed);
        $order_email = new TAction(array($this, 'onReload'));
        $order_email->setParameter('order', 'email');
        $column_email->setAction($order_email);
        $order_situacao = new TAction(array($this, 'onReload'));
        $order_situacao->setParameter('order', 'situacao');
        $column_situacao_transformed->setAction($order_situacao);
        $order_ultima = new TAction(array($this, 'onReload'));
        $order_ultima->setParameter('order', 'ultima_sincronizacao');
        $column_ultima_sincronizacao_transformed->setAction($order_ultima);

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_cpf_cnpj_transformed);
        $this->datagrid->addColumn($column_telefone_transformed);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_situacao_transformed);
        $this->datagrid->addColumn($column_ultima_sincronizacao_transformed);
        $this->datagrid->addColumn($column_mensagem_transformed);

        $action_onShow = new TDataGridAction(array('ApchatContatoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search #607D8B');
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
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $tr->add(TElement::tag('td', $nome_col));
        $tr->add(TElement::tag('td', $cpf_cnpj_col));
        $tr->add(TElement::tag('td', $telefone_col));
        $tr->add(TElement::tag('td', $email_col));
        $tr->add(TElement::tag('td', $situacao_col));
        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($nome_col);
        $this->datagrid_form->addField($cpf_cnpj_col);
        $this->datagrid_form->addField($telefone_col);
        $this->datagrid_form->addField($email_col);
        $this->datagrid_form->addField($situacao_col);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Integração Contatos APChat");
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;

        $panel->add($this->datagrid_form);

        $panel->getBody()->class .= ' table-responsive';

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

        $button_buscar = new TButton('button_button_buscar');
        $button_buscar->setAction(new TAction(['ApchatContatoList', 'onGlobalSearch']), "Buscar");
        $button_buscar->addStyleClass('btn-default');
        $button_buscar->setImage('fas:search #9E9E9E');

        $this->datagrid_form->addField($button_buscar);

        $button_cadastrar = new TButton('button_button_cadastrar');
        $button_cadastrar->setAction(new TAction(['ClienteForm', 'onShow']), "Cadastrar");
        $button_cadastrar->addStyleClass('btn-default');
        $button_cadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_cadastrar);

        $button_filtros = new TButton('button_button_filtros');
        $button_filtros->setAction(new TAction(['ApchatContatoList', 'onShowCurtainFilters']), "Filtros");
        $button_filtros->addStyleClass('btn-default');
        $button_filtros->setImage('fas:filter #000000');

        $this->datagrid_form->addField($button_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['ApchatContatoList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['ApchatContatoList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_sincronizar = new TButton('button_button_sincronizar');
        $button_sincronizar->setAction(new TAction(['ApchatContatoList', 'onSincronizarTodos'], ['static' => 1]), "Sincronizar todos");
        $button_sincronizar->addStyleClass('btn-default');
        $button_sincronizar->setImage('fab:whatsapp #25D366');

        $this->datagrid_form->addField($button_sincronizar);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['ApchatContatoList', 'onExportCsv'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:table #00b894' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['ApchatContatoList', 'onExportPdf'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['ApchatContatoList', 'onExportXls']), 'datagrid_'.self::$formName, 'fas:file-excel #4CAF50' );

        $head_left_actions->add($button_cadastrar);
        $head_left_actions->add($button_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($dropdown_button_exportar);
        $head_left_actions->add($button_sincronizar);

        $head_right_actions->add($global_filter);
        $head_right_actions->add($button_buscar);

        $this->datagrid_form->add($this->datagrid);

        $this->button_filtros = $button_filtros;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Configurações","Integração Contatos APChat"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    /**
     * Situacoes possiveis da ultima sincronizacao. NUNCA = cliente autorizado
     * que ainda nao passou pela sincronizacao (sem linha no log).
     */
    private static function situacoes(): array
    {
        return [
            'NUNCA'    => 'Nunca sincronizado',
            'SUCESSO'  => 'Sincronizado',
            'PENDENTE' => 'Pendente (telefone alterado)',
            'ERRO'     => 'Erro',
            'IGNORADO' => 'Não enviado (telefone inválido)',
        ];
    }

    public static function seloSituacao($situacao): string
    {
        $cores = [
            'SUCESSO'  => '#2e7d32',
            'PENDENTE' => '#ef6c00',
            'ERRO'     => '#c62828',
            'IGNORADO' => '#616161',
        ];

        $chave = empty($situacao) ? 'NUNCA' : (string) $situacao;
        $rotulo = self::situacoes()[$chave] ?? $chave;
        $cor = $cores[$chave] ?? '#90a4ae';

        return "<span class='label' style='background:{$cor}; color:#fff; padding:3px 8px; border-radius:10px; font-size:11px; white-space:nowrap;'>"
             . htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8') . "</span>";
    }

    // =================================================================
    // Sincronizar todos
    // =================================================================

    public static function onSincronizarTodos($param = null)
    {
        try
        {
            $total = count(APChatContactService::idsAutorizados());

            if ($total === 0)
            {
                new TMessage('info', 'Nenhum cliente autorizou mensagens por WhatsApp.');
                return;
            }

            new TQuestion(
                "Sincronizar com o APChat os {$total} clientes que autorizaram WhatsApp?<br><br>"
                . "Cada um é consultado no APChat e atualizado (se já existir) ou criado (se não existir), "
                . "com a mesma regra do cadastro de clientes. O processo roda em lotes de " . self::TAMANHO_LOTE . "; não feche esta tela até terminar.",
                new TAction([__CLASS__, 'onSincronizarLote'], ['offset' => 0, 'static' => 1])
            );
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Um lote. Ao terminar, agenda o proximo pelo navegador; no ultimo,
     * mostra o resumo e recarrega a lista.
     */
    public static function onSincronizarLote($param = null)
    {
        try
        {
            set_time_limit(180);

            $offset = max(0, (int) ($param['offset'] ?? 0));
            $ids = APChatContactService::idsAutorizados();
            $total = count($ids);

            $contagem = ($offset === 0) ? [] : (array) TSession::getValue(__CLASS__ . '_lote');

            foreach (array_slice($ids, $offset, self::TAMANHO_LOTE) as $pessoa_id)
            {
                $r = APChatContactService::sincronizarPessoa($pessoa_id, [], ApchatContatoLog::ORIGEM_LOTE);
                $chave = $r->situacao . '|' . $r->operacao;
                $contagem[$chave] = ($contagem[$chave] ?? 0) + 1;
            }

            TSession::setValue(__CLASS__ . '_lote', $contagem);

            $feitos = min($total, $offset + self::TAMANHO_LOTE);

            if ($feitos < $total)
            {
                TToast::show('info', "Sincronizando com o APChat: {$feitos} de {$total}...", 'topRight', 'fas:sync');
                TScript::create("__adianti_ajax_exec('class=ApchatContatoList&method=onSincronizarLote&offset={$feitos}');");
                return;
            }

            TSession::setValue(__CLASS__ . '_lote', null);

            $nomes = [
                'SUCESSO|CREATE'  => 'contatos criados',
                'SUCESSO|UPDATE'  => 'contatos atualizados',
                'PENDENTE|UPDATE' => 'pendentes (telefone alterado, contato antigo mantido)',
                'IGNORADO|NENHUMA'=> 'não enviados (telefone inválido ou sem autorização)',
            ];

            $linhas = [];

            foreach ($contagem as $chave => $quantidade)
            {
                $nome = $nomes[$chave] ?? ('com erro (' . strtolower(explode('|', $chave)[1]) . ')');
                $linhas[] = "<b>{$quantidade}</b> {$nome}";
            }

            new TMessage('info', "Sincronização concluída: {$total} clientes.<br><br>" . implode('<br>', $linhas) . "<br><br>O detalhe de cada um fica na coluna \"Detalhe\".");

            AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    // =================================================================
    // Padrao das listagens
    // =================================================================

    public function onGlobalSearch($param = null)
    {
        $param['globalSearch'] = true;
        $this->onSearch($param);
    }

    public static function onShowCurtainFilters($param = null)
    {
        try
        {
            $object = new stdClass();
            $object->nome = null;
            $object->cpf_cnpj = null;
            $object->email = null;
            $object->telefone = null;
            $object->situacao = null;

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
            $page->setProperty('page-name', 'ApchatContatoListSearch');
            $page->setProperty('page_name', 'ApchatContatoListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onRefresh($param = null)
    {
        $this->onReload([]);
    }

    public function onClearFilters($param = null)
    {
        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
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

                    fputcsv($handler, array_map(function ($column) { return $column->getLabel(); }, $this->datagrid->getColumns()));

                    foreach ($objects as $object)
                    {
                        fputcsv($handler, $this->linhaExportacao($object));
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
                $dompdf->setPaper('A4', 'landscape');
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

                        foreach ($this->linhaExportacao($object) as $value)
                        {
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

    /**
     * Valores de uma linha para CSV/XLS, na ordem das colunas e ja em texto
     * (sem o HTML dos selos).
     */
    private function linhaExportacao($object): array
    {
        $situacao = empty($object->situacao) ? 'NUNCA' : $object->situacao;

        return [
            $object->id,
            $object->nome,
            $object->cpf_cnpj,
            $object->telefone,
            $object->email,
            self::situacoes()[$situacao] ?? $situacao,
            empty($object->ultima_sincronizacao) ? '' : TDateTime::convertToMask($object->ultima_sincronizacao, 'yyyy-mm-dd hh:ii:ss', 'dd/mm/yyyy hh:ii'),
            $object->mensagem,
        ];
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

        foreach (['cpf_cnpj', 'cpf_cnpj_col', 'telefone', 'telefone_col'] as $campo)
        {
            if (isset($data->$campo) && is_scalar($data->$campo) && $data->$campo !== '')
            {
                $data->$campo = preg_replace('/\D/', '', $data->$campo);
            }
        }

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $texto = function ($valor) {
            return isset($valor) && is_scalar($valor) && $valor !== '';
        };

        foreach (['nome' => 'nome', 'nome_col' => 'nome'] as $campo => $coluna)
        {
            if ($texto($data->$campo ?? null))
            {
                $busca = str_replace(' ', '%', TratamentosService::removerAcentos($data->$campo));
                $filters[] = new TFilter('unaccent(nome)', 'ilike', "%{$busca}%");
            }
        }

        foreach (['cpf_cnpj' => 'cpf_cnpj', 'cpf_cnpj_col' => 'cpf_cnpj', 'telefone' => 'telefone', 'telefone_col' => 'telefone', 'email' => 'email', 'email_col' => 'email'] as $campo => $coluna)
        {
            if ($texto($data->$campo ?? null))
            {
                $filters[] = new TFilter($coluna, 'ilike', "%{$data->$campo}%");
            }
        }

        foreach (['situacao', 'situacao_col'] as $campo)
        {
            if ($texto($data->$campo ?? null))
            {
                $filters[] = ($data->$campo === 'NUNCA')
                    ? new TFilter('situacao', 'is', null)
                    : new TFilter('situacao', '=', $data->$campo);
            }
        }

        if ($texto($data->global_filter ?? null))
        {
            $globalCriteria = new TCriteria();

            $globalCriteria->add(new TFilter('unaccent(nome)', 'ilike', "%{$data->global_filter}%"), ' OR ');
            $globalCriteria->add(new TFilter('telefone', 'ilike', "%{$data->global_filter}%"), ' OR ');
            $globalCriteria->add(new TFilter('cpf_cnpj', 'ilike', "%{$data->global_filter}%"), ' OR ');

            $filters[] = $globalCriteria;
        }

        $this->button_filtros->style = 'position: relative';
        $countFiltros = count($filters);

        if ($countFiltros)
        {
            $this->button_filtros->setLabel('Filtros'. "<span class='badge badge-success' style='position: absolute'>{$countFiltros}<span>");
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
        if(!empty($data->global_filter)){
            TScript::create('$("[name=global_filter]").focus().each(function() {
                if (this.setSelectionRange) {
                    this.setSelectionRange(this.value.length, this.value.length);
                }
            });');
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

            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'id';
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'desc';
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
}
