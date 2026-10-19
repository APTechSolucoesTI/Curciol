<?php

class RequisicaoPagamentoEtapa3Visualizacao extends TWindow
{
    private static $database = 'escritorio';

    public function __construct($param = null)
    {
        parent::__construct();

        parent::setTitle('Visualizar lançamento');
        parent::setSize(0.62, 0.82);

        $this->aplicarCss();

        try {
            $id = (int) ($param['key'] ?? 0);

            if (empty($id)) {
                throw new Exception('Lançamento não informado.');
            }

            TTransaction::open(self::$database);
            $conn = TTransaction::get();

            $lancamento = $this->buscarLancamento($conn, $id);

            TTransaction::close();

            if (empty($lancamento)) {
                throw new Exception('Lançamento não encontrado.');
            }

            $possuiSaldo = strtoupper(trim((string) ($lancamento->possui_saldo ?? 'N'))) === 'S';

            $situacao = $possuiSaldo
                ? "<span class='e3-badge e3-badge-open'>Saldo em aberto</span>"
                : "<span class='e3-badge e3-badge-ok'>Sem saldo</span>";

            $uidJanela = 'e3_view_' . $id . '_' . uniqid();

            // botao Gerar / Ver financeiro (o detalhe se recarrega sozinho depois)
            // a tela que abriu este detalhe (historico da janela ou da aba) tambem e recarregada
            $pai = [];
            $urlPai = '';
            if (!empty($param['origem_pai']) && !empty($param['url_pai'])) {
                $pai = ['origem' => $param['origem_pai'], 'url' => $param['url_pai']];
                $urlPai = '&origem_pai=' . urlencode($param['origem_pai']) . '&url_pai=' . urlencode($param['url_pai']);
            }
            $botaoFinanceiro = LevantamentoFinanceiroForm::botaoFinanceiro(
                $lancamento,
                LevantamentoFinanceiroForm::idsComFinanceiro([$id]),
                'detalhe',
                'engine.php?class=RequisicaoPagamentoEtapa3Visualizacao&method=onShow&key=' . $id . $urlPai . '&register_state=false',
                true,
                $pai
            );
            // excluir o pagamento (fecha o detalhe e recarrega a tela que o abriu)
            $botaoExcluir = self::botaoExcluir($id, PagamentoExclusaoService::motivoBloqueio($id), (string) ($param['origem_pai'] ?? ''), (string) ($param['url_pai'] ?? ''), true);
            if ($botaoFinanceiro !== '' || $botaoExcluir !== '') {
                $botaoFinanceiro = "<div class='e3-section' style='padding: 10px 14px; text-align: right;'>{$botaoExcluir} {$botaoFinanceiro}</div>";
            }

            $html = "
                    <div id='{$uidJanela}' class='e3-wrap'>

                    <div class='e3-top-grid'>
                        <div class='e3-top-card'>
                            <div class='e3-top-label'>Cliente</div>
                            <div class='e3-top-value'>" . self::h($lancamento->cliente_nome) . "</div>
                        </div>

                        <div class='e3-top-card'>
                            <div class='e3-top-label'>CPF</div>
                            <div class='e3-top-value'>" . self::h(self::formatarCpfCnpj($lancamento->cpf_cnpj)) . "</div>
                        </div>

                        <div class='e3-top-card'>
                            <div class='e3-top-label'>Requisição</div>
                            <div class='e3-top-value'>#" . self::h($lancamento->requisicao_pagamento_id) . "</div>
                        </div>

                        <div class='e3-top-card'>
                            <div class='e3-top-label'>Lançamento</div>
                            <div class='e3-top-value'>" . self::h(($lancamento->numero_ciclo ?: 1) . 'º lançamento') . "</div>
                        </div>

                        <div class='e3-top-card'>
                            <div class='e3-top-label'>Situação</div>
                            <div class='e3-top-value'>{$situacao}</div>
                        </div>
                    </div>

                    <div class='e3-section'>
                        <div class='e3-section-title'>Dados do depósito</div>
                        <div class='e3-grid'>
                            " . $this->blocoInfo('Data do depósito', self::formatarDataBR($lancamento->data_deposito)) . "
                            " . $this->blocoInfo('Valor bruto depositado', self::formatarValorBR($lancamento->valor_bruto_depositado)) . "
                            " . ($lancamento->desconto_previdenciario !== null
                                ? $this->blocoInfo('Desconto previdenciário', self::formatarValorBR($lancamento->desconto_previdenciario))
                                    . $this->blocoInfo('Desconto assistencial', self::formatarValorBR($lancamento->desconto_assistencial))
                                    . $this->blocoInfo('Desconto IRRF', self::formatarValorBR($lancamento->desconto_irrf))
                                : '') . "
                            " . $this->blocoInfo('Valor do MLE', self::formatarValorBR($lancamento->valor_mle)) . "
                        </div>
                    </div>

                    <div class='e3-section'>
                        <div class='e3-section-title'>Dados do MLE</div>
                        <div class='e3-grid'>
                            " . $this->blocoInfo('Conta indicada no MLE', $lancamento->conta_indicada_mle) . "
                            " . $this->blocoInfo('Data do pedido de MLE', self::formatarDataBR($lancamento->data_pedido_mle)) . "
                            " . $this->blocoInfo('Data do deferimento do MLE', self::formatarDataBR($lancamento->data_deferimento_mle)) . "
                        </div>
                    </div>

                    <div class='e3-section'>
                        <div class='e3-section-title'>Saldo remanescente</div>
                        <div class='e3-grid'>
                            " . $this->blocoInfo('Sobrou saldo após este pagamento?', $possuiSaldo ? 'Sim' : 'Não') . "
                            " . $this->blocoInfo('Saldo bruto remanescente', self::formatarValorBR($lancamento->saldo_bruto)) . "
                            " . $this->blocoInfo('Data base do saldo', self::formatarDataBR($lancamento->data_base_saldo)) . "
                        </div>
                    </div>

                    " . $botaoFinanceiro . "

                </div>
            ";

            $box = new TVBox;
            $box->style = 'width: 100%;';
            $box->add($html);

            parent::add($box);

            $this->ajustarJanelaParaFrente($uidJanela);
        }
        catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {
    }

