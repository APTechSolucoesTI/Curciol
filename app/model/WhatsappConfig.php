<?php

class WhatsappConfig extends TRecord
{
    const TABLENAME  = 'whatsapp_config';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('escritorio_id');
        parent::addAttribute('phone');
        parent::addAttribute('status');
        parent::addAttribute('api_token');
        parent::addAttribute('api_key');
            
    }

    
}

