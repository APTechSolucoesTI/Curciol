<?php

class TutoriaisVideosList extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_TutoriaisVideosList';

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
        $this->form->setFormTitle("Tutoriais");


        $video1 = new BElement('iframe');


        $video1->setSize('100%', 80);

        $this->video1 = $video1;

        $video1 = '<iframe width="560" height="315" src="https://www.youtube.com/embed/mTxcSUPPw_Y?si=i2oW95FNTmIDqoTX" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
        $row1 = $this->form->addFields([$video1],[],[]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        // create the form actions

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Configurações","Tutoriais"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onShow($param = null)
    {               

    } 

}

