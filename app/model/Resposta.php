<?php

class Resposta extends TRecord
{
    const TABLENAME  = 'resposta';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('resposta_formulario_id');
        parent::addAttribute('questao_id');
        parent::addAttribute('resposta');
            
    }

    
}

