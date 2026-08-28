<?php

class RequisicaoPagamentoVisualizacaoAba extends TPage
{
    protected $form;

    private static $database = 'escritorio';
    private static $formName = 'form_RequisicaoPagamentoVisualizacao';

   public function __construct($param = null)
    {
        parent::__construct();

        if (!empty($param['target_container'])) {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->aplicarCss();

        if (!empty($param['processo_id'])) {
            $this->montarPorProcesso($param['processo_id'], true);
        } elseif (!empty($param['key'])) {
            $this->montarVisualizacao(
                $param['key'],
                $param['pessoa_id'] ?? null,
                true,
                $param['processo_origem_id'] ?? null,
                !empty($param['exibir_voltar'])
            );
        }
    }

    public function onShow($param = null)
    {
    }


    private static function criarAvisoSemRequisicao($processoId, $processo = null)
    {
        $numero = !empty($processo->numero_processo)
            ? self::h($processo->numero_processo)
            : self::h($processoId);

        $div = new TElement('div');
        $div->setProperty('class', 'req-empty-panel');

        $div->add("
            <div class='req-empty-icon'>
                <i class='fa fa-file-invoice-dollar'></i>
            </div>

            <div class='req-empty-title'>
                Não existe requisição de pagamento para este processo
            </div>

            <div class='req-empty-text'>
                O processo <strong>{$numero}</strong> ainda não possui uma requisição de pagamento vinculada.
            </div>
        ");

        return $div;
    }

    private function buscarDadosProcessoSimples($conn, $processoId)
    {
        $sql = "
            SELECT
                p.id,
                COALESCE(p.numero_cnj_numero, p.numero_outro, p.id::text) AS numero_processo
            FROM processo p
            WHERE p.id = ?
            LIMIT 1
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([(int) $processoId]);

        return $sth->fetch(PDO::FETCH_OBJ);
    }

    private function buscarRequisicoesPorProcesso($conn, $processoId)
    {
        $processoId = (int) $processoId;

        if (empty($processoId)) {
            return [];
        }

        $idsProcessos = $this->buscarIdsProcessosVinculadosEmCadeia(
            $conn,
            $processoId,
            true
        );

        if (empty($idsProcessos)) {
            $idsProcessos = [$processoId];
        }

        $placeholders = implode(',', array_fill(0, count($idsProcessos), '?'));

        $sql = "
            SELECT
                rp.id AS requisicao_pagamento_id,
                rpc.id AS requisicao_pagamento_cliente_id,
                rpc.pessoa_id,

                cliente.nome AS cliente_nome,

                COALESCE(
                    p_etapa2.numero_cnj_numero,
                    p_etapa2.numero_outro,
                    p_etapa2.id::text,
                    p_req.numero_cnj_numero,
                    p_req.numero_outro,
                    p_req.id::text
                ) AS numero_processo_requisicao,

                trp.nome AS tipo_requisicao,
                rp.data_criacao,

                rpc.valor,
                srp.nome AS status_cliente,
                srp.cor AS status_cor,

                CASE
                    WHEN rp.processo_id = ? THEN 'Processo principal'

                    WHEN EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa2 e2x
                        WHERE e2x.requisicao_pagamento_cliente_id = rpc.id
                        AND e2x.processo_filho_id = ?
                    ) THEN 'Expedição do requisitório'

                    WHEN EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa3 e3x
                        WHERE e3x.requisicao_pagamento_cliente_id = rpc.id
                        AND e3x.processo_filho_id = ?
                    ) THEN 'Pagamento / MLE'

                    ELSE 'Processo vinculado'
                END AS origem_vinculo

            FROM requisicao_pagamento rp

            JOIN requisicao_pagamento_cliente rpc
                ON rpc.requisicao_pagamento_id = rp.id

            LEFT JOIN pessoa cliente
                ON cliente.id = rpc.pessoa_id

            LEFT JOIN processo p_req
                ON p_req.id = rp.processo_id

            LEFT JOIN LATERAL (
                SELECT
                    e2.processo_filho_id
                FROM requisicao_pagamento_etapa2 e2
                WHERE e2.requisicao_pagamento_cliente_id = rpc.id
                AND e2.processo_filho_id IS NOT NULL
                ORDER BY e2.id DESC
                LIMIT 1
            ) e2_card ON true

            LEFT JOIN processo p_etapa2
                ON p_etapa2.id = e2_card.processo_filho_id

            LEFT JOIN tipos_requisicao_pagamento trp
                ON trp.id = rp.tipos_requisicao_pagamento_id

            LEFT JOIN status_requisicao_pagamento srp
                ON srp.id = rpc.status_requisicao_pagamento_id

            WHERE (
                rp.processo_id = ?

                OR EXISTS (
                    SELECT 1
                    FROM processo_view pvw
                    WHERE pvw.id = ?
                    AND pvw.pessoa_id = rpc.pessoa_id
                )

                OR EXISTS (
                    SELECT 1
                    FROM requisicao_pagamento_etapa2 e2_atual
                    WHERE e2_atual.requisicao_pagamento_cliente_id = rpc.id
                    AND e2_atual.processo_filho_id = ?
                )

                OR EXISTS (
                    SELECT 1
                    FROM requisicao_pagamento_etapa3 e3_atual
                    WHERE e3_atual.requisicao_pagamento_cliente_id = rpc.id
                    AND e3_atual.processo_filho_id = ?
                )
            )
            AND (
                rp.processo_id IN ({$placeholders})

                OR EXISTS (
                    SELECT 1
                    FROM requisicao_pagamento_etapa2 e2
                    WHERE e2.requisicao_pagamento_cliente_id = rpc.id
                    AND e2.processo_filho_id IN ({$placeholders})
                )

                OR EXISTS (
                    SELECT 1
                    FROM requisicao_pagamento_etapa3 e3
                    WHERE e3.requisicao_pagamento_cliente_id = rpc.id
                    AND e3.processo_filho_id IN ({$placeholders})
                )
            )

            ORDER BY
                CASE
                    WHEN rp.processo_id = ? THEN 0

                    WHEN EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa2 e2x
                        WHERE e2x.requisicao_pagamento_cliente_id = rpc.id
                        AND e2x.processo_filho_id = ?
                    ) THEN 0

