<?php

/**
 * Cadastro de beneficiario do credito (painel lateral, como ParceiroForm).
 * Excluir so e permitido se o beneficiario nunca foi usado num financeiro
 * de levantamento gerado; senao a orientacao e desativar.
 */
class BeneficiarioCreditoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private static $database = 'escritorio';
    private static $activeRecord = 'BeneficiarioCredito';
    private static $primaryKey = 'id';
    private static $formName = 'form_BeneficiarioCredito';

    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle("Cadastro de beneficiário do crédito");

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $regra = new TCombo('regra');
        $ativo = new TCheckButton('ativo');

        $nome->addValidation("Nome", new TRequiredValidator());
        $regra->addValidation("Regra de cálculo", new TRequiredValidator());

        $regra->addItems(BeneficiarioCredito::regras());
        $nome->forceUpperCase();
        $nome->setMaxLength(120);

        $ativo->setIndexValue('S');
        $ativo->setUseSwitch(true, 'blue');
        $ativo->setValue('S');

        $id->setEditable(false);

        $id->setSize('100%');
        $nome->setSize('100%');
        $regra->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id]);
        $row1->layout = [' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TLabel("Regra de cálculo:", '#ff0000', '14px', null, '100%'),$regra]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([new TLabel("Ativo:", null, '14px', null, '100%'),$ativo]);
        $row4->layout = [' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'], ['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary');

        $btn_ondelete = $this->form->addAction("Excluir", new TAction([$this, 'onDelete'], ['static' => 1]), 'far:trash-alt #dd5a43');
        $this->btn_ondelete = $btn_ondelete;

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['BeneficiarioCreditoList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);
    }

    public function onSave($param = null)
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $this->form->validate(); // validate form data

            $data = $this->form->getData();

            $nome = trim((string) $data->nome);
            self::exigirNomeUnico($nome, (int) $data->id);

            $object = new BeneficiarioCredito(); // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            $object->nome  = $nome;
            $object->ativo = ($data->ativo === 'S') ? 'S' : 'N';

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object

            $data->id = $object->id;

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('BeneficiarioCreditoList', 'onShow');

            TScript::create("Template.closeRightPanel();");
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Nome repetido (sem considerar acento nem caixa): a tabela tem UNIQUE no
     * nome, mas a comparacao dela e exata e o banco pode ter acentos em NFD.
     */
    private static function exigirNomeUnico(string $nome, int $id): void
    {
        $chave = self::chaveNome($nome);

        foreach (BeneficiarioCredito::getObjects() ?: [] as $outro)
        {
            if ((int) $outro->id !== $id && self::chaveNome((string) $outro->nome) === $chave)
            {
                throw new Exception("Já existe um beneficiário com o nome \"{$outro->nome}\".");
            }
        }
    }

    /** Nome sem acento (NFC ou NFD) e sem caixa, para comparar. */
    private static function chaveNome(string $nome): string
    {
        if (class_exists('Normalizer')) { $nome = Normalizer::normalize($nome, Normalizer::FORM_D) ?: $nome; }
        $nome = preg_replace('/\p{Mn}+/u', '', TratamentosService::removerAcentos($nome));

        return mb_strtolower(str_replace(['ç', 'Ç'], 'c', trim($nome)));
    }

    /**
     * Pede confirmacao e, se o beneficiario nunca foi usado, exclui.
     */
    public function onDelete($param = null)
    {
        try
        {
            $id = (int) ($param['id'] ?? 0);

            if ($id <= 0)
            {
                throw new Exception('Salve o beneficiário antes de excluir.');
            }

            self::exigirNaoUsado($id);

            new TQuestion(
                'Deseja realmente excluir este beneficiário?',
                new TAction([__CLASS__, 'onConfirmDelete'], ['id' => $id, 'static' => 1])
            );
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onConfirmDelete($param = null)
    {
        try
        {
            $id = (int) ($param['id'] ?? 0);

            TTransaction::open(self::$database);

            self::exigirNaoUsado($id);

            $object = new BeneficiarioCredito($id);
            $object->delete();

            TTransaction::close();

            TToast::show('success', "Registro excluído", 'topRight', 'far:check-circle');
            TApplication::loadPage('BeneficiarioCreditoList', 'onShow');
            TScript::create("Template.closeRightPanel();");
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Beneficiario usado em financeiro gerado nao pode sumir (historico).
     * Abre a propria transacao se nao houver uma.
     */
    public static function exigirNaoUsado(int $id): void
    {
        $abriu = false;

        if (!TTransaction::get())
        {
            TTransaction::open(self::$database);
            $abriu = true;
        }

        try
        {
            $usos = LevantamentoFinanceiro::where('beneficiario_credito_id', '=', $id)->count();
        }
        finally
        {
            if ($abriu) { TTransaction::close(); }
        }

        if ($usos > 0)
        {
            throw new Exception('Beneficiário usado em financeiros gerados — desative em vez de excluir.');
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

                $object = new BeneficiarioCredito($key); // instantiates the Active Record

                $this->form->setData($object); // fill the form

                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear();
                $this->form->setData((object) ['ativo' => 'S']);
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
        $this->form->setData((object) ['ativo' => 'S']);
    }

    public function onShow($param = null)
    {

    }

    public static function getFormName()
    {
        return self::$formName;
    }

}
