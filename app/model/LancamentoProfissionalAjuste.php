<?php

class LancamentoProfissionalAjuste extends TRecord
{
    const TABLENAME  = 'lancamento_profissional_ajuste';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private LancamentoProfissional $lancamento_profissional;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('lancamento_profissional_id');
        parent::addAttribute('tipo');
        parent::addAttribute('valor');
        parent::addAttribute('descricao');
            
    }

    /**
     * Method set_lancamento_profissional
     * Sample of usage: $var->lancamento_profissional = $object;
     * @param $object Instance of LancamentoProfissional
     */
    public function set_lancamento_profissional(LancamentoProfissional $object)
    {
        $this->lancamento_profissional = $object;
        $this->lancamento_profissional_id = $object->id;
    }

    /**
     * Method get_lancamento_profissional
     * Sample of usage: $var->lancamento_profissional->attribute;
     * @returns LancamentoProfissional instance
     */
    public function get_lancamento_profissional()
    {
    
        // loads the associated object
        if (empty($this->lancamento_profissional))
            $this->lancamento_profissional = new LancamentoProfissional($this->lancamento_profissional_id);
    
        // returns the associated object
        return $this->lancamento_profissional;
    }

    
}

