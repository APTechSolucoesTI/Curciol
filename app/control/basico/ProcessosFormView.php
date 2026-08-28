<?php

class ProcessosFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Pessoa';

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

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        TSession::setValue('keyVoltar', $param['key'] ?? null);

        $pessoa = new Pessoa($param['key']);
        // define the form title
        $this->form->setFormTitle("");

        $transformed_pessoa_cpf_cnpj = call_user_func(function($value, $object, $row)
        {
            if(strlen($value)==11){
                return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $value);
            } 

            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $value);

        }, $pessoa->cpf_cnpj, $pessoa, null);    

        $transformed_pessoa_telefone = call_user_func(function($value, $object, $row)
        {
            if($value!=NULL && $value!="" && isset($value) && !empty($value)){
                $number="(".substr($value,0,2).") ".substr($value,2,-4)."-".substr($value,-4);
                // primeiro substr pega apenas o DDD e coloca dentro do (), segundo subtr pega os números do 3º até faltar 4, insere o hifem, e o ultimo pega apenas o 4 ultimos digitos

                return $number;
            }
        }, $pessoa->telefone, $pessoa, null);

        $this->form->style = 'padding:18px; background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 8px 24px rgba(15,23,42,.06);';

        $style = new TElement('style');
        $style->add("
            #b69d541fa523dc,
            #b69d541fa523dc > div {
                width: 100%;
            }

            #b69d541fa523dc .panel,
            #b69d541fa523dc .panel-default,
            #b69d541fa523dc .card {
                box-shadow: none !important;
                margin-bottom: 0 !important;
                border-radius: 10px !important;
            }

            @media (max-width: 768px) {
                #formView_Pessoa {
                    width: calc(100% + 24px) !important;
                    max-width: calc(100% + 24px) !important;
                    margin-left: -12px !important;
                    margin-right: -12px !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow-x: hidden !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-row-voltar {
                    margin: 0 0 10px 0 !important;
                    padding: 0 12px !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-dados-cliente-card {
                    width: calc(100% - 24px) !important;
                    max-width: calc(100% - 24px) !important;
                    margin: 0 12px 16px 12px !important;
                    padding: 16px 12px 12px 12px !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-area-processos {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-area-processos > [class*='col-'],
                #formView_Pessoa .curciol-area-processos > div {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa #b69d541fa523dc {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa #b69d541fa523dc > div,
                #formView_Pessoa #b69d541fa523dc .form-container,
                #formView_Pessoa #b69d541fa523dc .tform,
                #formView_Pessoa #b69d541fa523dc .panel,
                #formView_Pessoa #b69d541fa523dc .panel-body,
                #formView_Pessoa #b69d541fa523dc .card,
                #formView_Pessoa #b69d541fa523dc .card-body {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa #b69d541fa523dc .row {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }

                #formView_Pessoa #b69d541fa523dc [class*='col-'] {
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }

                #formView_Pessoa .curciol-etapas-mobile {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 0 12px 0 !important;
                    padding: 34px 0 8px 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow-x: auto !important;
                    overflow-y: hidden !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-etapas-mobile:before {
                    left: 0 !important;
                    top: 8px !important;
                }

                #formView_Pessoa .curciol-timeline-mobile {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-timeline-page {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-wrapper {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-timeline-page > span,
                #formView_Pessoa .curciol-timeline-page > label {
                    margin: 0 0 8px 0 !important;
                    padding: 0 !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    color: #0f172a !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-item {
                    width: 100% !important;
                    max-width: 100% !important;
                    padding-left: 24px !important;
                    margin: 0 0 12px 0 !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-item:before {
                    left: 11px !important;
                    bottom: -12px !important;
                    width: 3px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-icon {
                    left: -23px !important;
                    top: 17px !important;
                    width: 22px !important;
                    height: 22px !important;
                    min-width: 22px !important;
                    font-size: 9px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-icon i,
                #formView_Pessoa .curciol-mobile-timeline-icon .fa,
                #formView_Pessoa .curciol-mobile-timeline-icon .fas {
                    font-size: 9px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-card {
                    width: 100% !important;
                    max-width: 100% !important;
                    border-radius: 12px !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-header {
                    min-height: 56px !important;
                    padding: 11px 10px !important;
                    grid-template-columns: minmax(0, 1fr) 32px !important;
                    gap: 8px !important;
                    box-sizing: border-box !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-title {
                    font-size: 15px !important;
                    line-height: 1.24 !important;
                    font-weight: 700 !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-toggle {
                    width: 32px !important;
                    height: 32px !important;
                    min-width: 32px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-item {
                    position: relative !important;
                    padding-left: 24px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-item:before {
                    left: 7px !important;
                    width: 3px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-card {
                    position: relative !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-header {
                    position: relative !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-icon {
                    left: -27px !important;
                    top: 50% !important;
                    transform: translateY(-50%) !important;

                    width: 22px !important;
                    height: 22px !important;
                    min-width: 22px !important;

                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;

                    font-size: 9px !important;
                    line-height: 1 !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-icon i,
                #formView_Pessoa .curciol-mobile-timeline-icon .fa,
                #formView_Pessoa .curciol-mobile-timeline-icon .fas {
                    font-size: 9px !important;
                    line-height: 1 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
            }

            @media (max-width: 390px) {
                #formView_Pessoa {
                    width: calc(100% + 20px) !important;
                    max-width: calc(100% + 20px) !important;
                    margin-left: -10px !important;
                    margin-right: -10px !important;
                }

                #formView_Pessoa .curciol-dados-cliente-card {
                    width: calc(100% - 20px) !important;
                    max-width: calc(100% - 20px) !important;
                    margin-left: 10px !important;
                    margin-right: 10px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-item {
                    padding-left: 22px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-item:before {
                    left: 10px !important;
                    width: 3px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-icon {
                    left: -21px !important;
                    width: 20px !important;
                    height: 20px !important;
                    min-width: 20px !important;
                    font-size: 8px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-title {
                    font-size: 14px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-header {
                    grid-template-columns: minmax(0, 1fr) 30px !important;
                    padding: 10px 9px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-toggle {
                    width: 30px !important;
                    height: 30px !important;
                    min-width: 30px !important;
                }
                #formView_Pessoa .curciol-mobile-timeline-item {
                    padding-left: 22px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-item:before {
                    left: 6px !important;
                    width: 3px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-icon {
                    left: -25px !important;
                    top: 50% !important;
                    transform: translateY(-50%) !important;

                    width: 20px !important;
                    height: 20px !important;
                    min-width: 20px !important;

                    font-size: 8px !important;
                }

                #formView_Pessoa .curciol-mobile-timeline-icon i,
                #formView_Pessoa .curciol-mobile-timeline-icon .fa,
                #formView_Pessoa .curciol-mobile-timeline-icon .fas {
                    font-size: 8px !important;
                    line-height: 1 !important;
                }
            }
        ");

        $label3 = new TLabel("Nome:", '', '12px', '', '100%');
        $text3 = new TTextDisplay($pessoa->nome, '', '12px', '');
        $label11 = new TLabel("Documento:", '', '12px', '', '100%');
        $text11 = new TTextDisplay($transformed_pessoa_cpf_cnpj, '', '12px', '');
        $label6 = new TLabel("Telefone:", '', '12px', '', '100%');
        $text6 = new TTextDisplay($transformed_pessoa_telefone, '', '12px', '');
        $label5 = new TLabel("Email:", '', '12px', '', '100%');
        $text5 = new TTextDisplay($pessoa->email, '', '12px', '');
        $processo_view = new BPageContainer();

        $processo_view->setSize('100%');
        $processo_view->setAction(new TAction(['ProcessoViewHeaderList', 'onShow'], ['key' => $pessoa->id]));
        $processo_view->setId('b69d541fa523dc');

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = 'fas fa-spinner fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $processo_view->add($loadingContainer);


        $keyVoltar = $param['key'] ?? TSession::getValue('keyVoltar');

        $action_voltar = new TAction(['ProcessosFormView', 'onShow']);
        $action_voltar->setParameter('key', $keyVoltar);

        $btn_voltar = new TButton('btn_voltar');
        $btn_voltar->setLabel('Voltar');
        $btn_voltar->setImage('fas:arrow-left');
        $btn_voltar->setAction($action_voltar, 'Voltar');

        $btn_voltar->style = '
            background:#ffffff;
            color:#334155;
            border:1px solid #cbd5e1;
            border-radius:10px;
            padding:8px 14px;
            font-weight:600;
            box-shadow:none;
        ';

        $row0 = $this->form->addFields([$btn_voltar]);
        $row0->layout = [' col-sm-12'];

        $row1 = $this->form->addFields([$label3,$text3],[$label11,$text11],[$label6,$text6],[$label5,$text5]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([$processo_view]);
        $row2->layout = [' col-sm-12'];

        $row0->class = trim(($row0->class ?? '') . ' curciol-row-voltar');
        $row1->class = trim(($row1->class ?? '') . ' curciol-dados-cliente-card');
        $row2->class = trim(($row2->class ?? '') . ' curciol-area-processos');

        $row1->style = 'margin-left:0; margin-right:0; padding:16px 10px 10px 10px; margin-bottom:18px; background:linear-gradient(180deg,#f8fafc 0%,#ffffff 100%); border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 2px 10px rgba(15,23,42,.05);';

        $row2->style = 'margin-left:0; margin-right:0; padding:0; background:transparent; border:0; border-radius:0; box-shadow:none; overflow:visible;';

        $row0->style = 'margin-left:0; margin-right:0; margin-bottom:12px;';

        foreach ([$label3, $label11, $label6, $label5] as $label)
        {
            $label->style = 'display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#64748b; margin-bottom:6px;';
        }

        foreach ([$text3, $text11, $text6, $text5] as $text)
        {
            $text->style = 'display:block; font-size:15px; font-weight:600; color:#0f172a; line-height:1.4; word-break:break-word;';
        }

        $processo_view->style = 'width:100%; background:transparent; border:0; border-radius:0; overflow:visible; box-shadow:none; padding:0; margin:0;';

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Básico","Processos"]));
        }
        $container->add($this->form);

        $container->add($style);

        TTransaction::close();
        parent::add($container);

    }

    public function onShow($param = null)
    {     

    }

}

