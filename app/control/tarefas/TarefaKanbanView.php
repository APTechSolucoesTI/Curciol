<?php

class TarefaKanbanView extends TPage
{
    private static $database = 'escritorio';
    private static $activeRecord = 'Tarefa';
    private static $primaryKey = 'id';

    private static $formName = 'TarefaKanbanForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        try
        {
            parent::__construct();

            $kanban = new TKanban;
            $kanban->setItemDatabase(self::$database);

            $limit = 20;
            $kanban->setLoadMoreAction(new TAction([$this, 'onLoadMore'], $param), $limit);

            $criteriaStage = new TCriteria();
            $criteriaItem = new TCriteria();

            $criteriaStage->setProperty('order', 'kanban asc');
            $criteriaItem->setProperty('order', 'prazo_entrega asc');

            TTransaction::open(self::$database);
            $configuracao = TarefaConfiguracao::find(1);
            if($configuracao->tem_dtvalidacao=="S" && $configuracao->dtvalidacao_obrigatoria=="S"){
                $criteriaItem->setProperty('order', 'prazo_validacao, prazo_entrega');
            }else{
                $criteriaItem->setProperty('order', 'prazo_entrega asc');
            }

            $master = TarefaUsuarioMaster::where('usuario_master_id','=',TSession::getValue('userid'))->first();
            if(!$master){
                $criteria1 = new TCriteria;
                $criteria1->add(new TFilter('usuario_destinatario_id', '=', TSession::getValue('userid')), TExpression::OR_OPERATOR); 
                $criteria1->add(new TFilter('criacao_user_id', '=', TSession::getValue('userid')), TExpression::OR_OPERATOR); 
                $criteriaItem->add($criteria1); 
            }

            if(TSession::getValue('TarefaKanbanView_filters')){
                foreach(TSession::getValue('TarefaKanbanView_filters') as $filter){
                    $criteriaItem->add($filter);
                }
            }
            TTransaction::close();

            TTransaction::open(self::$database);
            $stages = TarefaStatus::getObjects($criteriaStage);

            if($stages)
            {
                foreach ($stages as $key => $stage)
                {

                    $criteriaItemStage = clone $criteriaItem;
                    $criteriaItemStage->add(new TFilter('tarefa_status_id', '=', $stage->id));
                    $criteriaItemStage->setProperty('limit', $limit);

/*

                    $kanban->addStage($stage->id, "{nome}", $stage ,$stage->cor);

                    $items = Tarefa::getObjects($criteriaItemStage);

*/

                    $kanban->addStage($stage->id, "{nome}", $stage ,$stage->cor."2F");
                    $items = Tarefa::getObjects($criteriaItemStage);

                    if($items)
                    {
                        foreach ($items as $key => $item)
                        {

                            $item->data_disponibilizacao = call_user_func(function($value, $object, $row) 
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
                            }, $item->data_disponibilizacao, $item, null);

                            $item->prazo_validacao = call_user_func(function($value, $object, $row) 
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
                            }, $item->prazo_validacao, $item, null);

                            $item->prazo_entrega = call_user_func(function($value, $object, $row) 
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
                            }, $item->prazo_entrega, $item, null);

                            $item->observacao = call_user_func(function($value, $object, $row)
                            {
                                return substr($value, 0, 300);

                            }, $item->observacao, $item, null);

                            $item->titulo = call_user_func(function($value, $object, $row)
                            {

                                return str_replace(";","<br/>",$value);

                            }, $item->titulo, $item, null);

                            $item->data_entrega = call_user_func(function($value, $object, $row)
                            {
                                if(!empty(trim((string) $value)))
                                {
                                    try
                                    {
                                        $date = new DateTime($value);
                                        return $date->format('d/m/Y H:i');
                                    }
                                    catch (Exception $e)
                                    {
                                        return $value;
                                    }
                                }
                            }, $item->data_entrega, $item, null);

                            $configuracao = TarefaConfiguracao::find(1);
                            $titulo = "{titulo}";

                            //Buscar se essa é uma subtarefa
                            $vinculos = TarefaVinculo::where('subtarefa_id','=',$item->id)->first();
                            if($vinculos){
                                $titulo .= "<br/><span style='border-radius:2px; background-color:#ff0000; color:#ffffff'> Subtarefa de #".$vinculos->tarefa_id." </span>";

                            }elseif($item->tarefa_status_id != $configuracao->status_final_id && $item->tarefa_status_id != $configuracao->status_cancelado_id){

                                //Buscar as subtarefas desta tarefa
                                $vinculos = TarefaVinculo::where('tarefa_id','=',$item->id)->load();

                                if($vinculos){
                                    $subtarefasFinalizadas = 0;
                                    $subtarefasNFinalizadas = count($vinculos);
                                    foreach($vinculos as $key=>$subtarefa){
                                        $tarefa = Tarefa::find($subtarefa->subtarefa_id);
                                        if($tarefa->tarefa_status_id == $configuracao->status_final_id){
                                            $subtarefasFinalizadas++;
                                            $subtarefasNFinalizadas--;
                                        }
                                        if($tarefa->tarefa_status_id == $configuracao->status_cancelado_id){
                                            $subtarefasFinalizadas--;
                                            $subtarefasNFinalizadas--;
                                        }
                                    }
                                    if($subtarefasFinalizadas == count($vinculos)){
                                        if($item->tarefa_status_id != $configuracao->status_final_id){
                                            $titulo .= "<br/><span style='border-radius:2px; background-color:".$configuracao->status_final->cor."; color:#ffffff'> Subtarefas finalizadas </span>";
                                        }
                                    }else{
                                        $titulo .= "<br/><span style='border-radius:2px; background-color:#ff0000; color:#ffffff'> $subtarefasNFinalizadas subtarefas não finalizadas </span>";
                                    }
                                }
                            }

                            if($item->publicacao_id > 0){
                                $corpo   = "<b>Prazo de entrega:</b> {prazo_entrega} <br />
                                            <hr /> 
                                            {publicacao->numero_unico_processo}<br/>
                                            {observacao}";
                            }else{
                                $corpo   = "<b>Prazo de entrega:</b> {prazo_entrega} <br />
                                            <hr /> 
                                            {observacao}";
                            }
                            $kanban->addItem($item->id, $item->tarefa_status_id, $titulo, $corpo, $item->tarefa_status->cor, $item);

                            /*

                            $kanban->addItem($item->id, $item->tarefa_status_id, "{titulo}", "", $item->tarefa_status->cor, $item);

                            */
                        }    
                    }
                }    
            }

            $kanbanItemAction_TarefaFormView_onShow = new TAction(['TarefaFormView', 'onShow']);
            $kanbanItemAction_TarefaFormView_onShow->setParameter("key", $stage->id);
            $kanbanItemAction_TarefaFormView_onShow->setParameter("origem", "TarefaKanbanHeader");

            $kanban->addItemAction("Visualizar tarefa", $kanbanItemAction_TarefaFormView_onShow, 'fas:search-plus #000000');
            $kanbanItemAction_TarefaKanbanView_onArquivarTarefa = new TAction(['TarefaKanbanView', 'onArquivarTarefa']);
            $kanbanItemAction_TarefaKanbanView_onArquivarTarefa->setParameter("id", "id");

            $kanban->addItemAction("Arquivar", $kanbanItemAction_TarefaKanbanView_onArquivarTarefa, 'fas:archive #000000','TarefaKanbanView::canArquivar');
            $kanbanItemAction_TarefaKanbanView_onDesarquivar = new TAction(['TarefaKanbanView', 'onDesarquivar']);

            $kanban->addItemAction("Desarquivar", $kanbanItemAction_TarefaKanbanView_onDesarquivar, 'fas:backspace #000000','TarefaKanbanView::canDesarquivar');

            //$kanban->setTemplatePath('app/resources/card.html');

            $kanban->setItemDropAction(new TAction([__CLASS__, 'onUpdateItemDrop']));
            TTransaction::close();

            $container = new TVBox;

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {
                $container->add(TBreadCrumb::create(["Tarefas","Kanban de tarefas"]));
            }
            $container->add($kanban);

            $container->style = 'zoom: 90%';

            parent::add($container);
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onArquivarTarefa($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $vinculadas = TarefaVinculo::where('tarefa_id','=',$param['id'])->load();
            foreach($vinculadas as $vinculada){
                $tarefa = Tarefa::find($vinculada->subtarefa->id);
                $tarefa->arquivado = "S";
                $tarefa->store();
            }

            $tarefa = Tarefa::find($param['id']);
            $tarefa->arquivado = "S";
            $tarefa->store();
            TTransaction::close();
            TApplication::loadPage('TarefaKanbanHeader','onShow');
            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canArquivar($object)
    {
        try 
        {
            TTransaction::open(self::$database);
            $configuracao = TarefaConfiguracao::find(1);
            TTransaction::close();
            if($object->tarefa_status_id == $configuracao->status_final_id && $object->arquivado!="S"){
                return true;
            }

            return false;

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onDesarquivar($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $tarefa = Tarefa::find($param['id']);
            $tarefa->arquivado = "N";
            $tarefa->store();
            TTransaction::close();
            TApplication::loadPage('TarefaKanbanHeader','onShow');

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canDesarquivar($object)
    {
        try 
        {
            if($object->arquivado=="S"){
                return true;
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    /**
     * Update item on drop
     */
    public static function onUpdateItemDrop($param)
    {
        try
        {
            TTransaction::open(self::$database);

            $tarefa = Tarefa::find($param['key']);

            $tarefa->alterarStatusTarefa($param['stage_id']);

            if(isset($tarefa->publicacao_id) && !empty($tarefa->publicacao_id)){

                $tarefasNFinal = Tarefa::where('publicacao_id','=',$tarefa->publicacao_id)
                                        ->where('tarefa_status_id','not in',[
                                            (TarefaConfiguracao::find(1))->status_final_id,
                                            (TarefaConfiguracao::find(1))->status_cancelado_id
                                        ])->count();

                if($tarefasNFinal == 0){
                    new TQuestion("Deseja adicionar data de entrega na publicação?", new TAction([__CLASS__, 'finalizarPublicacao'], ['publicacao_id'=>$tarefa->publicacao_id]), new TAction([__CLASS__, 'onNo'], $param));
                }
            }

            TApplication::loadPage('TarefaKanbanHeader','onShow');

            if (!empty($param['order']))
            {
                foreach ($param['order'] as $key => $id)
                {
                    $sequence = ++$key;

                    $item = new Tarefa($id);
                    $item->prazo_entrega = $sequence;
                    $item->tarefa_status_id = $param['stage_id'];

                    /*

                    $item->store();

                    if($id == $param['key'])
                    {
                        TScript::create("$(\"div[item_id='{$param['key']}']\").css('border-top', '3px solid {$item->tarefa_status->cor}');");
                    }

                */
                }

                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onLoadMore($param)
    {
        try
        {
            TTransaction::open(self::$database);
            $criteriaItem = new TCriteria;

            $criteriaItem->add(new TFilter('tarefa_status_id', '=', $param['key'])); 
            $criteriaItem->setProperty('offset', $param['offset']);
            $criteriaItem->setProperty('limit', $param['limit']);
            $criteriaItem->setProperty('order', 'prazo_entrega asc');

            $items = Tarefa::getObjects($criteriaItem);

            if ($items)
            {
                $actions = [];
                $kanbanItemAction_TarefaFormView_onShow = new TAction(['TarefaFormView', 'onShow']);
                $kanbanItemAction_TarefaFormView_onShow->setParameter("key", $stage->id);
            $kanbanItemAction_TarefaFormView_onShow->setParameter("origem", "TarefaKanbanHeader");

                $actions[] = ["Visualizar tarefa", $kanbanItemAction_TarefaFormView_onShow, 'fas:search-plus #000000'];
                $kanbanItemAction_TarefaKanbanView_onArquivarTarefa = new TAction(['TarefaKanbanView', 'onArquivarTarefa']);
                $kanbanItemAction_TarefaKanbanView_onArquivarTarefa->setParameter("id", "id");

                $actions[] = ["Arquivar", $kanbanItemAction_TarefaKanbanView_onArquivarTarefa, 'fas:archive #000000','TarefaKanbanView::canArquivar'];
                $kanbanItemAction_TarefaKanbanView_onDesarquivar = new TAction(['TarefaKanbanView', 'onDesarquivar']);

                $actions[] = ["Desarquivar", $kanbanItemAction_TarefaKanbanView_onDesarquivar, 'fas:backspace #000000','TarefaKanbanView::canDesarquivar'];

                foreach($items as $item)
                {

                    $item->data_disponibilizacao = call_user_func(function($value, $object, $row) 
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
                    }, $item->data_disponibilizacao, $item, null);

                    $item->prazo_validacao = call_user_func(function($value, $object, $row) 
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
                    }, $item->prazo_validacao, $item, null);

                    $item->prazo_entrega = call_user_func(function($value, $object, $row) 
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
                    }, $item->prazo_entrega, $item, null);

                    $item->observacao = call_user_func(function($value, $object, $row)
                    {
                        return substr($value, 0, 300);

                    }, $item->observacao, $item, null);

                    $item->titulo = call_user_func(function($value, $object, $row)
                    {

                        return str_replace(";","<br/>",$value);

                    }, $item->titulo, $item, null);

                    $item->data_entrega = call_user_func(function($value, $object, $row)
                    {
                        if(!empty(trim((string) $value)))
                        {
                            try
                            {
                                $date = new DateTime($value);
                                return $date->format('d/m/Y H:i');
                            }
                            catch (Exception $e)
                            {
                                return $value;
                            }
                        }
                    }, $item->data_entrega, $item, null);

                    TKanban::createItem($item->id, $item->tarefa_status_id, "{titulo}", "", $item->tarefa_status->cor, $item, null, $actions);

                }
            }

            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {

    } 

    public static function finalizarPublicacao($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $publicacao = Publicacao::find($param['publicacao_id']);
            $publicacao->data_entrega = date('Y-m-d');
            $publicacao->store();
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onNo($param = null) 
    {
        try 
        {
            //code here
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

