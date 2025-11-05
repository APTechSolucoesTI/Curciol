<?php

class AtendimentoMaterial extends TRecord
{
    const TABLENAME  = 'atendimento_material';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Material $material;
    private Atendimento $atendimento;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('material_id');
        parent::addAttribute('atendimento_id');
        parent::addAttribute('quantidade');
            
    }

    /**
     * Method set_material
     * Sample of usage: $var->material = $object;
     * @param $object Instance of Material
     */
    public function set_material(Material $object)
    {
        $this->material = $object;
        $this->material_id = $object->id;
    }

    /**
     * Method get_material
     * Sample of usage: $var->material->attribute;
     * @returns Material instance
     */
    public function get_material()
    {
    
        // loads the associated object
        if (empty($this->material))
            $this->material = new Material($this->material_id);
    
        // returns the associated object
        return $this->material;
    }
    /**
     * Method set_atendimento
     * Sample of usage: $var->atendimento = $object;
     * @param $object Instance of Atendimento
     */
    public function set_atendimento(Atendimento $object)
    {
        $this->atendimento = $object;
        $this->atendimento_id = $object->id;
    }

    /**
     * Method get_atendimento
     * Sample of usage: $var->atendimento->attribute;
     * @returns Atendimento instance
     */
    public function get_atendimento()
    {
    
        // loads the associated object
        if (empty($this->atendimento))
            $this->atendimento = new Atendimento($this->atendimento_id);
    
        // returns the associated object
        return $this->atendimento;
    }

    
}