                    WHEN EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa3 e3x
                        WHERE e3x.requisicao_pagamento_cliente_id = rpc.id
                        AND e3x.processo_filho_id = ?
                    ) THEN 0

                    ELSE 1
                END,
                rp.data_criacao DESC NULLS LAST,
                rp.id DESC
        ";

        $params = array_merge(
            [
                $processoId, // CASE rp.processo_id
                $processoId, // CASE etapa2
                $processoId, // CASE etapa3

                $processoId, // WHERE rp.processo_id
                $processoId, // WHERE processo_view
                $processoId, // WHERE etapa2 atual
                $processoId  // WHERE etapa3 atual
            ],
            $idsProcessos,
            $idsProcessos,
            $idsProcessos,
            [
                $processoId,
                $processoId,
                $processoId
            ]
        );

        $sth = $conn->prepare($sql);
        $sth->execute($params);

        return $sth->fetchAll(PDO::FETCH_OBJ);
    }

    private function buscarRequisicaoPorProcesso($conn, $processoId)
    {
        $processoId = (int) $processoId;

        if (empty($processoId)) {
            return null;
        }

        $idsProcessos = $this->buscarIdsProcessosVinculadosEmCadeia(
            $conn,
            $processoId,
            true
        );

        if (empty($idsProcessos)) {
            $idsProcessos = [$processoId];
        }

        $placeholders = implode(',', array_fill(0, count($idsProcessos), '?'));

        $sql = "
            SELECT
                rp.id AS requisicao_pagamento_id,
                rpc.id AS requisicao_pagamento_cliente_id,
                rpc.pessoa_id
            FROM requisicao_pagamento rp
            JOIN requisicao_pagamento_cliente rpc
                ON rpc.requisicao_pagamento_id = rp.id
            WHERE (
                    rp.processo_id = ?

                    OR EXISTS (
                        SELECT 1
                        FROM processo_view pvw
                        WHERE pvw.id = ?
                        AND pvw.pessoa_id = rpc.pessoa_id
                    )

                    OR EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa2 e2_atual
                        WHERE e2_atual.requisicao_pagamento_cliente_id = rpc.id
                        AND e2_atual.processo_filho_id = ?
                    )

                    OR EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa3 e3_atual
                        WHERE e3_atual.requisicao_pagamento_cliente_id = rpc.id
                        AND e3_atual.processo_filho_id = ?
                    )
                )
                AND (
                rp.processo_id IN ({$placeholders})

                OR EXISTS (
                    SELECT 1
                    FROM requisicao_pagamento_etapa2 e2
                    WHERE e2.requisicao_pagamento_cliente_id = rpc.id
                    AND e2.processo_filho_id IN ({$placeholders})
                )

                OR EXISTS (
                    SELECT 1
                    FROM requisicao_pagamento_etapa3 e3
                    WHERE e3.requisicao_pagamento_cliente_id = rpc.id
                    AND e3.processo_filho_id IN ({$placeholders})
                )
            )
            ORDER BY
                CASE
                    WHEN rp.processo_id = ? THEN 0

                    WHEN EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa2 e2x
                        WHERE e2x.requisicao_pagamento_cliente_id = rpc.id
                        AND e2x.processo_filho_id = ?
                    ) THEN 0

                    WHEN EXISTS (
                        SELECT 1
                        FROM requisicao_pagamento_etapa3 e3x
                        WHERE e3x.requisicao_pagamento_cliente_id = rpc.id
                        AND e3x.processo_filho_id = ?
                    ) THEN 0

                    ELSE 1
                END,
                rp.data_criacao DESC NULLS LAST,
                rp.id DESC
            LIMIT 1
        ";

        $params = array_merge(
            [
                $processoId,
                $processoId,
                $processoId,
                $processoId
            ],
            $idsProcessos,
            $idsProcessos,
            $idsProcessos,
            [$processoId, $processoId, $processoId]
        );

        $sth = $conn->prepare($sql);
        $sth->execute($params);

        return $sth->fetch(PDO::FETCH_OBJ);
    }

   private function montarPorProcesso($processoId, $modoEmbutido = false)
    {
        try {
            TTransaction::open(self::$database);

            $conn = TTransaction::get();

            $processo = $this->buscarDadosProcessoSimples($conn, $processoId);
            $requisicoes = $this->buscarRequisicoesPorProcesso($conn, $processoId);

            TTransaction::close();

            if (empty($requisicoes)) {
                parent::add(self::criarAvisoSemRequisicao($processoId, $processo));
                return;
            }

            if (count($requisicoes) === 1) {
                $dados = $requisicoes[0];

                $this->montarVisualizacao(
                    $dados->requisicao_pagamento_id,
                    $dados->pessoa_id,
                    $modoEmbutido,
                    $processoId
                );

                return;
            }

            parent::add(
                $this->criarListaRequisicoesProcesso(
                    $requisicoes,
                    $processoId,
                    $processo
                )
            );
        }
        catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function montarVisualizacao($requisicao_id, $pessoaSelecionadaId = null, $modoEmbutido = false, $processoOrigemId = null, $exibirVoltarListagem = false){
        try {
            TTransaction::open(self::$database);

            $conn = TTransaction::get();

            $requisicao = $this->buscarRequisicao($conn, $requisicao_id);

            if (empty($requisicao)) {
                throw new Exception('Requisição de pagamento não encontrada.');
            }
            $clientes = $this->buscarClientes($conn, $requisicao_id);

            $processoOrigemId = !empty($processoOrigemId) ? (int) $processoOrigemId : null;
            $processoPrincipalId = !empty($requisicao->processo_id) ? (int) $requisicao->processo_id : null;

            $filtrarClientePorProcesso = false;

            if (
                !empty($processoOrigemId)
                && !empty($processoPrincipalId)
                && $processoOrigemId !== $processoPrincipalId
                && !empty($pessoaSelecionadaId)
            ) {
                $filtrarClientePorProcesso = true;

                $clientes = array_values(array_filter($clientes, function ($cliente) use ($pessoaSelecionadaId) {
                    return (int) $cliente->cliente_id === (int) $pessoaSelecionadaId;
                }));
            }

            $processosVinculados = $this->buscarProcessosVinculadosDoPrincipal(
                $conn,
                $requisicao->processo_id
            );

            $pagamentosPorCliente = $this->buscarPagamentosEtapa3PorCliente($conn, $requisicao_id);

            $coresStatus = $this->buscarCoresStatus($conn);

            TTransaction::close();

            $container = new TVBox;
            $container->style = 'width: 100%;';

            if (!empty($exibirVoltarListagem) && !empty($processoOrigemId)) {
                $container->add($this->criarBotaoVoltarListagemRequisicoes($processoOrigemId));
            }

            $resumo = new TElement('div');
            $resumo->setProperty('class', 'req-view-summary');

            $resumo->add('<div><strong>N° da requisição:</strong> ' . self::h($requisicao->requisicao_pagamento_id) . '</div>');
            $resumo->add('<div><strong>Tipo de requisição:</strong> ' . self::h($requisicao->tipo_requisicao) . '</div>');

            if (!empty($requisicao->data_criacao)) {
                $resumo->add('<div><strong>Data de criação:</strong> ' . self::formatarDataHoraBR($requisicao->data_criacao) . '</div>');
            }

            $resumo->add('<div><strong>Clientes:</strong> ' . count($clientes) . '</div>');

            $botaoVerProcesso = $modoEmbutido
                ? ''
                : self::criarBotaoVerProcesso($requisicao->processo_id);

            $resumo->add("
                <div class='req-summary-process-full'>
                    <div class='req-summary-process-number'>
                        <strong>Processo:</strong>
                        <span>" . self::h($requisicao->numero_processo) . "</span>
                    </div>

                    {$botaoVerProcesso}
                </div>
            ");

            $container->add($resumo);

            if (empty($clientes)) {
                $aviso = new TElement('div');
                $aviso->setProperty('class', 'req-view-warning');
                $aviso->add('Nenhum cliente vinculado a esta requisição.');

                $container->add($aviso);

                parent::add($container);
                return;
            }

            $this->form = new BootstrapFormBuilder(self::$formName);
            $this->form->setFormTitle('');
            $this->form->setFieldSizes('100%');

            $data = new stdClass;

            $ehMle = ((int) $requisicao->tipos_requisicao_pagamento_id === 3);

            $tituloSelecionado = null;
            $rpcSelecionadoId = null;

            foreach ($clientes as $cliente) {
                $rpcId = (int) $cliente->requisicao_pagamento_cliente_id;

                $processoPadraoCliente =  null;

                $titulo = $cliente->cliente_nome ?: 'Cliente ' . $rpcId;

                if (mb_strlen($titulo) > 26) {
                    $titulo = mb_substr($titulo, 0, 26) . '...';
                }

                if (!empty($pessoaSelecionadaId) && (int) $cliente->cliente_id === (int) $pessoaSelecionadaId) {
                    $tituloSelecionado = $titulo;
                    $rpcSelecionadoId = $rpcId;
                }

                $this->form->appendPage($titulo);

                $nome = new TEntry('nome_' . $rpcId);
                $cpf = new TEntry('cpf_' . $rpcId);
                $dtNascimento = new TDate('dt_nascimento_' . $rpcId);

                $status = new TDBCombo(
                    'status_' . $rpcId,
                    self::$database,
                    'StatusRequisicaoPagamento',
                    'id',
                    'nome',
                    'nome'
                );

                $status->enableSearch();

                $dtRequerimento = new TDate('data_requerimento_' . $rpcId);
                $valor = new TNumeric('valor_' . $rpcId, 2, ',', '.', true);
                $dtBase = new TDate('data_base_' . $rpcId);

                $etapa3ValorBruto = null;
                if ($ehMle) {
                    $etapa3ValorBruto = new TNumeric('etapa3_valor_bruto_depositado_' . $rpcId, 2, ',', '.', true);
                }
                $entidade = new TDBUniqueSearch('entidade_devedora_id_' . $rpcId, self::$database, 'Pessoa', 'id', 'nome', 'nome');
                $obs = new TText('obs_' . $rpcId);
                $obs->setSize('100%', 90);

                $contaIndicadaMle = new TEntry('conta_indicada_mle_' . $rpcId);
                $contaIndicadaMle->setSize('100%');

                $cpf->setMask('999.999.999-99');

                $nome->setEditable(false);
                $cpf->setEditable(false);

                $nome->setProperty('class', 'req-readonly-field');
                $cpf->setProperty('class', 'req-readonly-field');

                $dtNascimento->setMask('dd/mm/yyyy');
                $dtNascimento->setDatabaseMask('yyyy-mm-dd');

                $dtRequerimento->setMask('dd/mm/yyyy');
                $dtRequerimento->setDatabaseMask('yyyy-mm-dd');

                $dtBase->setMask('dd/mm/yyyy');
                $dtBase->setDatabaseMask('yyyy-mm-dd');

                $entidade->setMinLength(2);
                $entidade->setSize('100%');

                $row = $this->form->addFields(
                    [new TLabel('Nome'), $nome],
                    [new TLabel('CPF'), $cpf],
                    [new TLabel('Data de nascimento'), $dtNascimento],
                    [new TLabel('Status'), $status]
                );
                $row->layout = ['col-sm-5', 'col-sm-2', 'col-sm-2', 'col-sm-3'];

              if ($ehMle) {
                $row = $this->form->addFields(
                    [new TLabel('Data de Pedido do MLE'), $dtRequerimento],
                    [new TLabel('Data do depósito'), $dtBase],
                    [new TLabel('Valor bruto depositado'), $etapa3ValorBruto],
                    [new TLabel('Valor do MLE'), $valor],
                    [new TLabel('Entidade devedora/devedor'), $entidade]
                );
                $row->layout = ['col-sm-2', 'col-sm-2', 'col-sm-2', 'col-sm-2', 'col-sm-4'];
            } else {
                    $row = $this->form->addFields(
                        [new TLabel('Data do requerimento'), $dtRequerimento],
                        [new TLabel('Valor'), $valor],
                        [new TLabel('Data base do Cálculo'), $dtBase],
                        [new TLabel('Entidade devedora/devedor'), $entidade]
                    );
                    $row->layout = ['col-sm-2', 'col-sm-2', 'col-sm-2', 'col-sm-6'];
                }

                $labelContaIndicada = $ehMle ? 'Conta Indicada para Levantamento' : 'Conta Indicada para Levantamento';

                $row = $this->form->addFields(
                    [new TLabel($labelContaIndicada), $contaIndicadaMle]
                );
                $row->layout = ['col-sm-3'];
                                
                $data->{'nome_' . $rpcId} = $cliente->cliente_nome;
                $data->{'cpf_' . $rpcId} = self::formatarCpfCnpj($cliente->cpf_cnpj);
                $data->{'dt_nascimento_' . $rpcId} = self::formatarDataBR($cliente->dt_nascimento_abertura);
                $data->{'status_' . $rpcId} = $cliente->status_requisicao_pagamento_id;
                $data->{'data_requerimento_' . $rpcId} = self::formatarDataBR($cliente->data_requerimento);
                $data->{'valor_' . $rpcId} = $cliente->valor;
                $data->{'data_base_' . $rpcId} = self::formatarDataBR($cliente->data_base);
                $data->{'entidade_devedora_id_' . $rpcId} = $cliente->entidade_devedora_id;
                $data->{'conta_indicada_mle_' . $rpcId} = $cliente->conta_indicada_mle;

               if (!$ehMle) {
                    $this->form->addContent([self::criarLinhaSeparadora()]);

                    $etapa2Processo = new TCombo('etapa2_processo_filho_id_' . $rpcId);
                    $etapa2DataDeferimento = new TDate('etapa2_data_deferimento_expedicao_requisitorio_' . $rpcId);
                    $etapa2Protocolo = new TDate('etapa2_protocolo_depre_entidade_devedora_' . $rpcId);
                    $etapa2Protocolo->setMask('dd/mm/yyyy');
                    $etapa2Protocolo->setDatabaseMask('yyyy-mm-dd');
                    $etapa2Numero = new TEntry('etapa2_numero_depre_entidade_devedora_' . $rpcId);
                    $etapa2NumeroOrdem = new TEntry('etapa2_numero_ordem_' . $rpcId);

                    $etapa2Processo->addItems($processosVinculados);
                    $etapa2Processo->enableSearch();

                    $etapa2DataDeferimento->setMask('dd/mm/yyyy');
                    $etapa2DataDeferimento->setDatabaseMask('yyyy-mm-dd');

                    $row = $this->form->addFields(
                        [new TLabel('Processo vinculado'), $etapa2Processo],
                        [new TLabel('Data de deferimento da expedição'), $etapa2DataDeferimento],
                        [new TLabel('Protocolo na DEPRE/entidade'), $etapa2Protocolo],
                        [new TLabel('Número na DEPRE/entidade'), $etapa2Numero],
                        [new TLabel('Número da ordem'), $etapa2NumeroOrdem]
                    );
                    $row->layout = ['col-sm-3', 'col-sm-2', 'col-sm-3', 'col-sm-2', 'col-sm-2'];

                    $data->{'etapa2_processo_filho_id_' . $rpcId} = self::valorComboProcesso(
                        $cliente->etapa2_processo_filho_id
                    );

                    $data->{'etapa2_data_deferimento_expedicao_requisitorio_' . $rpcId} = self::formatarDataBR($cliente->etapa2_data_deferimento_expedicao_requisitorio);
                    $data->{'etapa2_protocolo_depre_entidade_devedora_' . $rpcId} = self::formatarDataBR($cliente->etapa2_protocolo_depre_entidade_devedora);
                    $data->{'etapa2_numero_depre_entidade_devedora_' . $rpcId} = $cliente->etapa2_numero_depre_entidade_devedora;
                    $data->{'etapa2_numero_ordem_' . $rpcId} = $cliente->etapa2_numero_ordem;
                }

                $this->form->addContent([self::criarLinhaSeparadora()]);

                $etapa3Id = new THidden('etapa3_id_' . $rpcId);
                $etapa3NumeroCicloHidden = new THidden('etapa3_numero_ciclo_' . $rpcId);

                $this->form->addFields([$etapa3Id], [$etapa3NumeroCicloHidden]);

                $etapa3Processo = new TCombo('etapa3_processo_filho_id_' . $rpcId);
                $etapa3DataDeposito = new TDate('etapa3_data_deposito_' . $rpcId);
                if (empty($etapa3ValorBruto)) {
                    $etapa3ValorBruto = new TNumeric('etapa3_valor_bruto_depositado_' . $rpcId, 2, ',', '.', true);
                }
                $etapa3ValorMle = new TNumeric('etapa3_valor_mle_' . $rpcId, 2, ',', '.', true);
                $etapa3ContaMle = null;
                $etapa3ContaMle = null;

                $etapa3DataPedidoMle = new TDate('etapa3_data_pedido_mle_' . $rpcId);
                $etapa3DataDeferimentoMle = new TDate('etapa3_data_deferimento_mle_' . $rpcId);

                $etapa3PossuiSaldo = new TCombo('etapa3_possui_saldo_' . $rpcId);
                $etapa3SaldoBruto = new TNumeric('etapa3_saldo_bruto_' . $rpcId, 2, ',', '.', true);
                $etapa3DataBaseSaldo = new TDate('etapa3_data_base_saldo_' . $rpcId);

                $etapa3Processo->addItems($processosVinculados);
                $etapa3Processo->enableSearch();

                $etapa3DataDeposito->setMask('dd/mm/yyyy');
                $etapa3DataDeposito->setDatabaseMask('yyyy-mm-dd');

                $etapa3DataPedidoMle->setMask('dd/mm/yyyy');
                $etapa3DataPedidoMle->setDatabaseMask('yyyy-mm-dd');

                $etapa3DataDeferimentoMle->setMask('dd/mm/yyyy');
                $etapa3DataDeferimentoMle->setDatabaseMask('yyyy-mm-dd');

                $etapa3DataBaseSaldo->setMask('dd/mm/yyyy');
                $etapa3DataBaseSaldo->setDatabaseMask('yyyy-mm-dd');

                $etapa3PossuiSaldo->addItems([
                    'N' => 'Não',
                    'S' => 'Sim'
                ]);

                $abrirNovoPagamento = !empty($cliente->etapa3_id)
                    && !empty($cliente->etapa3_data_deposito)
                    && strtoupper(trim($cliente->etapa3_possui_saldo ?? 'N')) === 'S';

                $numeroCicloEtapa3 = 1;

                if (!empty($cliente->etapa3_numero_ciclo)) {
                    $numeroCicloEtapa3 = (int) $cliente->etapa3_numero_ciclo;
                }

                if ($abrirNovoPagamento) {
                    $numeroCicloEtapa3++;

                    $this->form->addContent([self::criarAvisoSaldoAberto($cliente)]);
                }

                if (!$ehMle) {
                   $row = $this->form->addFields(
                        [new TLabel('Processo vinculado'), $etapa3Processo],
                        [new TLabel('Data do depósito'), $etapa3DataDeposito],
                        [new TLabel('Valor bruto depositado'), $etapa3ValorBruto],
                        [new TLabel('Valor do MLE'), $etapa3ValorMle]
                    );
                    $row->layout = ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3'];
                } 

                if ($ehMle) {
                    $row = $this->form->addFields(
                        [new TLabel('Data de deferimento'), $etapa3DataDeferimentoMle],
                        [new TLabel('Sobrou saldo após este pagamento?'), $etapa3PossuiSaldo]
                    );
                    $row->layout = ['col-sm-2', 'col-sm-3'];
                } else {
                    $row = $this->form->addFields(
                        [new TLabel('Data do pedido de MLE'), $etapa3DataPedidoMle],
                        [new TLabel('Data do deferimento do MLE'), $etapa3DataDeferimentoMle],
                        [new TLabel('Sobrou saldo após este pagamento?'), $etapa3PossuiSaldo]
                    );
                    $row->layout = ['col-sm-2', 'col-sm-2', 'col-sm-3'];
                }

                $this->form->addContent([self::criarAjudaPagamentoParcial($rpcId)]);

                $row = $this->form->addFields(
                    [new TLabel('Saldo bruto remanescente'), $etapa3SaldoBruto],
                    [new TLabel('Data base do saldo remanescente'), $etapa3DataBaseSaldo]
                );
                $row->layout = ['col-sm-2', 'col-sm-2'];
                $historico = $pagamentosPorCliente[$rpcId] ?? [];

                if (!empty($historico)) {
                    $this->form->addContent([self::criarHistoricoPagamentos($historico)]);
                }

                $data->{'etapa3_id_' . $rpcId} = $abrirNovoPagamento ? null : $cliente->etapa3_id;
                $data->{'etapa3_numero_ciclo_' . $rpcId} = $numeroCicloEtapa3;

                if ($abrirNovoPagamento) {
                    $data->{'etapa3_processo_filho_id_' . $rpcId} = self::valorComboProcesso(
                            $cliente->etapa3_processo_filho_id
                    );
                    $data->{'etapa3_data_deposito_' . $rpcId} = null;
                    $data->{'etapa3_valor_bruto_depositado_' . $rpcId} = null;
                    $data->{'etapa3_valor_mle_' . $rpcId} = null;
                    $data->{'etapa3_data_pedido_mle_' . $rpcId} = null;
                    $data->{'etapa3_data_deferimento_mle_' . $rpcId} = null;
                    $data->{'etapa3_possui_saldo_' . $rpcId} = 'N';
                    $data->{'etapa3_saldo_bruto_' . $rpcId} = null;
                    $data->{'etapa3_data_base_saldo_' . $rpcId} = null;
                } else {
                    $data->{'etapa3_processo_filho_id_' . $rpcId} = self::valorComboProcesso(
                        $cliente->etapa3_processo_filho_id
                    );
                    $data->{'etapa3_data_deposito_' . $rpcId} = self::formatarDataBR($cliente->etapa3_data_deposito);
                    $data->{'etapa3_valor_bruto_depositado_' . $rpcId} = $cliente->etapa3_valor_bruto_depositado;
                    $data->{'etapa3_valor_mle_' . $rpcId} = $cliente->etapa3_valor_mle;
                    $data->{'etapa3_data_pedido_mle_' . $rpcId} = self::formatarDataBR($cliente->etapa3_data_pedido_mle);
                    $data->{'etapa3_data_deferimento_mle_' . $rpcId} = self::formatarDataBR($cliente->etapa3_data_deferimento_mle);
                    $data->{'etapa3_possui_saldo_' . $rpcId} = $cliente->etapa3_possui_saldo ?: 'N';
                    $data->{'etapa3_saldo_bruto_' . $rpcId} = $cliente->etapa3_saldo_bruto;
                    $data->{'etapa3_data_base_saldo_' . $rpcId} = self::formatarDataBR($cliente->etapa3_data_base_saldo);
                }

                $this->form->addContent([self::criarLinhaSeparadora()]);

                $row = $this->form->addFields(
                    [new TLabel('Observação'), $obs]
                );
                $row->layout = ['col-sm-12'];

                $data->{'obs_' . $rpcId} = $cliente->obs;

                $this->criarScriptSaldoParcial($rpcId);
            }

            $this->form->setData($data);

            if (!empty($tituloSelecionado)) {
                $this->form->setCurrentPage($tituloSelecionado);
            }

            $parametrosSalvar = [
                'key' => $requisicao_id
            ];

            if (!empty($processoOrigemId)) {
                $parametrosSalvar['processo_origem_id'] = (int) $processoOrigemId;
            }

            if (!empty($filtrarClientePorProcesso) && !empty($pessoaSelecionadaId)) {
                $parametrosSalvar['pessoa_id'] = (int) $pessoaSelecionadaId;
            }

            $this->form->addAction(
                'Salvar',
                new TAction([$this, 'onSave'], $parametrosSalvar),
                'far:save #ffffff'
            )->class = 'btn btn-primary';

            $container->add($this->form);

            parent::add($container);

            $this->criarScriptStatusColorido($coresStatus);

            $campoAbaSelecionada = !empty($rpcSelecionadoId)
                ? 'nome_' . $rpcSelecionadoId
                : '';

            TScript::create("
                setTimeout(function() {
                    ativarAbaClienteSelecionado();
                }, 300);

                setTimeout(function() {
                    ativarAbaClienteSelecionado();
                }, 800);

                setTimeout(function() {
                    ativarAbaClienteSelecionado();
                }, 1300);

                function ativarAbaClienteSelecionado() {
                    var form = $('#" . self::$formName . "').last();

                    if (!form.length) {
                        return;
                    }

                    var campoSelecionado = '" . $campoAbaSelecionada . "';
                    var abaAlvo = $();

                    if (campoSelecionado !== '') {
                        var campo = form.find('[name=\"' + campoSelecionado + '\"]').first();

                        if (campo.length) {
                            var pane = campo.closest('.tab-pane');

                            if (pane.length) {
                                var idPane = pane.attr('id');

                                if (idPane) {
                                    abaAlvo = form.find('.nav-tabs a[href=\"#' + idPane + '\"]').first();
                                }

                                if (!abaAlvo.length) {
                                    var index = pane.parent().children('.tab-pane').index(pane);

                                    if (index >= 0) {
                                        abaAlvo = form.find('.nav-tabs li').eq(index).find('a').first();
                                    }
                                }
                            }
                        }
                    }

                    if (!abaAlvo.length) {
                        abaAlvo = form.find('.nav-tabs li.active a').first();
                    }

                    if (!abaAlvo.length) {
                        abaAlvo = form.find('.nav-tabs li:first a').first();
                    }

                    if (!abaAlvo.length) {
                        return;
                    }

                    var href = abaAlvo.attr('href');

                    form.find('.nav-tabs li').removeClass('active');
                    form.find('.tab-content .tab-pane').removeClass('active in');

                    abaAlvo.closest('li').addClass('active');

                    if (href) {
                        form.find(href).addClass('active in');
                    }

                    try {
                        abaAlvo.tab('show');
                    } catch (e) {
                        abaAlvo.trigger('click');
                    }
                }
            ");
        }
        catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSave($param = null)
    {
        try {
            $requisicao_id = (int) ($param['key'] ?? 0);

            if (empty($requisicao_id)) {
                throw new Exception('Requisição não informada.');
            }

            TTransaction::open(self::$database);

            $conn = TTransaction::get();

            $requisicao = $this->buscarRequisicao($conn, $requisicao_id);

            if (empty($requisicao)) {
                throw new Exception('Requisição de pagamento não encontrada.');
            }

            $clientes = $this->buscarClientes($conn, $requisicao_id);

            $pessoaSelecionadaId = self::inteiroOuNull($param['pessoa_id'] ?? null);

            if (!empty($pessoaSelecionadaId)) {
                $clientes = array_values(array_filter($clientes, function ($cliente) use ($pessoaSelecionadaId) {
                    return (int) $cliente->cliente_id === (int) $pessoaSelecionadaId;
                }));
            }

            $ehMle = ((int) $requisicao->tipos_requisicao_pagamento_id === 3);

            foreach ($clientes as $cliente) {
                $rpcId = (int) $cliente->requisicao_pagamento_cliente_id;
                $clienteId = (int) $cliente->cliente_id;
                
                $campoDtNascimento = 'dt_nascimento_' . $rpcId;
                $campoStatus = 'status_' . $rpcId;
                $campoDtRequerimento = 'data_requerimento_' . $rpcId;
                $campoValor = 'valor_' . $rpcId;
                $campoDtBase = 'data_base_' . $rpcId;
                $campoEntidade = 'entidade_devedora_id_' . $rpcId;
                $campoObs = 'obs_' . $rpcId;
                $campoContaIndicadaMle = 'conta_indicada_mle_' . $rpcId;

                $dtNascimento = self::campoVeioNoPost($param, $campoDtNascimento)
                    ? self::dataParaBanco(self::valorParam($param, $campoDtNascimento))
                    : (!empty($cliente->dt_nascimento_abertura) ? substr($cliente->dt_nascimento_abertura, 0, 10) : null);

                $statusId = self::campoVeioNoPost($param, $campoStatus)
                    ? self::inteiroOuNull(self::valorParam($param, $campoStatus))
                    : self::inteiroOuNull($cliente->status_requisicao_pagamento_id);

                $dtRequerimento = self::campoVeioNoPost($param, $campoDtRequerimento)
                    ? self::dataParaBanco(self::valorParam($param, $campoDtRequerimento))
                    : (!empty($cliente->data_requerimento) ? substr($cliente->data_requerimento, 0, 10) : null);

                $valor = self::campoVeioNoPost($param, $campoValor)
                    ? self::valorDecimalParaBanco(self::valorParam($param, $campoValor))
                    : ($cliente->valor !== null ? (float) $cliente->valor : null);

                $dtBase = self::campoVeioNoPost($param, $campoDtBase)
                    ? self::dataParaBanco(self::valorParam($param, $campoDtBase))
                    : (!empty($cliente->data_base) ? substr($cliente->data_base, 0, 10) : null);

                $entidadeId = self::campoVeioNoPost($param, $campoEntidade)
                    ? self::inteiroOuNull(self::valorParam($param, $campoEntidade))
                    : self::inteiroOuNull($cliente->entidade_devedora_id);

                if (empty($entidadeId)) {
                    $entidadeId = self::inteiroOuNull($cliente->entidade_devedora_id);
                }

                if (empty($entidadeId)) {
                    throw new Exception('Entidade devedora/devedor não informada para o cliente: ' . $cliente->cliente_nome);
                }

                $obs = self::campoVeioNoPost($param, $campoObs)
                    ? self::valorParam($param, $campoObs)
                    : $cliente->obs;

                $contaIndicadaMleCliente = self::campoVeioNoPost($param, $campoContaIndicadaMle)
                    ? self::valorParam($param, $campoContaIndicadaMle)
                    : $cliente->conta_indicada_mle;

                if (!empty($clienteId)) {
                    $sqlPessoa = "
                        UPDATE pessoa
                        SET
                            dt_nascimento_abertura = ?,
                            data_modificacao = CURRENT_TIMESTAMP,
                            modificacao_user_id = ?
                        WHERE id = ?
                    ";

                    $sthPessoa = $conn->prepare($sqlPessoa);
                    $sthPessoa->execute([
                        $dtNascimento,
                        TSession::getValue('userid'),
                        $clienteId
                    ]);
                }

               $sqlCliente = "
                    UPDATE requisicao_pagamento_cliente
                    SET
                        status_requisicao_pagamento_id = ?,
                        entidade_devedora_id = ?,
                        valor = ?,
                        data_base = ?,
                        data_requerimento = ?,
                        obs = ?,
                        conta_indicada_mle = ?,
                        data_modificacao = CURRENT_TIMESTAMP,
                        modificacao_user_id = ?
                    WHERE id = ?
                ";

                $sthCliente = $conn->prepare($sqlCliente);
                $sthCliente->execute([
                    $statusId,
                    $entidadeId,
                    $valor,
                    $dtBase,
                    $dtRequerimento,
                    $obs,
                    $contaIndicadaMleCliente,
                    TSession::getValue('userid'),
                    $rpcId
                ]);

                if (!$ehMle) {
                    $this->salvarEtapa2($conn, $param, $rpcId);
                }

                $this->salvarEtapa3(
                     $conn,
                    $param,
                    $rpcId,
                    $ehMle,
                    $requisicao->processo_id
                );
            }

            TTransaction::close();

            $processoOrigemId = self::inteiroOuNull($param['processo_origem_id'] ?? null);

            if (!empty($processoOrigemId)) {
                $urlProcesso = 'engine.php?' . http_build_query([
                    'class' => 'ProcessoFormView',
                    'method' => 'onShow',
                    'key' => $processoOrigemId,
                    'target_container' => 'adianti_right_panel',
                    'register_state' => 'false'
                ]);

                $urlProcesso = addslashes($urlProcesso);

                TScript::create("
                    (function() {
                        $('.select2-container--open, .select2-dropdown').remove();

                        $('.modal-backdrop, .swal2-backdrop-show, .ui-widget-overlay').remove();

                        $('.bootbox, .modal, .swal2-container, .ui-dialog').remove();

                        $('body')
                            .removeClass('modal-open offcanvas-open right-panel-open')
                            .css('overflow', '');

                        $('#adianti_right_panel').empty();

                        setTimeout(function() {
                            Adianti.waitMessage = false;
                            __adianti_load_page('{$urlProcesso}');
                        }, 150);

                        setTimeout(function() {
                            if (typeof __adianti_show_toast === 'function') {
                                __adianti_show_toast('success', 'Requisição de pagamento salva com sucesso.', 'top right', '');
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success('Requisição de pagamento salva com sucesso.');
                            }
                        }, 450);
                    })();
                ");

                return;
            }

            $action = new TAction(['RequisicaoPagamentoVisualizacaoAba', 'onShow']);
            $action->setParameter('key', $requisicao_id);
            $action->setParameter('register_state', 'false');

            new TMessage('info', 'Requisição de pagamento salva com sucesso.', $action);
            return;

        }
        catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function salvarEtapa2($conn, $param, $rpcId)
    {
        $processoFilhoId = self::inteiroOuNull(self::valorParam($param, 'etapa2_processo_filho_id_' . $rpcId));
        $dataDeferimento = self::dataParaBanco(self::valorParam($param, 'etapa2_data_deferimento_expedicao_requisitorio_' . $rpcId));
        $protocolo = self::dataParaBanco(self::valorParam($param, 'etapa2_protocolo_depre_entidade_devedora_' . $rpcId));        $numero = self::valorParam($param, 'etapa2_numero_depre_entidade_devedora_' . $rpcId);
        $numeroOrdem = self::valorParam($param, 'etapa2_numero_ordem_' . $rpcId);

        $temDados = !empty($processoFilhoId)
            || !empty($dataDeferimento)
            || !empty($protocolo)
            || !empty($numero)
            || !empty($numeroOrdem);

        if (!$temDados) {
            return;
        }

        if (empty($processoFilhoId)) {
            throw new Exception('Informe o processo vinculado dos dados de expedição do requisitório.');
        }

        $id = $this->buscarEtapaId($conn, 'requisicao_pagamento_etapa2', $rpcId);

        if (!empty($id)) {
            $sql = "
                UPDATE requisicao_pagamento_etapa2
                SET
                    processo_filho_id = ?,
                    data_deferimento_expedicao_requisitorio = ?,
                    protocolo_depre_entidade_devedora = ?,
                    numero_depre_entidade_devedora = ?,
                    numero_ordem = ?
                WHERE id = ?
            ";

            $sth = $conn->prepare($sql);
            $sth->execute([
                $processoFilhoId,
                $dataDeferimento,
                $protocolo,
                $numero,
                $numeroOrdem,
                $id
            ]);
        } else {
            $sql = "
                INSERT INTO requisicao_pagamento_etapa2
                (
                    requisicao_pagamento_cliente_id,
                    processo_filho_id,
                    data_deferimento_expedicao_requisitorio,
                    protocolo_depre_entidade_devedora,
                    numero_depre_entidade_devedora,
                    numero_ordem
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $sth = $conn->prepare($sql);
            $sth->execute([
                $rpcId,
                $processoFilhoId,
                $dataDeferimento,
                $protocolo,
                $numero,
                $numeroOrdem
            ]);
        }
    }

    private function salvarEtapa3($conn, $param, $rpcId, $ehMle = false, $processoPrincipalId = null) 
    {
        $etapa3Id = self::inteiroOuNull(
                self::valorParam(
                    $param,
                    'etapa3_id_' . $rpcId
                )
            );

            $numeroCiclo = self::inteiroOuNull(
                self::valorParam(
                    $param,
                    'etapa3_numero_ciclo_' . $rpcId
                )
            );

            $processoFilhoId = self::inteiroOuNull(
                self::valorParam(
                    $param,
                    'etapa3_processo_filho_id_' . $rpcId
                )
            );

            $dataDeposito = null;
            $valorBruto = null;
            $valorMle = null;
            $dataPedidoMle = null;

            $contaMle = self::valorParam(
                $param,
                'conta_indicada_mle_' . $rpcId
            );

            /*
            * Requisição tipo 3:
            * os campos principais do MLE ficam na parte superior.
            */
            if ($ehMle) {
                $processoFilhoId = self::inteiroOuNull(
                    $processoPrincipalId
                );

                $dataPedidoMle = self::dataParaBanco(
                    self::valorParam(
                        $param,
                        'data_requerimento_' . $rpcId
                    )
                );

                $dataDeposito = self::dataParaBanco(
                    self::valorParam(
                        $param,
                        'data_base_' . $rpcId
                    )
                );

                $valorBruto = self::valorDecimalParaBanco(
                    self::valorParam(
                        $param,
                        'etapa3_valor_bruto_depositado_' . $rpcId
                    )
                );

                $valorMle = self::valorDecimalParaBanco(
                    self::valorParam(
                        $param,
                        'valor_' . $rpcId
                    )
                );
            } else {
                /*
                * Requisições dos tipos 1 e 2:
                * usam os campos normais da Etapa 3.
                */
                $dataDeposito = self::dataParaBanco(
                    self::valorParam(
                        $param,
                        'etapa3_data_deposito_' . $rpcId
                    )
                );

                $valorBruto = self::valorDecimalParaBanco(
                    self::valorParam(
                        $param,
                        'etapa3_valor_bruto_depositado_' . $rpcId
                    )
                );

                $valorMle = self::valorDecimalParaBanco(
                    self::valorParam(
                        $param,
                        'etapa3_valor_mle_' . $rpcId
                    )
                );

                $dataPedidoMle = self::dataParaBanco(
                    self::valorParam(
                        $param,
                        'etapa3_data_pedido_mle_' . $rpcId
                    )
                );
            }

            $dataDeferimentoMle = self::dataParaBanco(
                self::valorParam(
                    $param,
                    'etapa3_data_deferimento_mle_' . $rpcId
                )
            );

            $possuiSaldo = strtoupper(trim(
                (string) self::valorParam(
                    $param,
                    'etapa3_possui_saldo_' . $rpcId
                )
            ));

            $saldoBruto = self::valorDecimalParaBanco(
                self::valorParam(
                    $param,
                    'etapa3_saldo_bruto_' . $rpcId
                )
            );

            $dataBaseSaldo = self::dataParaBanco(
                self::valorParam(
                    $param,
                    'etapa3_data_base_saldo_' . $rpcId
                )
            );

            /*
            * O processo vinculado não entra nesta validação,
            * porque ele pode vir preenchido automaticamente.
            *
            * Valor 0,00 também não conta como preenchimento.
            */
            if ($ehMle) {
                $temDadosEtapa3 = !empty($etapa3Id)
                    || !empty($dataDeposito)
                    || !empty($dataDeferimentoMle)
                    || ($valorBruto !== null && $valorBruto > 0);
            } else {
                $temDadosEtapa3 = !empty($etapa3Id)
                    || !empty($dataDeposito)
                    || !empty($dataPedidoMle)
                    || !empty($dataDeferimentoMle)
                    || ($valorBruto !== null && $valorBruto > 0)
                    || ($valorMle !== null && $valorMle > 0);
            }

            /*
            * Se somente o processo automático e valores 0,00
            * estiverem preenchidos, não cria a Etapa 3.
            *
            * Observação, status e outros dados continuam sendo
            * salvos normalmente pelo onSave.
            */
            if (!$temDadosEtapa3) {
                return;
            }

            /*
            * A Etapa 3 pode ser salva antes do depósito.
            *
            * Sem depósito:
            * - salva pedido/deferimento;
            * - não gera pagamento;
            * - não possui saldo;
            * - não entra no histórico de lançamentos.
            */
            $temDeposito = !empty($dataDeposito);

            if (!$temDeposito) {
                $possuiSaldo = 'N';
                $saldoBruto = null;
                $dataBaseSaldo = null;
            } else {
                if ($possuiSaldo !== 'S') {
                    $possuiSaldo = 'N';
                    $saldoBruto = null;
                    $dataBaseSaldo = null;
                }

                if ($possuiSaldo === 'S') {
                    if ($saldoBruto === null || $saldoBruto <= 0) {
                        throw new Exception(
                            'Quando sobrar saldo, informe o Saldo bruto remanescente.'
                        );
                    }

                    if (empty($dataBaseSaldo)) {
                        throw new Exception(
                            'Quando sobrar saldo, informe a Data base do saldo remanescente.'
                        );
                    }
                }
            }

            /*
            * Se começou a preencher a Etapa 3,
            * o processo precisa estar identificado.
            */
            if (empty($processoFilhoId)) {
                if ($ehMle) {
                    throw new Exception(
                        'Não foi possível identificar o processo principal da requisição MLE.'
                    );
                }

                throw new Exception(
                    'Informe o processo vinculado do pagamento/MLE.'
                );
            }

            if (empty($numeroCiclo)) {
                $numeroCiclo = $this->buscarProximoCicloEtapa3(
                    $conn,
                    $rpcId
                );
            }

            /*
            * Se já existe uma preparação ou lançamento,
            * atualiza a mesma linha.
            */
            if (!empty($etapa3Id)) {
                $sql = "
                    UPDATE requisicao_pagamento_etapa3
                    SET
                        processo_filho_id = ?,
                        numero_ciclo = ?,
                        data_deposito = ?,
                        valor_bruto_depositado = ?,
                        valor_mle = ?,
                        conta_indicada_mle = ?,
                        data_pedido_mle = ?,
                        data_deferimento_mle = ?,
                        saldo_bruto = ?,
                        data_base_saldo = ?,
                        possui_saldo = ?
                    WHERE id = ?
                    AND requisicao_pagamento_cliente_id = ?
                ";

                $sth = $conn->prepare($sql);

                $sth->execute([
                    $processoFilhoId,
                    $numeroCiclo,
                    $dataDeposito,
                    $valorBruto,
                    $valorMle,
                    $contaMle,
                    $dataPedidoMle,
                    $dataDeferimentoMle,
                    $saldoBruto,
                    $dataBaseSaldo,
                    $possuiSaldo,
                    $etapa3Id,
                    $rpcId
                ]);

                return;
            }

            /*
            * Cria uma nova linha da Etapa 3.
            *
            * Ela pode ser:
            * - preparação, quando não tem depósito;
            * - lançamento, quando tem depósito.
            */
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
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $sth = $conn->prepare($sql);

            $sth->execute([
                $rpcId,
                $processoFilhoId,
                $numeroCiclo,
                $dataDeposito,
                $valorBruto,
                $valorMle,
                $contaMle,
                $dataPedidoMle,
                $dataDeferimentoMle,
                $saldoBruto,
                $dataBaseSaldo,
                $possuiSaldo
            ]);
        }
    private function buscarEtapaId($conn, $tabela, $rpcId)
    {
        $sql = "
            SELECT id
            FROM {$tabela}
            WHERE requisicao_pagamento_cliente_id = ?
            ORDER BY id DESC
            LIMIT 1
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([(int) $rpcId]);

        return $sth->fetchColumn();
    }

    private function buscarProximoCicloEtapa3($conn, $rpcId)
    {
        $sql = "
            SELECT COALESCE(MAX(numero_ciclo), 0) + 1
            FROM requisicao_pagamento_etapa3
            WHERE requisicao_pagamento_cliente_id = ?
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([(int) $rpcId]);

        return (int) $sth->fetchColumn();
    }

    private function buscarRequisicao($conn, $requisicao_id)
    {
        $sql = "
            SELECT
                rp.id AS requisicao_pagamento_id,
                rp.processo_id,
                rp.tipos_requisicao_pagamento_id,
                COALESCE(p.numero_cnj_numero, p.numero_outro) AS numero_processo,
                trp.nome AS tipo_requisicao,
                rp.data_criacao
            FROM requisicao_pagamento rp
            LEFT JOIN processo p
                ON p.id = rp.processo_id
            LEFT JOIN tipos_requisicao_pagamento trp
                ON trp.id = rp.tipos_requisicao_pagamento_id
            WHERE rp.id = ?
            LIMIT 1
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([(int) $requisicao_id]);

        return $sth->fetch(PDO::FETCH_OBJ);
    }

    private function buscarClientes($conn, $requisicao_id)
    {
        $sql = "
            SELECT
                rpc.id AS requisicao_pagamento_cliente_id,
                rpc.status_requisicao_pagamento_id,
                rpc.valor,
                rpc.data_base,
                rpc.data_requerimento,
                rpc.obs,
                rpc.conta_indicada_mle,

                srp.nome AS status_cliente,
                srp.cor AS status_cor,

                cliente.id AS cliente_id,
                cliente.nome AS cliente_nome,
                cliente.cpf_cnpj,
                cliente.dt_nascimento_abertura,

                entidade.id AS entidade_devedora_id,
                entidade.nome AS entidade_devedora_nome,

                e2.id AS etapa2_id,
                e2.processo_filho_id AS etapa2_processo_filho_id,
                e2.data_deferimento_expedicao_requisitorio AS etapa2_data_deferimento_expedicao_requisitorio,
                e2.protocolo_depre_entidade_devedora AS etapa2_protocolo_depre_entidade_devedora,
                e2.numero_depre_entidade_devedora AS etapa2_numero_depre_entidade_devedora,
                e2.numero_ordem AS etapa2_numero_ordem,

                e3.id AS etapa3_id,
                e3.processo_filho_id AS etapa3_processo_filho_id,
                e3.data_deposito AS etapa3_data_deposito,
                e3.valor_bruto_depositado AS etapa3_valor_bruto_depositado,
                e3.valor_mle AS etapa3_valor_mle,
                e3.conta_indicada_mle AS etapa3_conta_indicada_mle,
                e3.data_pedido_mle AS etapa3_data_pedido_mle,
                e3.data_deferimento_mle AS etapa3_data_deferimento_mle,
                e3.numero_ciclo AS etapa3_numero_ciclo,
                e3.saldo_bruto AS etapa3_saldo_bruto,
                e3.data_base_saldo AS etapa3_data_base_saldo,
                e3.possui_saldo AS etapa3_possui_saldo

            FROM requisicao_pagamento_cliente rpc

            LEFT JOIN status_requisicao_pagamento srp
                ON srp.id = rpc.status_requisicao_pagamento_id

            LEFT JOIN pessoa cliente
                ON cliente.id = rpc.pessoa_id

            LEFT JOIN pessoa entidade
                ON entidade.id = rpc.entidade_devedora_id

            LEFT JOIN LATERAL (
                SELECT *
                FROM requisicao_pagamento_etapa2
                WHERE requisicao_pagamento_cliente_id = rpc.id
                ORDER BY id DESC
                LIMIT 1
            ) e2 ON true

            LEFT JOIN LATERAL (
                SELECT *
                FROM requisicao_pagamento_etapa3
                WHERE requisicao_pagamento_cliente_id = rpc.id
                ORDER BY COALESCE(numero_ciclo, 1) DESC, id DESC
                LIMIT 1
            ) e3 ON true

            WHERE rpc.requisicao_pagamento_id = ?
            ORDER BY cliente.nome
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([(int) $requisicao_id]);

        return $sth->fetchAll(PDO::FETCH_OBJ);
    }

    private function buscarProcessosVinculadosPorCliente($conn, $requisicao_id, $processoPrincipalId)
    {
        $map = [];

        $sql = "
            SELECT
                dados.requisicao_pagamento_cliente_id,
                dados.processo_id,
                dados.numero_processo,
                MIN(dados.prioridade) AS prioridade
            FROM (
                SELECT
                    rpc.id AS requisicao_pagamento_cliente_id,
                    p.id AS processo_id,
                    COALESCE(p.numero_cnj_numero, p.numero_outro, p.id::text) AS numero_processo,
                    CASE
                        WHEN p.id = ? THEN 3
                        ELSE 1
                    END AS prioridade
                FROM requisicao_pagamento_cliente rpc
                JOIN contrato_pessoa cpessoa
                    ON cpessoa.cliente_id = rpc.pessoa_id
                JOIN contrato_processo cprocesso
                    ON cprocesso.contrato_id = cpessoa.contrato_id
                JOIN processo p
                    ON p.id = cprocesso.processo_id
                WHERE rpc.requisicao_pagamento_id = ?

                UNION ALL

                SELECT
                    rpc.id AS requisicao_pagamento_cliente_id,
                    p.id AS processo_id,
                    COALESCE(p.numero_cnj_numero, p.numero_outro, p.id::text) AS numero_processo,
                    2 AS prioridade
                FROM requisicao_pagamento_cliente rpc
                JOIN processo_vinculo pv
                    ON pv.processo_principal_id = ?
                    OR pv.processo_incidente_id = ?
                JOIN processo p
                    ON p.id = CASE
                        WHEN pv.processo_principal_id = ? THEN pv.processo_incidente_id
                        ELSE pv.processo_principal_id
                    END
                WHERE rpc.requisicao_pagamento_id = ?

                UNION ALL

                SELECT
                    rpc.id AS requisicao_pagamento_cliente_id,
                    p.id AS processo_id,
                    COALESCE(p.numero_cnj_numero, p.numero_outro, p.id::text) AS numero_processo,
                    4 AS prioridade
                FROM requisicao_pagamento_cliente rpc
                JOIN processo p
                    ON p.id = ?
                WHERE rpc.requisicao_pagamento_id = ?
            ) dados
            GROUP BY
                dados.requisicao_pagamento_cliente_id,
                dados.processo_id,
                dados.numero_processo
            ORDER BY
                dados.requisicao_pagamento_cliente_id,
                MIN(dados.prioridade),
                dados.numero_processo
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([
            (int) $processoPrincipalId,
            (int) $requisicao_id,

            (int) $processoPrincipalId,
            (int) $processoPrincipalId,
            (int) $processoPrincipalId,
            (int) $requisicao_id,

            (int) $processoPrincipalId,
            (int) $requisicao_id
        ]);

        $rows = $sth->fetchAll(PDO::FETCH_OBJ);

        foreach ($rows as $row) {
            $rpcId = (int) $row->requisicao_pagamento_cliente_id;

            if (!isset($map[$rpcId])) {
                $map[$rpcId] = [
                    'itens' => [],
                    'padrao' => null
                ];
            }

            $map[$rpcId]['itens'][(int) $row->processo_id] = $row->numero_processo;

            if (empty($map[$rpcId]['padrao']) && (int) $row->processo_id !== (int) $processoPrincipalId) {
                $map[$rpcId]['padrao'] = (int) $row->processo_id;
            }
        }

        foreach ($map as $rpcId => $dados) {
            if (empty($map[$rpcId]['padrao']) && !empty($dados['itens'])) {
                $primeirosIds = array_keys($dados['itens']);
                $map[$rpcId]['padrao'] = (int) $primeirosIds[0];
            }
        }

        return $map;
    }
    private function buscarIdsProcessosVinculadosEmCadeia($conn, $processoPrincipalId, $incluirPrincipal = false)
    {
        $processoPrincipalId = (int) $processoPrincipalId;

        if (empty($processoPrincipalId)) {
            return [];
        }

        $fila = [$processoPrincipalId];

        $visitados = [];
        $enfileirados = [
            $processoPrincipalId => true
        ];

        $idsEncontrados = [];

        if ($incluirPrincipal) {
            $idsEncontrados[$processoPrincipalId] = $processoPrincipalId;
        }

        while (!empty($fila)) {
            $processoAtualId = (int) array_shift($fila);

            if (isset($visitados[$processoAtualId])) {
                continue;
            }

            $visitados[$processoAtualId] = true;

            $sql = "
                SELECT DISTINCT
                    CASE
                        WHEN pv.processo_principal_id = ? THEN pv.processo_incidente_id
                        ELSE pv.processo_principal_id
                    END AS processo_id
                FROM processo_vinculo pv
                WHERE pv.processo_principal_id = ?
                OR pv.processo_incidente_id = ?
            ";

            $sth = $conn->prepare($sql);
            $sth->execute([
                $processoAtualId,
                $processoAtualId,
                $processoAtualId
            ]);

            $rows = $sth->fetchAll(PDO::FETCH_OBJ);

            foreach ($rows as $row) {
                $idVinculado = (int) $row->processo_id;

                if (empty($idVinculado)) {
                    continue;
                }

                if ($idVinculado !== $processoPrincipalId || $incluirPrincipal) {
                    $idsEncontrados[$idVinculado] = $idVinculado;
                }

                if (!isset($visitados[$idVinculado]) && !isset($enfileirados[$idVinculado])) {
                    $fila[] = $idVinculado;
                    $enfileirados[$idVinculado] = true;
                }
            }
        }

        return array_values($idsEncontrados);
    }

    private function buscarProcessosVinculadosDoPrincipal($conn, $processoPrincipalId)
    {
        $itens = [];

        $processoPrincipalId = (int) $processoPrincipalId;

        if (empty($processoPrincipalId)) {
            return $itens;
        }

        $ids = $this->buscarIdsProcessosVinculadosEmCadeia(
            $conn,
            $processoPrincipalId,
            true
        );

        if (empty($ids)) {
            $ids = [$processoPrincipalId];
        }

        $ids = array_map('intval', $ids);
        $ids[] = $processoPrincipalId;
        $ids = array_values(array_unique($ids));

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT
                p.id,
                COALESCE(p.numero_cnj_numero, p.numero_outro, p.id::text) AS numero_processo
            FROM processo p
            WHERE p.id IN ({$placeholders})
            ORDER BY
                CASE
                    WHEN p.id = ? THEN 0
                    ELSE 1
                END,
                COALESCE(p.numero_cnj_numero, p.numero_outro, p.id::text)
        ";

        $params = array_merge(
            $ids,
            [$processoPrincipalId]
        );

        $sth = $conn->prepare($sql);
        $sth->execute($params);

        $rows = $sth->fetchAll(PDO::FETCH_OBJ);

        foreach ($rows as $row) {
            $itens[(string) $row->id] = $row->numero_processo;
        }

        return $itens;
    }

    private function buscarPagamentosEtapa3PorCliente($conn, $requisicao_id)
    {
        $sql = "
            SELECT
                rpc.id AS requisicao_pagamento_cliente_id,
                e3.id,
                e3.processo_filho_id,

                COALESCE(
                    p.numero_cnj_numero,
                    p.numero_outro,
                    p.id::text
                ) AS processo_filho_numero,

                e3.data_deposito,
                e3.valor_bruto_depositado,
                e3.valor_mle,
                e3.conta_indicada_mle,
                e3.data_pedido_mle,
                e3.data_deferimento_mle,
                e3.numero_ciclo,
                e3.saldo_bruto,
                e3.data_base_saldo,
                e3.possui_saldo

            FROM requisicao_pagamento_cliente rpc

            JOIN requisicao_pagamento_etapa3 e3
                ON e3.requisicao_pagamento_cliente_id = rpc.id

            LEFT JOIN processo p
                ON p.id = e3.processo_filho_id

            WHERE rpc.requisicao_pagamento_id = ?

            /*
            * Somente registros com depósito são
            * considerados pagamentos lançados.
            */
            AND e3.data_deposito IS NOT NULL

            ORDER BY
                rpc.id,
                COALESCE(e3.numero_ciclo, 1),
                e3.id
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([
            (int) $requisicao_id
        ]);

        $rows = $sth->fetchAll(PDO::FETCH_OBJ);

        $map = [];

        foreach ($rows as $row) {
            $rpcId = (int) $row->requisicao_pagamento_cliente_id;

            if (!isset($map[$rpcId])) {
                $map[$rpcId] = [];
            }

            $map[$rpcId][] = $row;
        }

        return $map;
    }

    private function criarBotaoVoltarListagemRequisicoes($processoId)
    {
        $processoId = (int) $processoId;

        if (empty($processoId)) {
            return '';
        }

        $targetParam = '';

        if (!empty($this->adianti_target_container)) {
            $targetParam = '&target_container=' . urlencode($this->adianti_target_container);
        }

        $url = "engine.php?class=RequisicaoPagamentoVisualizacaoAba"
            . "&method=onShow"
            . "&processo_id={$processoId}"
            . "{$targetParam}"
            . "&register_state=false";

        $url = addslashes($url);

        return "
            <div class='req-back-list-wrap'>
                <a href='javascript:void(0)'
                class='req-back-list-btn'
                onclick=\"
                        Adianti.waitMessage = false;
                        __adianti_load_page('{$url}');
                        return false;
                \">
                    <i class='fa fa-arrow-left'></i>
                    <span>Voltar para requisições</span>
                </a>
            </div>
        ";
    }

    private static function criarBotaoVerProcesso($processoId)
    {
        $processoId = (int) $processoId;

        if (empty($processoId)) {
            return '';
        }

        $url = "engine.php?class=ProcessoFormView&method=onShow&key={$processoId}&target_container=adianti_right_panel&register_state=false";

        return "
            <a href='{$url}'
            class='req-process-view-btn'
            title='Ver processo'>
                <i class='fa fa-folder-open'></i>
                <span>Ver processo</span>
            </a>
        ";
    }
    private static function criarLinhaSeparadora()
    {
        $div = new TElement('div');
        $div->setProperty('class', 'req-form-line');

        return $div;
    }

    private static function criarAvisoSaldoAberto($cliente)
    {
        $div = new TElement('div');
        $div->setProperty('class', 'req-open-balance');

        $saldo = self::formatarValorBR($cliente->etapa3_saldo_bruto);
        $dataBase = self::formatarDataBR($cliente->etapa3_data_base_saldo);

        $div->add("
            <div class='req-open-balance-title'>Existe saldo remanescente em aberto</div>
            <div class='req-open-balance-text'>
                Saldo bruto: <strong>{$saldo}</strong>
                <span>Data base do saldo: <strong>{$dataBase}</strong></span>
            </div>
            <div class='req-open-balance-help'>
                Preencha abaixo o próximo pagamento recebido. Se ainda sobrar saldo depois deste novo pagamento, marque novamente como sim.
            </div>
        ");

        return $div;
    }

    private static function criarHistoricoPagamentos($pagamentos)
    {
        $html = "
            <details class='req-payment-history'>
                <summary>Ver pagamentos lançados</summary>
                <div class='req-payment-history-body'>
                    <table>
                        <thead>
                            <tr>
                                <th>Pagamento</th>
                                <th>Processo</th>
                                <th>Data do depósito</th>
                                <th>Valor bruto</th>
                                <th>Valor MLE</th>
                                <th>Saldo remanescente</th>
                                <th>Data base do saldo</th>
                                <th>Situação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
        ";

        $contador = 1;

        foreach ($pagamentos as $pagamento) {
            $possuiSaldo = strtoupper(trim($pagamento->possui_saldo ?? 'N')) === 'S';
          
                $url = "engine.php?class=RequisicaoPagamentoEtapa3Visualizacao&method=onShow&key=" . (int) $pagamento->id . "&register_state=false";

                $url = addslashes($url);

                $botaoVisualizar = "
                    <a href='javascript:void(0)'
                    class='req-action-btn'
                    title='Visualizar lançamento'
                    onclick=\"
                        Adianti.waitMessage = false;
                        __adianti_load_page('{$url}');
                        return false;
                    \">
                        <i class='fa fa-search'></i>
                    </a>
                ";
            $html .= "
                <tr>
                    <td>" . self::h($contador . 'º lançamento') . "</td>
                    <td>" . self::h($pagamento->processo_filho_numero) . "</td>
                    <td>" . self::h(self::formatarDataBR($pagamento->data_deposito)) . "</td>
                    <td>" . self::h(self::formatarValorBR($pagamento->valor_bruto_depositado)) . "</td>
                    <td>" . self::h(self::formatarValorBR($pagamento->valor_mle)) . "</td>
                    <td>" . self::h(self::formatarValorBR($pagamento->saldo_bruto)) . "</td>
                    <td>" . self::h(self::formatarDataBR($pagamento->data_base_saldo)) . "</td>
                    <td>" . ($possuiSaldo
                        ? "<span class='req-badge req-badge-open'>Saldo em aberto</span>"
                        : "<span class='req-badge req-badge-ok'>Sem saldo</span>") . "</td>
                    <td class='req-actions-cell'>{$botaoVisualizar}</td>
                </tr>
            ";

            $contador++;
        }

        $html .= "
                        </tbody>
                    </table>
                </div>
            </details>
        ";

        $div = new TElement('div');
        $div->add($html);

        return $div;
    }

    private function criarListaRequisicoesProcesso($requisicoes, $processoId, $processo = null)
    {
        $numeroProcesso = !empty($processo->numero_processo)
            ? $processo->numero_processo
            : $processoId;

        $targetParam = '';

        if (!empty($this->adianti_target_container)) {
            $targetParam = '&target_container=' . urlencode($this->adianti_target_container);
        }

        $html = "
            <div class='req-choice-panel'>
                <div class='req-choice-header'>
                    <div class='req-choice-icon'>
                        <i class='fa fa-file-invoice-dollar'></i>
                    </div>

                    <div>
                        <div class='req-choice-title'>Escolha a requisição de pagamento</div>
                        <div class='req-choice-subtitle'>
                            O processo <strong>" . self::h($numeroProcesso) . "</strong> possui mais de uma requisição vinculada.
                        </div>
                    </div>
                </div>

                <div class='req-choice-list'>
        ";

        foreach ($requisicoes as $req) {
            $reqId = (int) $req->requisicao_pagamento_id;
            $pessoaId = (int) $req->pessoa_id;

            $clienteNome = !empty($req->cliente_nome)
                ? $req->cliente_nome
                : 'Cliente não informado';

            $tipo = !empty($req->tipo_requisicao)
                ? $req->tipo_requisicao
                : 'Tipo não informado';

            $status = !empty($req->status_cliente)
                ? $req->status_cliente
                : 'Sem status';

            $valor = self::formatarValorBR($req->valor);
            $dataCriacao = self::formatarDataHoraBR($req->data_criacao);

            $cor = trim((string) ($req->status_cor ?? ''));

            if ($cor !== '' && $cor[0] !== '#') {
                $cor = '#' . $cor;
            }

            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
                $cor = '#64748b';
            }

           $url = "engine.php?class=RequisicaoPagamentoVisualizacaoAba"
            . "&method=onShow"
            . "&key={$reqId}"
            . "&pessoa_id={$pessoaId}"
            . "&processo_origem_id=" . (int) $processoId
            . "&exibir_voltar=1"
            . "{$targetParam}"
            . "&register_state=false";

            $url = addslashes($url);

            $html .= "
                <a href='javascript:void(0)'
                class='req-choice-card'
                style='border-left-color: {$cor};'
                onclick=\"
                        Adianti.waitMessage = false;
                        __adianti_load_page('{$url}');
                        return false;
                \">

                    <div class='req-choice-card-top'>
                    <div>
                        <div class='req-choice-number'>" . self::h($clienteNome) . "</div>
                    </div>

                        <span class='req-choice-status'>
                            <span class='req-choice-dot' style='background: {$cor};'></span>
                            " . self::h($status) . "
                        </span>
                    </div>

                    <div class='req-choice-grid'>
                        <div>
                            <strong>Processo da requisição</strong>
                            <span>" . self::h($req->numero_processo_requisicao) . "</span>
                        </div>

                        <div>
                            <strong>Tipo</strong>
                            <span>" . self::h($tipo) . "</span>
                        </div>

                        <div>
                            <strong>Valor</strong>
                            <span>" . self::h($valor) . "</span>
                        </div>

                        <div>
                            <strong>Data de criação</strong>
                            <span>" . self::h($dataCriacao) . "</span>
                        </div>

                    </div>

                    <div class='req-choice-open'>
                        Abrir requisição
                        <i class='fa fa-arrow-right'></i>
                    </div>
                </a>
            ";
        }

        $html .= "
                </div>
            </div>
        ";

        $div = new TElement('div');
        $div->add($html);

        return $div;
    }

    private function criarScriptSaldoParcial($rpcId)
    {
        TScript::create("
            setTimeout(function() {
                function getColunaDoCampo(nomeCampo) {
                    var campo = $('[name=\"' + nomeCampo + '\"]');

                    if (!campo.length) {
                        return $();
                    }

                    var coluna = campo.closest('[class*=\"col-sm-\"], [class*=\"col-md-\"], [class*=\"col-lg-\"], [class*=\"col-xs-\"]');

                    if (!coluna.length) {
                        coluna = campo.closest('td');
                    }

                    return coluna;
                }

                function toggleSaldoParcial{$rpcId}() {
                    var valor = $('[name=\"etapa3_possui_saldo_{$rpcId}\"]').val();
                    var mostrar = valor === 'S';

                    var campos = [
                        'etapa3_saldo_bruto_{$rpcId}',
                        'etapa3_data_base_saldo_{$rpcId}'
                    ];

                    campos.forEach(function(nomeCampo) {
                        var campo = $('[name=\"' + nomeCampo + '\"]');
                        var coluna = getColunaDoCampo(nomeCampo);

                        if (!coluna.length) {
                            return;
                        }

                        if (mostrar) {
                            coluna.show();
                        } else {
                            coluna.hide();
                            campo.val('');
                        }
                    });

                    var ajuda = $('#req_partial_help_{$rpcId}');

                    if (mostrar) {
                        ajuda.show();
                    } else {
                        ajuda.hide();
                    }
                }

                $(document).off('change.saldo{$rpcId}', '[name=\"etapa3_possui_saldo_{$rpcId}\"]');
                $(document).on('change.saldo{$rpcId}', '[name=\"etapa3_possui_saldo_{$rpcId}\"]', toggleSaldoParcial{$rpcId});

                toggleSaldoParcial{$rpcId}();
            }, 300);
        ");
    }

    private static function valorParam($param, $campo)
    {
        if (!isset($param[$campo])) {
            return null;
        }

        if (is_array($param[$campo])) {
            return null;
        }

        $valor = trim((string) $param[$campo]);

        return $valor === '' ? null : $valor;
    }

    private static function campoVeioNoPost($param, $campo)
    {
        return is_array($param)
            && array_key_exists($campo, $param)
            && !is_array($param[$campo]);
    }

    private static function inteiroOuNull($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
    }

    private static function somenteNumeros($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return preg_replace('/\D/', '', $valor);
    }

    private static function valorDecimalParaBanco($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

        return (float) $valor;
    }

    private static function dataParaBanco($data)
    {
        if (empty($data)) {
            return null;
        }

        $data = trim($data);

        if (strpos($data, '/') !== false) {
            $partes = explode('/', $data);

            if (count($partes) == 3) {
                return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
            }
        }

        if (strpos($data, '-') !== false) {
            return substr($data, 0, 10);
        }

        return null;
    }

    private static function formatarCpfCnpj($valor)
    {
        if (empty($valor)) {
            return '';
        }

        $numero = preg_replace('/\D/', '', $valor);

        if (strlen($numero) == 11) {
            return substr($numero, 0, 3) . '.' .
                substr($numero, 3, 3) . '.' .
                substr($numero, 6, 3) . '-' .
                substr($numero, 9, 2);
        }

        if (strlen($numero) == 14) {
            return substr($numero, 0, 2) . '.' .
                substr($numero, 2, 3) . '.' .
                substr($numero, 5, 3) . '/' .
                substr($numero, 8, 4) . '-' .
                substr($numero, 12, 2);
        }

        return $valor;
    }

    private static function formatarValorBR($valor)
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }

    private static function formatarDataBR($data)
    {
        if (empty($data)) {
            return '';
        }

        $data = substr($data, 0, 10);

        if (strpos($data, '-') !== false) {
            $partes = explode('-', $data);

            if (count($partes) == 3) {
                return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
            }
        }

        return $data;
    }

    private static function formatarDataHoraBR($data)
    {
        if (empty($data)) {
            return '';
        }

        $data = str_replace('T', ' ', $data);

        $partes = explode(' ', $data);

        $dataFormatada = self::formatarDataBR($partes[0] ?? '');

        if (!empty($partes[1])) {
            return $dataFormatada . ' ' . substr($partes[1], 0, 5);
        }

        return $dataFormatada;
    }

    private static function criarAjudaPagamentoParcial($rpcId)
    {
        $div = new TElement('div');
        $div->setProperty('id', 'req_partial_help_' . $rpcId);
        $div->setProperty('class', 'req-partial-help');

        $div->add("
            <strong>Pagamento parcial:</strong>
            preencha o saldo remanescente somente se ainda existir valor pendente após este pagamento.
        ");

        return $div;
    }

    private static function valorComboProcesso($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (string) $valor;
    }


    private function buscarProcessoPadraoPorClientePelaView($conn, $requisicao_id, $processoPrincipalId)
    {
        $map = [];

        if (empty($requisicao_id) || empty($processoPrincipalId)) {
            return $map;
        }

        $ids = $this->buscarIdsProcessosVinculadosEmCadeia(
            $conn,
            $processoPrincipalId,
            false
        );

        if (empty($ids)) {
            return $map;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT DISTINCT ON (rpc.id)
                rpc.id AS requisicao_pagamento_cliente_id,
                pvw.id AS processo_id
            FROM requisicao_pagamento_cliente rpc

            JOIN processo_view pvw
                ON pvw.pessoa_id = rpc.pessoa_id

            JOIN processo p
                ON p.id = pvw.id

            WHERE rpc.requisicao_pagamento_id = ?
            AND pvw.id IN ({$placeholders})

            ORDER BY
                rpc.id,
                COALESCE(p.numero_cnj_numero, p.numero_outro, p.id::text)
        ";

        $params = array_merge(
            [(int) $requisicao_id],
            $ids
        );

        $sth = $conn->prepare($sql);
        $sth->execute($params);

        $rows = $sth->fetchAll(PDO::FETCH_OBJ);

        foreach ($rows as $row) {
            $map[(int) $row->requisicao_pagamento_cliente_id] = (string) $row->processo_id;
        }

        return $map;
    }

    private static function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function buscarCoresStatus($conn)
    {
        $sql = "
            SELECT
                id,
                cor
            FROM status_requisicao_pagamento
            WHERE cor IS NOT NULL
            AND TRIM(cor) <> ''
        ";

        $sth = $conn->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(PDO::FETCH_OBJ);

        $map = [];

        foreach ($rows as $row) {
            $cor = trim((string) $row->cor);

            if ($cor === '') {
                continue;
            }

            if ($cor[0] !== '#') {
                $cor = '#' . $cor;
            }

            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
                continue;
            }

            $map[(string) $row->id] = $cor;
        }

        return $map;
    }

    private function criarScriptStatusColorido($coresStatus)
    {
        $jsonCores = json_encode($coresStatus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        TScript::create("
            setTimeout(function() {
                var form = $('#" . self::$formName . "').last();
                var coresStatus = {$jsonCores};

                if (!form.length) {
                    return;
                }

                if (!$('#req_status_select2_style').length) {
                    $('head').append(
                        '<style id=\"req_status_select2_style\">' +

                        '.req-status-dropdown {' +
                            'border: 1px solid #d7dde8 !important;' +
                            'border-radius: 8px !important;' +
                            'overflow: hidden !important;' +
                            'box-shadow: 0 8px 22px rgba(15, 23, 42, 0.12) !important;' +
                        '}' +

                        '.req-status-dropdown .select2-search--dropdown {' +
                            'padding: 7px 8px 5px 8px !important;' +
                            'background: #ffffff !important;' +
                        '}' +

                        '.req-status-dropdown .select2-search__field {' +
                            'height: 28px !important;' +
                            'min-height: 28px !important;' +
                            'border: 1px solid #94a3b8 !important;' +
                            'border-radius: 5px !important;' +
                            'font-size: 11px !important;' +
                            'box-shadow: none !important;' +
                            'outline: none !important;' +
                        '}' +

                        '.req-status-dropdown .select2-results__options {' +
                            'padding: 4px !important;' +
                            'max-height: 260px !important;' +
                            'background: #ffffff !important;' +
                        '}' +

                        '.req-status-dropdown .select2-results__option {' +
                            'border-radius: 6px !important;' +
                            'margin: 2px 0 !important;' +
                            'padding: 6px 8px !important;' +
                            'background: #ffffff !important;' +
                            'color: #111827 !important;' +
                            'font-size: 11px !important;' +
                            'line-height: 1.25 !important;' +
                        '}' +

                        '.req-status-dropdown .select2-results__option--highlighted,' +
                        '.req-status-dropdown .select2-results__option--highlighted[aria-selected],' +
                        '.req-status-dropdown .select2-results__option[aria-selected=\"true\"] {' +
                            'background: #f1f5f9 !important;' +
                            'color: #111827 !important;' +
                        '}' +

                        '.req-status-dropdown .select2-results__option--highlighted {' +
                            'box-shadow: inset 0 0 0 1px #dbe3ef !important;' +
                        '}' +

                        '.req-status-option {' +
                            'display: flex !important;' +
                            'align-items: center !important;' +
                            'gap: 7px !important;' +
                            'min-height: 20px !important;' +
                        '}' +

                        '.req-status-dot {' +
                            'width: 10px !important;' +
                            'height: 10px !important;' +
                            'border-radius: 3px !important;' +
                            'display: inline-block !important;' +
                            'flex: 0 0 auto !important;' +
                        '}' +

                        '.req-status-name {' +
                            'font-weight: 800 !important;' +
                            'line-height: 1.2 !important;' +
                        '}' +

                        '</style>'
                    );
                }

                function normalizarCor(cor) {
                    if (!cor) {
                        return null;
                    }

                    cor = String(cor).trim();

                    if (cor.charAt(0) !== '#') {
                        cor = '#' + cor;
                    }

                    if (!/^#[0-9A-Fa-f]{6}$/.test(cor)) {
                        return null;
                    }

                    return cor;
                }

                function corTextoParaFundo(hex) {
                    hex = normalizarCor(hex);

                    if (!hex) {
                        return '#111827';
                    }

                    var r = parseInt(hex.substr(1, 2), 16);
                    var g = parseInt(hex.substr(3, 2), 16);
                    var b = parseInt(hex.substr(5, 2), 16);

                    var brilho = ((r * 299) + (g * 587) + (b * 114)) / 1000;

                    return brilho > 165 ? '#111827' : '#ffffff';
                }

                function aplicarImportant(elemento, propriedade, valor) {
                    if (!elemento || !elemento.length) {
                        return;
                    }

                    elemento.each(function() {
                        this.style.setProperty(propriedade, valor, 'important');
                    });
                }

                function limparImportant(elemento, propriedades) {
                    if (!elemento || !elemento.length) {
                        return;
                    }

                    elemento.each(function() {
                        var el = this;

                        propriedades.forEach(function(prop) {
                            el.style.removeProperty(prop);
                        });
                    });
                }

                function escaparHtml(texto) {
                    return $('<div/>').text(texto || '').html();
                }

                function buscarAbaDoCampo(campo) {
                    var pane = campo.closest('.tab-pane');

                    if (!pane.length) {
                        return $();
                    }

                    var idPane = pane.attr('id');

                    if (idPane) {
                        var aba = form.find('.nav-tabs a[href=\"#' + idPane + '\"]').first();

                        if (aba.length) {
                            return aba;
                        }
                    }

                    var index = pane.parent().children('.tab-pane').index(pane);

                    if (index >= 0) {
                        return form.find('.nav-tabs li').eq(index).find('a').first();
                    }

                    return $();
                }

                function aplicarCoresOptionsNativos(campo) {
                    campo.find('option').each(function() {
                        var option = $(this);
                        var valor = option.val();
                        var cor = normalizarCor(coresStatus[String(valor)]);

                        if (!cor) {
                            this.style.removeProperty('background-color');
                            this.style.removeProperty('color');
                            this.style.removeProperty('font-weight');
                            return;
                        }

                        var texto = corTextoParaFundo(cor);

                        this.style.setProperty('background-color', cor, 'important');
                        this.style.setProperty('color', texto, 'important');
                        this.style.setProperty('font-weight', '800', 'important');
                    });
                }

                function aplicarCoresNoDropdownStatus(campo) {
                    var opcoes = $('.select2-container--open .select2-results__option[role=\"option\"]');

                    if (!opcoes.length) {
                        return;
                    }

                    opcoes.each(function() {
                        var item = $(this);

                        if (item.attr('aria-disabled') === 'true') {
                            return;
                        }

                        var textoOpcao = $.trim(item.text());
                        var valorOpcao = null;

                        campo.find('option').each(function() {
                            var option = $(this);

                            if ($.trim(option.text()) === textoOpcao) {
                                valorOpcao = option.val();
                                return false;
                            }
                        });

                        if (!valorOpcao) {
                            return;
                        }

                        var cor = normalizarCor(coresStatus[String(valorOpcao)]);

                        if (!cor) {
                            return;
                        }

                        if (item.data('req-status-colorido')) {
                            return;
                        }

                        item.data('req-status-colorido', true);

                        item.html(
                            '<span class=\"req-status-option\">' +
                                '<span class=\"req-status-dot\" style=\"background:' + cor + '; border:1px solid ' + cor + ';\"></span>' +
                                '<span class=\"req-status-name\" style=\"color:' + cor + ';\">' +
                                    escaparHtml(textoOpcao) +
                                '</span>' +
                            '</span>'
                        );

                        item.css({
                            'border-left': '4px solid ' + cor,
                            'padding-left': '8px'
                        });
                    });
                }

                function aplicarCorStatus(campo) {
                    var valor = campo.val();
                    var cor = normalizarCor(coresStatus[String(valor)]);

                    aplicarCoresOptionsNativos(campo);

                    var select2 = campo.next('.select2-container').find('.select2-selection');

                    if (!select2.length) {
                        select2 = campo.closest('.form-group').find('.select2-selection').first();
                    }

                    var alvoCampo = select2.length ? select2 : campo;
                    var rendered = select2.find('.select2-selection__rendered');
                    var aba = buscarAbaDoCampo(campo);

                    if (!cor) {
                        limparImportant(alvoCampo, [
                            'background-color',
                            'border-color',
                            'color',
                            'box-shadow'
                        ]);

                        limparImportant(rendered, [
                            'color'
                        ]);

                        limparImportant(aba, [
                            'background-color',
                            'border-color',
                            'border-bottom-color',
                            'color',
                            'box-shadow'
                        ]);

                        return;
                    }

                    var texto = corTextoParaFundo(cor);

                    aplicarImportant(alvoCampo, 'background-color', cor);
                    aplicarImportant(alvoCampo, 'border-color', cor);
                    aplicarImportant(alvoCampo, 'color', texto);
                    aplicarImportant(alvoCampo, 'box-shadow', 'none');

                    aplicarImportant(rendered, 'color', texto);

                    aplicarImportant(aba, 'background-color', cor);
                    aplicarImportant(aba, 'border-color', cor);
                    aplicarImportant(aba, 'border-bottom-color', cor);
                    aplicarImportant(aba, 'color', texto);
                    aplicarImportant(aba, 'box-shadow', 'none');
                }

                function aplicarTodasCoresStatus() {
                    form.find('[name^=\"status_\"]').each(function() {
                        aplicarCorStatus($(this));
                    });
                }

                aplicarTodasCoresStatus();

                setTimeout(aplicarTodasCoresStatus, 300);
                setTimeout(aplicarTodasCoresStatus, 800);
                setTimeout(aplicarTodasCoresStatus, 1300);

                $(document).off('change.reqstatus select2:select.reqstatus', '#" . self::$formName . " [name^=\"status_\"]');

                $(document).on('change.reqstatus select2:select.reqstatus', '#" . self::$formName . " [name^=\"status_\"]', function() {
                    aplicarCorStatus($(this));
                });

                $(document).off('select2:open.reqstatusdropdown', '#" . self::$formName . " [name^=\"status_\"]');

                $(document).on('select2:open.reqstatusdropdown', '#" . self::$formName . " [name^=\"status_\"]', function() {
                    var campo = $(this);

                    $('.select2-container--open, .select2-dropdown').css({
                        'z-index': '30000'
                    });

                    $('.select2-dropdown').last().addClass('req-status-dropdown');

                    setTimeout(function() {
                        aplicarCoresNoDropdownStatus(campo);
                    }, 30);

                    setTimeout(function() {
                        aplicarCoresNoDropdownStatus(campo);
                    }, 150);

                    setTimeout(function() {
                        aplicarCoresNoDropdownStatus(campo);
                    }, 350);
                });

                $(document).off('keyup.reqstatusdropdown input.reqstatusdropdown', '.select2-search__field');

                $(document).on('keyup.reqstatusdropdown input.reqstatusdropdown', '.select2-search__field', function() {
                    var campoAberto = form.find('[name^=\"status_\"]').filter(function() {
                        return $(this).next('.select2-container').hasClass('select2-container--open');
                    }).last();

                    if (!campoAberto.length) {
                        return;
                    }

                    setTimeout(function() {
                        aplicarCoresNoDropdownStatus(campoAberto);
                    }, 60);
                });

                $(document).off('shown.bs.tab.reqstatus', '#" . self::$formName . " .nav-tabs a');

                $(document).on('shown.bs.tab.reqstatus', '#" . self::$formName . " .nav-tabs a', function() {
                    setTimeout(aplicarTodasCoresStatus, 50);
                });
            }, 450);
        ");
    }
    
    private function aplicarCss()
    {
        $style = new TElement('style');
        $style->add("
        .req-choice-panel {
            width: 100%;
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 10px;
            padding: 14px;
            color: #1E2843;
        }

        .req-choice-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .req-choice-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #1E2843;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex: 0 0 auto;
        }

        .req-choice-title {
            font-size: 13px;
            font-weight: 900;
            color: #111827;
            line-height: 1.2;
        }

        .req-choice-subtitle {
            margin-top: 3px;
            font-size: 11px;
            color: #475569;
            line-height: 1.35;
        }

        .req-choice-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .req-choice-card {
            display: block;
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-left: 5px solid #64748b;
            border-radius: 9px;
            padding: 11px 13px;
            color: #1E2843 !important;
            text-decoration: none !important;
            transition: all 0.18s ease;
        }

        .req-choice-card:hover {
            border-color: #94a3b8;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none !important;
        }

        .req-choice-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 9px;
        }

        .req-choice-number {
            font-size: 12px;
            font-weight: 900;
            color: #111827;
            line-height: 1.2;
        }

        .req-choice-client {
            margin-top: 2px;
            font-size: 10.5px;
            font-weight: 700;
            color: #475569;
            line-height: 1.25;
        }

        .req-choice-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #d9dee7;
            background: #f8fafc;
            color: #334155;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 10.5px;
            font-weight: 800;
            white-space: nowrap;
        }

        .req-choice-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            display: inline-block;
        }

        .req-choice-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .req-choice-grid div {
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 7px;
            padding: 7px 8px;
            min-width: 0;
        }

        .req-choice-grid strong {
            display: block;
            font-size: 10px;
            font-weight: 900;
            color: #111827;
            line-height: 1.15;
            margin-bottom: 3px;
        }

        .req-choice-grid span {
            display: block;
            font-size: 10.5px;
            font-weight: 700;
            color: #475569;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .req-choice-open {
            margin-top: 9px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 900;
            color: #1E2843;
        }

        .req-back-list-wrap {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 10px;
        }

        .req-back-list-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 28px;
            padding: 6px 11px;
            border-radius: 7px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #1E2843 !important;
            font-size: 10.5px;
            font-weight: 900;
            line-height: 1;
            text-decoration: none !important;
        }

        .req-back-list-btn:hover {
            background: #1E2843;
            border-color: #1E2843;
            color: #ffffff !important;
            text-decoration: none !important;
        }

        .req-back-list-btn i {
            font-size: 10px;
        }

        @media (max-width: 768px) {
            .req-choice-panel {
                padding: 10px;
            }

            .req-choice-header {
                align-items: flex-start;
            }

            .req-choice-card-top {
                flex-direction: column;
                gap: 7px;
            }

            .req-choice-grid {
                grid-template-columns: 1fr;
            }

            .req-choice-grid span {
                white-space: normal;
                word-break: break-word;
            }
        }
    ");
        $style->add("
            /* BASE GERAL */

            .modal-title,
            .ui-dialog-title,
            .window-title {
                font-size: 13px !important;
                font-weight: 800 !important;
                line-height: 1.2 !important;
            }

            #" . self::$formName . " {
                font-size: 10.5px !important;
                color: #1E2843 !important;
            }

            #" . self::$formName . " * {
                box-sizing: border-box;
            }

            #" . self::$formName . " .panel {
                margin-bottom: 0 !important;
                border-radius: 8px !important;
                overflow: visible !important;
                border: 1px solid #d9dee7 !important;
                box-shadow: none !important;
                background: #ffffff !important;
            }

            #" . self::$formName . " .panel-body {
                padding: 14px 20px 13px 20px !important;
                background: #ffffff !important;
            }

            #" . self::$formName . " .tab-content {
                padding-top: 8px !important;
                background: #ffffff !important;
            }

            /* RESUMO SUPERIOR */

          .req-view-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px 22px;
            background: #ffffff;
            border: 1px solid #d9dee7;
            color: #1E2843;
            padding: 9px 16px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 10.5px;
            line-height: 1.32;
        }

        .req-summary-process-full {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding-top: 7px;
            margin-top: 2px;
            border-top: 1px solid #e5e7eb;
        }

        .req-summary-process-number {
            display: flex;
            align-items: center;
            gap: 4px;
            min-width: 0;
            color: #1E2843;
        }

        .req-summary-process-number span {
            white-space: nowrap;
            overflow: visible;
            text-overflow: unset;
            color: #334155;
        }

        .req-process-view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            flex: 0 0 auto;
            height: 24px;
            padding: 0 10px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #1E2843 !important;
            font-size: 10.5px;
            font-weight: 800;
            line-height: 1;
            text-decoration: none !important;
        }

        .req-process-view-btn:hover {
            background: #1E2843;
            border-color: #1E2843;
            color: #ffffff !important;
            text-decoration: none !important;
        }

        .req-process-view-btn i {
            font-size: 10px;
        }

            .req-view-summary strong,
            .req-view-summary b {
                font-size: 10.5px;
                font-weight: 800;
                color: #111827;
            }

            .req-view-warning {
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #9a3412;
                padding: 10px 13px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 10.5px;
                line-height: 1.35;
            }

            /* ABAS DOS CLIENTES */

            #" . self::$formName . " .nav-tabs {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                white-space: nowrap !important;
                scrollbar-width: thin;
                border-bottom: 1px solid #cbd5e1 !important;
                padding-left: 6px;
                background: #ffffff;
            }

            #" . self::$formName . " .nav-tabs > li {
                flex: 0 0 auto !important;
                float: none !important;
                width: auto !important;
                margin-bottom: -1px !important;
            }

            #" . self::$formName . " .nav-tabs > li > a {
                max-width: 205px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                color: #475569 !important;
                font-weight: 800 !important;
                font-size: 10.5px !important;
                padding: 7px 12px !important;
                line-height: 1.25 !important;
                background: #f8fafc !important;
                border: 1px solid transparent !important;
                border-bottom: 1px solid #cbd5e1 !important;
                border-radius: 7px 7px 0 0 !important;
            }

            #" . self::$formName . " .nav-tabs > li:not(.active) > a {
                background: #f8fafc !important;
                color: #475569 !important;
                border-color: transparent !important;
                border-bottom-color: #cbd5e1 !important;
            }

            #" . self::$formName . " .nav-tabs > li.active > a,
            #" . self::$formName . " .nav-tabs > li.active > a:hover,
            #" . self::$formName . " .nav-tabs > li.active > a:focus {
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                border-bottom-color: #ffffff !important;
                color: #111827 !important;
            }

            #" . self::$formName . " .nav-tabs > li > a[aria-expanded='false'] {
                background: #f8fafc !important;
                color: #475569 !important;
                border-color: transparent !important;
                border-bottom-color: #cbd5e1 !important;
            }

            #" . self::$formName . " .nav-tabs > li > a[aria-expanded='true'] {
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                border-bottom-color: #ffffff !important;
                color: #111827 !important;
            }

            /* ESPAÇAMENTO ENTRE CAMPOS */

            #" . self::$formName . " .row {
                margin-left: -11px !important;
                margin-right: -11px !important;
            }

            #" . self::$formName . " .row > div,
            #" . self::$formName . " [class*='col-sm-'],
            #" . self::$formName . " [class*='col-md-'],
            #" . self::$formName . " [class*='col-lg-'] {
                padding-left: 11px !important;
                padding-right: 11px !important;
            }

            #" . self::$formName . " .form-group {
                margin-bottom: 11px !important;
            }

            #" . self::$formName . " label {
                font-size: 10.5px !important;
                font-weight: 800 !important;
                color: #111827 !important;
                margin-bottom: 4px !important;
                line-height: 1.18 !important;
                letter-spacing: 0.01em;
            }

            /* CAMPOS */

            #" . self::$formName . " input,
            #" . self::$formName . " select,
            #" . self::$formName . " .select2-selection {
                min-height: 29px !important;
                height: 29px !important;
                font-size: 10.5px !important;
                border-radius: 5px !important;
                border-color: #d7dde8 !important;
                box-shadow: none !important;
                padding-top: 3px !important;
                padding-bottom: 3px !important;
                color: #334155 !important;
            }

            #" . self::$formName . " .req-readonly-field,
            #" . self::$formName . " .req-readonly-field[readonly],
            #" . self::$formName . " .req-readonly-field:disabled {
                background: #f8fafc !important;
                color: #64748b !important;
                cursor: not-allowed !important;
                font-weight: 700 !important;
            }

            #" . self::$formName . " input:focus,
            #" . self::$formName . " select:focus,
            #" . self::$formName . " textarea:focus,
            #" . self::$formName . " .select2-selection:focus {
                border-color: #94a3b8 !important;
                box-shadow: none !important;
            }

            #" . self::$formName . " textarea {
                min-height: 82px !important;
                font-size: 10.5px !important;
                border-radius: 5px !important;
                border-color: #d7dde8 !important;
                box-shadow: none !important;
                resize: vertical;
                color: #334155 !important;
            }

            #" . self::$formName . " .select2-selection__rendered {
                line-height: 27px !important;
                font-size: 10.5px !important;
                color: #334155 !important;
                padding-left: 8px !important;
            }

            #" . self::$formName . " .select2-selection__arrow {
                height: 27px !important;
            }

            #" . self::$formName . " .select2-selection__choice {
                font-size: 10.5px !important;
                line-height: 17px !important;
                margin-top: 4px !important;
                padding: 0 6px !important;
            }

            /* SEPARADORES DE ETAPA */

            .req-form-line {
                width: 100%;
                height: 1px;
                background: #b8c2d1;
                margin: 18px 0 17px 0;
                clear: both;
            }

            /* SALDO ABERTO E AVISOS */

            .req-open-balance {
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #9a3412;
                border-radius: 8px;
                padding: 8px 10px;
                margin: 3px 0 10px 0;
                font-size: 10.5px;
                line-height: 1.35;
            }

            .req-open-balance-title {
                font-weight: 800;
                margin-bottom: 3px;
            }

            .req-open-balance-text {
                display: flex;
                gap: 16px;
                flex-wrap: wrap;
                margin-bottom: 2px;
            }

            .req-open-balance-help {
                color: #7c2d12;
            }

            .req-partial-help {
                background: #f8fafc;
                border: 1px dashed #cbd5e1;
                color: #334155;
                border-radius: 8px;
                padding: 7px 9px;
                margin: 3px 0 8px 0;
                font-size: 10.5px;
                line-height: 1.35;
            }

            /* PAGAMENTOS LANÇADOS */

            .req-payment-history {
                margin-top: 8px;
                margin-bottom: 4px;
                border: 1px solid #d6deeb;
                border-radius: 8px;
                background: #f8fafc;
                overflow: hidden;
            }

            .req-payment-history summary {
                cursor: pointer;
                padding: 8px 12px;
                color: #1E2843;
                font-weight: 800;
                font-size: 10.5px;
                line-height: 1.2;
                list-style: none;
            }

            .req-payment-history summary::-webkit-details-marker {
                display: none;
            }

            .req-payment-history summary:before {
                content: '+';
                display: inline-flex;
                width: 17px;
                height: 17px;
                align-items: center;
                justify-content: center;
                margin-right: 8px;
                border-radius: 999px;
                background: #1E2843;
                color: #ffffff;
                font-size: 11px;
                font-weight: 800;
                line-height: 1;
            }

            .req-payment-history[open] summary:before {
                content: '-';
            }

            .req-payment-history-body {
                overflow-x: auto;
                border-top: 1px solid #d6deeb;
                background: #ffffff;
            }

            .req-payment-history table {
                width: 100%;
                border-collapse: collapse;
                font-size: 10.5px;
            }

            .req-payment-history th {
                background: #f1f5f9;
                color: #1E2843;
                font-weight: 800;
                padding: 6px 8px;
                border-bottom: 1px solid #dbe3ef;
                white-space: nowrap;
                font-size: 10.5px;
            }

            .req-payment-history td {
                padding: 6px 8px;
                border-bottom: 1px solid #edf2f7;
                color: #334155;
                white-space: nowrap;
                font-size: 10.5px;
            }

            /* BADGES E BOTÕES */

            .req-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                padding: 3px 8px;
                font-size: 10.5px;
                font-weight: 800;
                line-height: 1;
            }

            .req-badge-open {
                background: #fff7ed;
                color: #9a3412;
                border: 1px solid #fed7aa;
            }

            .req-badge-ok {
                background: #ecfdf5;
                color: #166534;
                border: 1px solid #bbf7d0;
            }

            .req-actions-cell {
                text-align: center;
            }

            .req-empty-panel {
                background: #ffffff;
                border: 1px solid #d9dee7;
                border-radius: 10px;
                padding: 28px 20px;
                text-align: center;
                color: #1E2843;
                margin: 8px 0;
            }

            .req-empty-icon {
                width: 42px;
                height: 42px;
                margin: 0 auto 12px auto;
                border-radius: 999px;
                background: #f1f5f9;
                color: #1E2843;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
            }

            .req-empty-title {
                font-size: 14px;
                font-weight: 800;
                margin-bottom: 6px;
            }

            .req-empty-text {
                font-size: 12px;
                color: #475569;
                line-height: 1.45;
            }

            .req-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                border-radius: 8px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #1E2843;
                text-decoration: none !important;
                transition: all 0.2s ease;
            }

            .req-action-btn:hover {
                background: #1E2843;
                border-color: #1E2843;
                color: #ffffff !important;
            }

            /* RODAPÉ E BOTÃO SALVAR */

            #" . self::$formName . " .panel-footer,
            #" . self::$formName . " .card-footer,
            #" . self::$formName . " .form-actions {
                background: #ffffff !important;
                border-top: 1px solid #e5e7eb !important;
                padding: 9px 12px !important;
                min-height: auto !important;
                height: auto !important;
                text-align: right !important;
            }

            #" . self::$formName . " .panel-footer .btn,
            #" . self::$formName . " .card-footer .btn,
            #" . self::$formName . " .form-actions .btn {
                float: none !important;
                margin: 0 !important;
            }

            #" . self::$formName . " .btn {
                font-size: 10.5px !important;
                line-height: 1.25 !important;
            }

            #" . self::$formName . " .btn-primary {
                background: #1E2843 !important;
                border-color: #1E2843 !important;
                color: #ffffff !important;
                border-radius: 6px !important;
                font-weight: 800 !important;
                padding: 5px 13px !important;
            }

            #form_RequisicaoPagamentoVisualizacao .panel,
            #form_RequisicaoPagamentoVisualizacao .panel-body,
            #form_RequisicaoPagamentoVisualizacao .tab-content,
            #form_RequisicaoPagamentoVisualizacao .tab-pane {
                overflow: visible !important;
            }

            .select2-container--open,
            .select2-dropdown,
            .select2-drop,
            .select2-drop-active,
            .select2-drop-mask {
                z-index: 9999999 !important;
            }

            .ui-autocomplete,
            .ui-datepicker,
            .datepicker,
            .datepicker-dropdown,
            .bootstrap-datetimepicker-widget,
            .dropdown-menu {
                z-index: 9999999 !important;
            }

            /* NOTEBOOKS E TELAS MENORES */

            @media (max-width: 1366px) {
                #" . self::$formName . " .panel-body {
                    padding: 13px 18px 12px 18px !important;
                }

                #" . self::$formName . " .row {
                    margin-left: -10px !important;
                    margin-right: -10px !important;
                }

                #" . self::$formName . " .row > div,
                #" . self::$formName . " [class*='col-sm-'],
                #" . self::$formName . " [class*='col-md-'],
                #" . self::$formName . " [class*='col-lg-'] {
                    padding-left: 10px !important;
                    padding-right: 10px !important;
                }

                #" . self::$formName . " .form-group {
                    margin-bottom: 10px !important;
                }

                .req-form-line {
                    margin: 16px 0 15px 0;
                }

                .req-view-summary {
                    gap: 7px 18px;
                    padding: 8px 14px;
                }
            }

            /* CELULAR */

            @media (max-width: 768px) {
               .req-view-summary {
                    grid-template-columns: 1fr;
                    font-size: 10.5px;
                    gap: 6px;
                    padding: 9px 11px;
                }

                .req-summary-process-full {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }

                .req-summary-process-number {
                    display: block;
                    width: 100%;
                }

                .req-summary-process-number span {
                    display: inline;
                    white-space: normal;
                    word-break: break-word;
                }

                .req-process-view-btn {
                    height: 25px;
                    font-size: 10.5px;
                    padding: 0 10px;
                }
                #" . self::$formName . " .panel-body {
                    padding: 11px 9px !important;
                }

                #" . self::$formName . " .row {
                    margin-left: -6px !important;
                    margin-right: -6px !important;
                }

                #" . self::$formName . " .row > div,
                #" . self::$formName . " [class*='col-sm-'],
                #" . self::$formName . " [class*='col-md-'],
                #" . self::$formName . " [class*='col-lg-'] {
                    padding-left: 6px !important;
                    padding-right: 6px !important;
                }

                #" . self::$formName . " .form-group {
                    margin-bottom: 10px !important;
                }

                #" . self::$formName . " .nav-tabs > li > a {
                    max-width: 165px;
                    font-size: 10.5px !important;
                    padding: 7px 10px !important;
                }

                .req-form-line {
                    margin: 15px 0 14px 0;
                }

                .req-payment-history table {
                    min-width: 900px;
                }
            }
        ");

        parent::add($style);

    }
}