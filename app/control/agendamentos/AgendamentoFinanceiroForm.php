<?php
/**
 * Gerar financeiro do agendamento (29/10/2026): janela aberta pelo botao "Gerar financeiro" da tela do agendamento.
 * Conta a receber do cliente com a parceria de repasse do agendamento (so as de percentual fixo; agendamento sem
 * parceria escolhe aqui). "Valor já quitado?" (Sim / Sim e compensado) registra o recebimento na conta-caixa e gera os
 * repasses na hora; "Não, ainda a quitar": os repasses saem quando a conta for quitada.
 * Esta tela (e o desfazer) so para os grupos Financeiro e Financeiro do agendamento (a Vania; 30/10/2026), pela
 * permissao do programa AgendamentoFinanceiroForm. Sem ela, nem a aba Financeiro do agendamento aparece.
 * Regras em AgendamentoFinanceiroService.
 */
class AgendamentoFinanceiroForm extends TWindow
{
    protected $form;
    private static $database = 'escritorio';
    private static $formName = 'form_AgendamentoFinanceiro';

    public function __construct($param)
    {
        parent::__construct();
        parent::setSize(0.7, null);
        parent::setTitle('Gerar financeiro do agendamento');
        parent::setProperty('class', 'window_modal');
        parent::setDialogClass('curciol-janela-rolavel');

        $agendamentoId = (int) ($param['agendamento_id'] ?? $param['key'] ?? 0);
        try {
            TTransaction::open(self::$database);
            if ($motivo = AgendamentoFinanceiroService::motivoNaoGerar($agendamentoId)) {
                throw new Exception($motivo);
            }
            $this->montarFormulario($agendamentoId);
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollbackAll();
            parent::add(LevantamentoFinanceiroForm::caixaAviso(htmlspecialchars(LevantamentoFinanceiroForm::mensagem($e), ENT_QUOTES, 'UTF-8'), 'erro'));
        }
        LevantamentoFinanceiroForm::estilo($this);
        LevantamentoFinanceiroForm::trazerParaFrente($this);
    }

    public function onShow($param = null)
    {
    }

    /** Gerar e desfazer o financeiro do agendamento (e ver a aba Financeiro): so quem tem esta tela. */
    public static function podeGerar(): bool
    {
        return SystemPermission::checkPermission(__CLASS__);
    }

