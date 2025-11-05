<?php

class DashboardExtratoPagas extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_DashboardExtratoPagas';

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
        $this->form->setFormTitle("Dashboard de pagas");

        $criteria_compensado = new TCriteria();
        $criteria_receber = new TCriteria();
        $criteria_total = new TCriteria();
        $criteria_por_categoria = new TCriteria();
        $criteria_movimentacao_mes = new TCriteria();
        $criteria_movimento = new TCriteria();

        $filterVar = [TipoExtrato::PAGAR, TipoExtrato::SAIDA];
        $criteria_compensado->add(new TFilter('extrato.tipo_extrato_id', 'in', $filterVar)); 
        $filterVar = "S";
        $criteria_compensado->add(new TFilter('extrato.compensado', '=', $filterVar)); 
        $filterVar = date('Y-m-d');
        $criteria_compensado->add(new TFilter('extrato.data_lancamento', '<=', $filterVar)); 
        $filterVar = date('Y-m-d');
        $criteria_compensado->add(new TFilter('extrato.data_compensacao', '<=', $filterVar)); 
        $filterVar = [TipoExtrato::PAGAR, TipoExtrato::SAIDA];
        $criteria_receber->add(new TFilter('extrato.tipo_extrato_id', 'in', $filterVar)); 
        $filterVar = "N";
        $criteria_receber->add(new TFilter('extrato.compensado', '=', $filterVar)); 
        $filterVar = date('Y-m-d');
        $criteria_receber->add(new TFilter('extrato.data_lancamento', '<=', $filterVar)); 
        $filterVar = [TipoExtrato::PAGAR, TipoExtrato::SAIDA];
        $criteria_total->add(new TFilter('extrato.tipo_extrato_id', 'in', $filterVar)); 
        $filterVar = date('Y-m-d');
        $criteria_total->add(new TFilter('extrato.data_lancamento', '<=', $filterVar)); 
        $filterVar = [TipoExtrato::PAGAR, TipoExtrato::SAIDA];
        $criteria_por_categoria->add(new TFilter('extrato.tipo_extrato_id', 'in', $filterVar)); 
        $filterVar = date('Y-m-d');
        $criteria_por_categoria->add(new TFilter('extrato.data_compensacao', '<=', $filterVar)); 
        $filterVar = "S";
        $criteria_por_categoria->add(new TFilter('extrato.compensado', '=', $filterVar)); 
        $filterVar = [TipoExtrato::PAGAR, TipoExtrato::SAIDA];
        $criteria_movimentacao_mes->add(new TFilter('extrato.tipo_extrato_id', 'in', $filterVar)); 
        $filterVar = "S";
        $criteria_movimentacao_mes->add(new TFilter('extrato.compensado', '=', $filterVar)); 
        $filterVar = [TipoExtrato::PAGAR, TipoExtrato::SAIDA];
        $criteria_movimento->add(new TFilter('extrato.tipo_extrato_id', 'in', $filterVar)); 
        $filterVar = date('Y-m-d');
        $criteria_movimento->add(new TFilter('extrato.data_compensacao', '<=', $filterVar)); 
        $filterVar = "S";
        $criteria_movimento->add(new TFilter('extrato.compensado', '=', $filterVar)); 

        $atalho = new TRadioGroup('atalho');
        $de = new TDate('de');
        $ate = new TDate('ate');
        $button_buscar = new TButton('button_buscar');
        $compensado = new BIndicator('compensado');
        $receber = new BIndicator('receber');
        $total = new BIndicator('total');
        $por_categoria = new BDonutChart('por_categoria');
        $movimentacao_mes = new BBarChart('movimentacao_mes');
        $movimento = new BBarChart('movimento');

        $atalho->setChangeAction(new TAction([$this,'onChangeAtalho']));

        $atalho->addItems(["mes_atual"=>"Mês atual","mes_seguinte"=>"Mês seguinte","mes_passado"=>"Mês passado","ano_atual"=>"Ano atual"]);
        $atalho->setLayout('horizontal');
        $atalho->setUseButton();
        $button_buscar->setAction(new TAction(['DashboardExtratoPagas', 'onShow']), "Buscar");
        $button_buscar->addStyleClass('btn-default');
        $button_buscar->setImage('fas:search #00BCD4');
        $de->setMask('dd/mm/yyyy');
        $ate->setMask('dd/mm/yyyy');

        $de->setDatabaseMask('yyyy-mm-dd');
        $ate->setDatabaseMask('yyyy-mm-dd');

        $de->setSize(140);
        $ate->setSize(140);
        $atalho->setSize('100%');

        $compensado->setDatabase('escritorio');
        $compensado->setFieldValue("extrato.saida_valor");
        $compensado->setModel('Extrato');
        $compensado->setTransformerValue(function($value)
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
        $compensado->setTotal('sum');
        $compensado->setColors('#3498DB', '#FFFFFF', '#2980B9', '#FFFFFF');
        $compensado->setTitle("TOTAL COMPENSADO", '#FFFFFF', '20', '');
        $compensado->setCriteria($criteria_compensado);
        $compensado->setIcon(new TImage('fas:check #FFFFFF'));
        $compensado->setValueSize("35");
        $compensado->setValueColor("#FFFFFF", 'B');
        $compensado->setSize('100%', 95);
        $compensado->setLayout('horizontal', 'left');

        $receber->setDatabase('escritorio');
        $receber->setFieldValue("extrato.saida_valor");
        $receber->setModel('Extrato');
        $receber->setTransformerValue(function($value)
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
        $receber->setTotal('sum');
        $receber->setColors('#16A085', '#FFFFFF', '#1ABC9C', '#FFFFFF');
        $receber->setTitle("TOTAL A COMPENSAR", '#FFFFFF', '20', '');
        $receber->setCriteria($criteria_receber);
        $receber->setIcon(new TImage('fas:plus #FFFFFF'));
        $receber->setValueSize("35");
        $receber->setValueColor("#FFFFFF", 'B');
        $receber->setSize('100%', 95);
        $receber->setLayout('horizontal', 'left');

        $total->setDatabase('escritorio');
        $total->setFieldValue("extrato.saida_valor");
        $total->setModel('Extrato');
        $total->setTransformerValue(function($value)
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
        $total->setTotal('sum');
        $total->setColors('#27AE60', '#FFFFFF', '#2ECC71', '#FFFFFF');
        $total->setTitle("TOTAL", '#FFFFFF', '20', '');
        $total->setCriteria($criteria_total);
        $total->setIcon(new TImage('fas:dollar-sign #FFFFFF'));
        $total->setValueSize("35");
        $total->setValueColor("#FFFFFF", 'B');
        $total->setSize('100%', 95);
        $total->setLayout('horizontal', 'left');

        $por_categoria->setDatabase('escritorio');
        $por_categoria->setFieldValue("extrato.saida_valor");
        $por_categoria->setFieldGroup("categoria_conta.nome");
        $por_categoria->setModel('Extrato');
        $por_categoria->setTitle("Movimentação compensada por categoria");
        $por_categoria->setTransformerValue(function($value, $row, $data)
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
        $por_categoria->setJoins([
             'categoria_conta' => ['extrato.categoria_conta_id', 'categoria_conta.id']
        ]);
        $por_categoria->setTotal('sum');
        $por_categoria->showLegend(false);
        $por_categoria->showPercentage();
        $por_categoria->enableOrderByValue('asc');
        $por_categoria->setCriteria($criteria_por_categoria);
        $por_categoria->setSize('100%', 280);
        $por_categoria->disableZoom();

        $movimentacao_mes->setDatabase('escritorio');
        $movimentacao_mes->setFieldValue("extrato.saida_valor");
        $movimentacao_mes->setFieldGroup(["extrato.ano_mes"]);
        $movimentacao_mes->setModel('Extrato');
        $movimentacao_mes->setTitle("Movimentação compensada por mês");
        $movimentacao_mes->setTransformerLegend(function($value, $row, $data)
            {
                return TempoService::getMesAno($value);

            });
        $movimentacao_mes->setTransformerValue(function($value, $row, $data)
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
        $movimentacao_mes->setLayout('vertical');
        $movimentacao_mes->setTotal('sum');
        $movimentacao_mes->showLegend(true);
        $movimentacao_mes->setCriteria($criteria_movimentacao_mes);
        $movimentacao_mes->setLabelValue("Total");
        $movimentacao_mes->setSize('100%', 280);
        $movimentacao_mes->disableZoom();

        $movimento->setDatabase('escritorio');
        $movimento->setFieldValue("extrato.saida_valor");
        $movimento->setFieldGroup(["extrato.data_compensacao"]);
        $movimento->setModel('Extrato');
        $movimento->setTitle("Movimentação compensada por dia");
        $movimento->setTransformerLegend(function($value, $row, $data)
            {
                if(!empty(trim((string) $value)))
                {
                    try
                    {
                        $date = new DateTime($value);
                        return $date->format('d/m/Y');
                    }
                    catch (Exception $e)
                    {
                        return $value;
                    }
                }
            });
        $movimento->setTransformerValue(function($value, $row, $data)
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
        $movimento->setLayout('vertical');
        $movimento->setTotal('sum');
        $movimento->showLegend(false);
        $movimento->setCriteria($criteria_movimento);
        $movimento->setLabelValue("Valor");
        $movimento->setSize('100%', 280);

        $row1 = $this->form->addFields([new TLabel("Atalhos:", null, '14px', null, '100%'),$atalho],[new TLabel("Período:", null, '14px', null, '100%'),$de,new TLabel("até", null, '14px', null),$ate,$button_buscar]);
        $row1->layout = [' col-sm-7','col-sm-5'];

        $row2 = $this->form->addFields([$compensado],[$receber],[$total]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $this->form->addFields([$por_categoria],[$movimentacao_mes]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([$movimento]);
        $row4->layout = [' col-sm-12'];

        $searchData = $this->form->getData();
        $this->form->setData($searchData);

        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_compensado->add(new TFilter('extrato.data_lancamento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_compensado->add(new TFilter('extrato.data_lancamento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_receber->add(new TFilter('extrato.data_lancamento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_receber->add(new TFilter('extrato.data_lancamento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_total->add(new TFilter('extrato.data_lancamento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_total->add(new TFilter('extrato.data_lancamento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_por_categoria->add(new TFilter('extrato.data_lancamento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_por_categoria->add(new TFilter('extrato.data_lancamento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_movimentacao_mes->add(new TFilter('extrato.data_compensacao', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_movimentacao_mes->add(new TFilter('extrato.data_compensacao', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_movimento->add(new TFilter('extrato.data_compensacao', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_movimento->add(new TFilter('extrato.data_compensacao', '<=', $filterVar)); 
        }

        if($searchData->de == '' && $searchData->ate==''){
            $filterVarDE = date('Y-m-01', strtotime('now'));
            $filterVarATE = date('Y-m-t', strtotime('now'));;
            $criteria_compensado->add(new TFilter('extrato.data_lancamento', '>=', $filterVarDE)); 
            $criteria_compensado->add(new TFilter('extrato.data_lancamento', '<=', $filterVarATE)); 
            $criteria_receber->add(new TFilter('extrato.data_lancamento', '>=', $filterVarDE));
            $criteria_receber->add(new TFilter('extrato.data_lancamento', '<=', $filterVarATE)); 
            $criteria_total->add(new TFilter('extrato.data_lancamento', '>=', $filterVarDE)); 
            $criteria_total->add(new TFilter('extrato.data_lancamento', '<=', $filterVarATE)); 
            $criteria_por_categoria->add(new TFilter('extrato.data_lancamento', '>=', $filterVarDE)); 
            $criteria_por_categoria->add(new TFilter('extrato.data_lancamento', '<=', $filterVarATE)); 
            $criteria_movimentacao_mes->add(new TFilter('extrato.data_compensacao', '>=', $filterVarDE)); 
            $criteria_movimentacao_mes->add(new TFilter('extrato.data_compensacao', '<=', $filterVarATE)); 
            $criteria_movimento->add(new TFilter('extrato.data_compensacao', '>=', $filterVarDE)); 
            $criteria_movimento->add(new TFilter('extrato.data_compensacao', '<=', $filterVarATE)); 
        }
        BChart::generate($compensado, $receber, $total, $por_categoria, $movimentacao_mes, $movimento);

        // create the form actions

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Dashboard","Dashboard de pagas"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public static function onChangeAtalho($param = null) 
    {
        try 
        {
            if (! empty($param['atalho']))
            {
                $data = new stdClass;

                if ($param['atalho'] == 'mes_atual')
                {
                    $data->de = date('01/m/Y', strtotime('now'));
                    $data->ate = date('t/m/Y', strtotime('now'));
                }
                else if ($param['atalho'] == 'mes_seguinte')
                {
                    $data->de = date('01/m/Y', strtotime('now +1 month'));
                    $data->ate = date('t/m/Y', strtotime('now +1 month'));
                }
                else if ($param['atalho'] == 'mes_passado')
                {
                    $data->de = date('01/m/Y', strtotime('now -1 month'));
                    $data->ate = date('t/m/Y', strtotime('now -1 month'));
                }
                else if ($param['atalho'] == 'ano_atual')
                {
                    $data->de = date('01/01/Y', strtotime('now'));
                    $data->ate = date('t/12/Y', strtotime('now'));
                }

                TForm::sendData(self::$formName, $data);
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {               

        if(!isset($param['atalho'])){
            $object = new stdClass();
            $object->atalho = 'mes_atual';
            $object->de = date('01/01/Y', strtotime('now'));
            $object->ate = date('t/12/Y', strtotime('now'));

            TForm::sendData(self::$formName, $object);
        }

    } 

}

