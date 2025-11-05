<?php

class RespostaFormularioForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_RespostaFormularioForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Resposta Formulário");

        $criteria_formulario_id = new TCriteria();

        $filterVar = "S";
        $criteria_formulario_id->add(new TFilter('ativo', '=', $filterVar)); 

        $formulario_id = new TDBCombo('formulario_id', 'escritorio', 'Formulario', 'id', '{nome}','nome asc' , $criteria_formulario_id );
        $atendimento_id = new THidden('atendimento_id');
        $id = new THidden('id');

        $formulario_id->setChangeAction(new TAction([$this,'onChangeFormulario']));

        $id->setSize(200);
        $atendimento_id->setSize(200);
        $formulario_id->setSize('100%');

        $id->setValue($param['id']??'');
        $formulario_id->setValue($param['formulario_id']??'');
        $atendimento_id->setValue($param['atendimento_id']??'');


        $row1 = $this->form->addFields([new TFormSeparator("Formulário", '#333', '18', '#eee')]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([$formulario_id,new TLabel("*Escolha o formulário para responder as perguntas", '#607D8B', '14px', null),$atendimento_id,$id]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TFormSeparator("Perguntas", '#333', '18', '#eee')]);
        $row3->layout = [' col-sm-12'];

        // create the form actions
        $btn_onaction = $this->form->addAction("Salvar respostas", new TAction([$this, 'onAction']), 'fas:save #ffffff');
        $this->btn_onaction = $btn_onaction;
        $btn_onaction->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=RespostaFormularioForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChangeFormulario($param = null) 
    {
        try 
        {
            if (! empty($param['atendimento_id']) && ! empty($param['formulario_id']))
            {
                TApplication::loadPage('RespostaFormularioForm', 'onShow', ['formulario_id' => $param['formulario_id'], 'atendimento_id' => $param['atendimento_id']]);
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onAction($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');

            self::validate($param);

            $respostaFormulario = empty($param['id']) ? new  RespostaFormulario : RespostaFormulario::find($param['id']);

            $respostaFormulario->dt_resposta = date('Y-m-d');
            $respostaFormulario->formulario_id = $param['formulario_id'];
            $respostaFormulario->atendimento_id = $param['atendimento_id'];

            if(empty($param['id'])){
                $respostaFormulario->criacao_user_id = TSession::getValue('userid');
            }else{
                $respostaFormulario->modificacao_user_id = TSession::getValue('userid');
            }

            $respostaFormulario->store();

            $questoes = Questao::where('formulario_id', '=', $param['formulario_id'])->getIndexedArray('id', 'id');

            foreach($questoes as $questao_id)
            {
                $value = $param['questao_'. $questao_id]??null;

                $questao = Questao::find($questao_id);

                if ($questao->fl_obrigatorio == 'S' && ! $value)
                {
                    throw new Exception("A questão \"{$questao->nome}\", é obrigatória");
                }

                $resposta = Resposta::where(
                    'questao_id', '=', $questao_id
                )->where(
                    'resposta_formulario_id', '=', $respostaFormulario->id
                )->first();

                $resposta = $resposta ?? new Resposta;

                $resposta->questao_id = $questao_id;
                $resposta->resposta_formulario_id = $respostaFormulario->id;
                $resposta->resposta = $questao->tipo_campo === 'TCheckGroup' ? implode(',', $value) : $value;

                $resposta->store();
            }

            TTransaction::close();

            $loadPageParam = [
                'id' => $respostaFormulario->atendimento_id,
                'key' => $respostaFormulario->atendimento_id,
                'current_tab_abas' => 1
            ];

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();

            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

        try
        {
            TTransaction::open('escritorio');

            if (! empty($param['formulario_id']))
            {
                $formulario = Formulario::find($param['formulario_id']);

                $questoes = $formulario->getQuestaos();

                $data = new stdClass;
                $data->atendimento_id = $param['atendimento_id'];
                $data->formulario_id = $param['formulario_id'];

                $this->form->setData($data);

                if ($questoes)
                {
                    foreach ($questoes as $questao)
                    {
                        if ($questao->ativo == 'N')
                        {
                            continue;
                        }

                        $fieldQuestao = FormularioService::getField($questao);
                        $labelQuestao = FormularioService::getLabel($questao);

                        $rowCustom = $this->form->addFields([$labelQuestao, $fieldQuestao]);
                        $rowCustom->layout = ['col-sm-12'];
                    }
                }

                $rowInfoSep = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
                $retorno = FormularioService::getLinhaInfoCadastro('', '', '', '');
                $rowInfoCadastro = $this->form->addFields($retorno[0],$retorno[1],$retorno[2],$retorno[3]);
                $rowInfoCadastro->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

            }
            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
        }

    } 

    public static function validate($param)
    {
        if (empty($param['atendimento_id']))
        {
            throw new Exception('Atendimento é obrigatório');
        }

        if (empty($param['formulario_id']))
        {
            throw new Exception('Formulário é obrigatório');
        }
    }

    public function onEdit($param = null)
    {
        try
        {
            TTransaction::open('escritorio');

            if ($param['id'])
            {
                $respostaFormulario = RespostaFormulario::find($param['id']);

                $formulario = $respostaFormulario->formulario;

                $questoes = $formulario->getQuestaos();

                $data = new stdClass;
                $data->id = $respostaFormulario->id;
                $data->atendimento_id = $respostaFormulario->atendimento_id;
                $data->formulario_id = $respostaFormulario->formulario_id;

                $criacao_user_name = $respostaFormulario->criacao_user->name;
                $modificacao_user_name = $respostaFormulario->modificacao_user->name;
                $data_criacao = $respostaFormulario->data_criacao;
                $data_modificacao = $respostaFormulario->data_modificacao;

                $this->form->setData($data);

                if ($questoes)
                {
                    foreach ($questoes as $questao)
                    {
                        $resposta = Resposta::where('resposta_formulario_id', '=', $respostaFormulario->id)->where('questao_id', '=', $questao->id)->first();

                        if (! $resposta && $questao->ativo == 'N')
                        {
                            continue;
                        }

                        $fieldQuestao = FormularioService::getField($questao, $resposta);
                        $labelQuestao = FormularioService::getLabel($questao);

                        $rowCustom = $this->form->addFields([$labelQuestao, $fieldQuestao]);
                        $rowCustom->layout = ['col-sm-12'];
                    }   
                }

                $rowInfoSep = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
                $retorno = FormularioService::getLinhaInfoCadastro($criacao_user_name, $modificacao_user_name, $data_criacao, $data_modificacao);
                $rowInfoCadastro = $this->form->addFields($retorno[0],$retorno[1],$retorno[2],$retorno[3]);
                $rowInfoCadastro->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

            }

            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
        }
    }

}

