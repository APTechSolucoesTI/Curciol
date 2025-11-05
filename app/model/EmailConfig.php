<?php

class EmailConfig extends TRecord
{
    const TABLENAME  = 'email_config';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('escritorio_id');
        parent::addAttribute('port');
        parent::addAttribute('username');
        parent::addAttribute('password');
        parent::addAttribute('host');
        parent::addAttribute('from_email');
        parent::addAttribute('from_name');
        parent::addAttribute('smtp_auth');
            
    }

    
}

