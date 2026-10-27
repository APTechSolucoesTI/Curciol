<?php
/**
 * Aba "Contatos" dos cadastros de pessoa (Cliente, Parte contraria, Representante legal, Fornecedor, Parceiro,
 * Profissional), com a chave "Principal" como na aba Enderecos (26/10/2026; regras revistas em 27/10/2026).
 * Contato com telefone e/ou e-mail; nome preenchido sozinho quando possivel; um principal por pessoa, e o mesmo
 * telefone so e principal de uma pessoa (ver PessoaContatoService).
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
        // Telefone principal (aba Dados cadastrais): ao sair do campo, avisa se o formato e invalido ou se o numero ja e
        // o principal de outra pessoa (o salvar continua bloqueando o invalido)
        $telefoneDados = $this->form->getField('telefone');
        if ($telefoneDados instanceof TEntry)
        {
            $telefoneDados->setExitAction(new TAction([$this, 'onTelefoneDadosSaida'], ['static' => 1]));
        }

        $pessoa_contato_pessoa_nome = new TEntry('pessoa_contato_pessoa_nome');
        $pessoa_contato_pessoa_descricao = new TEntry('pessoa_contato_pessoa_descricao');
        $pessoa_contato_pessoa_id = new THidden('pessoa_contato_pessoa_id');
        $pessoa_contato_pessoa_telefone = new TEntry('pessoa_contato_pessoa_telefone');
        $pessoa_contato_pessoa_email = new TEntry('pessoa_contato_pessoa_email');
        $pessoa_contato_pessoa_principal = new TCheckButton('pessoa_contato_pessoa_principal');
        $button_adicionar_pessoa_contato_pessoa = new TButton('button_adicionar_pessoa_contato_pessoa');

        $pessoa_contato_pessoa_nome->setTip("De quem é o contato. Preenchido sozinho quando o telefone já é o principal de alguém.");
        $pessoa_contato_pessoa_nome->forceUpperCase();
        $pessoa_contato_pessoa_descricao->setTip("Casa, Escritório, Celular, Filha... (opcional)");
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
        $pessoa_contato_pessoa_nome->setMaxLength(255);

        // telefone que ja e o principal de alguem: sugere o nome dessa pessoa
        $pessoa_contato_pessoa_telefone->setExitAction(new TAction([$this, 'onTelefoneContatoSaida'], ['static' => 1]));

        $pessoa_contato_pessoa_id->setSize(200);
        $pessoa_contato_pessoa_email->setSize('100%');
        $pessoa_contato_pessoa_telefone->setSize('100%');
        $pessoa_contato_pessoa_descricao->setSize('100%');
        $pessoa_contato_pessoa_nome->setSize('100%');

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

        $row = $this->detailFormPessoaContatoPessoa->addFields([new TLabel("Nome:", null, '12px', null, '100%'),$pessoa_contato_pessoa_nome,$pessoa_contato_pessoa_id],[new TLabel("Descrição:", null, '12px', null, '100%'),$pessoa_contato_pessoa_descricao],[new TLabel("Principal:", null, '12px', null, '100%'),$pessoa_contato_pessoa_principal]);
        $row->layout = [' col-sm-5',' col-sm-5',' col-sm-2'];

        $row = $this->detailFormPessoaContatoPessoa->addFields([new TLabel("Telefone:", null, '12px', null, '100%'),$pessoa_contato_pessoa_telefone],[new TLabel("Email:", null, '12px', null, '100%'),$pessoa_contato_pessoa_email]);
        $row->layout = ['col-sm-6','col-sm-6'];

        $row = $this->detailFormPessoaContatoPessoa->addFields([new TLabel(new TImage('fas:info-circle #03A9F4')." Informe o telefone, o e-mail ou os dois. Só um contato é o principal: marcar outro tira a marca do anterior. O principal tem o telefone e o e-mail da aba Dados cadastrais: mudar um muda o outro. Um telefone só pode ser principal de uma pessoa.", '#607D8B', '11px', 'I')]);
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

        $column_pessoa_contato_pessoa_nome = new TDataGridColumn('nome', "Nome", 'left');
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

        $this->pessoa_contato_pessoa_list->addColumn($column_pessoa_contato_pessoa_nome);
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
            $data->pessoa_contato_pessoa_email = trim((string) $data->pessoa_contato_pessoa_email);
            $data->pessoa_contato_pessoa_principal = (($data->pessoa_contato_pessoa_principal ?? '') === 'S') ? 'S' : 'N';

            // telefone ou e-mail (um dos dois basta); telefone preenchido precisa ser valido
            $errors = [];
            if ($data->pessoa_contato_pessoa_telefone === '' && $data->pessoa_contato_pessoa_email === '')
            {
                $errors[] = PessoaContatoService::MSG_CONTATO_VAZIO;
            }
            if ($data->pessoa_contato_pessoa_telefone !== '' && PessoaContatoService::telefoneValido($data->pessoa_contato_pessoa_telefone) === null)
            {
                $errors[] = PessoaContatoService::MSG_TELEFONE_INVALIDO;
            }
            try
            {
                (new TEmailValidator)->validate("Email", $data->pessoa_contato_pessoa_email);
            }
            catch(Exception $e)
            {
                $errors[] = $e->getMessage() . '.';
            }
            if(count($errors) > 0)
            {
                throw new Exception(implode('<br>', $errors));
            }

            if ($data->pessoa_contato_pessoa_telefone !== '')
            {
                $data->pessoa_contato_pessoa_telefone = PessoaContatoService::telefoneValido($data->pessoa_contato_pessoa_telefone);
            }

            $__row__id = !empty($data->pessoa_contato_pessoa__row__id) ? $data->pessoa_contato_pessoa__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            // um telefone so pode ser principal de uma pessoa
            if ($data->pessoa_contato_pessoa_principal === 'S' && ($dono = PessoaContatoService::donoDoPrincipal($data->pessoa_contato_pessoa_telefone, $data->id ?? null)))
            {
                throw new Exception(PessoaContatoService::mensagemTelefoneDeOutro($data->pessoa_contato_pessoa_telefone, $dono));
            }

            // sem nome: o principal e da propria pessoa; nos outros, o dono do telefone (quem o tem como principal)
            if (trim((string) $data->pessoa_contato_pessoa_nome) === '')
            {
                $data->pessoa_contato_pessoa_nome = ($data->pessoa_contato_pessoa_principal === 'S')
                    ? mb_strtoupper(trim((string) ($data->nome ?? '')))
                    : PessoaContatoService::nomeDoTelefone($data->pessoa_contato_pessoa_telefone);
            }

            $grid_data = new PessoaContato();
            $grid_data->__row__id = $__row__id;
            $grid_data->nome = trim((string) $data->pessoa_contato_pessoa_nome) !== '' ? $data->pessoa_contato_pessoa_nome : null;
            $grid_data->descricao = trim((string) $data->pessoa_contato_pessoa_descricao) !== '' ? $data->pessoa_contato_pessoa_descricao : null;
            $grid_data->id = $data->pessoa_contato_pessoa_id;
            $grid_data->telefone = $data->pessoa_contato_pessoa_telefone !== '' ? $data->pessoa_contato_pessoa_telefone : null;
            $grid_data->email = $data->pessoa_contato_pessoa_email !== '' ? $data->pessoa_contato_pessoa_email : null;
            $grid_data->principal = $data->pessoa_contato_pessoa_principal;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['nome'] =  $grid_data->nome;
            $__row__data['__display__']['descricao'] =  $param['pessoa_contato_pessoa_descricao'] ?? null;
            $__row__data['__display__']['id'] =  $param['pessoa_contato_pessoa_id'] ?? null;
            $__row__data['__display__']['telefone'] =  $param['pessoa_contato_pessoa_telefone'] ?? null;
            $__row__data['__display__']['email'] =  $param['pessoa_contato_pessoa_email'] ?? null;
            $__row__data['__display__']['principal'] =  $data->pessoa_contato_pessoa_principal;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->pessoa_contato_pessoa_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('pessoa_contato_pessoa_list', $grid_data->__row__id, $row);

            // so um principal por pessoa: o que era principal deixa de ser e o telefone e o e-mail dos Dados acompanham
            $dados = null;
            if ($grid_data->principal === 'S')
            {
                $this->desmarcarOutrosPrincipais($param, $__row__id);
                $dados = ['telefone' => PessoaContatoService::formatarTelefone($grid_data->telefone), 'email' => (string) $grid_data->email];
            }

            TTransaction::close();

            $data = new stdClass;
            if ($dados !== null)
            {
                $data->telefone = $dados['telefone'];
                $data->email = $dados['email'];
            }
            $data->pessoa_contato_pessoa_nome = '';
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
            TTransaction::rollbackAll();
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    /**
     * Saida do Telefone principal (aba Dados cadastrais): formato invalido ou numero que ja e o principal de outra pessoa.
     */
    public static function onTelefoneDadosSaida($param = null)
    {
        try
        {
            $telefone = (string) ($param['telefone'] ?? '');
            if (preg_replace('/\D/', '', $telefone) === '')
            {
                return;
            }
            if (PessoaContatoService::telefoneValido($telefone) === null)
            {
                TToast::show('error', PessoaContatoService::MSG_TELEFONE_INVALIDO, 'topRight', 'fas:exclamation-circle');
                return;
            }
            TTransaction::open(self::$database);
            $dono = PessoaContatoService::donoDoPrincipal($telefone, $param['id'] ?? null);
            TTransaction::close();
            if ($dono)
            {
                TToast::show('warning', PessoaContatoService::mensagemTelefoneDeOutro($telefone, $dono, PessoaContatoService::MSG_SAIDA_TELEFONE_DE_OUTRO), 'topRight', 'fas:exclamation-triangle');
            }
        }
        catch (Exception $e)
        {
            TTransaction::rollbackAll();
        }
    }

    /**
     * Saida do telefone na aba Contatos: avisa se o formato e invalido; se o numero ja e o principal de alguem e o nome
     * esta vazio, sugere o nome.
     */
    public static function onTelefoneContatoSaida($param = null)
    {
        try
        {
            $telefone = (string) ($param['pessoa_contato_pessoa_telefone'] ?? '');
            if (preg_replace('/\D/', '', $telefone) !== '' && PessoaContatoService::telefoneValido($telefone) === null)
            {
                TToast::show('error', PessoaContatoService::MSG_TELEFONE_INVALIDO, 'topRight', 'fas:exclamation-circle');
                return;
            }
            if (trim((string) ($param['pessoa_contato_pessoa_nome'] ?? '')) !== '' || $telefone === '')
            {
                return;
            }
            TTransaction::open(self::$database);
            $nome = PessoaContatoService::nomeDoTelefone($param['pessoa_contato_pessoa_telefone']);
            TTransaction::close();
            if ($nome !== '')
            {
                TForm::sendData(self::$formName, (object) ['pessoa_contato_pessoa_nome' => $nome], false, false);
            }
        }
        catch (Exception $e)
        {
            TTransaction::rollbackAll();
        }
    }

    /**
     * Na grade (antes de salvar): as outras linhas marcadas como principal passam a "Não".
     */
    private function desmarcarOutrosPrincipais($param, string $rowIdAtual): void
    {
        foreach ((array) ($param['pessoa_contato_pessoa_list___row__data'] ?? []) as $serializado)
        {
            $item = unserialize(base64_decode((string) $serializado));
            if (!is_object($item) || ($item->__row__id ?? '') === $rowIdAtual || ($item->principal ?? '') !== 'S')
            {
                continue;
            }

            $item->principal = 'N';
            $item->__display__ = (object) ($item->__display__ ?? []);
            $item->__display__->principal = 'N';

            $contato = new PessoaContato();
            foreach (['id', 'nome', 'descricao', 'telefone', 'email', 'principal'] as $campo)
            {
                $contato->$campo = $item->$campo ?? null;
            }
            $contato->__row__id = $item->__row__id;
            $contato->__row__data = base64_encode(serialize($item));

            $row = $this->pessoa_contato_pessoa_list->addItem($contato);
            $row->id = $item->__row__id;
            TDataGrid::replaceRowById('pessoa_contato_pessoa_list', $item->__row__id, $row);
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
            $data->pessoa_contato_pessoa_nome = $__row__data->__display__->nome ?? ($__row__data->nome ?? null);
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
            $data->pessoa_contato_pessoa_nome = '';
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
     * Grava a grade de contatos e acerta o contato principal com o telefone e o e-mail dos Dados cadastrais (ver
     * PessoaContatoService::aplicarPrincipal). Chamar depois de gravar a pessoa, dentro da transacao do salvar.
     * $comRepresentantes: o cadastro tem a aba Representantes legais (Cliente) - os contatos deles entram/saem aqui.
     */
    protected function salvarContatos(Pessoa $object, $param, array $antes, bool $comRepresentantes = false): void
    {
        $marcados = array_count_values((array) ($param['pessoa_contato_pessoa_list_principal'] ?? []));
        if (($marcados['S'] ?? 0) > 1)
        {
            throw new Exception(PessoaContatoService::MSG_VARIOS_PRINCIPAIS);
        }

        PessoaContatoService::desmarcarPrincipais($object->id);

        $this->storeMasterDetailItems('PessoaContato', 'pessoa_id', 'pessoa_contato_pessoa', $object, $param['pessoa_contato_pessoa_list___row__data'] ?? [], $this->form, $this->pessoa_contato_pessoa_list, function($masterObject, $detailObject){

            $detailObject->principal = (($detailObject->principal ?? '') === 'S') ? 'S' : 'N';

            // um telefone so pode ser principal de uma pessoa: mensagem clara em vez do erro do indice unico
            if ($detailObject->principal === 'S' && ($dono = PessoaContatoService::donoDoPrincipal($detailObject->telefone, $masterObject->id)))
            {
                throw new Exception(PessoaContatoService::mensagemTelefoneDeOutro($detailObject->telefone, $dono));
            }

        }, $this->pessoa_contato_pessoa_criteria);

        $aviso = PessoaContatoService::aplicarPrincipal($object->id, $antes);

        if ($comRepresentantes)
        {
            PessoaContatoService::sincronizarRepresentantes($object->id);
        }

        // a grade pode ter trocado o telefone e o e-mail dos Dados cadastrais
        $dados = PessoaContatoService::dadosAtuais($object->id);
        $object->telefone = $dados['telefone'];
        $object->email = $dados['email'];
        TForm::sendData(self::$formName, (object) ['telefone' => PessoaContatoService::formatarTelefone($dados['telefone']), 'email' => $dados['email']]);

        if ($aviso)
        {
            TToast::show('warning', $aviso, 'topRight', 'fas:exclamation-triangle');
        }
    }

    protected function carregarContatos(Pessoa $object): void
    {
        // reaberto depois de salvar (Navegacao::continuarNoRegistro): volta na aba em que estava
        if (isset($_GET['current_tab']) && ctype_digit((string) $_GET['current_tab']))
        {
            $this->form->setCurrentPage((int) $_GET['current_tab']);
        }

        $this->loadMasterDetailItems('PessoaContato', 'pessoa_id', 'pessoa_contato_pessoa', $object, $this->form, $this->pessoa_contato_pessoa_list, $this->pessoa_contato_pessoa_criteria, function($masterObject, $detailObject, $objectItems){

        });
    }
}
