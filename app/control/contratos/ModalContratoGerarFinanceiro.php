<?php

class ModalContratoGerarFinanceiro extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalContratoGerarFinanceiro';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(850, null);
        parent::setTitle("Gerar financeiro de contrato");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Gerar financeiro de contrato");

        $criteria_contrato_id = new TCriteria();
        $criteria_categoria_conta_id = new TCriteria();
        $criteria_tipo_pagamento_id = new TCriteria();

        $filterVar = [TipoConta::AMBOS, TipoConta::RECEBER];
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_categoria_conta_id->add(new TFilter('tipo_conta_id', 'in', "(SELECT id FROM categoria_conta WHERE tipo_conta_id in ($filterVar))")); 

        $contrato_id = new TDBCombo('contrato_id', 'escritorio', 'Contrato', 'id', '{numero}','numero asc' , $criteria_contrato_id );
        $escritorio_id = new THidden('escritorio_id');
        $contrato_parcela_id = new THidden('contrato_parcela_id');
        $profissional_id = new THidden('profissional_id');
        $categoria_conta_id = new TDBCombo('categoria_conta_id', 'escritorio', 'CategoriaConta', 'id', '{nome}','nome asc' , $criteria_categoria_conta_id );
        $pessoa_id = new THidden('pessoa_id');
        $descricao = new TEntry('descricao');
        $valor = new TNumeric('valor', '2', ',', '.' );
        $dt_vencimento = new TDate('dt_vencimento');
        $tipo_pagamento_id = new TDBCombo('tipo_pagamento_id', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_tipo_pagamento_id );
        $numero_parcelas = new TSpinner('numero_parcelas');

        $categoria_conta_id->addValidation("Categoria de conta", new TRequiredValidator()); 
        $descricao->addValidation("Descrição", new TRequiredValidator()); 
        $valor->addValidation("Valor", new TRequiredValidator()); 
        $dt_vencimento->addValidation("Data de vencimento", new TRequiredValidator()); 
        $tipo_pagamento_id->addValidation("Forma de pagamento", new TRequiredValidator()); 

        $contrato_id->setEditable(false);
        $dt_vencimento->setMask('dd/mm/yyyy');
        $dt_vencimento->setDatabaseMask('yyyy-mm-dd');
        $numero_parcelas->setRange(1, 2000, 1);
        $contrato_id->enableSearch();
        $tipo_pagamento_id->enableSearch();
        $categoria_conta_id->enableSearch();

        $categoria_conta_id->setValue('20');
        $valor->setValue($param["valor"] ?? null);
        $descricao->setValue($param["desc"] ?? null);
        $dt_vencimento->setValue($param["dt"] ?? null);
        $pessoa_id->setValue($param['pessoa_id'] ?? null);
        $contrato_id->setValue($param["contrato_id"] ?? null);
        $escritorio_id->setValue($param['escritorio_id'] ?? null);
        $numero_parcelas->setValue($param['quant_parcela'] ?? null);
        $profissional_id->setValue($param['profissional_id'] ?? null);
        $contrato_parcela_id->setValue($param['contrato_parcela_id'] ?? null);

        $valor->setSize('100%');
        $pessoa_id->setSize(200);
        $descricao->setSize('100%');
        $escritorio_id->setSize(200);
        $contrato_id->setSize('100%');
        $profissional_id->setSize(200);
        $dt_vencimento->setSize('100%');
        $numero_parcelas->setSize('100%');
        $contrato_parcela_id->setSize(200);
        $tipo_pagamento_id->setSize('100%');
        $categoria_conta_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Contrato:", null, '14px', null, '100%'),$contrato_id,$escritorio_id,$contrato_parcela_id],[$profissional_id,new TLabel("Categoria de conta:", '#FF0000', '14px', null, '100%'),$categoria_conta_id],[$pessoa_id]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Descrição:", '#FF0000', '14px', null, '100%'),$descricao]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row4 = $this->form->addFields([new TLabel("Parcela", null, '16px', null, '100%')]);
        $row4->layout = [' col-sm-4'];

        $row5 = $this->form->addFields([new TLabel("Valor:", '#FF0000', '14px', null, '100%'),$valor],[new TLabel("Data de vencimento:", '#FF0000', '14px', null, '100%'),$dt_vencimento],[new TLabel("Forma de pagamento", '#FF0000', '14px', null, '100%'),$tipo_pagamento_id],[new TLabel("Parcelas:", '#FF0000', '14px', null, '100%'),$numero_parcelas]);
        $row5->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_ongerar = $this->form->addAction("Gerar", new TAction([$this, 'onGerar']), 'fas:cog #ffffff');
        $this->btn_ongerar = $btn_ongerar;
        $btn_ongerar->addStyleClass('btn-success'); 

        parent::add($this->form);

    }

    public function onGerar($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');

            $messageAction = null;

            $this->form->validate(); // validate form data
            $data = $this->form->getData(); // get form data as array

            $buscaContaContrato = Conta::where('contrato_id','=',$data->contrato_id)->first();
            if(!$buscaContaContrato){
                $conta = new Conta(); // create an empty object
            }else{
                $conta = Conta::find($buscaContaContrato->id); // create an empty object
            }

            $conta->fromArray( (array) $data); // load the object with data
            $conta->data_emissao = date('Y-m-d');
            $conta->tipo_conta_id = TipoConta::RECEBER;
            $conta->tipo_documento_financeiro_id = TipoDocumentoFinanceiro::CONTRATO;
            $conta->total_conta += $data->valor;
            $conta->store();

            $parcelas = $data->numero_parcelas;
            $valor_parcela = $data->valor/$parcelas;

            for($i=1;$i<=$parcelas;$i++){
                $lancamento = new Lancamento();
                $lancamento->fromArray( (array) $data); // load the object with data
                $lancamento->valor = $valor_parcela;
                $lancamento->conta_id = $conta->id;
                if($i==1){
                    $lancamento->dt_vencimento = $data->dt_vencimento;
                }else{
                    $aux=$i-1;
                    $lancamento->dt_vencimento = date('Y-m-d', strtotime("+{$aux} months", strtotime($data->dt_vencimento)));
                }
                $lancamento->store();
            }

            TScript::create("$(\"[page_name='ModalContratoGerarFinanceiro']\").remove()");
            TApplication::loadPage('ContratoFormView', 'onShow', ['key' => $data->contrato_id, 'id' => $data->contrato_id, 'current_tab_abas' => 3]);

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

        TTransaction::open('escritorio');

        $buscaContaContrato = Conta::where('contrato_id','=',$param['contrato_id'])->first();
        if($buscaContaContrato){
            $conta = Conta::find($buscaContaContrato->id);

            $data = new stdClass();

            if($conta){
                $data->descricao = $conta->descricao;
                $data->categoria_conta_id = $conta->categoria_conta_id;
            }
            TForm::sendData(self::$formName, $data);
        }

        TTransaction::close();
    } 

}

