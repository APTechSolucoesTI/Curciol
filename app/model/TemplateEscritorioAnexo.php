<?php

class TemplateEscritorioAnexo extends TRecord
{
    const TABLENAME  = 'template_escritorio_anexo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('template_escritorio_id');
        parent::addAttribute('email_anexo_id');
        parent::addAttribute('ordem');
    }
}
