<?php

class AgendamentoFormBtn extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Agendamento';
    private static $primaryKey = 'id';
    private static $formName = 'form_AgendamentoFormBtn';

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
        $this->form->setFormTitle("Agendamento");

        $criteria_agenda_id = new TCriteria();
        $criteria_especialidade_id = new TCriteria();
        $criteria_cliente_id = new TCriteria();
        $criteria_agendamento_procedimento_agendamento_parceiro_id = new TCriteria();
        $criteria_agendamento_procedimento_agendamento_procedimento_id = new TCriteria();
        $criteria_convidado_agendamento_agenda_id = new TCriteria();

        $filterVar = TSession::getValue("userunitid");
        $criteria_agenda_id->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')")); 
        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $id = new TEntry('id');
        $agenda_id = new TDBCombo('agenda_id', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_agenda_id );
        $agenda_profissional_nome = new TEntry('agenda_profissional_nome');
        $especialidade_id = new TDBCombo('especialidade_id', 'escritorio', 'Especialidade', 'id', '{descricao}','descricao asc' , $criteria_especialidade_id );
        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $btnHorarios = new TButton('btnHorarios');
        $mostra_horario = new TEntry('mostra_horario');
        $btnRemoveHorario = new TButton('btnRemoveHorario');
        $dt_inicial = new TDateTime('dt_inicial');
        $dt_final = new TDateTime('dt_final');
        $online = new TRadioGroup('online');
        $agendamento_procedimento_agendamento_id = new THidden('agendamento_procedimento_agendamento_id[]');
        $agendamento_procedimento_agendamento___row__id = new THidden('agendamento_procedimento_agendamento___row__id[]');
        $agendamento_procedimento_agendamento___row__data = new THidden('agendamento_procedimento_agendamento___row__data[]');
        $agendamento_procedimento_agendamento_parceiro_id = new TDBCombo('agendamento_procedimento_agendamento_parceiro_id[]', 'escritorio', 'Parceiro', 'id', '{nome}','nome asc' , $criteria_agendamento_procedimento_agendamento_parceiro_id );
        $agendamento_procedimento_agendamento_procedimento_id = new TDBCombo('agendamento_procedimento_agendamento_procedimento_id[]', 'escritorio', 'Procedimento', 'id', '{nome}','nome asc' , $criteria_agendamento_procedimento_agendamento_procedimento_id );
        $agendamento_procedimento_agendamento_valor = new TNumeric('agendamento_procedimento_agendamento_valor[]', '2', ',', '.' );
        $agendamento_procedimento_agendamento_quantidade = new TNumeric('agendamento_procedimento_agendamento_quantidade[]', '2', ',', '.' );
        $agendamento_procedimento_agendamento_valor_total = new TNumeric('agendamento_procedimento_agendamento_valor_total[]', '2', ',', '.' );
        $this->fieldList_procedimentos = new TFieldList();
        $observacao = new TText('observacao');
        $habilitar_repeticao = new TRadioGroup('habilitar_repeticao');
        $tipo_repeticao = new TCombo('tipo_repeticao');
        $repetir_ate = new TDate('repetir_ate');
        $quantidade_repeticoes = new TSpinner('quantidade_repeticoes');
        $convidado_agendamento_id = new THidden('convidado_agendamento_id[]');
        $convidado_agendamento___row__id = new THidden('convidado_agendamento___row__id[]');
        $convidado_agendamento___row__data = new THidden('convidado_agendamento___row__data[]');
        $convidado_agendamento_agenda_id = new TDBCombo('convidado_agendamento_agenda_id[]', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_convidado_agendamento_agenda_id );
        $this->fieldList_convidados = new TFieldList();

        $this->fieldList_procedimentos->addField(null, $agendamento_procedimento_agendamento_id, []);
        $this->fieldList_procedimentos->addField(null, $agendamento_procedimento_agendamento___row__id, ['uniqid' => true]);
        $this->fieldList_procedimentos->addField(null, $agendamento_procedimento_agendamento___row__data, []);
        $this->fieldList_procedimentos->addField(new TLabel("Parceiro", null, '14px', null), $agendamento_procedimento_agendamento_parceiro_id, ['width' => '25%']);
        $this->fieldList_procedimentos->addField(new TLabel("Procedimento", null, '14px', null), $agendamento_procedimento_agendamento_procedimento_id, ['width' => '25%']);
        $this->fieldList_procedimentos->addField(new TLabel("Valor", null, '14px', null), $agendamento_procedimento_agendamento_valor, ['width' => '15%']);
        $this->fieldList_procedimentos->addField(new TLabel("Quantidade", null, '14px', null), $agendamento_procedimento_agendamento_quantidade, ['width' => '12%']);
        $this->fieldList_procedimentos->addField(new TLabel("Valor total", null, '14px', null), $agendamento_procedimento_agendamento_valor_total, ['width' => '15%']);

        $this->fieldList_procedimentos->width = '100%';
        $this->fieldList_procedimentos->setFieldPrefix('agendamento_procedimento_agendamento');
        $this->fieldList_procedimentos->name = 'fieldList_procedimentos';

        $this->criteria_fieldList_procedimentos = new TCriteria();
        $this->default_item_fieldList_procedimentos = new stdClass();

        $this->form->addField($agendamento_procedimento_agendamento_id);
        $this->form->addField($agendamento_procedimento_agendamento___row__id);
        $this->form->addField($agendamento_procedimento_agendamento___row__data);
        $this->form->addField($agendamento_procedimento_agendamento_parceiro_id);
        $this->form->addField($agendamento_procedimento_agendamento_procedimento_id);
        $this->form->addField($agendamento_procedimento_agendamento_valor);
        $this->form->addField($agendamento_procedimento_agendamento_quantidade);
        $this->form->addField($agendamento_procedimento_agendamento_valor_total);

        $this->fieldList_procedimentos->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $this->fieldList_convidados->addField(null, $convidado_agendamento_id, []);
        $this->fieldList_convidados->addField(null, $convidado_agendamento___row__id, ['uniqid' => true]);
        $this->fieldList_convidados->addField(null, $convidado_agendamento___row__data, []);
        $this->fieldList_convidados->addField(new TLabel("Convidado", null, '14px', null), $convidado_agendamento_agenda_id, ['width' => '100%']);

        $this->fieldList_convidados->width = '100%';
        $this->fieldList_convidados->setFieldPrefix('convidado_agendamento');
        $this->fieldList_convidados->name = 'fieldList_convidados';

        $this->criteria_fieldList_convidados = new TCriteria();
        $this->default_item_fieldList_convidados = new stdClass();

        $this->form->addField($convidado_agendamento_id);
        $this->form->addField($convidado_agendamento___row__id);
        $this->form->addField($convidado_agendamento___row__data);
        $this->form->addField($convidado_agendamento_agenda_id);

        $this->fieldList_convidados->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $agenda_id->setChangeAction(new TAction([$this,'onChange']));
        $agendamento_procedimento_agendamento_parceiro_id->setChangeAction(new TAction([$this,'onChangeParceiro']));
        $agendamento_procedimento_agendamento_procedimento_id->setChangeAction(new TAction([$this,'onChangeProcedimento']));
        $habilitar_repeticao->setChangeAction(new TAction([$this,'onChangeRepeticao']));
        $convidado_agendamento_agenda_id->setChangeAction(new TAction([$this,'verificaConvidado']));

        $agendamento_procedimento_agendamento_valor->setExitAction(new TAction([$this,'onExitValor']));
        $agendamento_procedimento_agendamento_quantidade->setExitAction(new TAction([$this,'onExitQuantidade']));
        $repetir_ate->setExitAction(new TAction([$this,'changeDataRepeticao']));

        $agenda_id->addValidation("Agenda", new TRequiredValidator()); 
        $cliente_id->addValidation("Cliente", new TRequiredValidator()); 
        $agendamento_procedimento_agendamento_parceiro_id->addValidation("Parceiro", new TRequiredListValidator()); 
        $agendamento_procedimento_agendamento_procedimento_id->addValidation("Procedimento", new TRequiredListValidator()); 
        $agendamento_procedimento_agendamento_quantidade->addValidation("Quantidade", new TRequiredListValidator()); 

        $cliente_id->setMinLength(3);
        $quantidade_repeticoes->setRange(1, 2000, 1);
        $btnRemoveHorario->setAction(new TAction(['AgendamentoFormBtn', 'removeHorario']), "");
        $btnHorarios->setAction(new TAction(['ModalBuscarHorariosDisponiveis', 'onShow'],["formName" => self::$formName]), "Horarios disponíveis");

        $btnHorarios->addStyleClass('btn-default');
        $btnRemoveHorario->addStyleClass('btn-danger');

        $btnRemoveHorario->setImage('fas:trash #FFFFFF');
        $btnHorarios->setImage('fas:calendar-day #000000');

        $online->addItems(["T"=>"Online","F"=>"Presencial"]);
        $habilitar_repeticao->addItems(["S"=>"Sim","N"=>"Não"]);

        $online->setLayout('horizontal');
        $habilitar_repeticao->setLayout('horizontal');

        $online->setUseButton();
        $habilitar_repeticao->setUseButton();

        $online->setValue('F');
        $habilitar_repeticao->setValue('N');
        $mostra_horario->setValue('Selecione um horário');

        $repetir_ate->setDatabaseMask('yyyy-mm-dd');
        $dt_final->setDatabaseMask('yyyy-mm-dd hh:ii');
        $dt_inicial->setDatabaseMask('yyyy-mm-dd hh:ii');

        $cliente_id->setMask('{nome}');
        $repetir_ate->setMask('dd/mm/yyyy');
        $dt_final->setMask('dd/mm/yyyy hh:ii');
        $dt_inicial->setMask('dd/mm/yyyy hh:ii');

        $agenda_id->enableSearch();
        $tipo_repeticao->enableSearch();
        $especialidade_id->enableSearch();
        $convidado_agendamento_agenda_id->enableSearch();
        $agendamento_procedimento_agendamento_parceiro_id->enableSearch();
        $agendamento_procedimento_agendamento_procedimento_id->enableSearch();

        $id->setEditable(false);
        $dt_final->setEditable(false);
        $dt_inicial->setEditable(false);
        $repetir_ate->setEditable(false);
        $mostra_horario->setEditable(false);
        $tipo_repeticao->setEditable(false);
        $agenda_profissional_nome->setEditable(false);
        $agendamento_procedimento_agendamento_valor_total->setEditable(false);

        $id->setSize(100);
        $online->setSize('100%');
        $dt_final->setSize('100%');
        $agenda_id->setSize('100%');
        $cliente_id->setSize('100%');
        $dt_inicial->setSize('100%');
        $repetir_ate->setSize('100%');
        $tipo_repeticao->setSize('100%');
        $observacao->setSize('100%', 100);
        $habilitar_repeticao->setSize(80);
        $especialidade_id->setSize('100%');
        $quantidade_repeticoes->setSize(110);
        $agenda_profissional_nome->setSize('100%');
        $mostra_horario->setSize('calc(100% - 60px)');
        $convidado_agendamento_agenda_id->setSize('100%');
        $agendamento_procedimento_agendamento_valor->setSize('100%');
        $agendamento_procedimento_agendamento_quantidade->setSize('100%');
        $agendamento_procedimento_agendamento_parceiro_id->setSize('100%');
        $agendamento_procedimento_agendamento_valor_total->setSize('100%');
        $agendamento_procedimento_agendamento_procedimento_id->setSize('100%');

        $quantidade_repeticoes->setExitAction(new TAction([$this, 'onChangeQuantidade']));

        $this->form->appendPage("Informações gerais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Agenda:", '#ff0000', '14px', null, '100%'),$agenda_id],[new TLabel("Profissional:", null, '14px', null, '100%'),$agenda_profissional_nome],[new TLabel("Especialidade:", null, '14px', null, '100%'),$especialidade_id]);
        $row2->layout = ['col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $this->form->addFields([new TLabel("Cliente:", '#ff0000', '14px', null, '100%'),$cliente_id],[new TLabel(" ", null, '14px', null, '100%'),$btnHorarios],[new TLabel("Horário:", null, '14px', null, '100%'),$mostra_horario,$btnRemoveHorario]);
        $row3->layout = ['col-sm-6','col-sm-2','col-sm-4'];

        $row4 = $this->form->addFields([$dt_inicial],[$dt_final]);
        $row4->layout = ['col-sm-2','col-sm-2'];

        $row5 = $this->form->addFields([new TLabel("Tipo de atendimento:", null, '14px', null, '100%'),$online]);
        $row5->layout = ['col-sm-6'];

        $row6 = $this->form->addFields([new TFormSeparator("Procedimentos:", '#333', '18', '#eee')]);
        $row6->layout = [' col-sm-12'];

        $row7 = $this->form->addFields([$this->fieldList_procedimentos]);
        $row7->layout = [' col-sm-12'];

        $row8 = $this->form->addFields([new TLabel("Observação:", null, '14px', null, '100%'),$observacao]);
        $row8->layout = [' col-sm-12'];

        $this->form->appendPage("Repetições");
        $row9 = $this->form->addFields([new TLabel("Habilitar repetições:", null, '14px', null, '100%'),$habilitar_repeticao]);
        $row9->layout = [' col-sm-12'];

        $row10 = $this->form->addFields([new TLabel("Tipo de repetição:", null, '14px', null, '100%'),$tipo_repeticao],[new TLabel("Repetir até:", null, '14px', null, '100%'),$repetir_ate,new TLabel("<b>Atenção:</b> <small>Informe uma data ou uma quantidade</small>", '#FF0000', '14px', null, '100%')],[new TLabel("Quantidade:", null, '14px', null, '100%'),$quantidade_repeticoes]);
        $row10->layout = [' col-sm-6',' col-sm-3',' col-sm-3'];

        $this->form->appendPage("Convidados");
        $row11 = $this->form->addFields([$this->fieldList_convidados]);
        $row11->layout = [' col-sm-12'];

        // create the form actions
        $btnOnSave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btnOnSave = $btnOnSave;
        $btnOnSave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TScript::create("$(\"[name='dt_inicial']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='dt_final']\").closest('.fb-inline-field-container').hide()");

        $btnOnSave->getAction()->setParameter('origin', $param['origin']??'');

        if (! empty($param['key']))
        {
            TScript::create("$('[data-current_page=2]').hide()");
        }

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AgendamentoFormBtn]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onExitValor($param = null) 
    {
        try 
        {
            self::onExitQuantidade($param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onExitQuantidade($param = null) 
    {
        try 
        {
            // Código gerado pelo snippet: "Cálculos com campos"
            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);
            $field_data = json_decode($param['_field_data_json']);

            if(!empty($param['agendamento_procedimento_agendamento_valor'][$field_data->row]) && !empty($param['agendamento_procedimento_agendamento_quantidade'][$field_data->row]))
            {
                $agendamento_procedimento_agendamento_valor = (double) str_replace(',', '.', str_replace('.', '', $param['agendamento_procedimento_agendamento_valor'][$field_data->row]));
                $agendamento_procedimento_agendamento_quantidade = (double) str_replace(',', '.', str_replace('.', '', $param['agendamento_procedimento_agendamento_quantidade'][$field_data->row]));

                $agendamento_procedimento_agendamento_valor_total = $agendamento_procedimento_agendamento_valor * $agendamento_procedimento_agendamento_quantidade ;
                $object = new stdClass();
                $object->{"agendamento_procedimento_agendamento_valor_total_{$field_id}"} = number_format($agendamento_procedimento_agendamento_valor_total, 2, ',', '.');
                TForm::sendData(self::$formName, $object);
                // -----    
            }

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

    public static function onChange($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            if (! empty($param['agenda_id']))
            {
                //TFieldList::clear('fieldList_procedimentos');

                $agenda = Agenda::find($param['agenda_id']);

                $parceiros = EscritorioParceiro::where('escritorio_id', '=', $agenda->escritorio_id)->getIndexedArray('parceiro_id', 'parceiro_id')??[];
                $especialidades = PessoaEspecialidade::where('pessoa_id', '=', $agenda->profissional_id)->getIndexedArray('especialidade_id', '{especialidade->descricao}');

                $object = new stdClass();
                $object->agenda_profissional_nome = $agenda->profissional->nome;

                TForm::sendData(self::$formName, $object);

                TCombo::reload(self::$formName, 'especialidade_id', $especialidades);

                if ($agenda->aceita_agendamento_online == 'T')
                {
                    TScript::create('$("[name=online]").closest(".tformrow").show()');
                }
                else
                {
                    TScript::create('$("[name=online]").closest(".tformrow").hide()');
                }

                $obj = new stdClass;
                $obj->agendamento_procedimento_agendamento_parceiro_id = [ array_shift($parceiros) ];
                $obj->agendamento_procedimento_agendamento_procedimento_id =[ $agenda->procedimento_id];
                $obj->agendamento_procedimento_agendamento_quantidade = ['1,00'];

                TForm::sendData(self::$formName, $obj, false, true, 500);

                TSession::setValue('agenda_id', $param['agenda_id']);
                TButton::enableField(self::$formName, 'btnHorarios');
            }
            else
            {
                TFieldList::clear('fieldList_procedimentos');
                TCombo::clearField(self::$formName, 'especialidade_id', false);
            }

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeParceiro($param = null) 
    {
        try 
        {
            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);
            $row = json_decode($param['_field_data_json'], true)['row'];

            $param['key'] = $param["agendamento_procedimento_agendamento_procedimento_id"][$row];
            self::onChangeProcedimento($param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeProcedimento($param = null) 
    {
        try 
        {
            $field_id = explode('_', $param['_field_id']);
            $field_id = end($field_id);

            $row = json_decode($param['_field_data_json'], true)['row'];

            if(empty($param["agendamento_procedimento_agendamento_parceiro_id"][$row]))
            {
                $object = new stdClass();
                $object->{"agendamento_procedimento_agendamento_procedimento_id_{$field_id}"} = '';

                TForm::sendData(self::$formName, $object, false, false, false);

                throw new Exception('Antes de escolher uma procedimento, preencha o Convênio');
            }

            if(!empty($param['key']))
            {
                TTransaction::open(self::$database);

                $procedimentoPreco = ProcedimentoPreco::where('parceiro_id', '=', $param["agendamento_procedimento_agendamento_parceiro_id"][$row])
                                                      ->where('procedimento_id', '=', $param['key'])
                                                      ->first();
                $object = new stdClass();
                $object->{"agendamento_procedimento_agendamento_quantidade_{$field_id}"} = 1;
                $object->{"agendamento_procedimento_agendamento_valor_{$field_id}"} = TextService::toBRL($procedimentoPreco->valor);

                TForm::sendData(self::$formName, $object, false, false, false);

                $object = new stdClass();
                $object->{"agendamento_procedimento_agendamento_valor_total_{$field_id}"} = TextService::toBRL($procedimentoPreco->valor);

                TForm::sendData(self::$formName, $object);

                TTransaction::close();
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
            if (!empty($param['habilitar_repeticao']) AND $param['habilitar_repeticao'] == 'S')
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

    public static function verificaConvidado($param = null) 
    {
        try 
        {
            if($param['dt_inicial'] || $param['dt_final']){
                new TMessage('info', "Verifique o horario do agendamento novamente.");
                self::removeHorario();
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

            $object = new Agendamento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $new = empty($object->id) || empty(Agendamento::find($object->id));

            if ($new)
            {
                $object->estado_agenda_id = EstadoAgenda::AGENDADO;
            }

            if ($object->aceita_agendamento_online == 'F')
            {
                $object->online = 'F';
            }

            if ($object->online == 'T')
            {
                $object->link_atendimento_online = implode('', [bin2hex(random_bytes(4)),bin2hex(random_bytes(2)),bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),bin2hex(random_bytes(6))]);
            }

            $object->store(); // save the object 

            if ($new)
            {
                $estadoAgendamento = new EstadoAgendamento();
                $estadoAgendamento->agendamento_id = $object->id;
                $estadoAgendamento->estado_agenda_id = $object->estado_agenda_id;
                $estadoAgendamento->atribuido_em = date('Y-m-d H:i:s');
                $estadoAgendamento->system_users_id = TSession::getValue('userid');
                $estadoAgendamento->store();
            }

            $convidado_agendamento_items = $this->storeItems('Convidado', 'agendamento_id', $object, $this->fieldList_convidados, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_convidados); 

            $procedimentos = $this->fieldList_procedimentos->getPostData();
            if (count($procedimentos) == 1 AND ! $procedimentos[0]->procedimento_id)
            {
                $object->deleteComposite('AgendamentoProcedimento', 'agendamento_id', $object->id);
            }
            else
            {
            $agendamento_procedimento_agendamento_items = $this->storeItems('AgendamentoProcedimento', 'agendamento_id', $object, $this->fieldList_procedimentos, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_procedimentos); 
            }
            if ($new)
            {
                $this->clonar($object, $data);
            }

            if (! empty($param['origin']) && $param['origin'] === 'list' )
            {
                $messageAction = new TAction(['AgendaAgendamentoList', 'onShow']);
            }
            else
            {
                $messageAction = new TAction(['AgendamentosFilterForm', 'onShow']);
                $messageAction->setParameter('target_container', 'b60f9c87e9d935');
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

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

    public function onEdit( $param )
    {
        try
        {
            TScript::create("$(\"[name='dt_inicial']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='dt_final']\").closest('.fb-inline-field-container').hide()");
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Agendamento($key); // instantiates the Active Record 

                                $object->agenda_profissional_nome = $object->agenda->profissional->nome;

                $this->criteria_fieldList_convidados->setProperty('order', 'criacao_user_id asc');
                $this->fieldList_convidados_items = $this->loadItems('Convidado', 'agendamento_id', $object, $this->fieldList_convidados, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_convidados); 

                $this->fieldList_procedimentos_items = $this->loadItems('AgendamentoProcedimento', 'agendamento_id', $object, $this->fieldList_procedimentos, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_procedimentos); 

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

        $this->fieldList_procedimentos->addHeader();
        $this->fieldList_procedimentos->addDetail($this->default_item_fieldList_procedimentos);

        $this->fieldList_procedimentos->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_convidados->addHeader();
        $this->fieldList_convidados->addDetail($this->default_item_fieldList_convidados);

        $this->fieldList_convidados->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_procedimentos->addHeader();
        $this->fieldList_procedimentos->addDetail($this->default_item_fieldList_procedimentos);

        $this->fieldList_procedimentos->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->fieldList_convidados->addHeader();
        $this->fieldList_convidados->addDetail($this->default_item_fieldList_convidados);

        $this->fieldList_convidados->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        TButton::disableField(self::$formName, 'btnHorarios');
        TScript::create("$(\"[name='data_inicial']\").closest('.fb-inline-field-container').hide()");
    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    public static function removeHorario($param = null) 
    {
        try 
        {

            $object = new stdClass();
            $object->dt_inicial = '';
            $object->dt_final = '';
            $object->mostra_horario = 'Selecione um horário';

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
            $inicio = substr($object->dt_inicial, 0, 10);

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
            else if ($data->quantidade_repeticoes AND $data->quantidade_repeticoes > 0)
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

            if (! empty($repeticoes))
            {
                foreach($repeticoes AS $data)
                {
                    $this->clonarAgendamento($object, $data);
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

    public function clonarAgendamento($object, $novaData)
    {
        $newObject = clone $object;
        unset($newObject->id);

        $hora_inicial = date('H:i', strtotime($newObject->dt_inicial));

        $minutesDiff = (strtotime($newObject->dt_final) - strtotime($newObject->dt_inicial)) / 60;

        $newObject->dt_inicial = "{$novaData} {$hora_inicial}";
        $newObject->dt_final = date('Y-m-d H:i:s', strtotime("{$newObject->dt_inicial} +{$minutesDiff} minutes"));
        $newObject->agendamento_original_id = $object->id;

        if (! $newObject->validate()) 
        {
            throw new Exception('Verifique o horário. Existem bloqueios ou horarios de intervalo sobrepostos');
        }

        $newObject->store();

        // preenche procedimentos
        $estadoAgendamento = new EstadoAgendamento();
        $estadoAgendamento->agendamento_id = $newObject->id;
        $estadoAgendamento->estado_agenda_id = $newObject->estado_agenda_id;
        $estadoAgendamento->atribuido_em = date('Y-m-d H:i:s');
        $estadoAgendamento->system_users_id = TSession::getValue('userid');
        $estadoAgendamento->store();

        // preenche estados
        if (! empty($object->getAgendamentoProcedimentos()))
        {
            $agendamentoProcedimentos = $object->getAgendamentoProcedimentos();

            foreach ($agendamentoProcedimentos as $agendamentoProcedimento)
            {
                $newAgendamento = clone $agendamentoProcedimento;

                unset($newAgendamento->id);
                $newAgendamento->agendamento_id = $newObject->id;
                $newAgendamento->store();
            }

        }
    }

}

