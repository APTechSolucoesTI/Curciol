<?php

class ModalEditarParcelasAberto extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalEditarParcelasAberto';

    use BuilderMasterDetailFieldListTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.40, null);
        parent::setTitle("Modal Editar Parcelas em Aberto");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Modal Editar Parcelas em Aberto");

        $criteria_parcela_pagamento = new TCriteria();

        TSession::setValue('conta_id', (int) $param['conta_id']);

        $conta_id = new THidden('conta_id');
        $total_conta = new THidden('total_conta');
        $page = new THidden('page');
        $valor_em_aberto = new TNumeric('valor_em_aberto', '2', ',', '.' );
        $lancamento_conta_id = new THidden('lancamento_conta_id[]');
        $lancamento_conta___row__id = new THidden('lancamento_conta___row__id[]');
        $lancamento_conta___row__data = new THidden('lancamento_conta___row__data[]');
        $id = new TEntry('id[]');
        $parcela_numero = new TEntry('parcela_numero[]');
        $parcela_valor = new TNumeric('parcela_valor[]', '2', ',', '.' );
        $parcela_vencimento = new TDate('parcela_vencimento[]');
        $parcela_pagamento = new TDBCombo('parcela_pagamento[]', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_parcela_pagamento );
        $this->detalhe_parcelas = new TFieldList();
        $diferenca = new TNumeric('diferenca', '2', ',', '.' );

        $this->detalhe_parcelas->addField(null, $lancamento_conta_id, []);
        $this->detalhe_parcelas->addField(null, $lancamento_conta___row__id, ['uniqid' => true]);
        $this->detalhe_parcelas->addField(null, $lancamento_conta___row__data, []);
        $this->detalhe_parcelas->addField(new TLabel("", null, '12px', null), $id, ['width' => '10%']);
        $this->detalhe_parcelas->addField(new TLabel("Parcela", null, '12px', null), $parcela_numero, ['width' => '10%']);
        $this->detalhe_parcelas->addField(new TLabel("Valor", null, '12px', null), $parcela_valor, ['width' => '33%']);
        $this->detalhe_parcelas->addField(new TLabel("Data vencimento", null, '12px', null), $parcela_vencimento, ['width' => '33%']);
        $this->detalhe_parcelas->addField(new TLabel("Tipo de pagamento", null, '12px', null), $parcela_pagamento, ['width' => '33%']);

        $this->detalhe_parcelas->width = '100%';
        $this->detalhe_parcelas->setFieldPrefix('lancamento_conta');
        $this->detalhe_parcelas->name = 'detalhe_parcelas';

        $this->criteria_detalhe_parcelas = new TCriteria();
        $this->default_item_detalhe_parcelas = new stdClass();

        $this->form->addField($lancamento_conta_id);
        $this->form->addField($lancamento_conta___row__id);
        $this->form->addField($lancamento_conta___row__data);
        $this->form->addField($id);
        $this->form->addField($parcela_numero);
        $this->form->addField($parcela_valor);
        $this->form->addField($parcela_vencimento);
        $this->form->addField($parcela_pagamento);

        $this->detalhe_parcelas->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $parcela_valor->setExitAction(new TAction([$this,'onChangeValor']));

        $parcela_valor->addValidation("Valor", new TRequiredListValidator()); 
        $parcela_vencimento->addValidation("Data vencimento", new TRequiredListValidator()); 
        $parcela_pagamento->addValidation("Tipo de pagamento", new TRequiredListValidator()); 

        $parcela_vencimento->setMask('dd/mm/yyyy');
        $parcela_vencimento->setDatabaseMask('yyyy-mm-dd');
        $parcela_pagamento->setDefaultOption(false);
        $parcela_pagamento->enableSearch();
        $page->setValue($param['page']);
        $conta_id->setValue($param['conta_id']);
        $total_conta->setValue($param['total_conta'] ?? null);

        $id->setEditable(false);
        $diferenca->setEditable(false);
        $parcela_numero->setEditable(false);
        $valor_em_aberto->setEditable(false);

        $page->setSize(200);
        $id->setSize('100%');
        $conta_id->setSize(200);
        $total_conta->setSize(200);
        $diferenca->setSize('100%');
        $parcela_valor->setSize('100%');
        $parcela_numero->setSize('100%');
        $valor_em_aberto->setSize('100%');
        $parcela_pagamento->setSize('100%');
        $parcela_vencimento->setSize('100%');


        $row1 = $this->form->addFields([$conta_id],[$total_conta],[$page]);
        $row1->layout = ['col-sm-3','col-sm-3','col-sm-2'];

        $row2 = $this->form->addFields([new TLabel("Valor em aberto:", null, '12px', null, '100%'),$valor_em_aberto],[],[]);
        $row2->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $row3 = $this->form->addFields([$this->detalhe_parcelas]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([$diferenca],[],[]);
        $row4->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-success'); 

        parent::add($this->form);

    }

    public static function onChangeValor($param = null) 
    {
        try 
        {
            $total_conta = $param['total_conta'];
            $total_conta = str_replace(',', '.', str_replace('.', '', $total_conta));

            $parcela_valor = str_replace(',', '.', str_replace('.', '', $param['parcela_valor']));
            $soma = array_sum($parcela_valor);

            $data = new stdClass;
            $data->diferenca = number_format($total_conta - $soma, 2, ',', '.');

            TForm::sendData(self::$formName, $data);

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
            TTransaction::open('escritorio');

            $id = TSession::getValue('conta_id');

            if(str_replace(',', '.', str_replace('.', '', $param['diferenca'])) != 0){
                throw new Exception('A Soma dos valores informados é diferente do valor em aberto.');
            }

            for($i=0;$i<count($param['parcela_valor']);$i++){
                if($param['parcela_valor'][$i]=='' && $param['parcela_vencimento'][$i]=='' && !isset($param['parcela_pagamento'][$i])){
                    unset($param['lancamento_conta_id'][$i]);
                    unset($param['lancamento_conta___row__id'][$i]);
                    unset($param['lancamento_conta___row__data'][$i]);
                    unset($param['id'][$i]);
                    unset($param['parcela_numero'][$i]);
                    unset($param['parcela_valor'][$i]);
                    unset($param['parcela_vencimento'][$i]);
                    unset($param['parcela_pagamento'][$i]);
                }else{
                    if($param['parcela_valor'][$i]==''){
                        throw new Exception('Informe o valor da parcela.');
                        return ModalEditarParcelasAberto::onShow();
                    }
                    if($param['parcela_vencimento'][$i]==''){
                        throw new Exception('Informe a data de vencimento da parcela.');
                        return ModalEditarParcelasAberto::onShow();
                    }
                    if(!isset($param['parcela_pagamento'][$i])){
                        throw new Exception('Informe o tipo de pagamento da parcela.');
                        return ModalEditarParcelasAberto::onShow();
                    }
                }
            }

            $search = Lancamento::where('id','not in',$param['id'])
                                ->where('conta_id','=',$id)
                                ->load();

            if(count($search)>0){
                foreach($search as $objeto){
                    if($objeto->dt_pagamento==''){
                        $objeto->delete();
                    }
                }
            }

            for ($i = 0; $i < count($param['parcela_valor']); $i++) {
                $ids[] = $param['id'][$i];
                $vencimentos[] = implode('-', array_reverse(explode('/', $param['parcela_vencimento'][$i])));;
                $tipos[] = $param['parcela_pagamento'][$i];
                $valores[] = str_replace(',', '.', str_replace('.', '', $param['parcela_valor'][$i]));

                if($param['id'][$i]=='' || empty($param['id'][$i])){
                    $object = new Lancamento();
                    $object->conta_id = $id;
                    $object->parcela = $i+1;
                    $object->dt_vencimento = implode('-', array_reverse(explode('/', $param['parcela_vencimento'][$i])));
                    $object->valor = str_replace(',', '.', str_replace('.', '', $param['parcela_valor'][$i]));
                    $object->tipo_pagamento_id = $param['parcela_pagamento'][$i];
                    $object->store();
                }else{
                    $data = Lancamento::find($param['id'][$i]);
                    $data->dt_vencimento = implode('-', array_reverse(explode('/', $param['parcela_vencimento'][$i])));
                    $data->valor = str_replace(',', '.', str_replace('.', '', $param['parcela_valor'][$i]));
                    $data->tipo_pagamento_id = $param['parcela_pagamento'][$i];
                    $data->store();
                }
            }

            $pageParam = ['key'=>$id];
            TApplication::loadPage($param['page'], 'onEdit', $pageParam);
            TScript::create("$(\"[page_name='ModalEditarParcelasAberto']\").remove()");

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               
        $this->detalhe_parcelas->addHeader();
        $this->detalhe_parcelas->addDetail($this->default_item_detalhe_parcelas);

        $this->detalhe_parcelas->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $this->detalhe_parcelas->makeScrollable(250);
        TTransaction::open('escritorio');

        $id = TSession::getValue('conta_id');

        if(empty($id) || empty($param['total_conta']))
        {
            return;
        }

        $datas_pagamentos = $param['lancamento_conta_dt_pagamento'];
        $soma = 0;

        foreach ($datas_pagamentos as $key=>$data_pagamento){
            if($data_pagamento!=''){
                $soma += (float) str_replace(',', '.', str_replace('.', '', $param['lancamento_conta_valor'][$key]));
                unset($param['lancamento_conta_id'][$key]);
                unset($param['lancamento_conta___row__id'][$key]);
                unset($param['lancamento_conta___row__data'][$key]);
                unset($param['lancamento_conta_valor'][$key]);
                unset($param['lancamento_conta_dt_vencimento'][$key]);
                unset($param['lancamento_conta_tipo_pagamento_id'][$key]);
                unset($param['lancamento_conta_parcela'][$key]);
                unset($param['lancamento_conta_dt_pagamento'][$key]);

            }
        }

        $total_parcelas = count($param['lancamento_conta_valor']);
        reset($param['lancamento_conta_valor']);
        $primeiraKey = key($param['lancamento_conta_valor']);
        end($param['lancamento_conta_valor']);
        $ultimaKey = key($param['lancamento_conta_valor']);
        for ($i=$primeiraKey; $i<=$ultimaKey; $i++) {
            $ids[] = $param['lancamento_conta_id'][$i] ?? null;
            $vencimentos[] = $param['lancamento_conta_dt_vencimento'][$i];
            $tipos[] = $param['lancamento_conta_tipo_pagamento_id'][$i];
            $parcelas[] = $param['lancamento_conta_parcela'][$i];
            $valores[] = $param['lancamento_conta_valor'][$i];
        }

        $total = (float) str_replace(',', '.', str_replace('.', '', $param['total_conta']));

        $data = new stdClass;
        $data->id = $ids;
        $data->parcela_valor = $valores;
        $data->parcela_vencimento = $vencimentos;
        $data->parcela_pagamento = $tipos;
        $data->parcela_numero = $parcelas;
        $data->total_conta = number_format($total - $soma, 2, ',', '.');
        $data->valor_em_aberto = number_format($total - $soma, 2, ',', '.');
        $data->diferenca = number_format($total - $soma - (array_sum($valores)), 2, ',', '.');

        $this->detalhe_parcelas->addRows('detalhe_parcelas', count($valores));

        TForm::sendData(self::$formName, $data, false, false, 50*count($valores));

        TTransaction::close();
    } 

}

