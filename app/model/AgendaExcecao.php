<?php

/**
 * Excecao da agenda (05/11/2026): periodo liberado dentro de bloqueio, intervalo ou nao atende, em que se pode marcar
 * agendamentos e compromissos. As regras estao em AgendaConflitoService.
 */
class AgendaExcecao extends TRecord
{
    const TABLENAME  = 'agenda_excecao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    use SystemChangeLogTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agenda_id');
        parent::addAttribute('dt_inicio');
        parent::addAttribute('dt_final');
        parent::addAttribute('observacao');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    }

    public function get_agenda()
    {
        return Agenda::find($this->agenda_id);
    }

    public function get_criacao_user()
    {
        return $this->criacao_user_id ? SystemUsers::find($this->criacao_user_id) : null;
    }

    public function get_modificacao_user()
    {
        return $this->modificacao_user_id ? SystemUsers::find($this->modificacao_user_id) : null;
    }
}
