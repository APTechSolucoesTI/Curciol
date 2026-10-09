<?php

/**
 * Uma tentativa de sincronizacao de um cliente com o APChat.
 * Gravado por APChatContactService.
 */
class ApchatContatoLog extends TRecord
{
    const TABLENAME  = 'apchat_contato_log';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const ORIGEM_CADASTRO = 'CADASTRO_CLIENTE';
    const ORIGEM_LOTE     = 'SINCRONIZAR_TODOS';

    const OPERACAO_CREATE  = 'CREATE';
    const OPERACAO_UPDATE  = 'UPDATE';
    const OPERACAO_NENHUMA = 'NENHUMA';

    const SITUACAO_SUCESSO  = 'SUCESSO';
    const SITUACAO_PENDENTE = 'PENDENTE';
    const SITUACAO_ERRO     = 'ERRO';
    const SITUACAO_IGNORADO = 'IGNORADO';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('origem');
        parent::addAttribute('operacao');
        parent::addAttribute('situacao');
        parent::addAttribute('numero');
        parent::addAttribute('numero_anterior');
        parent::addAttribute('apchat_contato_id');
        parent::addAttribute('http_status');
        parent::addAttribute('mensagem');
        parent::addAttribute('data_criacao');
        parent::addAttribute('usuario_id');
    }
}
