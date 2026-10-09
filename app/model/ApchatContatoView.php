<?php

/**
 * Clientes com consentimento de WhatsApp ('T') e o resultado da ultima
 * sincronizacao com o APChat (view apchat_contato_view).
 */
class ApchatContatoView extends TRecord
{
    const TABLENAME  = 'apchat_contato_view';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'max'; // {max, serial}

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('tipo_pessoa_id');
        parent::addAttribute('cpf_cnpj');
        parent::addAttribute('email');
        parent::addAttribute('telefone');
        parent::addAttribute('ultima_sincronizacao');
        parent::addAttribute('ultima_operacao');
        parent::addAttribute('situacao');
        parent::addAttribute('mensagem');
        parent::addAttribute('numero_apchat');
        parent::addAttribute('apchat_contato_id');
    }
}