    /**
     * Botao de excluir o pagamento: ativo quando nada vindo dele foi compensado ou quitado; senao desabilitado com o motivo.
     * $origem/$urlRecarga: tela a recarregar depois (janela ou aba da requisicao). $texto: botao com rotulo (detalhe).
     */
    public static function botaoExcluir(int $etapa3Id, ?string $motivo, string $origem, string $urlRecarga, bool $texto = false): string
    {
        if ($motivo !== null) {
            $titulo = htmlspecialchars('Não pode excluir: ' . $motivo, ENT_QUOTES, 'UTF-8');
            return $texto
                ? "<span class='btn btn-sm btn-default disabled' title='{$titulo}' style='pointer-events:auto;cursor:not-allowed'><i class='fas fa-trash-alt'></i> Excluir pagamento</span>"
                : "<span class='req-action-btn' title='{$titulo}' style='opacity:.35;cursor:not-allowed'><i class='fas fa-trash-alt'></i></span>";
        }
        $url = 'engine.php?' . http_build_query([
            'class' => __CLASS__, 'method' => 'onExcluir', 'static' => '1', 'key' => $etapa3Id,
            'origem' => $origem, 'url_recarga' => $urlRecarga, 'fechar_detalhe' => $texto ? '1' : '0',
        ]);
        $onclick = htmlspecialchars('Adianti.waitMessage = false; __adianti_ajax_exec(' . json_encode(substr($url, strlen('engine.php?'))) . '); return false;', ENT_QUOTES, 'UTF-8');
        return $texto
            ? "<a href='javascript:void(0)' class='btn btn-sm btn-default' style='color:#b91c1c' onclick=\"{$onclick}\"><i class='fas fa-trash-alt'></i> Excluir pagamento</a>"
            : "<a href='javascript:void(0)' class='req-action-btn' title='Excluir pagamento' style='color:#b91c1c' onclick=\"{$onclick}\"><i class='fas fa-trash-alt'></i></a>";
    }

