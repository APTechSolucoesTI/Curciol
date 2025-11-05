<?php

class AgendamentoView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Agendamento';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Agendamento';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $agendamento = new Agendamento($param['key']);
        // define the form title
        $this->form->setFormTitle("Detalhe do agendamento");

        $transformed_agendamento_estado_agenda_nome = call_user_func(function($value, $object, $row)
        {

            return "<span class='label' style='width:235px;background-color:{$object->estado_agenda->cor}'> {$value} <span> "; 

        }, $agendamento->estado_agenda->nome, $agendamento, null);    

        $transformed_agendamento_link_atendimento_online = call_user_func(function($value, $object, $row)
        {

            if ($value)
            {
                $button = new TElement('a');
                $button->class = 'btn btn-block btn-success';
                $button->target = '_blank';
                $button->href = $object->agenda->escritorio->url_sistema.'/atendimento-' . $object->link_atendimento_online;
                $button->add(TElement::tag('i', '', ['class' => 'fas fa-video']));
                $button->add(TElement::tag('span', 'Link do atendimento'));
                return $button;
            }

            return '';

        }, $agendamento->link_atendimento_online, $agendamento, null);

        if (empty($param['token']))
        {
            new TMessage('error', 'Token não informado', new TAction(['Home', 'onShow']));
            die;
        }

        $token = AgendamentoService::decode($param['token']);

        if (empty($token) || empty($token->agendamento_id) || $token->agendamento_id != $agendamento->id)
        {
            new TMessage('error', 'Token inválido', new TAction(['Home', 'onShow']));
            die;
        }

        $label2 = new TLabel("Código:", '', '14px', 'B', '100%');
        $text1 = new TTextDisplay(new TImage('fab:slack-hash #009688').$agendamento->id, '', '14px', '');
        $label21 = new TLabel("Situação:", '', '14px', 'B', '100%');
        $text7s = new TTextDisplay($transformed_agendamento_estado_agenda_nome, '', '14px', '');
        $label4 = new TLabel("Cliente:", '', '14px', 'B', '100%');
        $text2 = new TTextDisplay(new TImage('fas:user-alt #2196F3').$agendamento->cliente->nome_formatado, '', '14px', '');
        $label6 = new TLabel("Data:", '', '14px', 'B', '100%');
        $text6 = new TTextDisplay(new TImage('fas:calendar-day #F44336').TDateTime::convertToMask($agendamento->dt_inicial, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '18px', '');
        $label12 = new TLabel("Profissional:", '', '14px', 'B', '100%');
        $text181 = new TTextDisplay(new TImage('fas:user-md #3F51B5').$agendamento->agenda->profissional->nome_formatado, '', '14px', '');
        $label8 = new TLabel("Observação:", '', '14px', 'B', '100%');
        $text9 = new TTextDisplay($agendamento->observacao, '', '12px', '');
        $label10 = new TLabel("Especialidade:", '', '14px', 'B', '100%');
        $text5 = new TTextDisplay($agendamento->especialidade->descricao, '', '14px', '');
        $text18 = new TTextDisplay($transformed_agendamento_link_atendimento_online, '', '12px', '');
        $text22 = new TTextDisplay(new TImage('far:building #FF9800').$agendamento->agenda->escritorio->nome, '', '14px', 'B');
        $text26 = new TTextDisplay(new TImage('fas:map-pin #8BC34A').$agendamento->agenda->escritorio->endereco_formatado, '', '12px', '');
        $text7 = new TTextDisplay(new TImage('far:envelope #F44336').$agendamento->agenda->escritorio->email, '', '12px', '');
        $text9a = new TTextDisplay(new TImage('fas:phone-alt #9C27B0').$agendamento->agenda->escritorio->telefone, '', '12px', '');

        $text7->setSize('100%');
        $text22->setSize('100%');
        $text26->setSize('100%');
        $text9a->setSize('100%');


        $row1 = $this->form->addFields([$label2,$text1],[$label21,$text7s],[$label4,$text2]);
        $row1->layout = [' col-sm-2',' col-sm-4','col-sm-6'];

        $row2 = $this->form->addFields([$label6,$text6],[$label12,$text181]);
        $row2->layout = [' col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([$label8,$text9],[$label10,$text5]);
        $row3->layout = [' col-sm-6','col-sm-6'];

        $row4 = $this->form->addFields([$text18]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([$text22],[$text26],[$text7],[$text9a]);
        $row5->layout = [' col-sm-12',' col-sm-12',' col-sm-12',' col-sm-12'];

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        }
        $container->add($this->form);

        TTransaction::close();
        parent::add($container);

    }

    public function onShow($param = null)
    {     

    }

    public function onConfirmar($param)
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['key']);

            if (in_array($agendamento->estado_agenda_id, [EstadoAgenda::AGENDADO, EstadoAgenda::AGUARDANDO_VALIDACAO_ESCRITORIO]))
            {
                $agendamento->estado_agenda_id = EstadoAgenda::CONFIRMADO;
                $agendamento->store();

                $historico = new EstadoAgendamento();
                $historico->agendamento_id = $agendamento->id;
                $historico->estado_agenda_id = $agendamento->estado_agenda_id;
                $historico->atribuido_em = date('Y-m-d H:i:s');
                $historico->store();

                TToast::show("success", 'Agendamento confirmado', "topRight", "fas:check");

                TApplication::loadPage('AgendamentoView', 'onShow', ['key' => $param['key'], 'token' => $param['token']]);
            }

            TTransaction::close();    
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onCancelar($param)
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['key']);

            if ($agendamento->estado_agenda->estado_final == 'S')
            {
                throw new Exception('Esse agendamento já está finalizado!');
            }

            if($agendamento->estado_agenda_id == EstadoAgenda::CONFIRMADO)
            {
                throw new Exception('Não é possível cancelar um agendamento confirmado. Entre em contato com a clínica!');
            }

            $agendamento->estado_agenda_id = EstadoAgenda::CANCELADO;
            $agendamento->store();

            $historico = new EstadoAgendamento();
            $historico->agendamento_id = $agendamento->id;
            $historico->estado_agenda_id = $agendamento->estado_agenda_id;
            $historico->atribuido_em = date('Y-m-d H:i:s');
            $historico->store();

            TToast::show("success", 'Agendamento cancelado', "topRight", "fas:check");

            TApplication::loadPage('AgendamentoView', 'onShow', ['key' => $param['key'], 'token' => $param['token']]);

            TTransaction::close();    
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}