    private function montarFormulario(int $agendamentoId): void
    {
        $agendamento = Agendamento::find($agendamentoId);
        $agenda = Agenda::find((int) $agendamento->agenda_id);
        $cliente = Pessoa::find((int) $agendamento->cliente_id);
        $valorAgendamento = AgendamentoFinanceiroService::valorDoAgendamento($agendamentoId);

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('');

        $contas     = self::opcoes("SELECT id, nome FROM conta_caixa WHERE COALESCE(ativo, 'S') <> 'N' ORDER BY nome");
        $pagamentos = self::opcoes("SELECT id, nome FROM tipo_pagamento WHERE COALESCE(ativo, 'S') <> 'N' ORDER BY nome");
        $categorias = self::opcoes("SELECT id, nome FROM categoria_conta WHERE tipo_conta_id IN ('1', '3') ORDER BY nome");
        $documentos = self::opcoes("SELECT id, nome FROM tipo_documento_financeiro WHERE tipo_conta_id IN ('1', '3') ORDER BY nome");
        $parcerias  = AgendamentoFinanceiroService::parceriasFixas((int) $agendamento->agenda_id);

        // --- origem
        $this->form->addContent([LevantamentoFinanceiroForm::titulo('Agendamento')]);
        $row = $this->form->addFields(
            [LevantamentoFinanceiroForm::rotulo('Cliente'), new TTextDisplay(htmlspecialchars((string) ($cliente->nome ?? '')), '', '12px', '')],
            [LevantamentoFinanceiroForm::rotulo('Data'), new TTextDisplay(date('d/m/Y H:i', strtotime((string) $agendamento->dt_inicial)), '', '12px', '')],
            [LevantamentoFinanceiroForm::rotulo('Agenda'), new TTextDisplay(htmlspecialchars((string) ($agenda->nome ?? '')), '', '12px', '')],
            [LevantamentoFinanceiroForm::rotulo('Valor do agendamento'), new TTextDisplay(LevantamentoFinanceiroForm::moeda($valorAgendamento), '', '12px', '')]
        );
        $row->layout = ['col-sm-5', 'col-sm-2', 'col-sm-3', 'col-sm-2'];

        // --- campos
        $id = new THidden('agendamento_id');
        $valor = new TNumeric('valor', 2, ',', '.');
        $valor->setSize('100%');
        // a parceria e a do agendamento (escolhida no cadastro); sem ela, escolhe aqui e fica gravada no agendamento
        $parceriaDoAgendamento = (int) $agendamento->parceria_levantamento_id;
        if ($parceriaDoAgendamento > 0) {
            $parceria = new THidden('parceria_levantamento_id');
            $parceriaCampo = [new TTextDisplay(htmlspecialchars((string) (ParceriaLevantamento::find($parceriaDoAgendamento)->nome ?? '')), '', '12px', ''), $parceria];
        } else {
            $parceria = new TCombo('parceria_levantamento_id');
            $parceria->addItems($parcerias);
            $parceria->setSize('100%');
            $parceriaCampo = [$parceria];
        }
        $contaCaixa = new TCombo('conta_caixa_id');
        $contaCaixa->addItems($contas);
        $contaCaixa->setSize('100%');
        $tipoPgto = new TCombo('tipo_pagamento_id');
        $tipoPgto->addItems($pagamentos);
        $tipoPgto->setSize('100%');
        $vencimento = new TDate('vencimento');
        $vencimento->setMask('dd/mm/yyyy');
        $vencimento->setDatabaseMask('yyyy-mm-dd');
        $vencimento->setSize('100%');
        $quitacao = new TCombo('quitacao');
        $quitacao->addItems(['N' => 'Não, ainda a quitar', 'S' => 'Sim, já quitado', 'C' => 'Sim, já quitado e compensado']);
        $quitacao->setDefaultOption(false);
        $quitacao->setSize('100%');
        $categoria = new TCombo('categoria_conta_id');
        $categoria->addItems($categorias);
        $categoria->setSize('100%');
        $documento = new TCombo('tipo_documento_financeiro_id');
        $documento->addItems($documentos);
        $documento->setSize('100%');

        $previa = new TAction([__CLASS__, 'onPrevia'], ['static' => '1']);
        if ($parceria instanceof TCombo) { $parceria->setChangeAction($previa); }
        $valor->setExitAction($previa);

        $this->form->addFields([$id]);
        $this->form->addContent([LevantamentoFinanceiroForm::titulo('Conta a receber')]);
        $row = $this->form->addFields(
            [LevantamentoFinanceiroForm::rotulo('Valor (R$)', true), $valor],
            array_merge([LevantamentoFinanceiroForm::rotulo('Parceria de repasse', true)], $parceriaCampo),
            [LevantamentoFinanceiroForm::rotulo('Conta-caixa', true), $contaCaixa],
            [LevantamentoFinanceiroForm::rotulo('Forma de pagamento', true), $tipoPgto]
        );
        $row->layout = ['col-sm-2', 'col-sm-4', 'col-sm-3', 'col-sm-3'];
        $row = $this->form->addFields(
            [LevantamentoFinanceiroForm::rotulo('Vencimento', true), $vencimento],
            [LevantamentoFinanceiroForm::rotulo('Valor já quitado?', true), $quitacao],
            [LevantamentoFinanceiroForm::rotulo('Categoria da conta', true), $categoria],
            [LevantamentoFinanceiroForm::rotulo('Documento da conta a receber', true), $documento]
        );
        $row->layout = ['col-sm-2', 'col-sm-4', 'col-sm-3', 'col-sm-3'];

        $nota = new TElement('div');
        $nota->class = 'lf-nota';
        $nota->add('Já quitado: o recebimento entra na conta-caixa (na data do vencimento; compensado ou a compensar) e os repasses aos colaboradores são gerados na hora. '
            . 'Ainda a quitar: os repasses saem quando a conta a receber for quitada. Depois de gerado, o agendamento não pode ser cancelado; '
            . 'para desfazer o financeiro, a conta a receber precisa estar sem recebimento (quitada, estorne/desquite antes).');
        $this->form->addContent([$nota]);

        // --- previa da divisao
        $this->form->addContent([LevantamentoFinanceiroForm::titulo('Divisão da parceria (repasses)')]);
        $div = new TElement('div');
        $div->id = 'agf_previa';
        $div->add($parceriaDoAgendamento > 0
            ? self::tabelaDivisao(AgendamentoFinanceiroService::divisao($parceriaDoAgendamento, $valorAgendamento, AgendamentoFinanceiroService::escritorioDoAgendamento($agendamentoId)))
            : LevantamentoFinanceiroForm::caixaAviso('Escolha a parceria para ver a divisão.', 'info'));
        $this->form->addContent([$div]);

        $btn = $this->form->addAction('Gerar financeiro', new TAction([__CLASS__, 'onGerar'], ['static' => '1']), 'fas:check');
        $btn->class = 'btn btn-sm btn-primary';
        $btn->addFunction("if (this.disabled) { return false; } this.disabled = true; var botao = this; $(document).on('ajaxComplete.travarAgendamentoFinanceiro', function (e, xhr, s) { if (s.url && s.url.indexOf('method=onGerar') >= 0) { botao.disabled = false; $(document).off('ajaxComplete.travarAgendamentoFinanceiro'); } })");

        // valores iniciais
        $porNome = function (array $lista, string $nome) {
            foreach ($lista as $k => $n) { if (LevantamentoFinanceiroForm::semAcento($n) === $nome) { return $k; } }
            return null;
        };
        $data = new stdClass;
        $data->agendamento_id = $agendamentoId;
        $data->valor = $valorAgendamento;
        $data->conta_caixa_id = (count($contas) === 1) ? key($contas) : null;
        $data->vencimento = date('Y-m-d');
        $data->parceria_levantamento_id = $parceriaDoAgendamento ?: null;
        $data->quitacao = 'N';
        $data->categoria_conta_id = $porNome($categorias, 'recebimentos de agendamentos');
        $data->tipo_documento_financeiro_id = $porNome($documentos, 'atendimento');
        $this->form->setData($data);

        parent::add($this->form);
    }

