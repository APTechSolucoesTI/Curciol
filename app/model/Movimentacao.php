<?php

class Movimentacao extends TRecord
{
    const TABLENAME  = 'movimentacao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Material $material;
    private SystemUsers $system_user;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('material_id');
        parent::addAttribute('system_user_id');
        parent::addAttribute('dt_movimentacao');
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
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_system_user(SystemUsers $object)
    {
        $this->system_user = $object;
        $this->system_user_id = $object->id;
    }

    /**
     * Method get_system_user
     * Sample of usage: $var->system_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_system_user()
    {
    
        // loads the associated object
        if (empty($this->system_user))
            $this->system_user = new SystemUsers($this->system_user_id);
    
        // returns the associated object
        return $this->system_user;
    }

    
}

