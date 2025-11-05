<?php

interface WhatsAppInterface
{
    public function __construct(WhatsAppConfig $whatsAppConfig);
    
    /**
     * Desconectar da api
     */
    public function disconnect();
    
    /**
     * Buscar QR Code para conectar
     */
    public function getQrCode();
    
    /**
     * Verificar status da api
     */
    public function getStatus();
    
    /**
     * Busca o numero conectado na api
     */
    public function getNumber();
    
    /**
     * Envia mensagem
     */
    public function send($to, $message, $title = null, $acoes = []);
    
    public function testarEnvio($to);
}
