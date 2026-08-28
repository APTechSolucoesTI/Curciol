<?php

class ProcessoView extends TRecord
{
    const TABLENAME  = 'processo_view';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'max'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tipo_processo');
        parent::addAttribute('numero');
        parent::addAttribute('cliente');
        parent::addAttribute('area');
        parent::addAttribute('assunto');
        parent::addAttribute('representante');
        parent::addAttribute('pessoa_id');
        parent::addAttribute('exibir_cliente');
        parent::addAttribute('ultima_etapa_id');
        parent::addAttribute('ultima_etapa');
            
    }

    
}

