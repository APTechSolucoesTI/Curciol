<?php


require_once '/www/wwwroot/curciol.sislawyer.com.br/app/lib/phpspreadsheet/vendor/autoload.php';

class RequisicaoPagamentoRelatorioDinamico extends TWindow
{
    protected $form;

    private static $database = 'escritorio';
    private static $formName = 'form_RequisicaoPagamentoRelatorioDinamico';

    public function __construct($param = null)
    {
        parent::__construct();

        parent::setTitle('Relatório Dinâmico - Requisição de Pagamento');
        parent::setSize(0.88, 0.92);

        if (!empty($param['target_container'])) {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('');
        $this->form->setFieldSizes('100%');

        /*
        |--------------------------------------------------------------------------
        | PERÍODO OBRIGATÓRIO
        |--------------------------------------------------------------------------
        */

        $dataInicial = new TDate('data_requerimento_ini');
        $dataFinal   = new TDate('data_requerimento_fim');

        $dataInicial->setMask('dd/mm/yyyy');
        $dataInicial->setDatabaseMask('yyyy-mm-dd');

        $dataFinal->setMask('dd/mm/yyyy');
        $dataFinal->setDatabaseMask('yyyy-mm-dd');

        $dataInicial->setValue(date('01/m/Y'));
        $dataFinal->setValue(date('d/m/Y'));

        $dataInicial->addValidation(
            'Data inicial do requerimento',
            new TRequiredValidator
        );

        $dataFinal->addValidation(
            'Data final do requerimento',
            new TRequiredValidator
        );

        /*
        |--------------------------------------------------------------------------
        | FILTROS OPCIONAIS
        |--------------------------------------------------------------------------
        */

        $processo = new TEntry('processo');
        $cliente  = new TEntry('cliente');

        $tipoRequisicao = new TDBCombo(
            'tipo_requisicao_id',
            self::$database,
            'TiposRequisicaoPagamento',
            'id',
            'nome',
            'nome'
        );

        $status = new TDBCombo(
            'status_id',
            self::$database,
            'StatusRequisicaoPagamento',
            'id',
            'nome',
            'nome'
        );

        $entidadeDevedora = new TDBUniqueSearch(
            'entidade_devedora_id',
            self::$database,
            'Pessoa',
            'id',
            'nome',
            'nome'
        );

        $numeroOrdem = new TEntry('numero_ordem');
        $numeroDepre = new TEntry('numero_depre');

        $valorMleDe = new TNumeric(
            'valor_mle_de',
            2,
            ',',
            '.',
            true
        );

        $valorMleAte = new TNumeric(
            'valor_mle_ate',
            2,
            ',',
            '.',
            true
        );

        $possuiSaldo = new TCombo('possui_saldo');

        $possuiSaldo->addItems([
            ''  => 'Todos',
            'S' => 'Sim',
            'N' => 'Não'
        ]);

        $dataDepositoIni = new TDate('data_deposito_ini');
        $dataDepositoFim = new TDate('data_deposito_fim');

        $dataDepositoIni->setMask('dd/mm/yyyy');
        $dataDepositoIni->setDatabaseMask('yyyy-mm-dd');

        $dataDepositoFim->setMask('dd/mm/yyyy');
        $dataDepositoFim->setDatabaseMask('yyyy-mm-dd');

        $tipoRequisicao->enableSearch();
        $status->enableSearch();

        $entidadeDevedora->setMinLength(2);
        $entidadeDevedora->setSize('100%');

        /*
        |--------------------------------------------------------------------------
        | COLUNAS DINÂMICAS
        |--------------------------------------------------------------------------
        */

        $colunasIdentificacao = new TCheckGroup('colunas_identificacao');

        $colunasIdentificacao->addItems([
            'requisicao'       => 'Nº da Requisição',
            'processo'         => 'Processo Principal',
            'tipo_requisicao'  => 'Tipo da Requisição',
            'data_criacao'     => 'Data de Criação',
            'cliente'          => 'Cliente',
            'cpf_cnpj'         => 'CPF/CNPJ'
        ]);

        $colunasRequerimento = new TCheckGroup('colunas_requerimento');

        $colunasRequerimento->addItems([
            'status'             => 'Status',
            'entidade_devedora'  => 'Entidade Devedora',
            'valor_requisicao'   => 'Valor da Requisição',
            'data_requerimento'  => 'Data do Requerimento',
            'data_base'          => 'Data Base',
            'conta_indicada_mle' => 'Conta Indicada',
            'observacao'         => 'Observação'
        ]);

        $colunasEtapa2 = new TCheckGroup('colunas_etapa2');

        $colunasEtapa2->addItems([
            'processo_etapa2'              => 'Processo Vinculado - Etapa 2',
            'data_deferimento_expedicao'   => 'Data de Deferimento da Expedição',
            'protocolo_depre'               => 'Protocolo DEPRE/Entidade',
            'numero_depre'                  => 'Número DEPRE/Entidade',
            'numero_ordem'                  => 'Número da Ordem'
        ]);

        $colunasEtapa3 = new TCheckGroup('colunas_etapa3');

        $colunasEtapa3->addItems([
            'processo_etapa3'       => 'Processo do Pagamento',
            'numero_ciclo'          => 'Ciclo / Pagamento',
            'data_deposito'         => 'Data do Depósito',
            'valor_bruto'           => 'Valor Bruto Depositado',
            'valor_mle'             => 'Valor do MLE',
            'data_pedido_mle'       => 'Data do Pedido MLE',
            'data_deferimento_mle'  => 'Data de Deferimento MLE',
            'saldo_bruto'           => 'Saldo Bruto Remanescente',
            'data_base_saldo'       => 'Data Base do Saldo',
            'possui_saldo'          => 'Possui Saldo?'
        ]);

        $colunasIdentificacao->setLayout('horizontal');
        $colunasRequerimento->setLayout('horizontal');
        $colunasEtapa2->setLayout('horizontal');
        $colunasEtapa3->setLayout('horizontal');

        /*
        |--------------------------------------------------------------------------
        | PADRÃO INICIAL
        |--------------------------------------------------------------------------
        */

        $colunasIdentificacao->setValue([
            'requisicao',
            'processo',
            'tipo_requisicao',
            'cliente'
        ]);

        $colunasRequerimento->setValue([
            'status',
            'entidade_devedora',
            'data_requerimento'
        ]);

        $colunasEtapa2->setValue([
            'numero_depre',
            'numero_ordem'
        ]);

        $colunasEtapa3->setValue([
            'numero_ciclo',
            'data_deposito',
            'valor_mle'
        ]);

        /*
        |--------------------------------------------------------------------------
        | INTERFACE
        |--------------------------------------------------------------------------
        */

        $tituloPeriodo = new TElement('div');
        $tituloPeriodo->class = 'rel-dinamico-section-title';
        $tituloPeriodo->add('1. Período obrigatório');

        $this->form->addContent([$tituloPeriodo]);

        $row = $this->form->addFields(
            [
                new TLabel('Data do requerimento - De *'),
                $dataInicial
            ],
            [
                new TLabel('Data do requerimento - Até *'),
                $dataFinal
            ]
        );

        $row->layout = [
            'col-sm-3',
            'col-sm-3'
        ];

        $ajudaPeriodo = new TElement('div');
        $ajudaPeriodo->class = 'rel-dinamico-help';
        $ajudaPeriodo->add(
            'O relatório sempre será limitado pela Data do Requerimento. ' .
            'Assim você pode gerar somente um mês, uma semana ou qualquer outro período.'
        );

        $this->form->addContent([$ajudaPeriodo]);

        $tituloFiltros = new TElement('div');
        $tituloFiltros->class = 'rel-dinamico-section-title';
        $tituloFiltros->add('2. Filtros opcionais');

        $this->form->addContent([$tituloFiltros]);

        $row = $this->form->addFields(
            [new TLabel('Processo'), $processo],
            [new TLabel('Cliente'), $cliente],
            [new TLabel('Tipo de Requisição'), $tipoRequisicao],
            [new TLabel('Status'), $status]
        );

        $row->layout = [
            'col-sm-3',
            'col-sm-3',
            'col-sm-3',
            'col-sm-3'
        ];

        $row = $this->form->addFields(
            [new TLabel('Entidade Devedora'), $entidadeDevedora],
            [new TLabel('Número da Ordem'), $numeroOrdem],
            [new TLabel('Número DEPRE/Entidade'), $numeroDepre]
        );

        $row->layout = [
            'col-sm-4',
            'col-sm-4',
            'col-sm-4'
        ];

        $row = $this->form->addFields(
            [new TLabel('Valor MLE - De'), $valorMleDe],
            [new TLabel('Valor MLE - Até'), $valorMleAte],
            [new TLabel('Possui saldo?'), $possuiSaldo],
            [new TLabel('Depósito - De'), $dataDepositoIni],
            [new TLabel('Depósito - Até'), $dataDepositoFim]
        );

        $row->layout = [
            'col-sm-2',
            'col-sm-2',
            'col-sm-2',
            'col-sm-3',
            'col-sm-3'
        ];

        $tituloColunas = new TElement('div');
        $tituloColunas->class = 'rel-dinamico-section-title';
        $tituloColunas->add('3. Escolha as colunas do Excel');

        $this->form->addContent([$tituloColunas]);

        $acoesColunas = new TElement('div');
        $acoesColunas->class = 'rel-dinamico-column-actions';

        $acoesColunas->add("
            <button
                type='button'
                class='btn btn-default btn-sm'
                onclick=\"
                    var form = $('#" . self::$formName . "');
                    form.find('input[type=checkbox][name^=colunas_]')
                        .prop('checked', true)
                        .trigger('change');
                \"
            >
                <i class='fa fa-check-square'></i>
                Marcar todas
            </button>

            <button
                type='button'
                class='btn btn-default btn-sm'
                onclick=\"
                    var form = $('#" . self::$formName . "');
                    form.find('input[type=checkbox][name^=colunas_]')
                        .prop('checked', false)
                        .trigger('change');
                \"
            >
                <i class='fa fa-square'></i>
                Limpar seleção
            </button>
        ");

        $this->form->addContent([$acoesColunas]);

        $this->form->addContent([
            self::criarGrupoColunas(
                'Identificação',
                $colunasIdentificacao
            )
        ]);

        $this->form->addContent([
            self::criarGrupoColunas(
                'Dados do requerimento',
                $colunasRequerimento
            )
        ]);

        $this->form->addContent([
            self::criarGrupoColunas(
                'Expedição / DEPRE - Etapa 2',
                $colunasEtapa2
            )
        ]);

        $this->form->addContent([
            self::criarGrupoColunas(
                'Pagamentos / MLE - Etapa 3',
                $colunasEtapa3
            )
        ]);

        /*
        |--------------------------------------------------------------------------
        | REGISTRO DOS CAMPOS
        |--------------------------------------------------------------------------
        */

        $this->form->addField($colunasIdentificacao);
        $this->form->addField($colunasRequerimento);
        $this->form->addField($colunasEtapa2);
        $this->form->addField($colunasEtapa3);

        $this->form->addAction(
            'Gerar Excel',
            new TAction([$this, 'onGerarExcel']),
            'fas:file-excel #ffffff'
        )->class = 'btn btn-success';

        $this->aplicarCss();

        parent::add($this->form);
    }

    public function onShow($param = null)
    {
    }

    public function onGerarExcel($param = null)
    {
        try
        {
            $this->form->validate();

            $dataInicial = self::dataParaBanco(
                $param['data_requerimento_ini'] ?? null
            );

            $dataFinal = self::dataParaBanco(
                $param['data_requerimento_fim'] ?? null
            );

            if (empty($dataInicial) || empty($dataFinal)) {
                throw new Exception(
                    'Informe o período da Data do Requerimento.'
                );
            }

            if ($dataInicial > $dataFinal) {
                throw new Exception(
                    'A data inicial do requerimento não pode ser maior que a data final.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | COLUNAS SELECIONADAS
            |--------------------------------------------------------------------------
            */

            $colunasSelecionadas = [];

            $grupos = [
                'colunas_identificacao',
                'colunas_requerimento',
                'colunas_etapa2',
                'colunas_etapa3'
            ];

            foreach ($grupos as $grupo) {
                $valores = self::normalizarArray(
                    $param[$grupo] ?? []
                );

                foreach ($valores as $valor) {
                    $colunasSelecionadas[] = $valor;
                }
            }

            $colunasSelecionadas = array_values(
                array_unique($colunasSelecionadas)
            );

            if (empty($colunasSelecionadas)) {
                throw new Exception(
                    'Selecione pelo menos uma coluna para o relatório.'
                );
            }

            $mapaColunas = self::getMapaColunas();

            foreach ($colunasSelecionadas as $coluna) {
                if (!isset($mapaColunas[$coluna])) {
                    throw new Exception(
                        'Foi selecionada uma coluna inválida no relatório.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | QUERY
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    rp.id AS requisicao,

                    COALESCE(
                        p_req.numero_cnj_numero,
                        p_req.numero_outro,
                        p_req.id::text
                    ) AS processo,

                    trp.nome AS tipo_requisicao,

                    rp.data_criacao,

                    cliente.nome AS cliente,
                    cliente.cpf_cnpj,

                    srp.nome AS status,

                    entidade.nome AS entidade_devedora,

                    rpc.valor AS valor_requisicao,
                    rpc.data_requerimento,
                    rpc.data_base,

                    COALESCE(
                        e3.conta_indicada_mle,
                        rpc.conta_indicada_mle
                    ) AS conta_indicada_mle,

                    rpc.obs AS observacao,

                    COALESCE(
                        p_e2.numero_cnj_numero,
                        p_e2.numero_outro,
                        p_e2.id::text
                    ) AS processo_etapa2,

                    e2.data_deferimento_expedicao_requisitorio
                        AS data_deferimento_expedicao,

                    e2.protocolo_depre_entidade_devedora
                        AS protocolo_depre,

                    e2.numero_depre_entidade_devedora
                        AS numero_depre,

                    e2.numero_ordem,

                    COALESCE(
                        p_e3.numero_cnj_numero,
                        p_e3.numero_outro,
                        p_e3.id::text
                    ) AS processo_etapa3,

                    e3.numero_ciclo,

                    CASE
                        WHEN rp.tipos_requisicao_pagamento_id = 3
                            THEN COALESCE(
                                e3.data_deposito,
                                rpc.data_base
                            )
                        ELSE e3.data_deposito
                    END AS data_deposito,

                    e3.valor_bruto_depositado
                        AS valor_bruto,

                    CASE
                        WHEN rp.tipos_requisicao_pagamento_id = 3
                            THEN COALESCE(
                                e3.valor_mle,
                                rpc.valor
                            )
                        ELSE e3.valor_mle
                    END AS valor_mle,

                    CASE
                        WHEN rp.tipos_requisicao_pagamento_id = 3
                            THEN COALESCE(
                                e3.data_pedido_mle,
                                rpc.data_requerimento
                            )
                        ELSE e3.data_pedido_mle
                    END AS data_pedido_mle,

                    e3.data_deferimento_mle,

                    e3.saldo_bruto,

                    e3.data_base_saldo,

                    COALESCE(
                        e3.possui_saldo,
                        'N'
                    ) AS possui_saldo

                FROM requisicao_pagamento rp

                JOIN requisicao_pagamento_cliente rpc
                    ON rpc.requisicao_pagamento_id = rp.id

                LEFT JOIN processo p_req
                    ON p_req.id = rp.processo_id

                LEFT JOIN tipos_requisicao_pagamento trp
                    ON trp.id = rp.tipos_requisicao_pagamento_id

                LEFT JOIN pessoa cliente
                    ON cliente.id = rpc.pessoa_id

                LEFT JOIN pessoa entidade
                    ON entidade.id = rpc.entidade_devedora_id

                LEFT JOIN status_requisicao_pagamento srp
                    ON srp.id = rpc.status_requisicao_pagamento_id

                LEFT JOIN LATERAL (
                    SELECT e2x.*
                    FROM requisicao_pagamento_etapa2 e2x
                    WHERE e2x.requisicao_pagamento_cliente_id = rpc.id
                    ORDER BY e2x.id DESC
                    LIMIT 1
                ) e2 ON true

                LEFT JOIN processo p_e2
                    ON p_e2.id = e2.processo_filho_id

                /*
                * NÃO usamos LATERAL com LIMIT na Etapa 3.
                *
                * Isso é proposital:
                * cada pagamento/ciclo precisa gerar uma linha
                * separada no Excel.
                */
                LEFT JOIN requisicao_pagamento_etapa3 e3
                    ON e3.requisicao_pagamento_cliente_id = rpc.id

                LEFT JOIN processo p_e3
                    ON p_e3.id = e3.processo_filho_id

                WHERE rpc.data_requerimento >= ?
                AND rpc.data_requerimento <= ?
            ";

            $paramsSql = [
                $dataInicial,
                $dataFinal
            ];

            /*
            |--------------------------------------------------------------------------
            | FILTROS OPCIONAIS
            |--------------------------------------------------------------------------
            */

            $processoFiltro = trim(
                (string) ($param['processo'] ?? '')
            );

            if ($processoFiltro !== '') {
                $sql .= "
                    AND (
                        COALESCE(
                            p_req.numero_cnj_numero,
                            p_req.numero_outro,
                            ''
                        ) ILIKE ?

                        OR COALESCE(
                            p_e2.numero_cnj_numero,
                            p_e2.numero_outro,
                            ''
                        ) ILIKE ?

                        OR COALESCE(
                            p_e3.numero_cnj_numero,
                            p_e3.numero_outro,
                            ''
                        ) ILIKE ?

                        OR COALESCE(
                            e2.numero_depre_entidade_devedora,
                            ''
                        ) ILIKE ?
                    )
                ";

                $valorBusca = '%' . $processoFiltro . '%';

                $paramsSql[] = $valorBusca;
                $paramsSql[] = $valorBusca;
                $paramsSql[] = $valorBusca;
                $paramsSql[] = $valorBusca;
            }

            $clienteFiltro = trim(
                (string) ($param['cliente'] ?? '')
            );

            if ($clienteFiltro !== '') {
                $sql .= "
                    AND cliente.nome ILIKE ?
                ";

                $paramsSql[] = '%' . $clienteFiltro . '%';
            }

            $tipoRequisicaoId = self::inteiroOuNull(
                $param['tipo_requisicao_id'] ?? null
            );

            if (!empty($tipoRequisicaoId)) {
                $sql .= "
                    AND rp.tipos_requisicao_pagamento_id = ?
                ";

                $paramsSql[] = $tipoRequisicaoId;
            }

            $statusId = self::inteiroOuNull(
                $param['status_id'] ?? null
            );

            if (!empty($statusId)) {
                $sql .= "
                    AND rpc.status_requisicao_pagamento_id = ?
                ";

                $paramsSql[] = $statusId;
            }

            $entidadeId = self::inteiroOuNull(
                $param['entidade_devedora_id'] ?? null
            );

            if (!empty($entidadeId)) {
                $sql .= "
                    AND rpc.entidade_devedora_id = ?
                ";

                $paramsSql[] = $entidadeId;
            }

            $numeroOrdem = trim(
                (string) ($param['numero_ordem'] ?? '')
            );

            if ($numeroOrdem !== '') {
                $sql .= "
                    AND COALESCE(e2.numero_ordem, '') ILIKE ?
                ";

                $paramsSql[] = '%' . $numeroOrdem . '%';
            }

            $numeroDepre = trim(
                (string) ($param['numero_depre'] ?? '')
            );

            if ($numeroDepre !== '') {
                $sql .= "
                    AND COALESCE(
                        e2.numero_depre_entidade_devedora,
                        ''
                    ) ILIKE ?
                ";

                $paramsSql[] = '%' . $numeroDepre . '%';
            }

            $valorMleDe = self::valorDecimalParaBanco(
                $param['valor_mle_de'] ?? null
            );

            if ($valorMleDe !== null) {
                $sql .= "
                    AND (
                        CASE
                            WHEN rp.tipos_requisicao_pagamento_id = 3
                                THEN COALESCE(
                                    e3.valor_mle,
                                    rpc.valor
                                )
                            ELSE e3.valor_mle
                        END
                    ) >= ?
                ";

                $paramsSql[] = $valorMleDe;
            }

            $valorMleAte = self::valorDecimalParaBanco(
                $param['valor_mle_ate'] ?? null
            );

            if ($valorMleAte !== null) {
                $sql .= "
                    AND (
                        CASE
                            WHEN rp.tipos_requisicao_pagamento_id = 3
                                THEN COALESCE(
                                    e3.valor_mle,
                                    rpc.valor
                                )
                            ELSE e3.valor_mle
                        END
                    ) <= ?
                ";

                $paramsSql[] = $valorMleAte;
            }

            $possuiSaldo = strtoupper(
                trim(
                    (string) ($param['possui_saldo'] ?? '')
                )
            );

            if (in_array($possuiSaldo, ['S', 'N'])) {
                $sql .= "
                    AND COALESCE(e3.possui_saldo, 'N') = ?
                ";

                $paramsSql[] = $possuiSaldo;
            }

            $depositoIni = self::dataParaBanco(
                $param['data_deposito_ini'] ?? null
            );

            if (!empty($depositoIni)) {
                $sql .= "
                    AND (
                        CASE
                            WHEN rp.tipos_requisicao_pagamento_id = 3
                                THEN COALESCE(
                                    e3.data_deposito,
                                    rpc.data_base
                                )
                            ELSE e3.data_deposito
                        END
                    ) >= ?
                ";

                $paramsSql[] = $depositoIni;
            }

            $depositoFim = self::dataParaBanco(
                $param['data_deposito_fim'] ?? null
            );

            if (!empty($depositoFim)) {
                $sql .= "
                    AND (
                        CASE
                            WHEN rp.tipos_requisicao_pagamento_id = 3
                                THEN COALESCE(
                                    e3.data_deposito,
                                    rpc.data_base
                                )
                            ELSE e3.data_deposito
                        END
                    ) <= ?
                ";

                $paramsSql[] = $depositoFim;
            }

            $sql .= "
                ORDER BY
                    rpc.data_requerimento,
                    rp.id,
                    cliente.nome,
                    COALESCE(e3.numero_ciclo, 1),
                    e3.id
            ";

            /*
            |--------------------------------------------------------------------------
            | EXECUÇÃO
            |--------------------------------------------------------------------------
            */

            TTransaction::open(self::$database);

            $conn = TTransaction::get();

            $sth = $conn->prepare($sql);
            $sth->execute($paramsSql);

            $registros = $sth->fetchAll(PDO::FETCH_OBJ);

            if (empty($registros)) {
                TTransaction::close();

                throw new Exception(
                    'Nenhum registro encontrado para os filtros informados.'
                );
            }

            $mapaColunas = self::getMapaColunas();

            $outputDir = 'app/output';

            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0775, true);
            }

            if (!is_writable($outputDir)) {
                throw new Exception(
                    'A pasta app/output não possui permissão de escrita.'
                );
            }

            $output = $outputDir
                . '/Relatorio_Requisicao_Pagamento_'
                . date('Ymd_His')
                . '.xlsx';


            /*
            |--------------------------------------------------------------------------
            | CRIA XLSX
            |--------------------------------------------------------------------------
            */

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Requisições');


            /*
            |--------------------------------------------------------------------------
            | CABEÇALHO
            |--------------------------------------------------------------------------
            */

            $colunaExcel = 1;

            foreach ($colunasSelecionadas as $coluna) {
                $config = $mapaColunas[$coluna];

                $endereco = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                    $colunaExcel
                ) . '1';

                $sheet->setCellValue(
                    $endereco,
                    $config['titulo']
                );

                $colunaExcel++;
            }


            /*
            |--------------------------------------------------------------------------
            | DADOS
            |--------------------------------------------------------------------------
            */

            $linhaExcel = 2;

            foreach ($registros as $registro) {
                $colunaExcel = 1;

                foreach ($colunasSelecionadas as $coluna) {
                    $config = $mapaColunas[$coluna];

                    $valor = $registro->{$coluna} ?? null;

                    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                        $colunaExcel
                    );

                    $celula = $letra . $linhaExcel;

                    self::escreverCelulaXlsx(
                        $sheet,
                        $celula,
                        $valor,
                        $config['tipo'] ?? 'texto'
                    );

                    $colunaExcel++;
                }

                $linhaExcel++;
            }


            /*
            |--------------------------------------------------------------------------
            | INTERVALO
            |--------------------------------------------------------------------------
            */

            $ultimaColuna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                count($colunasSelecionadas)
            );

            $ultimaLinha = $linhaExcel - 1;

            $intervalo = 'A1:' . $ultimaColuna . $ultimaLinha;


            /*
            |--------------------------------------------------------------------------
            | TABELA REAL DO EXCEL
            |--------------------------------------------------------------------------
            */

            if ($ultimaLinha >= 2) {
                $tabela = new \PhpOffice\PhpSpreadsheet\Worksheet\Table(

                    $intervalo,
                    'TabelaRequisicoesPagamento'
                );

                $estiloTabela = new \PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle();

                $estiloTabela->setTheme(
                    \PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle::TABLE_STYLE_MEDIUM2
                );

                $estiloTabela->setShowRowStripes(true);

                $tabela->setStyle($estiloTabela);

                $sheet->addTable($tabela);
            }


            /*
            |--------------------------------------------------------------------------
            | EXCEL - USABILIDADE
            |--------------------------------------------------------------------------
            */

            $sheet->freezePane('A2');

            for ($i = 1; $i <= count($colunasSelecionadas); $i++) {
                $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                    $i
                );

                $sheet->getColumnDimension($letra)
                    ->setAutoSize(true);
            }

            $sheet->getRowDimension(1)
                ->setRowHeight(22);


            /*
            |--------------------------------------------------------------------------
            | ABA DE PARÂMETROS
            |--------------------------------------------------------------------------
            */

            $filtrosSheet = $spreadsheet->createSheet();
            $filtrosSheet->setTitle('Parâmetros');

            $filtrosSheet->setCellValue(
                'A1',
                'Relatório de Requisições de Pagamento'
            );

            $filtrosSheet->setCellValue(
                'A3',
                'Data do Requerimento - De'
            );

            $filtrosSheet->setCellValue(
                'B3',
                self::formatarDataBR($dataInicial)
            );

            $filtrosSheet->setCellValue(
                'A4',
                'Data do Requerimento - Até'
            );

            $filtrosSheet->setCellValue(
                'B4',
                self::formatarDataBR($dataFinal)
            );

            $filtrosSheet->setCellValue('A6', 'Gerado em');
            $filtrosSheet->setCellValue(
                'B6',
                date('d/m/Y H:i')
            );

            $filtrosSheet->getColumnDimension('A')->setAutoSize(true);
            $filtrosSheet->getColumnDimension('B')->setAutoSize(true);


            /*
            |--------------------------------------------------------------------------
            | SALVA
            |--------------------------------------------------------------------------
            */

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(
                $spreadsheet
            );

            $writer->save($output);

            $spreadsheet->disconnectWorksheets();

            TTransaction::close();

            TPage::openFile($output);
        }
        catch (Exception $e)
        {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }

            new TMessage(
                'error',
                $e->getMessage()
            );
        }
    }

