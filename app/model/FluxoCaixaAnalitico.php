<?php

class FluxoCaixaAnalitico extends TRecord
{
    const TABLENAME  = 'fluxo_caixa_analitico';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dia');
        parent::addAttribute('tipo');
        parent::addAttribute('numero');
        parent::addAttribute('historico');
        parent::addAttribute('entrada');
        parent::addAttribute('saida');
        parent::addAttribute('saldo');
            
    }

    
}

