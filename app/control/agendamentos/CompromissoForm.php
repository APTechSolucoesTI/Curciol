<?php

class CompromissoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Compromisso';
    private static $primaryKey = 'id';
    private static $formName = 'form_CompromissoForm';

    use BuilderMasterDetailFieldListTrait;

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
        $this->form->setFormTitle("Cadastro de compromisso");

        $criteria_tipo_compromisso_id = new TCriteria();
        $criteria_agenda_id = new TCriteria();
        $criteria_convidado_compromisso_compromisso_agenda_id = new TCriteria();

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
        $tipo_compromisso_id = new TDBCombo('tipo_compromisso_id', 'escritorio', 'TipoCompromisso', 'id', '{nome}','nome asc' , $criteria_tipo_compromisso_id );
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
        $convidado_compromisso_compromisso_id = new THidden('convidado_compromisso_compromisso_id[]');
        $convidado_compromisso_compromisso___row__id = new THidden('convidado_compromisso_compromisso___row__id[]');
        $convidado_compromisso_compromisso___row__data = new THidden('convidado_compromisso_compromisso___row__data[]');
        $convidado_compromisso_compromisso_agenda_id = new TDBCombo('convidado_compromisso_compromisso_agenda_id[]', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_convidado_compromisso_compromisso_agenda_id );
        $this->fieldList_673c8dedfbf3a = new TFieldList();

        $this->fieldList_673c8dedfbf3a->addField(null, $convidado_compromisso_compromisso_id, []);
        $this->fieldList_673c8dedfbf3a->addField(null, $convidado_compromisso_compromisso___row__id, ['uniqid' => true]);
        $this->fieldList_673c8dedfbf3a->addField(null, $convidado_compromisso_compromisso___row__data, []);
        $this->fieldList_673c8dedfbf3a->addField(new TLabel("Convidado", null, '14px', null), $convidado_compromisso_compromisso_agenda_id, ['width' => '100%']);

        $this->fieldList_673c8dedfbf3a->width = '100%';
        $this->fieldList_673c8dedfbf3a->setFieldPrefix('convidado_compromisso_compromisso');
        $this->fieldList_673c8dedfbf3a->name = 'fieldList_673c8dedfbf3a';

        $this->criteria_fieldList_673c8dedfbf3a = new TCriteria();
        $this->default_item_fieldList_673c8dedfbf3a = new stdClass();

        $this->form->addField($convidado_compromisso_compromisso_id);
        $this->form->addField($convidado_compromisso_compromisso___row__id);
        $this->form->addField($convidado_compromisso_compromisso___row__data);
        $this->form->addField($convidado_compromisso_compromisso_agenda_id);

        $this->fieldList_673c8dedfbf3a->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $habilitar_repeticao->setChangeAction(new TAction([$this,'onChangeRepeticao']));

        $dt_inicio->setExitAction(new TAction([$this,'onChangeDate']));
        $repetir_ate->setExitAction(new TAction([$this,'changeDataRepeticao']));

        $tipo_compromisso_id->addValidation("Tipo compromisso id", new TRequiredValidator()); 
        $agenda_id->addValidation("Agenda", new TRequiredValidator()); 
        $dt_inicio->addValidation("Início", new TRequiredValidator()); 
        $dt_final->addValidation("Fim", new TRequiredValidator()); 

        $habilitar_repeticao->addItems(["S"=>"Sim","N"=>"Não"]);
        $habilitar_repeticao->setLayout('horizontal');
        $habilitar_repeticao->setValue('N');
        $habilitar_repeticao->setUseButton();
        $quantidade_repeticoes->setRange(1, 2000, 1);
        $agenda_id->enableSearch();
        $tipo_repeticao->enableSearch();
        $tipo_compromisso_id->enableSearch();
        $convidado_compromisso_compromisso_agenda_id->enableSearch();

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
        $tipo_repeticao->setSize('100%');
        $observacao->setSize('100%', 100);
        $habilitar_repeticao->setSize(80);
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $tipo_compromisso_id->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $quantidade_repeticoes->setSize('100%');
        $convidado_compromisso_compromisso_agenda_id->setSize('100%');

        $quantidade_repeticoes->setExitAction(new TAction([$this, 'onChangeQuantidade']));

        $this->form->appendPage("Informações gerais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Tipo de compromisso:", '#ff0000', '14px', null, '100%'),$tipo_compromisso_id]);
        $row2->layout = [' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Agenda:", '#ff0000', '14px', null, '100%'),$agenda_id],[new TLabel("Início:", '#ff0000', '14px', null, '100%'),$dt_inicio],[new TLabel("Fim:", '#ff0000', '14px', null, '100%'),$dt_final]);
        $row3->layout = [' col-sm-6',' col-sm-3',' col-sm-3'];

        $row4 = $this->form->addFields([new TLabel("Observação:", null, '14px', null, '100%'),$observacao]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $this->form->appendPage("Repetições");
        $row7 = $this->form->addFields([new TLabel("Habilitar repetições:", null, '14px', null, '100%'),$habilitar_repeticao]);
        $row7->layout = [' col-sm-12'];

        $row8 = $this->form->addFields([new TLabel("Tipo de repetição:", null, '14px', null, '100%'),$tipo_repeticao],[new TLabel("Repetir até:", null, '14px', null, '100%'),$repetir_ate,new TLabel("<b>Atenção:</b> <small>Informe uma data ou uma quantidade</small>", '#FF0000', '14px', null)],[new TLabel("Quantidade:", null, '14px', null, '100%'),$quantidade_repeticoes]);
        $row8->layout = [' col-sm-6','col-sm-3',' col-sm-3'];

        $this->form->appendPage("Convidados");
        $row9 = $this->form->addFields([$this->fieldList_673c8dedfbf3a]);
        $row9->layout = [' col-sm-12'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_ondeletecompromisso = $this->form->addAction("Remover Compromisso", new TAction([$this, 'onDeleteCompromisso']), 'fas:trash-alt #FF5722');
        $this->btn_ondeletecompromisso = $btn_ondeletecompromisso;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        $btn_onsave->getAction()->setParameter('origin', $param['origin']??'');

        if (!empty($param['key']))
        {
            TScript::create("$('[data-current_page=2]').hide()");
        }

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=CompromissoForm]');
        $style->width = '60% !important';   
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

            $object = new Compromisso(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $new = empty($object->id) || empty(Compromisso::find($object->id));

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            $convidado_compromisso_compromisso_items = $this->storeItems('ConvidadoCompromisso', 'compromisso_id', $object, $this->fieldList_673c8dedfbf3a, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_673c8dedfbf3a); 

            if ($new)
            {
                $this->clonar($object, $data);
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('AgendamentosFilterForm', 'onShow', $loadPageParam); 

            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");
                        TScript::create("Template.closeRightPanel();");
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    public static function onDeleteCompromisso($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $bloqueio = new Compromisso($param['id']);
            ConvidadoCompromisso::where('compromisso_id','=',$param['id'])->delete();
            $bloqueio->delete();

            TTransaction::close();

            TToast::show("success", 'Compromisso removido', "topRight", "fas:check");
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

                $object = new Compromisso($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->fieldList_673c8dedfbf3a_items = $this->loadItems('ConvidadoCompromisso', 'compromisso_id', $object, $this->fieldList_673c8dedfbf3a, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_673c8dedfbf3a); 

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

        $this->fieldList_673c8dedfbf3a->addHeader();
        $this->fieldList_673c8dedfbf3a->addDetail($this->default_item_fieldList_673c8dedfbf3a);

        $this->fieldList_673c8dedfbf3a->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_673c8dedfbf3a->addHeader();
        $this->fieldList_673c8dedfbf3a->addDetail($this->default_item_fieldList_673c8dedfbf3a);

        $this->fieldList_673c8dedfbf3a->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    public  function removerHorario($param = null) 
    {
        try 
        {
            $object = new stdClass();
            $object->dt_inicio = '';
            $object->dt_final = '';

            TForm::sendData(self::$formName, $object);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
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