    /** Confirmacao da exclusao. */
    public static function onExcluir($param)
    {
        try {
            $id = (int) ($param['key'] ?? 0);
            $comFinanceiro = (LevantamentoFinanceiroService::existePara($id) !== null);
            $acao = new TAction([__CLASS__, 'onExcluirConfirmado'], [
                'static' => '1', 'key' => $id, 'origem' => $param['origem'] ?? '',
                'url_recarga' => $param['url_recarga'] ?? '', 'fechar_detalhe' => $param['fechar_detalhe'] ?? '0',
            ]);
            new TQuestion('Excluir este pagamento?' . ($comFinanceiro
                ? '<br><br>O financeiro gerado por ele também será desfeito: a conta a receber, a conta a pagar ao cliente e as contas a pagar dos colaboradores serão apagadas.'
                : ''), $acao);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onExcluirConfirmado($param)
    {
        try {
            PagamentoExclusaoService::excluir((int) ($param['key'] ?? 0));
            if (($param['fechar_detalhe'] ?? '0') === '1') {
                TScript::create("$('.e3-wrap').closest('.ui-dialog, .modal').remove();");
            }
            new TMessage('info', 'Pagamento excluído.');
            LevantamentoFinanceiroForm::recarregarOrigem(['origem' => $param['origem'] ?? '', 'url_recarga' => $param['url_recarga'] ?? '']);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    private function buscarLancamento($conn, $id)
    {
        $sql = "
            SELECT
                e3.id,
                e3.requisicao_pagamento_cliente_id,
                e3.processo_filho_id,
                e3.numero_ciclo,
                e3.data_deposito,
                e3.valor_bruto_depositado,
                e3.valor_mle,
                e3.desconto_previdenciario,
                e3.desconto_assistencial,
                e3.desconto_irrf,
                e3.conta_indicada_mle,
                e3.data_pedido_mle,
                e3.data_deferimento_mle,
                e3.saldo_bruto,
                e3.data_base_saldo,
                e3.possui_saldo,

                rpc.id AS rpc_id,
                rpc.obs,
                rp.id AS requisicao_pagamento_id,

                cli.nome AS cliente_nome,
                cli.cpf_cnpj,

                COALESCE(pp.numero_cnj_numero, pp.numero_outro, pp.id::text) AS processo_principal_numero,
                COALESCE(pf.numero_cnj_numero, pf.numero_outro, pf.id::text) AS processo_filho_numero

            FROM requisicao_pagamento_etapa3 e3

            JOIN requisicao_pagamento_cliente rpc
                ON rpc.id = e3.requisicao_pagamento_cliente_id

            JOIN requisicao_pagamento rp
                ON rp.id = rpc.requisicao_pagamento_id

            LEFT JOIN pessoa cli
                ON cli.id = rpc.pessoa_id

            LEFT JOIN processo pp
                ON pp.id = rp.processo_id

            LEFT JOIN processo pf
                ON pf.id = e3.processo_filho_id

            WHERE e3.id = ?
            LIMIT 1
        ";

        $sth = $conn->prepare($sql);
        $sth->execute([(int) $id]);

        return $sth->fetch(PDO::FETCH_OBJ);
    }

    private function blocoInfo($label, $valor)
    {
        $valor = ($valor === null || $valor === '') ? '-' : $valor;

        return "
            <div class='e3-info-card'>
                <div class='e3-info-label'>" . self::h($label) . "</div>
                <div class='e3-info-value'>" . self::h($valor) . "</div>
            </div>
        ";
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
            return '-';
        }

        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }

    private static function formatarDataBR($data)
    {
        if (empty($data)) {
            return '-';
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

    private static function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function ajustarJanelaParaFrente($uidJanela)
    {
        $uidJanela = addslashes($uidJanela);

        TScript::create("
            setTimeout(function() {
                function ajustarE3() {
                    var conteudo = $('#{$uidJanela}');

                    if (!conteudo.length) {
                        return;
                    }

                    var janela = conteudo.closest('.ui-dialog, .twindow, .modal, .window');

                    if (!janela.length) {
                        return;
                    }

                    janela.appendTo('body');

                    janela.css({
                        'z-index': '60000',
                        'position': 'fixed'
                    });

                    janela.find('.ui-dialog-titlebar, .modal-header, .window-header, .panel-heading').css({
                        'z-index': '60001'
                    });

                    var overlay = $('.ui-widget-overlay:last, .modal-backdrop:last, .window_modal:last');

                    if (overlay.length) {
                        overlay.css({
                            'z-index': '59990',
                            'background': 'transparent',
                            'opacity': '0',
                            'pointer-events': 'none'
                        });
                    }
                }

                ajustarE3();
                setTimeout(ajustarE3, 250);
                setTimeout(ajustarE3, 700);
                setTimeout(ajustarE3, 1200);
            }, 80);
        ");
    }

    private function aplicarCss()
    {
        $style = new TElement('style');

        $style->add("
            .e3-wrap {
                padding: 12px;
                background: #f8fafc;
            }

            .e3-top-grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 12px;
                margin-bottom: 14px;
            }

            .e3-top-card,
            .e3-info-card,
            .e3-obs,
            .e3-section {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }

            .e3-top-card {
                padding: 14px;
            }

            .e3-top-label {
                font-size: 11px;
                font-weight: 800;
                color: #64748b;
                margin-bottom: 6px;
                text-transform: uppercase;
            }

            .e3-top-value {
                font-size: 15px;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.35;
            }

            .e3-section {
                padding: 14px;
                margin-bottom: 14px;
            }

            .e3-section-title {
                font-size: 14px;
                font-weight: 800;
                color: #1E2843;
                margin-bottom: 12px;
            }

            .e3-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .e3-info-card {
                padding: 12px;
            }

            .e3-info-label {
                font-size: 11px;
                font-weight: 800;
                color: #64748b;
                margin-bottom: 5px;
                text-transform: uppercase;
            }

            .e3-info-value {
                font-size: 14px;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.4;
                word-break: break-word;
            }

            .e3-obs {
                padding: 14px;
                font-size: 13px;
                color: #334155;
                line-height: 1.55;
                white-space: normal;
            }

            .e3-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                padding: 5px 10px;
                font-size: 11px;
                font-weight: 800;
                line-height: 1;
            }

            .e3-header {
                background: #1E2843;
                color: #ffffff;
                border-radius: 10px;
                padding: 13px 16px;
                margin-bottom: 14px;
            }

            .e3-header-title {
                font-size: 14px;
                font-weight: 800;
                line-height: 1.2;
            }

            .e3-header-subtitle {
                font-size: 11px;
                opacity: 0.85;
                margin-top: 3px;
            }

            .e3-badge-open {
                background: #fff7ed;
                color: #9a3412;
                border: 1px solid #fed7aa;
            }

            .e3-badge-ok {
                background: #ecfdf5;
                color: #166534;
                border: 1px solid #bbf7d0;
            }

            @media (max-width: 900px) {
                .e3-top-grid,
                .e3-grid {
                    grid-template-columns: 1fr;
                }
            }
        ");

        parent::add($style);
    }
}