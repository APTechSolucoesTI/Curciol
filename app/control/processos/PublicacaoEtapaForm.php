<?php

class PublicacaoEtapaForm extends TWindow
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'PublicacaoEtapa';
    private static $primaryKey = 'id';
    private static $formName = 'form_PublicacaoEtapaForm';

    use BuilderMasterDetailFieldListTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        parent::setSize(0.60, null);
        parent::setTitle("Cadastro de Etapa de Andamento");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Cadastro de Etapa de Andamento");

        TTransaction::open(self::$database);

        $valorPadrao = 1;

        $procura = PublicacaoEtapa::all();

        if (!empty($procura)) {

            $priorities = [];

            foreach ($procura as $p) {
                $priorities[] = (int) $p->ordem_prioridade;
            }

            $valorPadrao = max($priorities) + 1;
        }

        TTransaction::close();

        $id = new TEntry('id');
        $etapa_nome = new TEntry('etapa_nome');
        $ordem_prioridade = new TSpinner('ordem_prioridade');
        $cor = new TColor('cor');
        $detalhamento = new TText('detalhamento');
        $descricao = new TText('descricao');
        $etapa_palavras_chaves_publicacao_etapa_id = new THidden('etapa_palavras_chaves_publicacao_etapa_id[]');
        $etapa_palavras_chaves_publicacao_etapa___row__id = new THidden('etapa_palavras_chaves_publicacao_etapa___row__id[]');
        $etapa_palavras_chaves_publicacao_etapa___row__data = new THidden('etapa_palavras_chaves_publicacao_etapa___row__data[]');
        $etapa_palavras_chaves_publicacao_etapa_palavra_chave = new TEntry('etapa_palavras_chaves_publicacao_etapa_palavra_chave[]');
        $this->fieldList_69cd0d44bad25 = new TFieldList();
        $extrajudicial = new TCombo('extrajudicial');
        $judicial = new TCombo('judicial');

        /*
            PRE-PROCESSO: marca esta etapa como a que abre um pre-processo.

            Vale por trilha: no maximo uma etapa marcada para Judicial e uma
            para Extrajudicial. Uma etapa que sirva as duas ocupa os dois
            lugares. Ao criar um pre-processo, o andamento inicial nasce nesta
            etapa, com o nome e a explicacao cadastrados aqui.
        */
        $padrao_pre_processo = new TCheckButton('padrao_pre_processo');

        $this->fieldList_69cd0d44bad25->addField(null, $etapa_palavras_chaves_publicacao_etapa_id, []);
        $this->fieldList_69cd0d44bad25->addField(null, $etapa_palavras_chaves_publicacao_etapa___row__id, ['uniqid' => true]);
        $this->fieldList_69cd0d44bad25->addField(null, $etapa_palavras_chaves_publicacao_etapa___row__data, []);
        $this->fieldList_69cd0d44bad25->addField(new TLabel("Palavras chave:", null, '14px', null), $etapa_palavras_chaves_publicacao_etapa_palavra_chave, ['width' => '100%']);

        $this->fieldList_69cd0d44bad25->width = '100%';
        $this->fieldList_69cd0d44bad25->setFieldPrefix('etapa_palavras_chaves_publicacao_etapa');
        $this->fieldList_69cd0d44bad25->name = 'fieldList_69cd0d44bad25';

        $this->criteria_fieldList_69cd0d44bad25 = new TCriteria();
        $this->default_item_fieldList_69cd0d44bad25 = new stdClass();

        $this->form->addField($etapa_palavras_chaves_publicacao_etapa_id);
        $this->form->addField($etapa_palavras_chaves_publicacao_etapa___row__id);
        $this->form->addField($etapa_palavras_chaves_publicacao_etapa___row__data);
        $this->form->addField($etapa_palavras_chaves_publicacao_etapa_palavra_chave);

        $this->fieldList_69cd0d44bad25->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $etapa_nome->addValidation("Nome", new TRequiredValidator()); 
        $ordem_prioridade->addValidation("Prioridade", new TRequiredValidator()); 
        $cor->addValidation("Cor", new TRequiredValidator()); 
        $descricao->addValidation("O que acontece nesta etapa?", new TRequiredValidator()); 
        $etapa_palavras_chaves_publicacao_etapa_palavra_chave->addValidation("Palavras Chave", new TRequiredListValidator()); 

        $id->setEditable(false);
        $ordem_prioridade->setRange(0, 2000, 1);
        $etapa_palavras_chaves_publicacao_etapa_palavra_chave->setTip("Insira as palavras chaves que determinarão a etapa da publicação!");
        $etapa_palavras_chaves_publicacao_etapa_palavra_chave->forceLowerCase();
        $judicial->addItems(["S"=>"Sim","N"=>"Não"]);
        $extrajudicial->addItems(["S"=>"Sim","N"=>"Não"]);

        /* PRE-PROCESSO */
        $padrao_pre_processo->setUseSwitch(true, 'orange');
        $padrao_pre_processo->setIndexValue("S");
        $padrao_pre_processo->setInactiveIndexValue("N");
        $padrao_pre_processo->setValue('N');

        $judicial->enableSearch();
        $extrajudicial->enableSearch();

        $id->setSize(100);
        $cor->setSize(110);
        $judicial->setSize('100%');
        $etapa_nome->setSize('100%');
        $ordem_prioridade->setSize(110);
        $detalhamento->setSize('100%', 70);
        $descricao->setSize('100%', 70);
        $extrajudicial->setSize('100%');
        $etapa_palavras_chaves_publicacao_etapa_palavra_chave->setSize('100%');

        $ordem_prioridade->setValue($valorPadrao);
        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id],[new TLabel("Nome da etapa:", null, '14px', null, '100%'),$etapa_nome],[new TLabel("Prioridade:", null, '14px', null, '100%'),$ordem_prioridade],[new TLabel("Cor:", null, '14px', null, '100%'),$cor]);
        $row1->layout = ['col-sm-1',' col-sm-7',' col-sm-2',' col-sm-2'];

        $row2 = $this->form->addFields([new TLabel("O que acontece nesta etapa?", null, '14px', null, '100%'),$descricao]);
        $row2->layout = [' col-sm-12'];

        $row2b = $this->form->addFields([new TLabel("Detalhamento:", null, '14px', null, '100%'),$detalhamento]);
        $row2b->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([$this->fieldList_69cd0d44bad25]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([new TLabel("Extrajudicial:", null, '14px', null, '100%'),$extrajudicial],[new TLabel("Judicial:", null, '14px', null, '100%'),$judicial],[new TLabel("Padrão pré-processo:", null, '14px', null, '100%'),$padrao_pre_processo]);
        $row4->layout = [' col-sm-2','col-sm-2','col-sm-4'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['PublicacaoEtapaHeaderList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::add($this->form);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $data = $this->form->getData(); // get form data as array

            $validaOrdem = PublicacaoEtapa::where('ordem_prioridade', '=', $data->ordem_prioridade)->first();
            $validaCor = PublicacaoEtapa::where('cor', '=', $data->cor)->first();

            if (!empty($validaOrdem) && $data->id != $validaOrdem->id) {
                TTransaction::close();
                new TMessage('info', 'Já existe uma etapa com esta prioridade!');
                return;
            }                
            if (!empty($validaCor) && $data->id != $validaCor->id) {
                TTransaction::close();
                new TMessage('info', 'Já existe uma etapa com esta cor!');
                return;
            }  

            $object = new PublicacaoEtapa(); // create an empty object

            $object->fromArray( (array) $data); // load the object with data

            /*
                PRE-PROCESSO: somente uma etapa padrao por trilha.

                A etapa que ocupava o lugar e desmarcada antes de gravar esta.
                Sem isso o salvamento esbarraria nos indices unicos parciais do
                banco, e o usuario veria um erro em vez de uma troca.

                A troca e silenciosa no banco, entao a tela precisa dizer o que
                saiu - senao alguem marca uma etapa nova sem perceber que
                desligou outra.
            */
            $substituidas = [];

            if (strtoupper(trim((string) $object->padrao_pre_processo)) === 'S')
            {
                $substituidas = PreProcessoService::liberarEtapaPadrao($object);
            }

            $object->store(); // save the object

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

//<generatedAutoCode>
            $this->criteria_fieldList_69cd0d44bad25->setProperty('order', 'palavra_chave asc');
//</generatedAutoCode>
            $etapa_palavras_chaves_publicacao_etapa_items = $this->storeItems('EtapaPalavrasChaves', 'publicacao_etapa_id', $object, $this->fieldList_69cd0d44bad25, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_69cd0d44bad25); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');

            if (!empty($substituidas))
            {
                TToast::show(
                    'info',
                    'Esta etapa passou a ser o padrão de pré-processo. Deixou de ser: ' . implode(', ', $substituidas) . '.',
                    'topRight',
                    'fas:info-circle'
                );
            }

            TApplication::loadPage('PublicacaoEtapaHeaderList', 'onShow', $loadPageParam); 

                TWindow::closeWindow(parent::getId());
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
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new PublicacaoEtapa($key); // instantiates the Active Record 

                $this->criteria_fieldList_69cd0d44bad25->setProperty('order', 'palavra_chave asc');
                $this->fieldList_69cd0d44bad25_items = $this->loadItems('EtapaPalavrasChaves', 'publicacao_etapa_id', $object, $this->fieldList_69cd0d44bad25, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_69cd0d44bad25); 

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

        $this->fieldList_69cd0d44bad25->addHeader();
        $this->fieldList_69cd0d44bad25->addDetail($this->default_item_fieldList_69cd0d44bad25);

        $this->fieldList_69cd0d44bad25->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_69cd0d44bad25->addHeader();
        $this->fieldList_69cd0d44bad25->addDetail($this->default_item_fieldList_69cd0d44bad25);

        $this->fieldList_69cd0d44bad25->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

