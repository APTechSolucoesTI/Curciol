<?php

interface EmailInterface
{
    public function __construct(EmailConfig $emailConfig);
    
    /**
     * Send email
     * @param $tos array of target emails
     * @param $subject message subject
     * @param $body message body
     * @param $bodytype body type (text, html)
     * @param $attachs attachments array with files paths
     */
    public function send($tos, $subject, $body, $bodytype = 'text', $attachs = []);
    
    /**
     * Testa a conexão enviando um e-mail para si mesmo
     */
    public function testarEnvio($to);
}
