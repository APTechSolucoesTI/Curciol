<?php
/**
 * Compensar em lote (Contas a pagar / Contas a receber): compensa as linhas ainda nao compensadas do extrato
 * das contas marcadas. Pode ser desfeito por 30 minutos (ultima acao em lote do usuario).
 */
class LoteCompensarForm extends TWindow
{
    protected $form;
    private static $formName = 'form_LoteCompensar';

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.7, null);
        parent::setTitle('Compensar contas selecionadas');
        parent::setProperty('class', 'window_modal');

        $tipo = LoteQuitarForm::tipo($param['tipo'] ?? '');
        $ids = LoteQuitarForm::ids($param['ids'] ?? '');
        $this->form = new BootstrapFormBuilder(self::$formName);

        $linhas = [];
        try {
            TTransaction::open('escritorio');
            if ($ids) {
                $daTela = TTransaction::get()->query('SELECT id FROM conta WHERE id IN (' . implode(',', $ids) . ") AND tipo_conta_id = '{$tipo}'")->fetchAll(PDO::FETCH_COLUMN);
                $linhas = LoteFinanceiroService::linhasACompensar($daTela);
            }
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
        $total = array_sum(array_map(function ($l) { return (float) $l->valor; }, $linhas));
        $contas = array_unique(array_map(function ($l) { return (int) $l->conta_id; }, $linhas));

        $resumo = "<div class='lote-resumo'>";
        if (!$linhas) {
            $resumo .= '<b>Nenhuma das contas selecionadas tem pagamento a compensar.</b> Quite as contas antes (ou elas já estão compensadas).';
        } else {
            $resumo .= '<div><b>' . count($linhas) . ' linha(s) do extrato</b> de ' . count($contas) . ' conta(s) · total <b>' . LoteQuitarForm::moeda($total) . '</b></div>';
            $resumo .= "<div class='lote-tabela'><table><thead><tr><th>Pessoa</th><th>Conta</th><th>Parcela</th><th>Baixa</th><th>Conta-caixa</th><th class='v'>Valor</th></tr></thead><tbody>";
            foreach ($linhas as $l) {
                $resumo .= '<tr><td>' . htmlspecialchars((string) $l->pessoa) . '</td><td>' . htmlspecialchars(mb_strimwidth((string) $l->descricao, 0, 60, '…')) . '</td><td>' . (int) $l->parcela
                    . '</td><td>' . ($l->dt_pagamento ? date('d/m/Y', strtotime($l->dt_pagamento)) : '') . '</td><td>' . htmlspecialchars((string) $l->conta_caixa) . "</td><td class='v'>" . LoteQuitarForm::moeda($l->valor) . '</td></tr>';
            }
            $resumo .= '</tbody></table></div>';
        }
        $resumo .= '</div>' . LoteQuitarForm::estilo();

        $idsCampo = new THidden('ids');
        $idsCampo->setValue(implode(',', $contas));
        $tipoCampo = new THidden('tipo');
        $tipoCampo->setValue($tipo);
        $data = new TDate('data_compensacao');
        $data->setMask('dd/mm/yyyy');
        $data->setDatabaseMask('yyyy-mm-dd');
        $data->setSize('100%');
        $data->setValue(date('d/m/Y'));

        $this->form->addContent([$resumo]);
        $this->form->addFields([$idsCampo, $tipoCampo]);
        $r = $this->form->addFields([new TLabel('Data da compensação:', '#ff0000', '14px', null, '100%'), $data], []);
        $r->layout = ['col-sm-4', 'col-sm-8'];
        $this->form->addContent(["<div class='lote-nota'>Dá para desfazer este lote por " . LoteFinanceiroService::PRAZO_DESFAZER_MIN . ' minutos (botão "Desfazer último lote" na listagem).</div>']);

        if ($linhas) {
            $b = $this->form->addAction('Compensar ' . LoteQuitarForm::moeda($total), new TAction([$this, 'onCompensar']), 'fas:dollar-sign #ffffff');
            $b->addStyleClass('btn-success');
        }
        $this->form->addAction('Cancelar', new TAction(['LoteQuitarForm', 'onFechar'], ['static' => '1']), 'fas:times #333');
        parent::add($this->form);
    }

    public function onShow($param = null) {}

    public function onCompensar($param = null)
    {
        try {
            $d = $this->form->getData();
            $tipo = LoteQuitarForm::tipo($d->tipo ?? '');
            $lote = LoteFinanceiroService::compensar(LoteQuitarForm::ids($d->ids ?? ''), $tipo, (string) ($d->data_compensacao ?? ''));
            TWindow::closeWindow();
            TToast::show('success', 'Lote nº ' . (int) $lote->id . ': ' . (int) $lote->quantidade . ' linha(s) compensada(s), ' . LoteQuitarForm::moeda($lote->total), 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage(LoteQuitarForm::lista($tipo), 'onReload');
        } catch (Throwable $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
}
