<?php

class ContaForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Conta';
    private static $primaryKey = 'id';
    private static $formName = 'form_Conta';

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
        $this->form->setFormTitle("Cadastro de conta a receber");

        $criteria_tipo_documento_financeiro_id = new TCriteria();
        $criteria_escritorio_id = new TCriteria();
        $criteria_pessoa_id = new TCriteria();
        $criteria_profissional_id = new TCriteria();
        $criteria_categoria_conta_id = new TCriteria();
        $criteria_tipo_pagamento = new TCriteria();
        $criteria_lancamento_conta_tipo_pagamento_id = new TCriteria();

        $filterVar = TipoConta::PAGAR;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('tipo_conta_id', '!=', $filterVar)); 
        $filterVar = TipoDocFinanceiroPadrao::ATENDIMENTO;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('padrao_id', '!=', $filterVar)); 
        $filterVar = TipoDocFinanceiroPadrao::CONTRATO;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('padrao_id', '!=', $filterVar)); 
        $filterVar = TipoDocFinanceiroPadrao::PROCESSO;
        $criteria_tipo_documento_financeiro_id->add(new TFilter('padrao_id', '!=', $filterVar)); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_escritorio_id->add(new TFilter('system_unit_id', '=', $filterVar)); 
        $filterVar = Grupo::CLIENTE;
        $criteria_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::PROFISSIONAL;
        $criteria_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = [TipoConta::RECEBER,TipoConta::AMBOS];
        $criteria_categoria_conta_id->add(new TFilter('tipo_conta_id', 'in', $filterVar)); 

        $id = new TEntry('id');
        $tipo_conta_id = new THidden('tipo_conta_id');
        $tipo_documento_financeiro_id = new TDBCombo('tipo_documento_financeiro_id', 'escritorio', 'TipoDocumentoFinanceiro', 'id', '{nome}','nome asc' , $criteria_tipo_documento_financeiro_id );
        $atendimento_id = new TEntry('atendimento_id');
        $contrato_numero = new TEntry('contrato_numero');
        $processo_numero_cnj_numero = new TEntry('processo_numero_cnj_numero');
        $numero_documento = new TEntry('numero_documento');
        $escritorio_id = new TDBCombo('escritorio_id', 'escritorio', 'Escritorio', 'id', '{nome}','nome asc' , $criteria_escritorio_id );
        $pessoa_id = new TDBUniqueSearch('pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_pessoa_id );
        $button_ = new TButton('button_');
        $profissional_id = new TDBCombo('profissional_id', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_profissional_id );
        $categoria_conta_id = new TDBCombo('categoria_conta_id', 'escritorio', 'CategoriaConta', 'id', '{nome}','nome asc' , $criteria_categoria_conta_id );
        $descricao = new TEntry('descricao');
        $tipo = new TRadioGroup('tipo');
        $valor = new TNumeric('valor', '2', ',', '.', true, true );
        $tipo_pagamento = new TDBCombo('tipo_pagamento', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_tipo_pagamento );
        $data_vencimento = new TDate('data_vencimento');
        $repetir_ate_final_ano = new TCheckButton('repetir_ate_final_ano');
        $total_conta = new TNumeric('total_conta', '2', ',', '.', true, true );
        $total_parcelas = new TSpinner('total_parcelas');
        $button_atualizar_parcelas = new TButton('button_atualizar_parcelas');
        $btnEditarParcelas = new TButton('btnEditarParcelas');
        $btnCancelarParcelas = new TButton('btnCancelarParcelas');
        $lancamento_conta_id = new TEntry('lancamento_conta_id[]');
        $lancamento_conta___row__id = new THidden('lancamento_conta___row__id[]');
        $lancamento_conta___row__data = new THidden('lancamento_conta___row__data[]');
        $lancamento_conta_parcela = new TEntry('lancamento_conta_parcela[]');
        $lancamento_conta_valor = new TNumeric('lancamento_conta_valor[]', '2', ',', '.' );
        $lancamento_conta_dt_vencimento = new TDate('lancamento_conta_dt_vencimento[]');
        $lancamento_conta_tipo_pagamento_id = new TDBCombo('lancamento_conta_tipo_pagamento_id[]', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_lancamento_conta_tipo_pagamento_id );
        $lancamento_conta_dt_pagamento = new TDate('lancamento_conta_dt_pagamento[]');
        $this->parcelas = new TFieldList();
        $total_valor_parcelas = new TNumeric('total_valor_parcelas', '2', ',', '.' );
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->parcelas->addField(null, $lancamento_conta_id, []);
        $this->parcelas->addField(null, $lancamento_conta___row__id, ['uniqid' => true]);
        $this->parcelas->addField(null, $lancamento_conta___row__data, []);
        $this->parcelas->addField(new TLabel("", null, '14px', null), $lancamento_conta_id, ['width' => '10%']);
        $this->parcelas->addField(new TLabel("Parcela", null, '14px', null), $lancamento_conta_parcela, ['width' => '10%']);
        $this->parcelas->addField(new TLabel("Valor", null, '14px', null), $lancamento_conta_valor, ['width' => '22%','sum' => true]);
        $this->parcelas->addField(new TLabel("Data vencimento", null, '14px', null), $lancamento_conta_dt_vencimento, ['width' => '22%']);
        $this->parcelas->addField(new TLabel("Tipo de pagamento", null, '14px', null), $lancamento_conta_tipo_pagamento_id, ['width' => '22%']);
        $this->parcelas->addField(new TLabel("Data pagamento", null, '14px', null), $lancamento_conta_dt_pagamento, ['width' => '22%']);

        $this->parcelas->width = '100%';
        $this->parcelas->setFieldPrefix('lancamento_conta');
        $this->parcelas->name = 'parcelas';

        $this->criteria_parcelas = new TCriteria();
        $this->default_item_parcelas = new stdClass();

        $this->form->addField($lancamento_conta_id);
        $this->form->addField($lancamento_conta___row__id);
        $this->form->addField($lancamento_conta___row__data);
        $this->form->addField($lancamento_conta_id);
        $this->form->addField($lancamento_conta_parcela);
        $this->form->addField($lancamento_conta_valor);
        $this->form->addField($lancamento_conta_dt_vencimento);
        $this->form->addField($lancamento_conta_tipo_pagamento_id);
        $this->form->addField($lancamento_conta_dt_pagamento);

        $this->parcelas->disableRemoveButton();

        $this->parcelas->disableCloneButton();

        $tipo_documento_financeiro_id->setChangeAction(new TAction([$this,'onSelectTipoDoc']));
        $tipo->setChangeAction(new TAction([$this,'onChange']));

        $lancamento_conta_valor->setExitAction(new TAction([$this,'onChangeValor']));

        $categoria_conta_id->addValidation("Categoria", new TRequiredValidator()); 
        $descricao->addValidation("Descrição", new TRequiredValidator()); 
        $total_parcelas->addValidation("Total de parcelas", new TRequiredValidator()); 

        $pessoa_id->setMinLength(3);
        $descricao->forceUpperCase();
        $tipo->addItems(["S"=>"Simples","P"=>"Parcelada"]);
        $tipo->setLayout('horizontal');
        $tipo->setUseButton();
        $repetir_ate_final_ano->setUseSwitch(true, 'blue');
        $repetir_ate_final_ano->setIndexValue("1");
        $total_parcelas->setRange(1, 2000, 1);
        $total_parcelas->setValue('1');
        $total_valor_parcelas->setValue('0,00');

        $button_->setAction(new TAction(['ClienteForm', 'onShow'],['page' => 'ContaReceber']), "");
        $button_atualizar_parcelas->setAction(new TAction([$this, 'onGerarParcelas']), "Atualizar parcelas");
        $btnCancelarParcelas->setAction(new TAction(['ModalCancelarParcelas', 'onShow'],['conta_id' => $param['key'] ?? null,"page" => "ContaForm"]), "Cancelar parcelas");
        $btnEditarParcelas->setAction(new TAction(['ModalEditarParcelasAberto', 'onShow'],['conta_id' => $param['key'] ?? null,"total_conta" => $param['total_conta'] ?? null,"page" => "ContaForm"]), "Editar parcelas");

        $button_->addStyleClass('btn-default');
        $btnEditarParcelas->addStyleClass('btn-default');
        $btnCancelarParcelas->addStyleClass('btn-default');
        $button_atualizar_parcelas->addStyleClass('btn-default');

        $button_->setImage('fas:plus #000000');
        $btnEditarParcelas->setImage('fas:edit #000000');
        $button_atualizar_parcelas->setImage('fas:cog #03A9F4');
        $btnCancelarParcelas->setImage('fas:times-circle #000000');

        $escritorio_id->enableSearch();
        $tipo_pagamento->enableSearch();
        $profissional_id->enableSearch();
        $tipo_documento_financeiro_id->enableSearch();
        $lancamento_conta_tipo_pagamento_id->enableSearch();

        $data_vencimento->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $lancamento_conta_dt_pagamento->setDatabaseMask('yyyy-mm-dd');
        $lancamento_conta_dt_vencimento->setDatabaseMask('yyyy-mm-dd');

        $pessoa_id->setMask('{nome_formatado}');
        $data_vencimento->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $lancamento_conta_dt_pagamento->setMask('dd/mm/yyyy');
        $lancamento_conta_dt_vencimento->setMask('dd/mm/yyyy');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $atendimento_id->setEditable(false);
        $contrato_numero->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $lancamento_conta_id->setEditable(false);
        $total_valor_parcelas->setEditable(false);
        $modificacao_user_name->setEditable(false);
        $lancamento_conta_valor->setEditable(false);
        $lancamento_conta_parcela->setEditable(false);
        $processo_numero_cnj_numero->setEditable(false);
        $lancamento_conta_dt_pagamento->setEditable(false);
        $lancamento_conta_dt_vencimento->setEditable(false);
        $lancamento_conta_tipo_pagamento_id->setEditable(false);

        $id->setSize('100%');
        $tipo->setSize('100%');
        $valor->setSize('100%');
        $descricao->setSize('100%');
        $tipo_conta_id->setSize(200);
        $data_vencimento->setSize(150);
        $data_criacao->setSize('100%');
        $escritorio_id->setSize('100%');
        $total_parcelas->setSize('27%');
        $atendimento_id->setSize('100%');
        $tipo_pagamento->setSize('100%');
        $contrato_numero->setSize('100%');
        $profissional_id->setSize('100%');
        $numero_documento->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $categoria_conta_id->setSize('100%');
        $lancamento_conta_id->setSize('100%');
        $total_valor_parcelas->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $pessoa_id->setSize('calc(100% - 50px)');
        $lancamento_conta_valor->setSize('100%');
        $lancamento_conta_parcela->setSize('100%');
        $total_conta->setSize('calc(100% - 175px)');
        $processo_numero_cnj_numero->setSize('100%');
        $tipo_documento_financeiro_id->setSize('100%');
        $lancamento_conta_dt_pagamento->setSize('100%');
        $lancamento_conta_dt_vencimento->setSize('100%');
        $lancamento_conta_tipo_pagamento_id->setSize('100%');


        $this->parcelas->class = ' tfieldlist';

        $lancamento_conta_valor->setEditable(false);
        $lancamento_conta_dt_vencimento->setEditable(false);
        $lancamento_conta_tipo_pagamento_id->setEditable(false);
        $total_parcelas->style = 'text-align: right';

        $row1 = $this->form->addFields([new TLabel("Código:", null, '12px', null, '100%'),$id,$tipo_conta_id],[new TLabel("Tipo de documento:", '#FF0000', '12px', null, '100%'),$tipo_documento_financeiro_id],[new TLabel("Número do documento:", '#FF0000', '12px', null, '100%'),$atendimento_id,$contrato_numero,$processo_numero_cnj_numero,$numero_documento],[new TLabel("Escritório:", null, '12px', null, '100%'),$escritorio_id]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Cliente:", '#ff0000', '12px', null, '100%'),$pessoa_id,$button_],[new TLabel("Profissional:", null, '12px', null, '100%'),$profissional_id]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Categoria:", '#ff0000', '12px', null, '100%'),$categoria_conta_id],[new TLabel("Descrição:", '#ff0000', '12px', null, '100%'),$descricao]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Tipo de conta:", null, '12px', null, '100%'),$tipo]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([new TLabel("Valor:", '#FF0000', '12px', null, '100%'),$valor],[new TLabel("Tipo de pagamento:", '#FF0000', '12px', null, '100%'),$tipo_pagamento],[new TLabel("Data de vencimento:", '#FF0000', '12px', null, '100%'),$data_vencimento,new TLabel("Repetir até o final do ano:", null, '14px', null),$repetir_ate_final_ano]);
        $row5->layout = ['col-sm-3',' col-sm-3',' col-sm-6'];

        $row6 = $this->form->addFields([new TLabel("Total:", '#ff0000', '12px', null, '100%'),$total_conta],[new TLabel("Total de parcelas:", '#ff0000', '12px', null, '100%'),$total_parcelas,$button_atualizar_parcelas],[new TLabel(" ", null, '12px', null, '100%'),$btnEditarParcelas,$btnCancelarParcelas]);
        $row6->layout = ['col-sm-3','col-sm-3',' col-sm-3'];

        $bcontainer_62cf238b6bca9 = new BContainer('bcontainer_62cf238b6bca9');
        $this->bcontainer_62cf238b6bca9 = $bcontainer_62cf238b6bca9;

        $bcontainer_62cf238b6bca9->setTitle("Parcelas", '#333', '18px', '', '#fff');
        $bcontainer_62cf238b6bca9->setBorderColor('#c0c0c0');

        $row7 = $bcontainer_62cf238b6bca9->addFields([new TLabel("Motivo do cancelamento das parcelas:", null, '10px', null, '100%'),new TLabel("MOTIVO CANCELAMENTO", null, '12px', null, '100%')],[$this->parcelas],[$total_valor_parcelas]);
        $row7->layout = ['col-sm-6','col-sm-12','col-sm-3'];

        $row8 = $this->form->addFields([$bcontainer_62cf238b6bca9]);
        $row8->layout = [' col-sm-9'];

        $row9 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row10 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row10->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ContaReceberList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TScript::create("$(\"[name='total_valor_parcelas']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='btnEditarParcelas']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='btnCancelarParcelas']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='atendimento_id']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='contrato_numero']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='numero_documento']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='processo_numero_cnj_numero']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$('label:contains(\"Motivo do cancelamento das parcelas:\")').hide();");
        TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').hide();");

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ContaForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChangeValor($param = null) 
    {
        try 
        {
            //code here

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onSelectTipoDoc($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $tipoDoc = TipoDocumentoFinanceiro::find((int) $param['tipo_documento_financeiro_id']);

            $data = new stdClass();

            if($tipoDoc->gera_codigo == 'S'){
                //Pesquisa a ultima conta com esse tipo de documento
                $conta = Conta::where('tipo_documento_financeiro_id','=',$tipoDocFinanceiro->id)
                                ->orderBy('numero_documento')
                                ->last();
                //Se existir
                if($conta){
                    //popula o numero com o ultimo somado de 1
                    $data->numero_documento = $conta->numero_documento +1;
                }else{
                    //caso contrario, popula como 1
                    $data->numero_documento = 1;
                }
                //Desabilita a edição de número
                TEntry::disableField(self::$formName, 'numero_documento');
            }else{
                $data->numero_documento = null;
                TEntry::enableField(self::$formName, 'numero_documento');
            }

            TForm::sendData(self::$formName, $data);

            TTransaction::close();

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
            if (empty($param['tipo']) || $param['tipo'] == 'S')
            {
                TScript::create("$('[name=valor]').closest('.tformrow').show();");
                TScript::create("$('[name=total_conta]').closest('.tformrow').hide();");
                TScript::create("$('[name*=lancamento_conta_valor').closest('.bContainer-fieldset').closest('.tformrow').hide();");
                TScript::create("$('[name=total_valor_parcelas').closest('.tformrow').hide();");
            }
            else
            {
                TScript::create("$('[name=valor]').closest('.tformrow').hide();");
                TScript::create("$('[name=total_conta]').closest('.tformrow').show();");
                TScript::create("$('[name*=lancamento_conta_valor').closest('.bContainer-fieldset').closest('.tformrow').show();");
                TScript::create("$('[name=total_valor_parcelas').closest('.tformrow').show();");
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onGerarParcelas($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $cancelado = false;

            $id = (int) $param['id'];

            $search = Lancamento::where('conta_id','=',$id)
                                ->load();

            foreach($search as $lancamento){
                if($lancamento->cancelado=='S'){
                    $cancelado = true;
                }
            }

            if($cancelado){
                TToast::show("error", "Lançamento cancelado, não é possível editar.", "topRight", "fas:info-circle");
                return;
            }

            if(empty($param['total_parcelas']) || empty($param['total_conta']))
            {
                return;
            }

            $parcelas = [];
            $valores = [];
            $tipos = [];
            $dt_pagamentos = [];
            $quitada = [];
            $ids = [];
            $parcelas = [];

            $parcCount = 0;

            if(count($search)>0){
                foreach($search as $objeto){
                    if($objeto->dt_pagamento==''){
                        $objeto->delete();
                    }
                }
            }

            $lancamentos = Lancamento::where('conta_id','=',$id)->load();
            foreach($lancamentos as $lancamento){
                $parcCount++;
                if($lancamento->dt_pagamento){
                    $quitada[] = $lancamento->id;
                    $ids[] = $lancamento->id;

                    $data = new DateTime($lancamento->dt_vencimento);
                    $data = $data->format('d/m/Y');
                    $vencimentos[] = $data;

                    $parcelas[] = $parcCount;

                    $valores[] = $lancamento->valor;
                    $tipos[] = $lancamento->tipo_pagamento_id;

                    $data = new DateTime($lancamento->dt_pagamento);
                    $data = $data->format('d/m/Y');
                    $dt_pagamentos[] = $data;
                }else{
                    $ids[] = $lancamento->id;

                    $parcelas[] = $parcCount;
                }
            }

            $total = (float) str_replace(',', '.', str_replace('.', '', $param['total_conta']));

            for($i=0;$i<count($quitada);$i++){
                $total = $total - $valores[$i];
                $valores[$i] = number_format($valores[$i], 2, ',', '.');
            }

            $total_parcelas = $param['total_parcelas']-count($quitada);
            if($total_parcelas<=0){
                throw new Exception("Não é possivel alterar o valor de parcelas quitadas. Aumente o número de parcelas.");
            }
            $valorParcela = round(($total / $total_parcelas), 2);

            for ($i = 1; $i <= $total_parcelas; $i++) {
                if(empty($quitada)){
                    $vencimentos[] = date('d/m/Y', strtotime("now +{$i} month"));
                    $tipos[] = TipoPagamento::DINHEIRO;

                    $dt_pagamentos[] = "";
                    $parcelas[] = end($parcelas) +1;
                }else{
                    $data = new DateTime(end($vencimentos));
                    $data = $data->format('d/m/Y');
                    $vencimentos[] = date('d/m/Y', strtotime("+1 months", strtotime($data)));

                    $parcelas[] = end($parcelas) +1;

                    $tipos[] = end($tipos) ?? TipoPagamento::DINHEIRO;
                }
                $valores[] = ($i == $param['total_parcelas']) ? number_format(($total - ($valorParcela * ($i-1))), 2, ',', '.') : number_format($valorParcela, 2, ',', '.');
            }

            $valoresSoma = str_replace(',', '.', str_replace('.', '', $valores));
            $data = new stdClass;

            $data->lancamento_conta_id = $ids;
            $data->lancamento_conta_parcela = $parcelas;
            $data->lancamento_conta_dt_vencimento = $vencimentos;
            $data->lancamento_conta_valor = $valores;
            $data->lancamento_conta_tipo_pagamento_id = $tipos;
            $data->lancamento_conta_dt_pagamento = $dt_pagamentos;
            $data->total_valor_parcelas = array_sum($valoresSoma);

            for ($i = 0; $i < 2; $i++) {
                if(empty($ids[$i]) || $ids[$i]==''){
                    if($id){
                        $object = new Lancamento();
                        $object->conta_id = $id;
                        $object->parcela = $i+1;
                        $object->dt_vencimento = implode('-', array_reverse(explode('/', $vencimentos[$i])));
                        $object->valor = str_replace(',', '.', str_replace('.', '', $valores[$i]));
                        $object->tipo_pagamento_id = $tipos[$i];
                        $object->store();
                        $ids[$i]=$object->id;
                    }
                }else{
                    $object = Lancamento::find($ids[$i]);
                    $object->dt_vencimento = implode('-', array_reverse(explode('/', $vencimentos[$i])));
                    $object->valor = str_replace(',', '.', str_replace('.', '', $valores[$i]));
                    $object->tipo_pagamento_id = $tipos[$i];
                    $object->store();
                }
            }

            TFieldList::clearRows('parcelas');
            TFieldList::addRows('parcelas', $param['total_parcelas'] - 1);

            TForm::sendData(self::$formName, $data, false, false, 50*$total_parcelas);

            TTransaction::close();

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
            $cancelado = false;

            $this->form->validate(); // validate form data

            $object = new Conta(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$object->id)
            {
                $object->tipo_conta_id = TipoConta::RECEBER;
                $object->data_emissao = date('Y-m-d H:i:s');

                if(!$object->tipo_documento_financeiro_id){
                    throw new Exception("O campo Tipo de documento é obrigatório.");
                }

                if(!$object->numero_documento){
                    throw new Exception("O campo Número do documento é obrigatório.");
                }

                if(!$object->pessoa_id){
                    throw new Exception("O campo Cliente é obrigatório.");
                }
            }else{
                $lancamentos = Lancamento::where('conta_id','=',$object->id)->load();
                foreach($lancamentos as $lancamento){
                    if($lancamento->cancelado=='S'){
                        $cancelado = true;
                    }
                }

                /*$objeto = Conta::find($object->id);
                $object->tipo_documento_financeiro_id = $objeto->tipo_documento_financeiro_id;
                $object->numero_documento = $objeto->numero_documento;
                $object->atendimento_id = $objeto->atendimento_id;
                $object->contrato_id = $objeto->contrato_id;
                $object->pessoa_id = $objeto->pessoa_id;*/
                if($cancelado){
                    $object->total_conta = $objeto->total_conta;
                    $object->total_parcelas = $objeto->total_parcelas;
                }
            }

            if ($data->tipo == 'S' && !$cancelado)
            {
                $object->total_conta = $data->valor;

            }

            if (empty($object->total_conta))
            {
                throw new Exception('Valor não foi preenchido');
            }

            if(!$data->id){
                if ($data->tipo != 'P' && !$cancelado){
                    $object->proximo_vencimento_lancamento = $data->data_vencimento;
                }else if ($data->tipo == 'P'){
                    $object->proximo_vencimento_lancamento = date('Y-m-d', strtotime("now +1 month"));
                }
            }else{
                $lancamentosConta = Lancamento::where('conta_id','=',$data->id)->orderBy('dt_vencimento')->load();
                foreach($lancamentosConta as $lancamentoConta){
                    if(empty($lancamentoConta->dt_pagamento)){
                        $object->proximo_vencimento_lancamento = $lancamentoConta->dt_vencimento;
                        break;
                    }
                }
            }

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

            $objetos = Lancamento::where('conta_id','=',$object->id)->load();
            if($objetos)
            {
                foreach($objetos as $objeto){
                    if(!$objeto->dt_pagamento && $objeto->cancelado!='S'){
                        $objeto->delete();  
                    }
                }
            }

//<generatedAutoCode>
            $this->criteria_parcelas->setProperty('order', 'parcela asc');
//</generatedAutoCode>
            $lancamento_conta_items = $this->storeItems('Lancamento', 'conta_id', $object, $this->parcelas, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_parcelas); 

            $this->total_valor_parcelas = 0;
            $this->count_parcelas = 1;

            if ($data->tipo != 'P' && !$cancelado)
            {
                if ($data->id)
                {
                    Lancamento::where('conta_id', '=', $data->id)->delete();
                }

                if (empty($data->valor) || empty($data->data_vencimento) || empty($data->tipo_pagamento))
                {
                    throw new Exception('Valor, data de vencimento e tipo de pagamento são obrigatórios');
                }

                $object->total_conta = $data->valor;
                $object->total_parcelas = 1;

                $lancamento = new Lancamento();
                $lancamento->dt_vencimento = $data->data_vencimento;
                $lancamento->valor = $data->valor;
                $lancamento->tipo_pagamento_id = $data->tipo_pagamento;
                $lancamento->conta_id = $object->id;
                $lancamento->store();

                if (empty($data->id) && $data->repetir_ate_final_ano)
                {
                    $mes = date('m', strtotime($lancamento->dt_vencimento));
                    $qtde = 12 - $mes;

                    for($i = 1; $i <= $qtde; $i++)
                    {
                        $conta = clone $object;
                        unset($conta->id);
                        $conta->store();

                        $newlancamento = clone $lancamento;
                        unset($newlancamento->id);
                        unset($newlancamento->dt_vencimento);
                        $newlancamento->conta_id = $conta->id;
                        $newlancamento->dt_vencimento = date('Y-m-d', strtotime("+$i months", strtotime($lancamento->dt_vencimento)));
                        $newlancamento->store();
                    }
                }
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ContaReceberList', 'onShow', $loadPageParam); 

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
            if (isset($param['key']))
            {
                $this->form->getField('pessoa_id')->setEditable(FALSE);
                $this->form->getField('profissional_id')->setEditable(FALSE);
                $this->form->getField('tipo_documento_financeiro_id')->setEditable(FALSE);

                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Conta($key); // instantiates the Active Record 

                                $object->contrato_numero = $object->contrato->numero;
                $object->processo_numero_cnj_numero = $object->processo->numero_cnj_numero;
                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->criteria_parcelas->setProperty('order', 'parcela asc');
                $this->parcelas_items = $this->loadItems('Lancamento', 'conta_id', $object, $this->parcelas, function($masterObject, $detailObject, $objectItems){ 
                    if($detailObject->dt_pagamento=='' || $detailObject->dt_pagamento==null){
                        $masterObject->em_aberto = true;
                    }
                    if($detailObject->cancelado=='S'){
                        $masterObject->cancelado = true;
                        $masterObject->motivo_cancelamento = $detailObject->motivo_cancelamento;
                    }

                }, $this->criteria_parcelas); 

                if($object->em_aberto){
                    if(!$object->cancelado){
                        TScript::create("$(\"[name='btnEditarParcelas']\").closest('.fb-inline-field-container').show()");
                        TScript::create("$(\"[name='btnCancelarParcelas']\").closest('.fb-inline-field-container').show()");
                    }else{
                        TScript::create("$('label:contains(\"Motivo do cancelamento das parcelas:\")').show();");
                        TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').show();");
                        TScript::create("$('label:contains(\"MOTIVO CANCELAMENTO\")').html('".$object->motivo_cancelamento."')");
                    }
                }

                if($object->atendimento_id!=null && $object->atendimento_id!='' && $object->tipo_documento_financeiro->padrao_id == TipoDocFinanceiroPadrao::ATENDIMENTO){
                    TScript::create("$('label:contains(\"Número do documento:\")').html('Número do atendimento:')");
                    TScript::create("$(\"[name='atendimento_id']\").closest('.fb-inline-field-container').show()");
                }else
                if($object->contrato_id!=null && $object->contrato_id!='' && $object->tipo_documento_financeiro->padrao_id == TipoDocFinanceiroPadrao::CONTRATO){
                    TScript::create("$('label:contains(\"Número do documento:\")').html('Número do contrato:')");
                    TScript::create("$(\"[name='contrato_numero']\").closest('.fb-inline-field-container').show()");
                }else
                if($object->contrato_id!=null && $object->contrato_id!='' && $object->tipo_documento_financeiro->padrao_id == TipoDocFinanceiroPadrao::PROCESSO){
                    TScript::create("$('label:contains(\"Número do documento:\")').html('Número do processo:')");
                    TScript::create("$(\"[name='processo_numero_cnj_numero']\").closest('.fb-inline-field-container').show()");
                }else
                if($object->numero_documento!=null && $object->numero_documento!=''){
                    TScript::create("$(\"[name='numero_documento']\").closest('.fb-inline-field-container').show()");
                }

                $object->total_valor_parcelas = 0;
                $object->tipo = 'P';

                $this->form->setData($object); // fill the form 

                $this->parcelas->getFoot()->style = 'display: none';

                self::onChange(['tipo' => 'P']);

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

        $this->parcelas->addHeader();
        $this->parcelas->addDetail($this->default_item_parcelas);

        TTransaction::open(self::$database);

        $escritorio = Escritorio::where('system_unit_id', '=', TSession::getValue('userunitid'))->first();

        $data = new stdClass;
        $data->escritorio_id = $escritorio->id;
        $data->tipo = 'S';
        $this->form->setData($data);

        TTransaction::close();

        self::onChange(['tipo' => 'S']);

    }

    public function onShow($param = null)
    {
        $this->parcelas->addHeader();
        $this->parcelas->addDetail($this->default_item_parcelas);

        TTransaction::open(self::$database);
        $data = new stdClass;

        $escritorio = Escritorio::where('system_unit_id', '=', TSession::getValue('userunitid'))->first();

        $data->escritorio_id = $escritorio->id;
        $data->tipo = 'S';

        //Pesquisa um tipo de documento financeiro configurado como padrão para inserção manual
        $tiposDocFinanceiro = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::MANUAL)
                                                    ->load();

        foreach($tiposDocFinanceiro as $tipoDocFinanceiro){
            //Se for de contas a receber ou ambos
            if($tipoDocFinanceiro->tipo_conta_id == TipoConta::RECEBER || $tipoDocFinanceiro->tipo_conta_id == TipoConta::AMBOS){
                //Envia id para o campo de tipo de documento
                $data->tipo_documento_financeiro_id = $tipoDocFinanceiro->id;

                //Se gera codigo automaticamente
                if($tipoDocFinanceiro->gera_codigo == 'S'){
                    //Pesquisa a ultima conta com esse tipo de documento
                    $conta = Conta::where('tipo_documento_financeiro_id','=',$tipoDocFinanceiro->id)
                                    ->orderBy('numero_documento')
                                    ->last();
                    //Se existir
                    if($conta){
                        //popula o numero com o ultimo somado de 1
                        $data->numero_documento = $conta->numero_documento +1;
                    }else{
                        //caso contrario, popula como 1
                        $data->numero_documento = 1;
                    }
                    //Desabilita a edição de número
                    TEntry::disableField(self::$formName, 'numero_documento');

                }
            }
        }

        $this->form->setData($data);

        //exibe o numero do documento
        TScript::create("$(\"[name='numero_documento']\").closest('.fb-inline-field-container').show()");

        TTransaction::close();

        self::onChange(['tipo' => 'S']);

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

