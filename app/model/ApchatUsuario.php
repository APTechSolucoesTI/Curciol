<?php
/**
 * Usuario da Curciol <-> atendente do APChat (10/11/2026). avisos: S/N (balao); mostrar_abertos: S/N (tickets abertos com ele).
 */
class ApchatUsuario extends TRecord
{
    const TABLENAME  = 'apchat_usuario';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_user_id');
        parent::addAttribute('apchat_usuario_id');
        parent::addAttribute('avisos');
        parent::addAttribute('mostrar_abertos');
        parent::addAttribute('atualizado_em');
    }
}
