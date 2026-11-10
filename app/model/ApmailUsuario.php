<?php
/**
 * Usuario da Curciol no APMail (10/11/2026): e-mail (vazio = o do cadastro do usuario) e avisos S/N do envelope do topo.
 */
class ApmailUsuario extends TRecord
{
    const TABLENAME  = 'apmail_usuario';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_user_id');
        parent::addAttribute('email');
        parent::addAttribute('avisos');
        parent::addAttribute('atualizado_em');
    }
}
