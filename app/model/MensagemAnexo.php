<?php

class MensagemAnexo extends TRecord
{
    const TABLENAME  = 'mensagem_anexo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('mensagem_id');
        parent::addAttribute('email_anexo_id');
    }
}
