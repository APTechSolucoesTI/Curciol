<?php

class BloqueioForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Bloqueio';
    private static $primaryKey = 'id';
    private static $formName = 'form_Bloqueio';

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

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Bloqueio");

        $criteria_agenda_id = new TCriteria();

        $filterVar = TSession::getValue("userunitid");
        $criteria_agenda_id->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')")); 

        $filterVar = TSession::getValue('userid');

        $criteriaAcesso = new TCriteria;
        // está como um profissional relacionado com a agenda
        $criteriaAcesso->add(new TFilter('id', 'in', "(SELECT agenda.id FROM agenda, agenda_profissional, pessoa WHERE pessoa.id = agenda_profissional.profissional_id AND agenda.id = agenda_profissional.agenda_id AND system_users_id = '{$filterVar}')"));
        // oyu é o profissional responsável da agenda
        $criteriaAcesso->add(new TFilter('id', 'in', "(SELECT agenda.id FROM agenda, pessoa WHERE pessoa.id = agenda.profissional_id AND system_users_id = '{$filterVar}')"), TExpression::OR_OPERATOR); 

        $criteria_agenda_id->add($criteriaAcesso);

        $id = new TEntry('id');
        $agenda_id = new TDBCombo('agenda_id', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_agenda_id );
        $dt_inicio = new TDateTime('dt_inicio');
        $dt_final = new TDateTime('dt_final');
        $observacao = new TText('observacao');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');
        $habilitar_repeticao = new TRadioGroup('habilitar_repeticao');
        $tipo_repeticao = new TCombo('tipo_repeticao');
        $repetir_ate = new TDate('repetir_ate');
        $quantidade_repeticoes = new TSpinner('quantidade_repeticoes');

        $habilitar_repeticao->setChangeAction(new TAction([$this,'onChangeRepeticao']));

        $dt_inicio->setExitAction(new TAction([$this,'onChangeDate']));
        $repetir_ate->setExitAction(new TAction([$this,'changeDataRepeticao']));

        $agenda_id->addValidation("Agenda", new TRequiredValidator()); 
        $dt_inicio->addValidation("Início", new TRequiredValidator()); 
        $dt_final->addValidation("Fim", new TRequiredValidator()); 
        $observacao->addValidation("Observação", new TRequiredValidator()); 

        $habilitar_repeticao->addItems(["S"=>"Sim","N"=>"Não"]);
        $habilitar_repeticao->setLayout('horizontal');
        $habilitar_repeticao->setValue('N');
        $habilitar_repeticao->setUseButton();
        $tipo_repeticao->enableSearch();
        $quantidade_repeticoes->setRange(1, 2000, 1);
        $repetir_ate->setMask('dd/mm/yyyy');
        $dt_final->setMask('dd/mm/yyyy hh:ii');
        $dt_inicio->setMask('dd/mm/yyyy hh:ii');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $repetir_ate->setDatabaseMask('yyyy-mm-dd');
        $dt_final->setDatabaseMask('yyyy-mm-dd hh:ii');
        $dt_inicio->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $repetir_ate->setEditable(false);
        $data_criacao->setEditable(false);
        $tipo_repeticao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $dt_final->setSize(150);
        $dt_inicio->setSize(150);
        $agenda_id->setSize('100%');
        $repetir_ate->setSize('100%');
        $data_criacao->setSize('100%');
        $observacao->setSize('100%', 70);
        $tipo_repeticao->setSize('100%');
        $habilitar_repeticao->setSize(80);
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $quantidade_repeticoes->setSize('100%');

        $quantidade_repeticoes->setExitAction(new TAction([$this, 'onChangeQuantidade']));

        $this->form->appendPage("Informações gerais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Agenda:", '#ff0000', '14px', null, '100%'),$agenda_id],[new TLabel("Início:", '#ff0000', '14px', null, '100%'),$dt_inicio],[new TLabel("Fim:", '#ff0000', '14px', null, '100%'),$dt_final]);
        $row2->layout = [' col-sm-6',' col-sm-3',' col-sm-3'];

        $row3 = $this->form->addFields([new TLabel("Observação:", '#FF0000', '14px', null, '100%'),$observacao]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row5 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row5->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        $this->form->appendPage("Repetições");
        $row6 = $this->form->addFields([new TLabel("Habilitar repetições:", null, '14px', null, '100%'),$habilitar_repeticao]);
        $row6->layout = [' col-sm-12'];

        $row7 = $this->form->addFields([new TLabel("Tipo de repetição:", null, '14px', null, '100%'),$tipo_repeticao],[new TLabel("Repetir até:", null, '14px', null, '100%'),$repetir_ate,new TLabel("<b>Atenção:</b> <small>Informe uma data ou uma quantidade</small>", '#FF0000', '14px', null)],[new TLabel("Quantidade:", null, '14px', null, '100%'),$quantidade_repeticoes]);
        $row7->layout = [' col-sm-6','col-sm-3',' col-sm-3'];

        // create the form actions
        $btnSalvar = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btnSalvar = $btnSalvar;
        $btnSalvar->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_ondeletebloqueio = $this->form->addAction("Remover Bloqueio", new TAction([$this, 'onDeleteBloqueio']), 'fas:trash-alt #FF5722');
        $this->btn_ondeletebloqueio = $btn_ondeletebloqueio;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        $btnSalvar->getAction()->setParameter('origin', $param['origin']??'');

        if (!empty($param['key']))
        {
            TScript::create("$('[data-current_page=2]').hide()");
        }

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=BloqueioForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChangeDate($param = null) 
    {
        try 
        {
            $options = [];

            if (! empty($param['dt_inicio']))
            {
                $date = $param['dt_inicio'];
                $url_parts = explode(" ", $date);
                $date = $url_parts[0];
                $date = implode('-', array_reverse(explode('/', $date)));

                $options['TD']  = 'Todos os dias';
                $options['TDU'] = 'Todos os dias utéis';
                $options['S']   = 'Semanal a cada: ' . DateService::getDayWeek($date);
                $options['M']   = 'Toda a '. DateService::getWeekOfMonth($date) . ' do mês';
                $options['A']   = 'Anual em ' . date('d', strtotime($date)) . ' de ' . DateService::getMonthName($date);
            }

            TCombo::reload(self::$formName, 'tipo_repeticao', $options, true, false);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function changeDataRepeticao($param = null) 
    {
        try 
        {
            if (!empty($param['repetir_ate']))
            {
                $object = new stdClass();
                $object->quantidade_repeticoes = '';

                TForm::sendData(self::$formName, $object, false, false);
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeRepeticao($param = null) 
    {
        try 
        {
            if (!empty($param['habilitar_repeticao']) && $param['habilitar_repeticao'] == 'S')
            {
                TDate::enableField(self::$formName, 'repetir_ate');
                TCombo::enableField(self::$formName, 'tipo_repeticao');
                TSpinner::enableField(self::$formName, 'quantidade_repeticoes');
            }
            else
            {
                TDate::disableField(self::$formName, 'repetir_ate');
                TCombo::disableField(self::$formName, 'tipo_repeticao');
                TSpinner::disableField(self::$formName, 'quantidade_repeticoes');
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Bloqueio(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $new = empty($object->id) || empty(Bloqueio::find($object->id));

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            if ($new)
            {
                $this->clonar($object, $data);
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");
                        TScript::create("Template.closeRightPanel();"); 

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    public static function onDeleteBloqueio($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $bloqueio = new Bloqueio($param['id']);
            $bloqueio->delete();

            TTransaction::close();

            TToast::show("success",'Bloqueio removido', "topRight", "fas:check");
            TApplication::loadPage('AgendamentosFilterForm', 'onShow');
            TScript::create("Template.closeRightPanel();");
            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Bloqueio($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->form->setData($object); // fill the form 

                TTransaction::close(); // close the transaction 
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);

    }

    public function onShow($param = null)
    {

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    private function clonar($object, $data)
    {
        if ($data->habilitar_repeticao == 'S')
        {
            $inicio = substr($object->dt_inicio, 0, 10);

            if ($data->repetir_ate)
            {
                if ($inicio >= $data->repetir_ate)
                {
                    throw new Exception('O limite de repetições deve ser maior que a data do agendamento');
                }

                if($data->tipo_repeticao == 'TD' || $data->tipo_repeticao == 'TDU')
                {
                    $repeticoes = DateService::gerarRepeticoesDiarias(
                        $inicio,
                        $data->repetir_ate,
                        (
                            $data->tipo_repeticao == 'TD' ? 
                                [0, 1, 2, 3, 4, 5, 6] :
                                [1, 2, 3, 4, 5]
                        )
                    );
                }
                else if($data->tipo_repeticao == 'S')
                {
                    $repeticoes = DateService::gerarRepeticoesSemanais(
                        $inicio,
                        $data->repetir_ate,
                        [['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][date('w', strtotime($inicio))]]
                    );
                }
                else if($data->tipo_repeticao == 'A')
                {
                    $repeticoes = DateService::gerarRepeticoesAnuais(
                        $inicio,
                        $data->repetir_ate
                    );
                    throw new Exception($inicio . '==' .json_encode($repeticoes));
                }
                else
                {
                    $repeticoes = DateService::gerarRepeticoesMensais(
                        $inicio,
                        $data->repetir_ate,
                        ($data->tipo_repeticao != 'M' ? DateService::DAY_OF_MONTH : DateService::DAY_OF_WEEK )
                    );
                }
            }
            else if ($data->quantidade_repeticoes && $data->quantidade_repeticoes > 0)
            {
                if($data->tipo_repeticao == 'TD' || $data->tipo_repeticao == 'TDU')
                {
                    $repeticoes = DateService::gerarRepeticoesDiariasByQuantidade(
                        $inicio,
                        $data->quantidade_repeticoes,
                        (
                            $data->tipo_repeticao == 'TD' ? 
                                [0, 1, 2, 3, 4, 5, 6] :
                                [1, 2, 3, 4, 5]
                        )
                    );
                }
                else if($data->tipo_repeticao == 'S')
                {
                    $repeticoes = DateService::gerarRepeticoesSemanaisByQuantidade(
                        $inicio,
                        $data->quantidade_repeticoes,
                        [['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][date('w', strtotime($inicio))]]
                    );
                }
                else if($data->tipo_repeticao == 'A')
                {
                    $repeticoes = DateService::gerarRepeticoesAnuaisByQuantidade(
                        $inicio,
                        $data->quantidade_repeticoes
                    );
                    throw new Exception($inicio . '==' .json_encode($repeticoes));
                }
                else
                {
                    $repeticoes = DateService::gerarRepeticoesMensaisByQuantidade(
                        $inicio,
                        $data->quantidade_repeticoes,
                        ($data->tipo_repeticao != 'M' ? DateService::DAY_OF_MONTH : DateService::DAY_OF_WEEK )
                    );
                }
            }
            else
            {
                throw new Exception('Repetições ativadas!<br/>Informe uma data de limite ou uma quantida de repetições');
            }

            if (!empty($repeticoes))
            {
                foreach($repeticoes as $data)
                {
                    $this->clonarBloqueio($object, $data);
                }
            }
        }
    }

    public static function onChangeQuantidade($param)
    {
        if (!empty($param['quantidade_repeticoes']))
        {
            $object = new stdClass();
            $object->repetir_ate = '';

            TForm::sendData(self::$formName, $object, false, false);
        }    
    }

    public function clonarBloqueio($object, $novaData)
    {
        $newObject = clone $object;
        unset($newObject->id);

        $hora_inicio = date('H:i', strtotime($newObject->dt_inicio));

        $minutesDiff = (strtotime($newObject->dt_final) - strtotime($newObject->dt_inicio)) / 60;

        $newObject->dt_inicio = "{$novaData} {$hora_inicio}";
        $newObject->dt_final = date('Y-m-d H:i:s', strtotime("{$newObject->dt_inicio} +{$minutesDiff} minutes"));
        //$newObject->agendamento_original_id = $object->id;

        $newObject->store();

    }

}

