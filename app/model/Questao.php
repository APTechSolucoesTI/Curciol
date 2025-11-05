<?php

class Questao extends TRecord
{
    const TABLENAME  = 'questao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Formulario $formulario;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('formulario_id');
        parent::addAttribute('nome');
        parent::addAttribute('tipo_campo');
        parent::addAttribute('fl_obrigatorio');
        parent::addAttribute('ativo');
        parent::addAttribute('opcoes');
    
    }

    /**
     * Method set_formulario
     * Sample of usage: $var->formulario = $object;
     * @param $object Instance of Formulario
     */
    public function set_formulario(Formulario $object)
    {
        $this->formulario = $object;
        $this->formulario_id = $object->id;
    }

    /**
     * Method get_formulario
     * Sample of usage: $var->formulario->attribute;
     * @returns Formulario instance
     */
    public function get_formulario()
    {
    
        // loads the associated object
        if (empty($this->formulario))
            $this->formulario = new Formulario($this->formulario_id);
    
        // returns the associated object
        return $this->formulario;
    }

}