    private static function adicionarLinhaExcel(
        $table,
        $registro,
        $colunasSelecionadas,
        $mapaColunas
    )
    {
        $table->addRow();

        foreach ($colunasSelecionadas as $coluna) {
            $config = $mapaColunas[$coluna];

            $valor = $registro->{$coluna} ?? null;

            $valor = self::formatarValorRelatorio(
                $valor,
                $config['tipo'] ?? 'texto'
            );

            $table->addCell(
                $valor,
                'left',
                'data'
            );
        }
    }

    private static function formatarValorRelatorio(
        $valor,
        $tipo
    )
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        switch ($tipo) {
            case 'data':
                return self::formatarDataBR($valor);

            case 'datahora':
                return self::formatarDataHoraBR($valor);

            case 'moeda':
                return number_format(
                    (float) $valor,
                    2,
                    ',',
                    '.'
                );

            case 'simnao':
                return strtoupper(
                    trim((string) $valor)
                ) === 'S'
                    ? 'Sim'
                    : 'Não';

            case 'cpfcnpj':
                return self::formatarCpfCnpj($valor);

            default:
                return (string) $valor;
        }
    }

    private static function getMapaColunas()
    {
        return [
            'requisicao' => [
                'titulo'  => 'Nº Requisição',
                'tipo'    => 'texto',
                'largura' => 80
            ],

            'processo' => [
                'titulo'  => 'Processo Principal',
                'tipo'    => 'texto',
                'largura' => 170
            ],

            'tipo_requisicao' => [
                'titulo'  => 'Tipo da Requisição',
                'tipo'    => 'texto',
                'largura' => 140
            ],

            'data_criacao' => [
                'titulo'  => 'Data de Criação',
                'tipo'    => 'datahora',
                'largura' => 120
            ],

            'cliente' => [
                'titulo'  => 'Cliente',
                'tipo'    => 'texto',
                'largura' => 190
            ],

            'cpf_cnpj' => [
                'titulo'  => 'CPF/CNPJ',
                'tipo'    => 'cpfcnpj',
                'largura' => 120
            ],

            'status' => [
                'titulo'  => 'Status',
                'tipo'    => 'texto',
                'largura' => 120
            ],

            'entidade_devedora' => [
                'titulo'  => 'Entidade Devedora',
                'tipo'    => 'texto',
                'largura' => 190
            ],

            'valor_requisicao' => [
                'titulo'  => 'Valor da Requisição',
                'tipo'    => 'moeda',
                'largura' => 100
            ],

            'data_requerimento' => [
                'titulo'  => 'Data do Requerimento',
                'tipo'    => 'data',
                'largura' => 110
            ],

            'data_base' => [
                'titulo'  => 'Data Base',
                'tipo'    => 'data',
                'largura' => 95
            ],

            'conta_indicada_mle' => [
                'titulo'  => 'Conta Indicada',
                'tipo'    => 'texto',
                'largura' => 170
            ],

            'observacao' => [
                'titulo'  => 'Observação',
                'tipo'    => 'texto',
                'largura' => 260
            ],

            'processo_etapa2' => [
                'titulo'  => 'Processo Vinculado - Etapa 2',
                'tipo'    => 'texto',
                'largura' => 175
            ],

            'data_deferimento_expedicao' => [
                'titulo'  => 'Deferimento da Expedição',
                'tipo'    => 'data',
                'largura' => 130
            ],

            'protocolo_depre' => [
                'titulo'  => 'Protocolo DEPRE/Entidade',
                'tipo'    => 'data',
                'largura' => 130
            ],

            'numero_depre' => [
                'titulo'  => 'Número DEPRE/Entidade',
                'tipo'    => 'texto',
                'largura' => 180
            ],

            'numero_ordem' => [
                'titulo'  => 'Número da Ordem',
                'tipo'    => 'texto',
                'largura' => 120
            ],

            'processo_etapa3' => [
                'titulo'  => 'Processo do Pagamento',
                'tipo'    => 'texto',
                'largura' => 175
            ],

            'numero_ciclo' => [
                'titulo'  => 'Ciclo',
                'tipo'    => 'texto',
                'largura' => 65
            ],

            'data_deposito' => [
                'titulo'  => 'Data do Depósito',
                'tipo'    => 'data',
                'largura' => 110
            ],

            'valor_bruto' => [
                'titulo'  => 'Valor Bruto Depositado',
                'tipo'    => 'moeda',
                'largura' => 125
            ],

            'valor_mle' => [
                'titulo'  => 'Valor do MLE',
                'tipo'    => 'moeda',
                'largura' => 105
            ],

            'data_pedido_mle' => [
                'titulo'  => 'Data do Pedido MLE',
                'tipo'    => 'data',
                'largura' => 115
            ],

            'data_deferimento_mle' => [
                'titulo'  => 'Data de Deferimento MLE',
                'tipo'    => 'data',
                'largura' => 125
            ],

            'saldo_bruto' => [
                'titulo'  => 'Saldo Bruto Remanescente',
                'tipo'    => 'moeda',
                'largura' => 135
            ],

            'data_base_saldo' => [
                'titulo'  => 'Data Base do Saldo',
                'tipo'    => 'data',
                'largura' => 115
            ],

            'possui_saldo' => [
                'titulo'  => 'Possui Saldo?',
                'tipo'    => 'simnao',
                'largura' => 90
            ]
        ];
    }

    private static function criarGrupoColunas(
        $titulo,
        $campo
    )
    {
        $box = new TElement('div');
        $box->class = 'rel-dinamico-column-group';

        $title = new TElement('div');
        $title->class = 'rel-dinamico-column-group-title';
        $title->add($titulo);

        $content = new TElement('div');
        $content->class = 'rel-dinamico-column-group-content';
        $content->add($campo);

        $box->add($title);
        $box->add($content);

        return $box;
    }

    private static function normalizarArray($valor)
    {
        if ($valor === null || $valor === '') {
            return [];
        }

        if (is_array($valor)) {
            return array_values(
                array_filter(
                    $valor,
                    function ($item) {
                        return $item !== null
                            && $item !== '';
                    }
                )
            );
        }

        return [$valor];
    }

    private static function inteiroOuNull($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
    }

    private static function valorDecimalParaBanco($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $valor = trim((string) $valor);

        $valor = preg_replace(
            '/[^0-9,.\-]/',
            '',
            $valor
        );

        if (
            strpos($valor, ',') !== false
            && strpos($valor, '.') !== false
        ) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }
        elseif (strpos($valor, ',') !== false) {
            $valor = str_replace(',', '.', $valor);
        }

        return is_numeric($valor)
            ? (float) $valor
            : null;
    }

    private static function dataParaBanco($data)
    {
        if (empty($data)) {
            return null;
        }

        $data = trim((string) $data);

        if (
            preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $data
            )
        ) {
            return $data;
        }

        if (
            preg_match(
                '/^(\d{2})\/(\d{2})\/(\d{4})$/',
                $data,
                $match
            )
        ) {
            return $match[3]
                . '-'
                . $match[2]
                . '-'
                . $match[1];
        }

        return null;
    }

    private static function formatarDataBR($data)
    {
        if (empty($data)) {
            return '';
        }

        $data = substr(
            (string) $data,
            0,
            10
        );

        $partes = explode('-', $data);

        if (count($partes) !== 3) {
            return $data;
        }

        return $partes[2]
            . '/'
            . $partes[1]
            . '/'
            . $partes[0];
    }

    private static function formatarDataHoraBR($data)
    {
        if (empty($data)) {
            return '';
        }

        $data = str_replace(
            'T',
            ' ',
            (string) $data
        );

        $partes = explode(' ', $data);

        $resultado = self::formatarDataBR(
            $partes[0] ?? ''
        );

        if (!empty($partes[1])) {
            $resultado .= ' '
                . substr($partes[1], 0, 5);
        }

        return $resultado;
    }

    private static function formatarCpfCnpj($valor)
    {
        if (empty($valor)) {
            return '';
        }

        $numero = preg_replace(
            '/\D/',
            '',
            (string) $valor
        );

        if (strlen($numero) === 11) {
            return substr($numero, 0, 3)
                . '.'
                . substr($numero, 3, 3)
                . '.'
                . substr($numero, 6, 3)
                . '-'
                . substr($numero, 9, 2);
        }

        if (strlen($numero) === 14) {
            return substr($numero, 0, 2)
                . '.'
                . substr($numero, 2, 3)
                . '.'
                . substr($numero, 5, 3)
                . '/'
                . substr($numero, 8, 4)
                . '-'
                . substr($numero, 12, 2);
        }

        return (string) $valor;
    }

    private static function escreverCelulaXlsx(
        $sheet,
        $celula,
        $valor,
        $tipo
    )
    {
        if ($valor === null || $valor === '') {
            $sheet->setCellValue($celula, null);
            return;
        }

        switch ($tipo) {
            case 'moeda':

                $sheet->setCellValueExplicit(
                    $celula,
                    (float) $valor,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                );

                $sheet
                    ->getStyle($celula)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                break;


            case 'data':

                try {
                    $data = new DateTime(
                        substr((string) $valor, 0, 10)
                    );

                    $sheet->setCellValue(
                        $celula,
                        \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                            $data
                        )
                    );

                    $sheet
                        ->getStyle($celula)
                        ->getNumberFormat()
                        ->setFormatCode('dd/mm/yyyy');
                }
                catch (Exception $e) {
                    $sheet->setCellValueExplicit(
                        $celula,
                        (string) $valor,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }

                break;


            case 'datahora':

                try {
                    $data = new DateTime(
                        (string) $valor
                    );

                    $sheet->setCellValue(
                        $celula,
                        \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                            $data
                        )
                    );

                    $sheet
                        ->getStyle($celula)
                        ->getNumberFormat()
                        ->setFormatCode('dd/mm/yyyy hh:mm');
                }
                catch (Exception $e) {
                    $sheet->setCellValueExplicit(
                        $celula,
                        (string) $valor,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }

                break;


            case 'simnao':

                $sheet->setCellValueExplicit(
                    $celula,
                    strtoupper(trim((string) $valor)) === 'S'
                        ? 'Sim'
                        : 'Não',
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );

                break;


            case 'cpfcnpj':

                $sheet->setCellValueExplicit(
                    $celula,
                    self::formatarCpfCnpj($valor),
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );

                break;


            default:

                $sheet->setCellValueExplicit(
                    $celula,
                    (string) $valor,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );

                break;
        }
    }

        private function aplicarCss()
        {
            $style = new TElement('style');

            $style->add("
                #" . self::$formName . " {
                    font-family: inherit !important;
                    color: inherit !important;
                }

                #" . self::$formName . " .panel-body {
                    padding: 18px 22px !important;
                }

                #" . self::$formName . " label {
                    font-family: inherit !important;
                    font-size: inherit !important;
                    color: inherit !important;
                }

                #" . self::$formName . " input:not([type='checkbox']),
                #" . self::$formName . " select,
                #" . self::$formName . " .select2-selection {
                    min-height: 34px !important;
                    height: 34px !important;
                    font-family: inherit !important;
                    font-size: inherit !important;
                }

                .rel-dinamico-section-title {
                    margin: 18px 0 12px 0;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #dee2e6;
                    font-size: 14px;
                    font-weight: 600;
                }

                .rel-dinamico-help {
                    margin: 4px 0 16px 0;
                    padding: 10px 12px;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    background: #f8f9fa;
                    color: #6c757d;
                    font-size: 12px;
                    line-height: 1.5;
                }

                .rel-dinamico-column-actions {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 12px;
                }

                .rel-dinamico-column-group {
                    margin-bottom: 12px;
                    border: 1px solid #dee2e6;
                    border-radius: 5px;
                    background: #ffffff;
                    overflow: hidden;
                }

                .rel-dinamico-column-group-title {
                    padding: 9px 12px;
                    background: #f8f9fa;
                    border-bottom: 1px solid #dee2e6;
                    font-weight: 600;
                }

                /*
                |--------------------------------------------------------------------------
                | SELEÇÃO DAS COLUNAS
                |--------------------------------------------------------------------------
                */

                .rel-dinamico-column-group-content {
                    padding: 12px 14px;
                }

                /*
                * Checkbox real fica invisível.
                * Continua funcionando e enviando normalmente no formulário.
                */
                .rel-dinamico-column-group-content input[type='checkbox'] {
                    position: absolute !important;
                    opacity: 0 !important;
                    width: 1px !important;
                    height: 1px !important;
                    min-width: 1px !important;
                    min-height: 1px !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    pointer-events: none !important;
                }

                /*
                * Cada coluna aparece como botão.
                */
                #" . self::$formName . " .rel-dinamico-column-group-content label {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;

                    min-height: 34px !important;

                    margin: 0 8px 8px 0 !important;
                    padding: 7px 12px !important;

                    border: 1px solid #d7dde8 !important;
                    border-radius: 6px !important;

                    background: #ffffff !important;

                    font-family: inherit !important;
                    font-size: inherit !important;
                    font-weight: 400 !important;

                    color: #334155 !important;

                    cursor: pointer !important;
                    white-space: nowrap !important;

                    box-shadow: none !important;

                    transition:
                        background-color .15s ease,
                        border-color .15s ease,
                        color .15s ease,
                        box-shadow .15s ease !important;
                }

                /*
                * Remove qualquer decoração que o tema/TCheckGroup tente inserir.
                */
                #" . self::$formName . " .rel-dinamico-column-group-content label::before,
                #" . self::$formName . " .rel-dinamico-column-group-content label::after {
                    display: none !important;
                    content: none !important;
                }

                /*
                * Estado normal ao passar o mouse.
                */
                #" . self::$formName . " .rel-dinamico-column-group-content label:hover {
                    background: #f8fafc !important;
                    border-color: #94a3b8 !important;
                    color: #1E2843 !important;
                }

                /*
                |--------------------------------------------------------------------------
                | SELECIONADO
                |--------------------------------------------------------------------------
                |
                | Usa o ID do formulário no seletor para ganhar das regras globais
                | do tema que estavam deixando o texto escuro.
                */

                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:checked + label {
                    background: #1E2843 !important;
                    border-color: #1E2843 !important;

                    color: #ffffff !important;
                    font-weight: 600 !important;

                    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.16) !important;
                }

                /*
                * Garante branco também se o TCheckGroup colocar span, div ou outro
                * elemento dentro do label.
                */
                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:checked + label,
                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:checked + label *,
                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:checked + label span {
                    color: #ffffff !important;
                }

                /*
                * Hover de selecionado.
                */
                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:checked + label:hover {
                    background: #263654 !important;
                    border-color: #263654 !important;
                    color: #ffffff !important;
                }

                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:checked + label:hover *,
                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:checked + label:hover span {
                    color: #ffffff !important;
                }

                /*
                * Foco por teclado.
                */
                #" . self::$formName . " .rel-dinamico-column-group-content input[type='checkbox']:focus + label {
                    outline: 2px solid #94a3b8 !important;
                    outline-offset: 2px !important;
                }

                /*
                |--------------------------------------------------------------------------
                | BOTÃO GERAR EXCEL
                |--------------------------------------------------------------------------
                */

                #" . self::$formName . " .btn-success {
                    font-family: inherit !important;
                    font-weight: 600 !important;
                }

                /*
                |--------------------------------------------------------------------------
                | RESPONSIVO
                |--------------------------------------------------------------------------
                */

                @media (max-width: 768px) {
                    #" . self::$formName . " .panel-body {
                        padding: 12px !important;
                    }

                    #" . self::$formName . " .rel-dinamico-column-group-content label {
                        display: flex !important;
                        width: 100% !important;
                        margin-right: 0 !important;
                    }

                    .rel-dinamico-column-actions {
                        flex-wrap: wrap;
                    }
                }
            ");

            parent::add($style);
        }

}