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