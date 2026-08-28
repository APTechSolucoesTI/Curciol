<?php

class LancamentoProfissional extends TRecord
{
    const TABLENAME  = 'lancamento_profissional';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Lancamento $lancamento;
    private Pessoa $pessoa;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('lancamento_id');
        parent::addAttribute('pessoa_id');
        parent::addAttribute('percentual');
        parent::addAttribute('valor');
            
    }

    /**
     * Method set_lancamento
     * Sample of usage: $var->lancamento = $object;
     * @param $object Instance of Lancamento
     */
    public function set_lancamento(Lancamento $object)
    {
        $this->lancamento = $object;
        $this->lancamento_id = $object->id;
    }

    /**
     * Method get_lancamento
     * Sample of usage: $var->lancamento->attribute;
     * @returns Lancamento instance
     */
    public function get_lancamento()
    {
    
        // loads the associated object
        if (empty($this->lancamento))
            $this->lancamento = new Lancamento($this->lancamento_id);
    
        // returns the associated object
        return $this->lancamento;
    }
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_pessoa(Pessoa $object)
    {
        $this->pessoa = $object;
        $this->pessoa_id = $object->id;
    }

    /**
     * Method get_pessoa
     * Sample of usage: $var->pessoa->attribute;
     * @returns Pessoa instance
     */
    public function get_pessoa()
    {
    
        // loads the associated object
        if (empty($this->pessoa))
            $this->pessoa = new Pessoa($this->pessoa_id);
    
        // returns the associated object
        return $this->pessoa;
    }

    /**
     * Method getLancamentoProfissionalAjustes
     */
    public function getLancamentoProfissionalAjustes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('lancamento_profissional_id', '=', $this->id));
        return LancamentoProfissionalAjuste::getObjects( $criteria );
    }

    public function set_lancamento_profissional_ajuste_lancamento_profissional_to_string($lancamento_profissional_ajuste_lancamento_profissional_to_string)
    {
        if(is_array($lancamento_profissional_ajuste_lancamento_profissional_to_string))
        {
            $values = LancamentoProfissional::where('id', 'in', $lancamento_profissional_ajuste_lancamento_profissional_to_string)->getIndexedArray('id', 'id');
            $this->lancamento_profissional_ajuste_lancamento_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_profissional_ajuste_lancamento_profissional_to_string = $lancamento_profissional_ajuste_lancamento_profissional_to_string;
        }

        $this->vdata['lancamento_profissional_ajuste_lancamento_profissional_to_string'] = $this->lancamento_profissional_ajuste_lancamento_profissional_to_string;
    }

    public function get_lancamento_profissional_ajuste_lancamento_profissional_to_string()
    {
        if(!empty($this->lancamento_profissional_ajuste_lancamento_profissional_to_string))
        {
            return $this->lancamento_profissional_ajuste_lancamento_profissional_to_string;
        }
    
        $values = LancamentoProfissionalAjuste::where('lancamento_profissional_id', '=', $this->id)->getIndexedArray('lancamento_profissional_id','{lancamento_profissional->id}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(LancamentoProfissionalAjuste::where('lancamento_profissional_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

