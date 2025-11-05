<?php

class LogCrontab extends TRecord
{
    const TABLENAME  = 'log_crontab';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_hora';

    private SystemUnit $system_unit;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_unit_id');
        parent::addAttribute('classe');
        parent::addAttribute('metodo');
        parent::addAttribute('data_hora');
        parent::addAttribute('status');
        parent::addAttribute('mensagem');
        parent::addAttribute('observacao');
    
    }

    /**
     * Method set_system_unit
     * Sample of usage: $var->system_unit = $object;
     * @param $object Instance of SystemUnit
     */
    public function set_system_unit(SystemUnit $object)
    {
        $this->system_unit = $object;
        $this->system_unit_id = $object->id;
    }

    /**
     * Method get_system_unit
     * Sample of usage: $var->system_unit->attribute;
     * @returns SystemUnit instance
     */
    public function get_system_unit()
    {
    
        // loads the associated object
        if (empty($this->system_unit))
            $this->system_unit = new SystemUnit($this->system_unit_id);
    
        // returns the associated object
        return $this->system_unit;
    }

    public static function registrarLog($classe, $metodo, $status, $mensagem, $obs, $unit = 1){
        TTransaction::open('escritorio');
    
        $whatsapp = ($status == 1) ? "Curciol: Verificar execução de crontab." : "Curciol: Crontab concluído";
        APIPublicacaoController::enviarAppChat($whatsapp);

        $log = new LogCrontab();
        $log->system_unit_id = (int) $unit;
        $log->classe         = is_string($classe) ? $classe : 'LOG';
        $log->metodo         = is_string($metodo) ? $metodo : 'LogCrontab::registrarLog';
        $log->mensagem       = is_string($mensagem) ? $mensagem : '';
        $log->observacao     = is_string($obs) ? $obs : '';
        $log->status         = (int) $status;
        $log->store();
    
        TTransaction::close();
    }
                                            
}

