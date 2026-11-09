<?php
/**
 * Icone de mensagens do topo (atualizado a cada verify_messages_menu pelo BuilderTemplate.js).
 *
 * Curciol (09/11/2026): as mensagens internas entre usuarios do framework sairam (11 mensagens, a ultima de 05/2024). O icone
 * fica vazio ate a integracao com o APChat (balao de chat com as conversas a responder).
 */
class MessageList extends TElement
{
    public function __construct($param)
    {
        parent::__construct('div');
    }
}
