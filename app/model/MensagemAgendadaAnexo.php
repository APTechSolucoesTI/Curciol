<?php

class MensagemAgendadaAnexo extends TRecord
{
    const TABLENAME  = 'mensagem_agendada_anexo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('mensagem_agendada_id');
        parent::addAttribute('email_anexo_id');
    }
}