    /** Tabela da divisao da parceria (participante, %, valor e se vira repasse). */
    private static function tabelaDivisao(array $linhas)
    {
        if (!$linhas) {
            return LevantamentoFinanceiroForm::caixaAviso('A parceria não tem participantes com percentual.', 'info');
        }
        $html = new TElement('table');
        $html->class = 'lf-tabela';
        $html->add('<tr><th>Participante</th><th class="lf-num">%</th><th class="lf-num">Valor</th><th></th></tr>');
        foreach ($linhas as $l) {
            $html->add('<tr><td>' . htmlspecialchars($l['nome']) . '</td><td class="lf-num">' . number_format($l['percentual'], 2, ',', '.') . '%</td><td class="lf-num">'
                . LevantamentoFinanceiroForm::moeda($l['valor']) . '</td><td>' . ($l['escritorio'] ? '<span class="lf-nota">fica com o escritório (sem conta a pagar)</span>' : 'repasse (conta a pagar)') . '</td></tr>');
        }
        return $html;
    }

    /** Previa da divisao da parceria para o valor informado. */
    public static function onPrevia($param)
    {
        try {
            $parceriaId = (int) ($param['parceria_levantamento_id'] ?? 0);
            $valor = self::valor($param['valor'] ?? '');
            if ($parceriaId <= 0 || $valor === null || $valor <= 0) {
                $html = LevantamentoFinanceiroForm::caixaAviso('Escolha a parceria e informe o valor para ver a divisão.', 'info');
            } else {
                TTransaction::open(self::$database);
                $linhas = AgendamentoFinanceiroService::divisao($parceriaId, $valor, AgendamentoFinanceiroService::escritorioDoAgendamento((int) ($param['agendamento_id'] ?? 0)));
                TTransaction::close();
                $html = self::tabelaDivisao($linhas);
            }
            TScript::create("$('#agf_previa').html(" . json_encode((string) $html) . ");");
        } catch (Throwable $e) {
            TTransaction::rollbackAll();
            TScript::create("$('#agf_previa').html(" . json_encode((string) LevantamentoFinanceiroForm::caixaAviso(htmlspecialchars(LevantamentoFinanceiroForm::mensagem($e), ENT_QUOTES, 'UTF-8'), 'erro')) . ");");
        }
    }

