<?php

class PessoaConsultaFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Pessoa';

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

        $pessoa = new Pessoa($param['key']);
        // define the form title
        $this->form->setFormTitle("Consulta de pessoa");

        $param['nova_pessoa_grupo_nome'] = $param['nova_pessoa_grupo_nome'] ?? null;

        $label1 = new TLabel("Código:", '', '14px', 'B', '100%');
        $text1 = new TTextDisplay($pessoa->id, '', '14px', '');
        $label2 = new TLabel("Tipo de pessoa:", '', '14px', 'B', '100%');
        $text2 = new TTextDisplay($pessoa->tipo_pessoa->nome, '', '14px', '');
        $label3 = new TLabel("Nome:", '', '14px', 'B', '100%');
        $text3 = new TTextDisplay($pessoa->nome, '', '14px', '');
        $image1 = new TImage($pessoa->foto);
        $label5 = new TLabel("Telefone:", '', '14px', 'B', '100%');
        $text5 = new TTextDisplay($pessoa->telefone, '', '14px', '');
        $label4 = new TLabel("Email:", '', '14px', 'B', '100%');
        $text4 = new TTextDisplay($pessoa->email, '', '14px', '');
        $label8 = new TLabel("Cpf cnpj:", '', '14px', 'B', '100%');
        $text8 = new TTextDisplay($pessoa->cpf_cnpj, '', '14px', '');
        $label9 = new TLabel("Rg ie:", '', '14px', 'B', '100%');
        $text9 = new TTextDisplay($pessoa->rg_ie, '', '14px', '');
        $label10 = new TLabel("Criado em:", '', '14px', 'B', '100%');
        $datetimetext2 = new TTextDisplay(TDateTime::convertToMask($pessoa->data_criacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '14px', '');
        $label11 = new TLabel("Criado por:", '', '14px', 'B', '100%');
        $text12 = new TTextDisplay($pessoa->criacao_user->name, '', '14px', '');
        $label12 = new TLabel("Atualizado em:", '', '14px', 'B', '100%');
        $datetimetext4 = new TTextDisplay(TDateTime::convertToMask($pessoa->data_modificacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '14px', '');
        $label13 = new TLabel("Atualizado por:", '', '14px', 'B', '100%');
        $text14 = new TTextDisplay($pessoa->modificacao_user->name, '', '14px', '');

        $image1->width = '100%';
        $image1->height = '200px';

        $row1 = $this->form->addFields([$label1,$text1,$label2,$text2,$label3,$text3],[$image1]);
        $row1->layout = [' col-sm-8',' col-sm-4'];

        $row2 = $this->form->addFields([$label5,$text5],[$label4,$text4]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([$label8,$text8],[$label9,$text9]);
        $row3->layout = ['col-sm-6','col-sm-6'];

        $row4 = $this->form->addContent([new TFormSeparator(" ", '#333', '18', '#797979')]);
        $row5 = $this->form->addFields([$label10,$datetimetext2],[$label11,$text12],[$label12,$datetimetext4],[$label13,$text14]);
        $row5->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $btnAddGrupoAction = new TAction([$this, 'onAdicionarGrupo'],['key'=>$pessoa->id, 'static' => 1]);
        $btnAddGrupoLabel = new TLabel("Adicionar como {$param['nova_pessoa_grupo_nome']}");

        $btnAddGrupo = $this->form->addHeaderAction($btnAddGrupoLabel, $btnAddGrupoAction, 'fas:upload #FFFFFF'); 
        $btnAddGrupo->addStyleClass('btn-warning'); 
        $btnAddGrupoLabel->setFontSize('14px'); 
        $btnAddGrupoLabel->setFontColor('#FFFFFF'); 
        $btnAddGrupoLabel->setFontStyle('B'); 

        $btnEditarAction = new TAction(['PessoaConsultaFormView', 'editarPessoaGrupo'],['key'=>$pessoa->id]);
        $btnEditarLabel = new TLabel("Editar");

        $btnEditar = $this->form->addHeaderAction($btnEditarLabel, $btnEditarAction, 'fas:edit #FFFFFF'); 
        $btnEditar->addStyleClass('btn-info'); 
        $btnEditarLabel->setFontSize('14px'); 
        $btnEditarLabel->setFontColor('#FFFFFF'); 
        $btnEditarLabel->setFontStyle('B'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TButton::disableField(self::$formName, 'btnAddGrupo');
        TButton::disableField(self::$formName, 'btnEditar');
        TScript::create("$(\"[name='btnAddGrupo']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='btnEditar']\").closest('.fb-inline-field-container').hide()");

        TTransaction::close();
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=PessoaConsultaFormView]');
        $style->width = '40% !important';   
        $style->show(true);

    }

    public static function onAdicionarGrupo($param = null) 
    {
        try 
        {

        $param['key'] = (int) $param['key'];
        $novoGrupo    = (int) TSession::getValue('nova_pessoa_grupo');

        TTransaction::open(self::$database);

        $pessoaGrupos = PessoaGrupo::where('pessoa_id','=',$param['key'])
                                    ->where('grupo_id','=',$novoGrupo)
                                    ->count();

        if($pessoaGrupos<=0){
            $objeto = new PessoaGrupo();
            $objeto->grupo_id = -1;
            $objeto->pessoa_id = $param['key'];

            if(!isset($novoGrupo)){
                throw new Exception("Não foi possível encontrar um grupo!");
            }else{
                $objeto->grupo_id = $novoGrupo;
            }
            $objeto->store();

        }

        $pessoa = Pessoa::find($param['key']);
        $grupo = Grupo::find($novoGrupo);

        TToast::show("success", "$pessoa->nome adicionada como $grupo->nome!", "bottomRight", "");

        switch ($novoGrupo) {
            case Grupo::CLIENTE:
                TApplication::loadPage('ClienteList', 'onReload');
                break;
            case Grupo::PROFISSIONAL:
                TApplication::loadPage('ProfissionalList', 'onReload');
                break;

            case Grupo::FORNECEDOR:
                TApplication::loadPage('FornecedorList', 'onReload');
                break;

            case Grupo::REPRESENTANTE_LEGAL:
                TApplication::loadPage('RepresentantesLegaisList', 'onReload');
                break;

            case Grupo::CONTRAPARTE:
                TApplication::loadPage('ContraparteList', 'onReload');
                break;

            default:
                break;
        }

        TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {     

            TTransaction::open(self::$database);
            $pessoa = new Pessoa((int) $param['key']);
            if($pessoa->tipo_pessoa_id==TipoPessoa::FISICA){
                TScript::create("$('label:contains(\"Cpf cnpj:\")').html('CPF:')");
                TScript::create("$('label:contains(\"Rg ie:\")').html('RG:')");
            }else if($pessoa->tipo_pessoa_id==TipoPessoa::JURIDICA){
                TScript::create("$('label:contains(\"Cpf cnpj:\")').html('CNPJ:')");
                TScript::create("$('label:contains(\"Rg ie:\")').html('Inscrição Estadual:')");
            }

            TTransaction::close();
    }

    public  function editarPessoaGrupo($param = null) 
    {
        try 
        {
            $param['key'] = (int) $param['key'];
            $novoGrupo    = (int) TSession::getValue('nova_pessoa_grupo');

            TTransaction::open(self::$database);

            $pessoaGrupo = PessoaGrupo::where('pessoa_id','=',$param['key'])
                                        ->where('grupo_id','=',$novoGrupo)
                                        ->count();

            if($pessoaGrupo<=0){
                $objeto = new PessoaGrupo();
                $objeto->grupo_id = -1;
                $objeto->pessoa_id = $param['key'];

                if(!isset($novoGrupo)){
                    throw new Exception("Não foi possível encontrar um grupo!");
                }else{
                    $objeto->grupo_id = $novoGrupo;
                }
                $objeto->store();
            }

            $pageParam['key'] = $param['key'];
            TScript::create("Template.closeRightPanel();");
            switch ($novoGrupo) {
                case Grupo::CLIENTE:
                    TApplication::loadPage('ClienteForm', 'onEdit', $pageParam);
                    break;
                case Grupo::PROFISSIONAL:
                    TApplication::loadPage('ProfissionalForm', 'onEdit', $pageParam);
                    break;

                case Grupo::FORNECEDOR:
                    TApplication::loadPage('FornecedorForm', 'onEdit', $pageParam);
                    break;

                case Grupo::REPRESENTANTE_LEGAL:
                    TApplication::loadPage('RepresentanteLegalForm', 'onEdit', $pageParam);
                    break;

                case Grupo::REPRESENTANTE_LEGAL:
                    TApplication::loadPage('ContraparteForm', 'onEdit', $pageParam);
                    break;

                default:
                    break;
            }

            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

