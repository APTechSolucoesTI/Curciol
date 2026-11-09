<?php

/**
 * Anexo de e-mail (09/11/2026): registro imutavel; arquivo em files/email/anexos/<hash>/<nome>. EmailAnexoService.
 */
class EmailAnexo extends TRecord
{
    const TABLENAME  = 'email_anexo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome_original');
        parent::addAttribute('caminho');
        parent::addAttribute('tamanho');
        parent::addAttribute('criado_em');
        parent::addAttribute('system_user_id');
    }
}
