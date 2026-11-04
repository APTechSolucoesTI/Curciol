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

        if (empty($param['token']))
        {
            new TMessage('error', 'Token não informado', new TAction(['Home', 'onShow']));
            die;
        }

        $token = AgendamentoService::decode($param['token']);

        if (empty($token) || empty($token->agendamento_id) || $token->agendamento_id != ($param['key'] ?? null))
        {
            new TMessage('error', 'Token inválido', new TAction(['Home', 'onShow']));
            die;
        }
    }

    /** A pagina e montada depois da resposta, para mostrar a situacao ja atualizada. */
    private function montar($param, ?array $aviso = null): void
    {
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

        if ($aviso)
        {
            $caixa = new TElement('div');
            $caixa->class = 'alert alert-' . $aviso['tipo'];
            $caixa->add('<b style="font-size:16px">' . htmlspecialchars($aviso['texto']) . '</b><br>Se precisar alterar algo, entre em contato com o escritório pelo WhatsApp.');
            $this->form->addFields([$caixa])->layout = [' col-sm-12'];
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
        $this->montar($param);
    }

    /** Links do e-mail: abrir ja confirma ou cancela e a pagina avisa o resultado. */
    public function onConfirmar($param)
    {
        $this->responder($param, (int) EstadoAgenda::CONFIRMADO);
    }

    public function onCancelar($param)
    {
        $this->responder($param, (int) EstadoAgenda::CANCELADO);
    }

    private function responder($param, int $estado): void
    {
        try
        {
            TTransaction::open(self::$database);

            $aviso = AgendamentoService::respostaPeloLink(Agendamento::find($param['key']), $estado);

            TTransaction::close();

            $this->montar($param, $aviso);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
