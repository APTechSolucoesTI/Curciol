<?php

class DashboardContasPagarGeral extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_DashboardContasPagarGeral';

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
        $this->form->setFormTitle("Dashboard de contas a pagar");

        $criteria_vencidas = new TCriteria();
        $criteria_avencer = new TCriteria();
        $criteria_todas = new TCriteria();
        $criteria_categoria = new TCriteria();
        $criteria_movimentacao_mes = new TCriteria();
        $criteria_movimento = new TCriteria();

        $filterVar = date('Y-m-d');
        $criteria_vencidas->add(new TFilter('lancamento.dt_vencimento', '<', $filterVar)); 
        $filterVar = TipoConta::PAGAR;
        $criteria_vencidas->add(new TFilter('lancamento.conta_id', 'in', "(SELECT id FROM conta WHERE tipo_conta_id = '{$filterVar}')")); 
        $filterVar = null;
        $criteria_vencidas->add(new TFilter('lancamento.dt_pagamento', 'is', $filterVar)); 
        $filterVar = date('Y-m-d');
        $criteria_avencer->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVar)); 
        $filterVar = TipoConta::PAGAR;
        $criteria_avencer->add(new TFilter('lancamento.conta_id', 'in', "(SELECT id FROM conta WHERE tipo_conta_id = '{$filterVar}')")); 
        $filterVar = null;
        $criteria_avencer->add(new TFilter('lancamento.dt_pagamento', 'is', $filterVar)); 
        $filterVar = null;
        $criteria_todas->add(new TFilter('lancamento.dt_pagamento', 'is', $filterVar)); 
        $filterVar = TipoConta::PAGAR;
        $criteria_todas->add(new TFilter('lancamento.conta_id', 'in', "(SELECT id FROM conta WHERE tipo_conta_id = '{$filterVar}')")); 
        $filterVar = null;
        $criteria_categoria->add(new TFilter('lancamento.dt_pagamento', 'is', $filterVar)); 
        $filterVar = TipoConta::PAGAR;
        $criteria_categoria->add(new TFilter('lancamento.conta_id', 'in', "(SELECT id FROM conta WHERE tipo_conta_id = '{$filterVar}')")); 
        $filterVar = TipoConta::PAGAR;
        $criteria_movimentacao_mes->add(new TFilter('lancamento.conta_id', 'in', "(SELECT id FROM conta WHERE tipo_conta_id = '{$filterVar}')")); 
        $filterVar = null;
        $criteria_movimentacao_mes->add(new TFilter('lancamento.dt_pagamento', 'is', $filterVar)); 
        $filterVar = TipoConta::PAGAR;
        $criteria_movimento->add(new TFilter('lancamento.conta_id', 'in', "(SELECT id FROM conta WHERE tipo_conta_id = '{$filterVar}')")); 
        $filterVar = null;
        $criteria_movimento->add(new TFilter('lancamento.dt_pagamento', 'is', $filterVar)); 

        $atalho = new TRadioGroup('atalho');
        $de = new TDate('de');
        $ate = new TDate('ate');
        $button_buscar = new TButton('button_buscar');
        $vencidas = new BIndicator('vencidas');
        $avencer = new BIndicator('avencer');
        $todas = new BIndicator('todas');
        $categoria = new BDonutChart('categoria');
        $movimentacao_mes = new BBarChart('movimentacao_mes');
        $movimento = new BBarChart('movimento');

        $atalho->setChangeAction(new TAction([$this,'onChangeAtalho']));

        $atalho->addItems(["mes_atual"=>"Mês atual","mes_seguinte"=>"Mês seguinte","mes_passado"=>"Mês passado","ano_atual"=>"Ano atual"]);
        $atalho->setLayout('horizontal');
        $atalho->setUseButton();
        $button_buscar->setAction(new TAction(['DashboardContasPagarGeral', 'onShow']), "Buscar");
        $button_buscar->addStyleClass('btn-default');
        $button_buscar->setImage('fas:search #00BCD4');
        $de->setMask('dd/mm/yyyy');
        $ate->setMask('dd/mm/yyyy');

        $de->setDatabaseMask('yyyy-mm-dd');
        $ate->setDatabaseMask('yyyy-mm-dd');

        $de->setSize(140);
        $ate->setSize(140);
        $atalho->setSize('100%');

        $vencidas->setDatabase('escritorio');
        $vencidas->setFieldValue("lancamento.valor");
        $vencidas->setModel('Lancamento');
        $vencidas->setTransformerValue(function($value)
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
        $vencidas->setTotal('sum');
        $vencidas->setColors('#16A085', '#FFFFFF', '#1ABC9C', '#FFFFFF');
        $vencidas->setTitle("vencidas", '#FFFFFF', '20', '');
        $vencidas->setCriteria($criteria_vencidas);
        $vencidas->setIcon(new TImage('fas:plus #FFFFFF'));
        $vencidas->setValueSize("35");
        $vencidas->setValueColor("#FFFFFF", 'B');
        $vencidas->setSize('100%', 95);
        $vencidas->setLayout('horizontal', 'left');

        $avencer->setDatabase('escritorio');
        $avencer->setFieldValue("lancamento.valor");
        $avencer->setModel('Lancamento');
        $avencer->setTransformerValue(function($value)
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
        $avencer->setTotal('sum');
        $avencer->setColors('#3498DB', '#FFFFFF', '#2980B9', '#FFFFFF');
        $avencer->setTitle("a vencer", '#FFFFFF', '20', '');
        $avencer->setCriteria($criteria_avencer);
        $avencer->setIcon(new TImage('fas:check #FFFFFF'));
        $avencer->setValueSize("35");
        $avencer->setValueColor("#FFFFFF", 'B');
        $avencer->setSize('100%', 95);
        $avencer->setLayout('horizontal', 'left');

        $todas->setDatabase('escritorio');
        $todas->setFieldValue("lancamento.valor");
        $todas->setModel('Lancamento');
        $todas->setTransformerValue(function($value)
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
        $todas->setTotal('sum');
        $todas->setColors('#27AE60', '#FFFFFF', '#2ECC71', '#FFFFFF');
        $todas->setTitle("Total a pagar", '#FFFFFF', '20', '');
        $todas->setCriteria($criteria_todas);
        $todas->setIcon(new TImage('fas:dollar-sign #FFFFFF'));
        $todas->setValueSize("35");
        $todas->setValueColor("#FFFFFF", 'B');
        $todas->setSize('100%', 95);
        $todas->setLayout('horizontal', 'left');

        $categoria->setDatabase('escritorio');
        $categoria->setFieldValue("lancamento.valor");
        $categoria->setFieldGroup("categoria_conta.nome");
        $categoria->setModel('Lancamento');
        $categoria->setTitle("Total de contas por categoria");
        $categoria->setTransformerValue(function($value, $row, $data)
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
        $categoria->setJoins([
             'conta' => ['lancamento.conta_id', 'conta.id'],
             'categoria_conta' => ['conta.categoria_conta_id', 'categoria_conta.id']
        ]);
        $categoria->setTotal('sum');
        $categoria->showLegend(false);
        $categoria->showPercentage();
        $categoria->enableOrderByValue('asc');
        $categoria->setCriteria($criteria_categoria);
        $categoria->setSize('100%', 280);
        $categoria->disableZoom();

        $movimentacao_mes->setDatabase('escritorio');
        $movimentacao_mes->setFieldValue("lancamento.valor");
        $movimentacao_mes->setFieldGroup(["lancamento.ano_mes_vencimento"]);
        $movimentacao_mes->setModel('Lancamento');
        $movimentacao_mes->setTitle("Movimentação por mês");
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
        $movimentacao_mes->enableOrderByValue('desc');
        $movimentacao_mes->setCriteria($criteria_movimentacao_mes);
        $movimentacao_mes->setLabelValue("Total");
        $movimentacao_mes->setSize('100%', 280);
        $movimentacao_mes->disableZoom();

        $movimento->setDatabase('escritorio');
        $movimento->setFieldValue("lancamento.valor");
        $movimento->setFieldGroup(["lancamento.dt_vencimento"]);
        $movimento->setModel('Lancamento');
        $movimento->setTitle("Movimentação por dia");
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

        $row2 = $this->form->addFields([$vencidas],[$avencer],[$todas]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $this->form->addFields([$categoria],[$movimentacao_mes]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([$movimento]);
        $row4->layout = [' col-sm-12'];

        $searchData = $this->form->getData();
        $this->form->setData($searchData);

        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_vencidas->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_vencidas->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_avencer->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_avencer->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_todas->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_todas->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_categoria->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_categoria->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_movimentacao_mes->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_movimentacao_mes->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVar)); 
        }
        $filterVar = $searchData->de;
        if($filterVar)
        {
            $criteria_movimento->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVar)); 
        }
        $filterVar = $searchData->ate;
        if($filterVar)
        {
            $criteria_movimento->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVar)); 
        }

        if($searchData->de == '' && $searchData->ate==''){
            $filterVarDE = date('Y-m-01', strtotime('now'));
            $filterVarATE = date('Y-m-t', strtotime('now'));;
            $criteria_vencidas->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVarDE)); 
            $criteria_vencidas->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVarATE)); 
            $criteria_avencer->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVarDE));
            $criteria_avencer->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVarATE)); 
            $criteria_todas->add(new TFilter('lancamento.dt_vencimento', '>=', $filterVarDE)); 
            $criteria_todas->add(new TFilter('lancamento.dt_vencimento', '<=', $filterVarATE)); 
            $criteria_categoria->add(new TFilter('conta.data_emissao', '>=', $filterVarDE)); 
            $criteria_categoria->add(new TFilter('conta.data_emissao', '<=', $filterVarATE)); 
            $criteria_movimentacao_mes->add(new TFilter('lancamento.dt_pagamento', '>=', $filterVarDE)); 
            $criteria_movimentacao_mes->add(new TFilter('lancamento.dt_pagamento', '<=', $filterVarATE)); 
            $criteria_movimento->add(new TFilter('lancamento.dt_pagamento', '>=', $filterVarDE)); 
            $criteria_movimento->add(new TFilter('lancamento.dt_pagamento', '<=', $filterVarATE)); 
        }
        BChart::generate($vencidas, $avencer, $todas, $categoria, $movimentacao_mes, $movimento);

        // create the form actions

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Dashboard","Dashboard a pagar"]));
        }
        $container->add($this->form);

// var_dump($criteria_total_por_agenda->dump()); die();

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

