<?php

/**
 * Assinatura de e-mail (09/11/2026): registro imutavel; html com src relativos (assinaturas/<hash>.png). EmailAssinaturaService.
 */
class EmailAssinatura extends TRecord
{
    const TABLENAME  = 'email_assinatura';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const IMAGEM = 'IMAGEM';
    const HTML   = 'HTML';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome_original');
        parent::addAttribute('tipo');
        parent::addAttribute('html');
        parent::addAttribute('criado_em');
        parent::addAttribute('system_user_id');
    }
}
