<?php

class MensagemAgendada extends TRecord
{
    const TABLENAME  = 'mensagem_agendada';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const PENDENTE  = 'PENDENTE';
    const ENVIADA   = 'ENVIADA';
    const CANCELADA = 'CANCELADA';
    const FALHOU    = 'FALHOU';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agendamento_id');
        parent::addAttribute('template_escritorio_id');
        parent::addAttribute('mensagem_id');
        parent::addAttribute('system_user_id');
        parent::addAttribute('tipo_mensagem');
        parent::addAttribute('destino');
        parent::addAttribute('titulo');
        parent::addAttribute('texto');
        parent::addAttribute('enviar_em');
        parent::addAttribute('situacao');
        parent::addAttribute('tentativas');
        parent::addAttribute('motivo');
        parent::addAttribute('processado_em');
        parent::addAttribute('criado_em');
        parent::addAttribute('assinatura_id');
    }
}
