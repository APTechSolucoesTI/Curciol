<?php

class WhatsAppClient
{
    private $gatway;
    
    public function __construct(WhatsAppConfig $config)
    {
        // get ini
        $this->gatway = new ZApiGatway($config);
    }
    
    public function getQrCode()
    {
        return $this->gatway->getQrCode();
    }
    
    public function getStatus()
    {
        return $this->gatway->getStatus();
    }
    
    public function getNumber()
    {
        return $this->gatway->getNumber();
    }
    
    public function disconnect()
    {
        return $this->gatway->disconnect();
    }
    
    public function testarEnvio($to)
    {
        return $this->gatway->testarEnvio($to);
    }
    
    public function send($to, $message, $title = null, $acoes = [])
    {
       return $this->gatway->send($to, $message, $title, $acoes);
    }
}
