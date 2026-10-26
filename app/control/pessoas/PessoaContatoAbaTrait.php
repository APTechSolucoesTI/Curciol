<?php
/**
 * Aba "Contatos" dos cadastros de pessoa (Cliente, Parte contraria, Representante legal, Fornecedor, Parceiro,
 * Profissional), com a chave "Principal" como na aba Enderecos (26/10/2026).
 *
 * A classe que usa precisa de: BuilderMasterDetailTrait, $this->form e private static $formName.
 * No construtor: $this->criarAbaContatos() onde a aba deve aparecer.
 * No onSave: $antes = PessoaContatoService::estadoAtual($id) antes de gravar a pessoa;
 *            PessoaContatoService::exigirTelefoneValido($data->telefone) antes de gravar;
 *            $this->salvarContatos($object, $param, $antes) depois de gravar a pessoa (mesma transacao).
 * No onEdit: $this->carregarContatos($object).
 */
trait PessoaContatoAbaTrait
{
    protected function criarAbaContatos(): void
    {
        $pessoa_contato_pessoa_descricao = new TEntry('pessoa_contato_pessoa_descricao');
        $pessoa_contato_pessoa_id = new THidden('pessoa_contato_pessoa_id');
        $pessoa_contato_pessoa_telefone = new TEntry('pessoa_contato_pessoa_telefone');
        $pessoa_contato_pessoa_email = new TEntry('pessoa_contato_pessoa_email');
        $pessoa_contato_pessoa_principal = new TCheckButton('pessoa_contato_pessoa_principal');
        $button_adicionar_pessoa_contato_pessoa = new TButton('button_adicionar_pessoa_contato_pessoa');

        $pessoa_contato_pessoa_descricao->setTip("Casa, Escritório, Celular");
        $pessoa_contato_pessoa_descricao->forceUpperCase();
        $pessoa_contato_pessoa_email->forceLowerCase();

        $pessoa_contato_pessoa_principal->setUseSwitch(true, 'blue');
        $pessoa_contato_pessoa_principal->setIndexValue("S");
        $pessoa_contato_pessoa_principal->setInactiveIndexValue("N");

        $button_adicionar_pessoa_contato_pessoa->addStyleClass('btn-default');
        $button_adicionar_pessoa_contato_pessoa->setImage('fas:plus #2ecc71');
        $button_adicionar_pessoa_contato_pessoa->setAction(new TAction([$this, 'onAddDetailPessoaContatoPessoa'],['static' => 1]), "Adicionar");
        $button_adicionar_pessoa_contato_pessoa->id = '64be92cf7046f';

        $pessoa_contato_pessoa_email->setMaxLength(255);
        $pessoa_contato_pessoa_telefone->setMaxLength(20);
        $pessoa_contato_pessoa_descricao->setMaxLength(255);

        $pessoa_contato_pessoa_id->setSize(200);
        $pessoa_contato_pessoa_email->setSize('100%');
        $pessoa_contato_pessoa_telefone->setSize('100%');
        $pessoa_contato_pessoa_descricao->setSize('100%');

        TScript::create(
            "$(document).on('keydown', 'input[name=\"pessoa_contato_pessoa_telefone\"]', function (e) {
            var digit = e.key.replace(/\D/g, '');
            var value = $(this).val().replace(/\D/g, '');
            var size = value.concat(digit).length;
            $(this).mask((size <= 10) ? '(##) ####-####' : '(##) #####-####');
            });"
        );

        $this->form->appendPage("Contatos");

        $this->detailFormPessoaContatoPessoa = new BootstrapFormBuilder('detailFormPessoaContatoPessoa');
        $this->detailFormPessoaContatoPessoa->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormPessoaContatoPessoa->setProperty('class', 'form-horizontal builder-detail-form');

        $row = $this->detailFormPessoaContatoPessoa->addFields([new TLabel("Descrição:", '#ff0000', '12px', null, '100%'),$pessoa_contato_pessoa_descricao,$pessoa_contato_pessoa_id],[new TLabel("Principal:", null, '12px', null, '100%'),$pessoa_contato_pessoa_principal]);
        $row->layout = [' col-sm-9',' col-sm-3'];

        $row = $this->detailFormPessoaContatoPessoa->addFields([new TLabel("Telefone:", null, '12px', null, '100%'),$pessoa_contato_pessoa_telefone],[new TLabel("Email:", null, '12px', null, '100%'),$pessoa_contato_pessoa_email]);
        $row->layout = ['col-sm-6','col-sm-6'];

        $row = $this->detailFormPessoaContatoPessoa->addFields([new TLabel(new TImage('fas:info-circle #03A9F4')." O contato principal tem o mesmo telefone da aba Dados cadastrais: mudar um muda o outro.", '#607D8B', '11px', 'I')]);
        $row->layout = [' col-sm-12'];

        $row = $this->detailFormPessoaContatoPessoa->addFields([$button_adicionar_pessoa_contato_pessoa]);
        $row->layout = [' col-sm-12'];

        $row = $this->detailFormPessoaContatoPessoa->addFields([new THidden('pessoa_contato_pessoa__row__id')]);
        $this->pessoa_contato_pessoa_criteria = new TCriteria();
        $this->pessoa_contato_pessoa_criteria->setProperty('order', 'id desc');

        $this->pessoa_contato_pessoa_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->pessoa_contato_pessoa_list->generateHiddenFields();
        $this->pessoa_contato_pessoa_list->setId('pessoa_contato_pessoa_list');

        $this->pessoa_contato_pessoa_list->style = 'width:100%';
        $this->pessoa_contato_pessoa_list->class .= ' table-bordered';

        $column_pessoa_contato_pessoa_descricao = new TDataGridColumn('descricao', "Descrição", 'left');
        $column_pessoa_contato_pessoa_telefone = new TDataGridColumn('telefone', "Telefone", 'left');
        $column_pessoa_contato_pessoa_email = new TDataGridColumn('email', "Email", 'left');
        $column_pessoa_contato_pessoa_principal_transformed = new TDataGridColumn('principal', "Principal", 'left');

        $column_pessoa_contato_pessoa__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_pessoa_contato_pessoa__row__data->setVisibility(false);

        $action_onEditDetailPessoaContato = new TDataGridAction(array(__CLASS__, 'onEditDetailPessoaContato'));
        $action_onEditDetailPessoaContato->setUseButton(false);
        $action_onEditDetailPessoaContato->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailPessoaContato->setLabel("Editar");
        $action_onEditDetailPessoaContato->setImage('far:edit #478fca');
        $action_onEditDetailPessoaContato->setFields(['__row__id', '__row__data']);

        $this->pessoa_contato_pessoa_list->addAction($action_onEditDetailPessoaContato);
        $action_onDeleteDetailPessoaContato = new TDataGridAction(array(__CLASS__, 'onDeleteDetailPessoaContato'));
        $action_onDeleteDetailPessoaContato->setUseButton(false);
        $action_onDeleteDetailPessoaContato->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailPessoaContato->setLabel("Excluir");
        $action_onDeleteDetailPessoaContato->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailPessoaContato->setFields(['__row__id', '__row__data']);

        $this->pessoa_contato_pessoa_list->addAction($action_onDeleteDetailPessoaContato);

        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_descricao);
        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_telefone);
        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_email);
        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_principal_transformed);

        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa__row__data);

        $column_pessoa_contato_pessoa_principal_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return ($value === 'S') ? 'Sim' : 'Não';
        });

        $this->pessoa_contato_pessoa_list->createModel();
        $tableResponsiveDiv = new TElement('div');
        $tableResponsiveDiv->class = 'table-responsive';
        $tableResponsiveDiv->add($this->pessoa_contato_pessoa_list);
        $this->detailFormPessoaContatoPessoa->addContent([$tableResponsiveDiv]);
        $row = $this->form->addFields([$this->detailFormPessoaContatoPessoa]);
        $row->layout = [' col-sm-12'];
    }

    public  function onAddDetailPessoaContatoPessoa($param = null)
    {
        try
        {
            $data = $this->form->getData();

            $data->pessoa_contato_pessoa_telefone = preg_replace('/[^0-9]/', '', (string) $data->pessoa_contato_pessoa_telefone);
            $data->pessoa_contato_pessoa_principal = (($data->pessoa_contato_pessoa_principal ?? '') === 'S') ? 'S' : 'N';

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"Descrição", 'name'=>"pessoa_contato_pessoa_descricao", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Email", 'name'=>"pessoa_contato_pessoa_email", 'class'=>'TEmailValidator', 'value'=>[]];
            foreach($requiredFields as $requiredField)
            {
                try
                {
                    (new $requiredField['class'])->validate($requiredField['label'], $data->{$requiredField['name']}, $requiredField['value']);
                }
                catch(Exception $e)
                {
                    $errors[] = $e->getMessage() . '.';
                }
             }
             if ($data->pessoa_contato_pessoa_principal === 'S' && PessoaContatoService::telefoneValido($data->pessoa_contato_pessoa_telefone) === null)
             {
                 $errors[] = PessoaContatoService::MSG_PRINCIPAL_INVALIDO;
             }
             if(count($errors) > 0)
             {
                 throw new Exception(implode('<br>', $errors));
             }

            $__row__id = !empty($data->pessoa_contato_pessoa__row__id) ? $data->pessoa_contato_pessoa__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new PessoaContato();
            $grid_data->__row__id = $__row__id;
            $grid_data->descricao = $data->pessoa_contato_pessoa_descricao;
            $grid_data->id = $data->pessoa_contato_pessoa_id;
            $grid_data->telefone = $data->pessoa_contato_pessoa_telefone;
            $grid_data->email = $data->pessoa_contato_pessoa_email;
            $grid_data->principal = $data->pessoa_contato_pessoa_principal;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['descricao'] =  $param['pessoa_contato_pessoa_descricao'] ?? null;
            $__row__data['__display__']['id'] =  $param['pessoa_contato_pessoa_id'] ?? null;
            $__row__data['__display__']['telefone'] =  $param['pessoa_contato_pessoa_telefone'] ?? null;
            $__row__data['__display__']['email'] =  $param['pessoa_contato_pessoa_email'] ?? null;
            $__row__data['__display__']['principal'] =  $data->pessoa_contato_pessoa_principal;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->pessoa_contato_pessoa_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('pessoa_contato_pessoa_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->pessoa_contato_pessoa_descricao = '';
            $data->pessoa_contato_pessoa_id = '';
            $data->pessoa_contato_pessoa_telefone = '';
            $data->pessoa_contato_pessoa_email = '';
            $data->pessoa_contato_pessoa_principal = '';
            $data->pessoa_contato_pessoa__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#64be92cf7046f');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public static function onEditDetailPessoaContato($param = null)
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->pessoa_contato_pessoa_descricao = $__row__data->__display__->descricao ?? null;
            $data->pessoa_contato_pessoa_id = $__row__data->__display__->id ?? null;
            $data->pessoa_contato_pessoa_telefone = $__row__data->__display__->telefone ?? null;
            $data->pessoa_contato_pessoa_email = $__row__data->__display__->email ?? null;
            $data->pessoa_contato_pessoa_principal = $__row__data->__display__->principal ?? ($__row__data->principal ?? null);
            $data->pessoa_contato_pessoa__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#64be92cf7046f');
               if(!element.attr('add')){
                   element.attr('add', base64_encode(element.html()));
               }
               element.html(\"<span><i class='fas fa-save' style='color:#000000;padding-right:4px;'></i>Salvar</span>\");
               if(!element.attr('edit')){
                   element.attr('edit', base64_encode(element.html()));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public static function onDeleteDetailPessoaContato($param = null)
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->pessoa_contato_pessoa_descricao = '';
            $data->pessoa_contato_pessoa_id = '';
            $data->pessoa_contato_pessoa_telefone = '';
            $data->pessoa_contato_pessoa_email = '';
            $data->pessoa_contato_pessoa_principal = '';
            $data->pessoa_contato_pessoa__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('pessoa_contato_pessoa_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#64be92cf7046f');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    /**
     * Grava a grade de contatos e acerta o contato principal com o telefone dos Dados cadastrais (ver
     * PessoaContatoService::aplicarPrincipal). Chamar depois de gravar a pessoa, dentro da transacao do salvar.
     */
    protected function salvarContatos(Pessoa $object, $param, array $antes): void
    {
        $marcados = array_count_values((array) ($param['pessoa_contato_pessoa_list_principal'] ?? []));
        if (($marcados['S'] ?? 0) > 1)
        {
            throw new Exception(PessoaContatoService::MSG_VARIOS_PRINCIPAIS);
        }

        PessoaContatoService::desmarcarPrincipais($object->id);

        $this->storeMasterDetailItems('PessoaContato', 'pessoa_id', 'pessoa_contato_pessoa', $object, $param['pessoa_contato_pessoa_list___row__data'] ?? [], $this->form, $this->pessoa_contato_pessoa_list, function($masterObject, $detailObject){

            $detailObject->principal = (($detailObject->principal ?? '') === 'S') ? 'S' : 'N';
            $detailObject->descricao = (string) ($detailObject->descricao ?? '');

        }, $this->pessoa_contato_pessoa_criteria);

        PessoaContatoService::aplicarPrincipal($object->id, $antes);

        // a grade pode ter trocado o telefone dos Dados cadastrais
        $object->telefone = PessoaContatoService::telefoneDados($object->id);
        TForm::sendData(self::$formName, (object) ['telefone' => $object->telefone]);
    }

    protected function carregarContatos(Pessoa $object): void
    {
        $this->loadMasterDetailItems('PessoaContato', 'pessoa_id', 'pessoa_contato_pessoa', $object, $this->form, $this->pessoa_contato_pessoa_list, $this->pessoa_contato_pessoa_criteria, function($masterObject, $detailObject, $objectItems){

        });
    }
}
