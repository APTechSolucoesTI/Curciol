<?php

/**
 * Excecao da agenda (05/11/2026): libera um periodo que esta em bloqueio, intervalo ou nao atende, para marcar agendamentos e
 * compromissos pelas telas normais. Nova: uma por agenda marcada. Editando: so a observacao; para mudar o periodo, remove e
 * cria de novo (assim o que ja esta marcado dentro dela nunca fica de fora sem aviso).
 */
class AgendaExcecaoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private static $database = 'escritorio';
    private static $formName = 'form_AgendaExcecao';

    public function __construct($param)
    {
        parent::__construct();

        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('Exceção da agenda');

        $criteria_agenda_id = new TCriteria();
        $unidade = TSession::getValue('userunitid');
        $criteria_agenda_id->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$unidade}')"));

        $usuario = TSession::getValue('userid');
        $criteriaAcesso = new TCriteria;
        $criteriaAcesso->add(new TFilter('id', 'in', "(SELECT agenda.id FROM agenda, agenda_profissional, pessoa WHERE pessoa.id = agenda_profissional.profissional_id AND agenda.id = agenda_profissional.agenda_id AND system_users_id = '{$usuario}')"));
        $criteriaAcesso->add(new TFilter('id', 'in', "(SELECT agenda.id FROM agenda, pessoa WHERE pessoa.id = agenda.profissional_id AND system_users_id = '{$usuario}')"), TExpression::OR_OPERATOR);
        $criteria_agenda_id->add($criteriaAcesso);

        $editando = AgendaSelecaoMultipla::editando($param);

        $id = new TEntry('id');
        $agenda_id = new TDBCombo('agenda_id', 'escritorio', 'Agenda', 'id', '{nome}', 'nome asc', $criteria_agenda_id);
        $dt_inicio = new TDateTime('dt_inicio');
        $dt_final = new TDateTime('dt_final');
        $observacao = new TText('observacao');

        $dt_inicio->setMask('dd/mm/yyyy hh:ii');
        $dt_final->setMask('dd/mm/yyyy hh:ii');
        $dt_inicio->setDatabaseMask('yyyy-mm-dd hh:ii');
        $dt_final->setDatabaseMask('yyyy-mm-dd hh:ii');
        $observacao->addValidation('Observação', new TRequiredValidator());

        $id->setEditable(false);
        $id->setSize(100);
        $dt_inicio->setSize(150);
        $dt_final->setSize(150);
        $agenda_id->setSize('100%');
        $observacao->setSize('100%', 70);

        $this->form->addFields([new TLabel('Código:', null, '14px', null, '100%'), $id]);

        if ($editando)
        {
            $agenda_id->setEditable(false);
            $dt_inicio->setEditable(false);
            $dt_final->setEditable(false);
            $linha = $this->form->addFields([new TLabel('Agenda:', null, '14px', null, '100%'), $agenda_id], [new TLabel('Início:', null, '14px', null, '100%'), $dt_inicio], [new TLabel('Fim:', null, '14px', null, '100%'), $dt_final]);
            $linha->layout = [' col-sm-6', ' col-sm-3', ' col-sm-3'];
        }
        else
        {
            $dt_inicio->addValidation('Início', new TRequiredValidator());
            $dt_final->addValidation('Fim', new TRequiredValidator());
            $linha = $this->form->addFields([new TLabel('Agendas:', '#ff0000', '14px', null, '100%'), AgendaSelecaoMultipla::campo($criteria_agenda_id, '', $param)]);
            $linha->layout = [' col-sm-12'];
            $linha = $this->form->addFields([new TLabel('Início:', '#ff0000', '14px', null, '100%'), $dt_inicio], [new TLabel('Fim:', '#ff0000', '14px', null, '100%'), $dt_final]);
            $linha->layout = [' col-sm-3', ' col-sm-3'];
        }

        $linha = $this->form->addFields([new TLabel('Observação:', '#FF0000', '14px', null, '100%'), $observacao]);
        $linha->layout = [' col-sm-12'];

        $ajuda = $editando
            ? 'Para mudar a agenda ou o período, remova esta exceção e crie outra.'
            : 'A exceção só pode ser criada dentro de bloqueio, intervalo ou não atende. Durante ela, é possível marcar agendamentos e compromissos.';
        $linha = $this->form->addFields([new TLabel(new TImage('fas:info-circle #03A9F4') . ' ' . $ajuda, '#607D8B', '12px', 'I')]);
        $linha->layout = [' col-sm-12'];

        $btnSalvar = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $btnSalvar->addStyleClass('btn-primary');

        if ($editando)
        {
            $this->form->addAction('Remover exceção', new TAction([__CLASS__, 'onRemover'], ['static' => 1]), 'fas:trash-alt #FF5722');
        }

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = 'Template.closeRightPanel();';
        $btnClose->setLabel('Fechar');
        $btnClose->setImage('fas:times');
        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AgendaExcecaoForm]');
        $style->width = '70% !important';
        $style->show(true);
    }

    public function onSave($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $this->form->validate();
            $data = $this->form->getData();
            $gravada = !empty($data->id) ? AgendaExcecao::find((int) $data->id) : null;
            $quantidade = 1;

            if ($gravada)
            {
                $gravada->observacao = $data->observacao;
                $gravada->modificacao_user_id = TSession::getValue('userid');
                $gravada->store();
            }
            else
            {
                $agendas = AgendaSelecaoMultipla::marcadas((array) $param);
                $quantidade = count($agendas);
                foreach ($agendas as $agenda)
                {
                    AgendaConflitoService::exigirPeriodoDeExcecao((int) $agenda, (string) $data->dt_inicio, (string) $data->dt_final);
                    $excecao = new AgendaExcecao();
                    $excecao->agenda_id = (int) $agenda;
                    $excecao->dt_inicio = $data->dt_inicio;
                    $excecao->dt_final = $data->dt_final;
                    $excecao->observacao = $data->observacao;
                    $excecao->criacao_user_id = TSession::getValue('userid');
                    $excecao->store();
                }
            }

            TTransaction::close();

            TToast::show('success', $quantidade > 1 ? "{$quantidade} exceções salvas, uma em cada agenda marcada" : 'Registro salvo', 'topRight', 'far:check-circle');
            self::voltarParaAgenda();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
            TTransaction::rollback();
        }
    }

    /**
     * Remover: o que esta marcado dentro dela ficaria em periodo sem atendimento. Confirmado ou em atendimento impede; o resto
     * pede confirmacao e continua na agenda.
     */
    public static function onRemover($param = null)
    {
        try
        {
            TTransaction::open(self::$database);
            $excecao = new AgendaExcecao((int) ($param['id'] ?? 0));
            $marcados = AgendaConflitoService::sobreposicoesDoBloqueio([[(int) $excecao->agenda_id, (string) $excecao->dt_inicio, (string) $excecao->dt_final]]);
            TTransaction::close();

            if ($marcados['impedem'])
            {
                throw new Exception('Não é possível remover esta exceção porque há agendamento confirmado ou em atendimento dentro dela:<br><br>' . AgendaConflitoService::listaParaAviso($marcados['impedem']));
            }

            $pergunta = 'Deseja remover esta exceção?';
            if ($marcados['avisam'])
            {
                $pergunta = 'Há horários marcados dentro desta exceção:<br><br>' . AgendaConflitoService::listaParaAviso($marcados['avisam']) . '<br><br>Eles continuam na agenda, mas ficarão em período sem atendimento. Deseja remover mesmo assim?';
            }
            new TQuestion($pergunta, new TAction([__CLASS__, 'onRemoverConfirmado'], ['id' => (int) $excecao->id, 'static' => 1]), null, 'Remover exceção', 'Remover', 'Não remover');
        }
        catch (Exception $e)
        {
            TTransaction::rollbackAll();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onRemoverConfirmado($param = null)
    {
        try
        {
            TTransaction::open(self::$database);
            (new AgendaExcecao((int) ($param['id'] ?? 0)))->delete();
            TTransaction::close();

            TToast::show('success', 'Exceção removida', 'topRight', 'fas:check');
            self::voltarParaAgenda();
        }
        catch (Exception $e)
        {
            TTransaction::rollbackAll();
            new TMessage('error', $e->getMessage());
        }
    }

    private static function voltarParaAgenda(): void
    {
        TScript::create("if ($('#agendamentos').data('fullcalendar')) { $('#agendamentos').data('fullcalendar').refetchEvents(); }");
        TScript::create('Template.closeRightPanel();');
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                TTransaction::open(self::$database);
                $this->form->setData(new AgendaExcecao((int) $param['key']));
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {
    }

    public static function getFormName()
    {
        return self::$formName;
    }
}
