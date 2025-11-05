<?php

class DashboardContasCaixa extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_DashboardContasCaixa';

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
        $this->form->setFormTitle("Dashboard de Contas Caixa");

        $criteria_saldo_instantaneo_ccBanco = new TCriteria();
        $criteria_saldo_instantaneo_ccDinheiro = new TCriteria();
        $criteria_saldo_instantaneo = new TCriteria();
        $criteria_saldo_ncompensado_banco = new TCriteria();
        $criteria_saldo_ncompensado_dinheiro = new TCriteria();
        $criteria_saldo_ncompensado = new TCriteria();
        $criteria_grafico_banco = new TCriteria();
        $criteria_grafico_nao_compensado = new TCriteria();

        $filterVar = "S";
        $criteria_saldo_instantaneo_ccBanco->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 
        $filterVar = TipoContaCaixa::BANCO;
        $criteria_saldo_instantaneo_ccBanco->add(new TFilter('conta_caixa.tipo_conta_caixa_id', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_saldo_instantaneo_ccDinheiro->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 
        $filterVar = TipoContaCaixa::DINHEIRO;
        $criteria_saldo_instantaneo_ccDinheiro->add(new TFilter('conta_caixa.tipo_conta_caixa_id', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_saldo_instantaneo->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_saldo_ncompensado_banco->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 
        $filterVar = TipoContaCaixa::BANCO;
        $criteria_saldo_ncompensado_banco->add(new TFilter('conta_caixa.tipo_conta_caixa_id', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_saldo_ncompensado_dinheiro->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 
        $filterVar = TipoContaCaixa::DINHEIRO;
        $criteria_saldo_ncompensado_dinheiro->add(new TFilter('conta_caixa.tipo_conta_caixa_id', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_saldo_ncompensado->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_grafico_banco->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_grafico_nao_compensado->add(new TFilter('conta_caixa.ativo', '=', $filterVar)); 

        $saldo_instantaneo_ccBanco = new BIndicator('saldo_instantaneo_ccBanco');
        $saldo_instantaneo_ccDinheiro = new BIndicator('saldo_instantaneo_ccDinheiro');
        $saldo_instantaneo = new BIndicator('saldo_instantaneo');
        $saldo_ncompensado_banco = new BIndicator('saldo_ncompensado_banco');
        $saldo_ncompensado_dinheiro = new BIndicator('saldo_ncompensado_dinheiro');
        $saldo_ncompensado = new BIndicator('saldo_ncompensado');
        $grafico_banco = new BBarChart('grafico_banco');
        $grafico_nao_compensado = new BBarChart('grafico_nao_compensado');


        $saldo_instantaneo_ccBanco->setDatabase('escritorio');
        $saldo_instantaneo_ccBanco->setFieldValue("conta_caixa.saldo_instantaneo");
        $saldo_instantaneo_ccBanco->setModel('ContaCaixa');
        $saldo_instantaneo_ccBanco->setTransformerValue(function($value)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $saldo_instantaneo_ccBanco->setTotal('sum');
        $saldo_instantaneo_ccBanco->setColors('#145A8A', '#FFFFFF', '#568BB5', '#FFFFFF');
        $saldo_instantaneo_ccBanco->setTitle("saldo em banco", '#FFFFFF', '20', '');
        $saldo_instantaneo_ccBanco->setCriteria($criteria_saldo_instantaneo_ccBanco);
        $saldo_instantaneo_ccBanco->setIcon(new TImage('fas:university #FFFFFF'));
        $saldo_instantaneo_ccBanco->setValueSize("20");
        $saldo_instantaneo_ccBanco->setValueColor("#FFFFFF", 'B');
        $saldo_instantaneo_ccBanco->setSize('100%', 95);
        $saldo_instantaneo_ccBanco->setLayout('horizontal', 'left');

        $saldo_instantaneo_ccDinheiro->setDatabase('escritorio');
        $saldo_instantaneo_ccDinheiro->setFieldValue("conta_caixa.saldo_instantaneo");
        $saldo_instantaneo_ccDinheiro->setModel('ContaCaixa');
        $saldo_instantaneo_ccDinheiro->setTransformerValue(function($value)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $saldo_instantaneo_ccDinheiro->setTotal('sum');
        $saldo_instantaneo_ccDinheiro->setColors('#145A8A', '#ffffff', '#568BB5', '#ffffff');
        $saldo_instantaneo_ccDinheiro->setTitle("saldo em dinheiro", '#ffffff', '20', '');
        $saldo_instantaneo_ccDinheiro->setCriteria($criteria_saldo_instantaneo_ccDinheiro);
        $saldo_instantaneo_ccDinheiro->setIcon(new TImage('fas:money-bill #ffffff'));
        $saldo_instantaneo_ccDinheiro->setValueSize("20");
        $saldo_instantaneo_ccDinheiro->setValueColor("#ffffff", 'B');
        $saldo_instantaneo_ccDinheiro->setSize('100%', 95);
        $saldo_instantaneo_ccDinheiro->setLayout('horizontal', 'left');

        $saldo_instantaneo->setDatabase('escritorio');
        $saldo_instantaneo->setFieldValue("conta_caixa.saldo_instantaneo");
        $saldo_instantaneo->setModel('ContaCaixa');
        $saldo_instantaneo->setTransformerValue(function($value)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $saldo_instantaneo->setTotal('sum');
        $saldo_instantaneo->setColors('#145A8A', '#ffffff', '#568BB5', '#ffffff');
        $saldo_instantaneo->setTitle("saldo total", '#ffffff', '20', '');
        $saldo_instantaneo->setCriteria($criteria_saldo_instantaneo);
        $saldo_instantaneo->setIcon(new TImage('fas:dollar-sign #ffffff'));
        $saldo_instantaneo->setValueSize("20");
        $saldo_instantaneo->setValueColor("#ffffff", 'B');
        $saldo_instantaneo->setSize('100%', 95);
        $saldo_instantaneo->setLayout('horizontal', 'left');

        $saldo_ncompensado_banco->setDatabase('escritorio');
        $saldo_ncompensado_banco->setFieldValue("conta_caixa.saldo_nao_compensado");
        $saldo_ncompensado_banco->setModel('ContaCaixa');
        $saldo_ncompensado_banco->setTransformerValue(function($value)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $saldo_ncompensado_banco->setTotal('sum');
        $saldo_ncompensado_banco->setColors('#145A8A', '#FFFFFF', '#568BB5', '#FFFFFF');
        $saldo_ncompensado_banco->setTitle("saldo em banco", '#FFFFFF', '20', '');
        $saldo_ncompensado_banco->setCriteria($criteria_saldo_ncompensado_banco);
        $saldo_ncompensado_banco->setIcon(new TImage('fas:university #FFFFFF'));
        $saldo_ncompensado_banco->setValueSize("20");
        $saldo_ncompensado_banco->setValueColor("#FFFFFF", 'B');
        $saldo_ncompensado_banco->setSize('100%', 95);
        $saldo_ncompensado_banco->setLayout('horizontal', 'left');

        $saldo_ncompensado_dinheiro->setDatabase('escritorio');
        $saldo_ncompensado_dinheiro->setFieldValue("conta_caixa.saldo_nao_compensado");
        $saldo_ncompensado_dinheiro->setModel('ContaCaixa');
        $saldo_ncompensado_dinheiro->setTransformerValue(function($value)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $saldo_ncompensado_dinheiro->setTotal('sum');
        $saldo_ncompensado_dinheiro->setColors('#145A8A', '#FFFFFF', '#568BB5', '#FFFFFF');
        $saldo_ncompensado_dinheiro->setTitle("saldo em dinheiro", '#FFFFFF', '20', '');
        $saldo_ncompensado_dinheiro->setCriteria($criteria_saldo_ncompensado_dinheiro);
        $saldo_ncompensado_dinheiro->setIcon(new TImage('fas:money-bill #FFFFFF'));
        $saldo_ncompensado_dinheiro->setValueSize("20");
        $saldo_ncompensado_dinheiro->setValueColor("#FFFFFF", 'B');
        $saldo_ncompensado_dinheiro->setSize('100%', 95);
        $saldo_ncompensado_dinheiro->setLayout('horizontal', 'left');

        $saldo_ncompensado->setDatabase('escritorio');
        $saldo_ncompensado->setFieldValue("conta_caixa.saldo_nao_compensado");
        $saldo_ncompensado->setModel('ContaCaixa');
        $saldo_ncompensado->setTransformerValue(function($value)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $saldo_ncompensado->setTotal('sum');
        $saldo_ncompensado->setColors('#145A8A', '#FFFFFF', '#568BB5', '#FFFFFF');
        $saldo_ncompensado->setTitle("saldo total", '#FFFFFF', '20', '');
        $saldo_ncompensado->setCriteria($criteria_saldo_ncompensado);
        $saldo_ncompensado->setIcon(new TImage('fas:dollar-sign #FFFFFF'));
        $saldo_ncompensado->setValueSize("20");
        $saldo_ncompensado->setValueColor("#FFFFFF", 'B');
        $saldo_ncompensado->setSize('100%', 95);
        $saldo_ncompensado->setLayout('horizontal', 'left');

        $grafico_banco->setDatabase('escritorio');
        $grafico_banco->setFieldValue("conta_caixa.saldo_instantaneo");
        $grafico_banco->setFieldColor("conta_caixa.cor_compensado");
        $grafico_banco->setFieldGroup(["conta_caixa.nome"]);
        $grafico_banco->setModel('ContaCaixa');
        $grafico_banco->setTitle("Saldo instantâneo ");
        $grafico_banco->setTransformerValue(function($value, $row, $data)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $grafico_banco->setLayout('vertical');
        $grafico_banco->setTotal('sum');
        $grafico_banco->showLegend(true);
        $grafico_banco->setCriteria($criteria_grafico_banco);
        $grafico_banco->setSize('100%', 300);

        $grafico_nao_compensado->setDatabase('escritorio');
        $grafico_nao_compensado->setFieldValue("conta_caixa.saldo_nao_compensado");
        $grafico_nao_compensado->setFieldColor("conta_caixa.cor_nao_compensado");
        $grafico_nao_compensado->setFieldGroup(["conta_caixa.nome"]);
        $grafico_nao_compensado->setModel('ContaCaixa');
        $grafico_nao_compensado->setTitle("Saldo não compensado");
        $grafico_nao_compensado->setTransformerValue(function($value, $row, $data)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });
        $grafico_nao_compensado->setLayout('vertical');
        $grafico_nao_compensado->setTotal('sum');
        $grafico_nao_compensado->showLegend(false);
        $grafico_nao_compensado->setCriteria($criteria_grafico_nao_compensado);
        $grafico_nao_compensado->setSize('100%', 300);

        $row1 = $this->form->addContent([new TFormSeparator("Instantâneo", '#333333', '18', '#EEEEEE')]);
        $row2 = $this->form->addFields([$saldo_instantaneo_ccBanco],[$saldo_instantaneo_ccDinheiro],[$saldo_instantaneo]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $this->form->addContent([new TFormSeparator("Não compensado", '#333333', '18', '#EEEEEE')]);
        $row4 = $this->form->addFields([$saldo_ncompensado_banco],[$saldo_ncompensado_dinheiro],[$saldo_ncompensado]);
        $row4->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row6 = $this->form->addFields([$grafico_banco],[$grafico_nao_compensado]);
        $row6->layout = ['col-sm-6',' col-sm-6'];

        $searchData = $this->form->getData();
        $this->form->setData($searchData);

        BChart::generate($saldo_instantaneo_ccBanco, $saldo_instantaneo_ccDinheiro, $saldo_instantaneo, $saldo_ncompensado_banco, $saldo_ncompensado_dinheiro, $saldo_ncompensado, $grafico_banco, $grafico_nao_compensado);

        // create the form actions

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Dashboard","Dashboard de Contas Caixa"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onShow($param = null)
    {               

    } 

}

