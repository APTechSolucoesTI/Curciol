<?php

class CepCache extends TRecord
{
    const TABLENAME  = 'cep_cache';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cep');
        parent::addAttribute('codigo_ibge');
        parent::addAttribute('rua');
        parent::addAttribute('cidade');
        parent::addAttribute('bairro');
        parent::addAttribute('uf');
        parent::addAttribute('cidade_id');
    
    }

}

