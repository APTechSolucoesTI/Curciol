<?php

class ValidarDocumentoForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ValidarDocumentoForm';

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
        $this->form->setFormTitle("Validar Documento");


        $codigo = new TEntry('codigo');


        $codigo->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Código autenticador:", null, '14px', null, '100%'),$codigo]);
        $row1->layout = [' col-sm-12'];

        // create the form actions
        $btn_onaction = $this->form->addAction("Validar", new TAction([$this, 'onAction']), 'fas:check-square #ffffff');
        $this->btn_onaction = $btn_onaction;
        $btn_onaction->addStyleClass('btn-success'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onAction($param = null) 
    {
        $this->form->setData($this->form->getData());

        try
        {
            TTransaction::open('escritorio');

            if (empty($param['codigo']))
            {
                throw new Exception('Código inválido!');
            }

            $documento = Documento::where('autenticador', '=', $param['codigo'])->first();

            if (! $documento)
            {
                throw new Exception('Código inválido!');
            }

            $document = DocumentoDocument::onGenerate(['returnFile' => 1, 'key' => $documento->id]);

            new TMessage('info', "<b>Documento válido</b><br/><br/><a class='btn btn-default' target='newwindow' href='engine.php?class=ValidarDocumentoForm&method=openDocument&file={$document}'>Clique aqui para visualizar o documento original</a><br/><br/>");

            TTransaction::close();
        }
        catch (Exception $e)
        {
            sleep(2);
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

    } 

    public function openDocument($param)
    {
        $file              = $param['file']??'';
        $info              = pathinfo($file);
        $extension         = $info['extension'];
        $content_type_list = ['pdf' => 'application/pdf'];

        if (file_exists($file) AND in_array(strtolower($extension), array_keys($content_type_list)))
        {
            $basename = basename($file);
            $filesize = filesize($file);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-type: " . $content_type_list[strtolower($extension)] );
            header("Content-Length: {$filesize}");
            header("Content-disposition: inline; filename=\"{$basename}\"");
            header("Content-Transfer-Encoding: binary");

            echo file_get_contents($file);
        }
        else
        {
            echo 'Oopss: Essa solicitação não é válida'; die;
        }
    }

}

