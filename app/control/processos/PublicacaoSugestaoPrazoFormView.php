<?php

class PublicacaoSugestaoPrazoFormView extends TWindow
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'PublicacaoSugestaoPrazo';
    private static $primaryKey = 'id';
    private static $formName = 'formView_PublicacaoSugestaoPrazo';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        parent::setSize(0.8, null);
        parent::setTitle("Sugestão de prazo");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $publicacao_sugestao_prazo = new PublicacaoSugestaoPrazo($param['key']);
        // define the form title
        $this->form->setFormTitle("Sugestão de prazo");

        $transformed_publicacao_sugestao_prazo_config_busca_prazo_titulo = call_user_func(function($value, $object, $row)
        {

            return "<span style='background-color:#DEE9FF;'>$value</span>";

        }, $publicacao_sugestao_prazo->config_busca_prazo->titulo, $publicacao_sugestao_prazo, null);

        $texto_sugerido = new TTextDisplay($transformed_publicacao_sugestao_prazo_config_busca_prazo_titulo, '', '12px', 'B');
        $label2 = new TLabel(" - ", '', '12px', 'B');
        $text14 = new TTextDisplay($publicacao_sugestao_prazo->config_busca_prazo->prazo, '', '12px', 'B');
        $text16 = new TTextDisplay($publicacao_sugestao_prazo->config_busca_prazo->tipo_prazo->nome, '', '12px', 'B');
        $text10 = new TTextDisplay($publicacao_sugestao_prazo->resultado_busca, '', '12px', '');
        $btnAnterior = new TButton('btnAnterior');
        $btnConfirmarPrazo = new TButton('btnConfirmarPrazo');
        $btnProximo = new TButton('btnProximo');

        $btnProximo->setAction(new TAction([$this, 'onProximo'],['key' => 'key']), "Próximo");
        $btnAnterior->setAction(new TAction([$this, 'onAnterior'],['key' => 'key']), "Anterior");
        $btnConfirmarPrazo->setAction(new TAction([$this, 'onConfirmaPrazo']), "Confirmar prazo");

        $btnProximo->addStyleClass('btn-default');
        $btnAnterior->addStyleClass('btn-default');
        $btnConfirmarPrazo->addStyleClass('publicacao_em_andamento');

        $btnAnterior->setImage('fas:arrow-left #000000');
        $btnProximo->setImage('fas:arrow-right #000000');
        $btnConfirmarPrazo->setImage('fas:check-circle #000000');

        $row1 = $this->form->addFields([$texto_sugerido,$label2,$text14,$text16]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row3 = $this->form->addFields([$text10]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row5 = $this->form->addFields([$btnAnterior,$btnConfirmarPrazo,$btnProximo]);
        $row5->layout = [' col-sm-12'];


        $btnProximo->name = "btnProximo";
        $btnAnterior->name = "btnAnterior";
        $btnConfirmarPrazo->name = "btnConfirmarPrazo";

        $btnConfirmarPrazo->setAction(new TAction([$this, 'onConfirmaPrazo'],['key' => ($publicacao_sugestao_prazo->id)]), "Confirmar prazo");

        $sugestoes_prazo_proximo = PublicacaoSugestaoPrazo::where('publicacao_id','=',$publicacao_sugestao_prazo->publicacao_id)->where('id','=',$publicacao_sugestao_prazo->id+1)->load();
        $sugestoes_prazo_anterior = PublicacaoSugestaoPrazo::where('publicacao_id','=',$publicacao_sugestao_prazo->publicacao_id)->where('id','=',$publicacao_sugestao_prazo->id-1)->load();

        if(!$sugestoes_prazo_proximo){
            TScript::create("$(\"[name='btnProximo']\").hide()");
        }else{
            $btnProximo->setAction(new TAction([$this, 'onProximo'],['key' => ($publicacao_sugestao_prazo->id+1)]), "Próximo");
        }

        if(!$sugestoes_prazo_anterior){
            TScript::create("$(\"[name='btnAnterior']\").hide()");
        }else{
            $btnAnterior->setAction(new TAction([$this, 'onAnterior'],['key' => ($publicacao_sugestao_prazo->id-1)]), "Anterior");
        }

        TTransaction::close();
        parent::add($this->form);

    }

    public  function onAnterior($param = null) 
    {
        try 
        {
            TApplication::loadPage('PublicacaoSugestaoPrazoFormView', 'onShow', $param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onConfirmaPrazo($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $publicacao_sugestao_prazo = new PublicacaoSugestaoPrazo($param['key']);

            $tipo_prazo = $publicacao_sugestao_prazo->config_busca_prazo->tipo_prazo_id;

            $data = new DateTime($publicacao_sugestao_prazo->publicacao->data_disponibilizacao);

            $add_dias = $publicacao_sugestao_prazo->config_busca_prazo->config_busca_a_partir->add_dias -1 ;

            $add_dias += $publicacao_sugestao_prazo->config_busca_prazo->prazo;

            $adicionado = 0;
            do{
                $addUmDia = $data->add(new DateInterval("P1D"));

                if($tipo_prazo == TipoPrazo::UTIL){
                    if(calculaDataService::seDiaUtil($addUmDia->format('d'),$addUmDia->format('m'),$addUmDia->format('Y'))){
                        $data = $addUmDia;
                        $adicionado++;
                    }
                }else{
                    $data = $addUmDia;
                    $adicionado++;
                }

            }while($adicionado < $add_dias);

            $publicacao_sugestao_prazo->config_busca_prazo->pont = ($publicacao_sugestao_prazo->config_busca_prazo->pont ?? 0) +1;
            $publicacao_sugestao_prazo->config_busca_prazo->store();

            $publicacao = Publicacao::find($publicacao_sugestao_prazo->publicacao_id);
            $publicacao_sugestao_prazo->publicacao->prazo = $data->format('Y-m-d');
            $publicacao_sugestao_prazo->publicacao->store();

            APIPublicacaoController::adicionarMovimentacao($publicacao_sugestao_prazo->publicacao_id, "Prazo adicionado.", null, null);

            TTransaction::close();

            TWindow::closeWindow();
            TApplication::loadPage('PublicacaoFormView', 'onShow', ['key' => $publicacao_sugestao_prazo->publicacao->id]);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onProximo($param = null) 
    {
        try 
        {
            TApplication::loadPage('PublicacaoSugestaoPrazoFormView', 'onShow', $param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {     

    }

}

