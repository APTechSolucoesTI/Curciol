<?php

class ClienteTarefas extends TRecord
{
    const TABLENAME  = 'cliente_tarefas';
    const PRIMARYKEY = 'tarefa_id';
    const IDPOLICY   =  'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('origem');
        parent::addAttribute('tarefa_status_id');
        parent::addAttribute('usuario_destinatario_id');
        parent::addAttribute('titulo');
        parent::addAttribute('prazo_entrega');
        parent::addAttribute('data_entrega');
        parent::addAttribute('complemento_id');
    
    }

}