    public static function onGerar($param)
    {
        try {
            $agendamentoId = (int) ($param['agendamento_id'] ?? 0);
            $valor = self::valor($param['valor'] ?? '');
            $vencimento = self::data($param['vencimento'] ?? '');
            AgendamentoFinanceiroService::gerar($agendamentoId, [
                'valor' => $valor ?? 0,
                'parceria_levantamento_id' => (int) ($param['parceria_levantamento_id'] ?? 0),
                'conta_caixa_id' => (int) ($param['conta_caixa_id'] ?? 0),
                'tipo_pagamento_id' => (int) ($param['tipo_pagamento_id'] ?? 0),
                'categoria_conta_id' => (int) ($param['categoria_conta_id'] ?? 0),
                'tipo_documento_financeiro_id' => (int) ($param['tipo_documento_financeiro_id'] ?? 0),
                'vencimento' => $vencimento ?? '',
                'quitacao' => (string) ($param['quitacao'] ?? ''),
            ]);
            TScript::create('$("#' . self::$formName . '").closest(".ui-dialog").remove();');
            TToast::show('success', 'Financeiro do agendamento gerado', 'topRight', 'far:check-circle');
            TApplication::loadPage('AgendamentoFormView', 'onShow', ['key' => $agendamentoId, 'current_tab' => 2]); // volta na aba Financeiro
        } catch (Throwable $e) {
            new TMessage('error', LevantamentoFinanceiroForm::mensagem($e));
        }
    }

    /** Pergunta antes de desfazer (chamado pelo botao da tela do agendamento). */
    public static function onDesfazer($param)
    {
        try {
            $agendamentoId = (int) ($param['key'] ?? 0);
            if ($motivo = AgendamentoFinanceiroService::motivoBloqueioDesfazer($agendamentoId)) {
                throw new Exception($motivo);
            }
            new TQuestion('Desfazer o financeiro deste agendamento? A conta a receber gerada será excluída.',
                new TAction([__CLASS__, 'onDesfazerConfirmado'], ['key' => $agendamentoId]));
        } catch (Throwable $e) {
            new TMessage('error', LevantamentoFinanceiroForm::mensagem($e));
        }
    }

    public static function onDesfazerConfirmado($param)
    {
        try {
            $agendamentoId = (int) ($param['key'] ?? 0);
            AgendamentoFinanceiroService::desfazer($agendamentoId);
            TToast::show('success', 'Financeiro do agendamento desfeito', 'topRight', 'far:check-circle');
            TApplication::loadPage('AgendamentoFormView', 'onShow', ['key' => $agendamentoId, 'current_tab' => 2]); // volta na aba Financeiro
        } catch (Throwable $e) {
            new TMessage('error', LevantamentoFinanceiroForm::mensagem($e));
        }
    }

    private static function opcoes(string $sql): array
    {
        $lista = [];
        foreach (TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_OBJ) as $l) { $lista[(int) $l->id] = $l->nome; }
        return $lista;
    }

    /** '1.234,56' / '1234.56' => float; vazio => null; invalido => erro. */
    private static function valor($v): ?float
    {
        $t = trim(str_replace(['R$', ' '], '', (string) $v));
        if ($t === '') { return null; }
        if (strpos($t, ',') !== false) { $t = str_replace(',', '.', str_replace('.', '', $t)); }
        if (!preg_match('/^\d+(\.\d+)?$/', $t)) { throw new Exception("Valor inválido: '" . trim((string) $v) . "'."); }
        return (float) $t;
    }

    /** dd/mm/aaaa ou aaaa-mm-dd => aaaa-mm-dd; vazio => null. */
    private static function data($v): ?string
    {
        $t = trim((string) $v);
        if ($t === '') { return null; }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $t, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
        return substr($t, 0, 10);
    }
}
