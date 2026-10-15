<?php

class FinanceiroConfiguracaoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private static $database = 'escritorio';
    private static $formName = 'form_FinanceiroConfiguracaoForm';

    public function __construct($param)
    {
        parent::__construct();

        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle("Configuração do financeiro");

        $id = new THidden('id');
        $pct_nf_padrao = new TEntry('pct_nf_padrao');
        $criteriaCategoria = new TCriteria();
        $criteriaCategoria->add(new TFilter('tipo_conta_id', '=', TipoConta::PAGAR));
        $criteriaDocumento = new TCriteria();
        $criteriaDocumento->add(new TFilter('tipo_conta_id', '=', TipoConta::PAGAR));
        $repasse_dias_vencimento = new TEntry('repasse_dias_vencimento');
        $repasse_categoria_conta_id = new TDBCombo('repasse_categoria_conta_id', 'escritorio', 'CategoriaConta', 'id', '{nome}', 'nome asc', $criteriaCategoria);
        $repasse_tipo_documento_id = new TDBCombo('repasse_tipo_documento_id', 'escritorio', 'TipoDocumentoFinanceiro', 'id', '{nome}', 'nome asc', $criteriaDocumento);
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_id = new TDBCombo('modificacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}', 'name asc');

        $pct_nf_padrao->setSize('100%');
        $pct_nf_padrao->setTip('Sugerido no campo "% custo de NF" ao gerar o financeiro do levantamento (pode ser alterado em cada geração).');
        $pct_nf_padrao->addValidation("% custo de NF padrão", new TRequiredValidator());

        $repasse_dias_vencimento->setSize('100%');
        $repasse_dias_vencimento->setMask('9!');
        $repasse_dias_vencimento->setTip('A conta a pagar do colaborador vence N dias depois do recebimento (0 = no mesmo dia).');
        $repasse_dias_vencimento->addValidation("Vencimento do repasse (dias)", new TRequiredValidator());
        $repasse_categoria_conta_id->enableSearch();
        $repasse_categoria_conta_id->setSize('100%');
        $repasse_categoria_conta_id->addValidation("Categoria do repasse", new TRequiredValidator());
        $repasse_tipo_documento_id->enableSearch();
        $repasse_tipo_documento_id->setSize('100%');
        $repasse_tipo_documento_id->addValidation("Documento do repasse", new TRequiredValidator());

        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setEditable(false);
        $data_modificacao->setSize('100%');
        $modificacao_user_id->setEditable(false);
        $modificacao_user_id->setSize('100%');

        $this->form->addFields([$id]);

        $this->form->addContent([new TFormSeparator("Levantamento judicial", '#333', '18', '#eee')]);
        $row1 = $this->form->addFields([new TLabel("% custo de NF padrão:", '#ff0000', '14px', null, '100%'), $pct_nf_padrao], []);
        $row1->layout = ['col-sm-4', 'col-sm-8'];

        $this->form->addContent([new TFormSeparator("Repasses a colaboradores", '#333', '18', '#eee')]);
        $this->form->addContent(["<div style='color:#64748b;font-size:12px;margin-bottom:6px'>A cada recebimento de conta a receber com repasse, o sistema gera uma conta a pagar para cada colaborador com estes dados.</div>"]);
        $rowRepasse = $this->form->addFields([new TLabel("Vencimento (dias após o recebimento):", '#ff0000', '14px', null, '100%'), $repasse_dias_vencimento], [new TLabel("Categoria:", '#ff0000', '14px', null, '100%'), $repasse_categoria_conta_id], [new TLabel("Documento:", '#ff0000', '14px', null, '100%'), $repasse_tipo_documento_id]);
        $rowRepasse->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row2 = $this->form->addFields([], [new TLabel("Atualizado em:", null, '14px', null, '100%'), $data_modificacao], [new TLabel("Atualizado por:", null, '14px', null, '100%'), $modificacao_user_id]);
        $row2->layout = ['col-sm-6', 'col-sm-3', 'col-sm-3'];

        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'], ['static' => 1]), 'fas:save #ffffff');
        $btn_onsave->addStyleClass('btn-primary');

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');
        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=FinanceiroConfiguracaoForm]');
        $style->width = '50% !important';
        $style->show(true);
    }

    public function onSave($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $this->form->validate();
            $data = $this->form->getData();

            $texto = trim((string) $data->pct_nf_padrao);
            if (!preg_match('/^\d+([.,]\d+)?$/', $texto))
            {
                throw new Exception("% custo de NF padrão inválido: '{$texto}'. Use apenas números com vírgula ou ponto (ex.: 16 ou 15,5).");
            }
            $pct = (float) str_replace(',', '.', $texto);
            if ($pct > 100)
            {
                throw new Exception('O % custo de NF padrão deve estar entre 0 e 100.');
            }

            $dias = trim((string) $data->repasse_dias_vencimento);
            if (!preg_match('/^\d{1,3}$/', $dias))
            {
                throw new Exception('Vencimento do repasse: informe o número de dias (0 a 999).');
            }

            $object = FinanceiroConfiguracao::find(FinanceiroConfiguracao::ID_UNICO) ?: new FinanceiroConfiguracao();
            $object->id = FinanceiroConfiguracao::ID_UNICO;
            $object->pct_nf_padrao = $pct;
            $object->repasse_dias_vencimento = (int) $dias;
            $object->repasse_categoria_conta_id = (int) $data->repasse_categoria_conta_id;
            $object->repasse_tipo_documento_id = (int) $data->repasse_tipo_documento_id;
            $object->store();

            TTransaction::close();

            TToast::show('success', "Configuração salva", 'topRight', 'far:check-circle');
            TScript::create("Template.closeRightPanel();");
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
            TTransaction::rollback();
        }
    }

    public function onEdit($param)
    {
        try
        {
            TTransaction::open(self::$database);
            $object = FinanceiroConfiguracao::find(FinanceiroConfiguracao::ID_UNICO);
            TTransaction::close();

            $data = new stdClass;
            $data->id = FinanceiroConfiguracao::ID_UNICO;
            $data->pct_nf_padrao = LevantamentoFinanceiroForm::pctTexto($object ? $object->pct_nf_padrao : FinanceiroConfiguracao::PCT_NF_PADRAO);
            $data->repasse_dias_vencimento = $object->repasse_dias_vencimento ?? 0;
            $data->repasse_categoria_conta_id = $object->repasse_categoria_conta_id ?? null;
            $data->repasse_tipo_documento_id = $object->repasse_tipo_documento_id ?? null;
            $data->data_modificacao = $object->data_modificacao ?? null;
            $data->modificacao_user_id = $object->modificacao_user_id ?? null;
            $this->form->setData($data);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {
        $this->onEdit($param);
    }

    public static function getFormName()
    {
        return self::$formName;
    }
}
