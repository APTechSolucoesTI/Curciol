<?php

class ConfigBuscaPrazoTexto extends TRecord
{
    const TABLENAME  = 'config_busca_prazo_texto';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private ConfigBuscaPrazo $config_busca_prazo;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('config_busca_prazo_id');
        parent::addAttribute('texto');
            
    }

    /**
     * Method set_config_busca_prazo
     * Sample of usage: $var->config_busca_prazo = $object;
     * @param $object Instance of ConfigBuscaPrazo
     */
    public function set_config_busca_prazo(ConfigBuscaPrazo $object)
    {
        $this->config_busca_prazo = $object;
        $this->config_busca_prazo_id = $object->id;
    }

    /**
     * Method get_config_busca_prazo
     * Sample of usage: $var->config_busca_prazo->attribute;
     * @returns ConfigBuscaPrazo instance
     */
    public function get_config_busca_prazo()
    {
    
        // loads the associated object
        if (empty($this->config_busca_prazo))
            $this->config_busca_prazo = new ConfigBuscaPrazo($this->config_busca_prazo_id);
    
        // returns the associated object
        return $this->config_busca_prazo;
    }

    
}

