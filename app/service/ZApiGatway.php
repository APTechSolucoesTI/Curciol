<?php

class ZApiGatway implements WhatsAppInterface
{
    const ENDPOINT = 'https://api.z-api.io/'; 
    
    private $url;
    private $config;
    
    public function __construct($whatsAppConfig)
    {
        $this->config = $whatsAppConfig;
        $this->url = self::ENDPOINT . "/instances/{$whatsAppConfig->api_key}/token/{$whatsAppConfig->api_token}/";
    }
    
    public function getQrCode()
    {
        $qrcode = $this->get($this->url . 'qr-code/image');
        
        if (! empty($qrcode->value))
        {
            return $qrcode->value;
        }
        
        if ($qrcode->connected)
        {
            throw new Exception('Você já está conectado.');
        }
    }
    
    public function disconnect()
    {
        $this->get($this->url . 'disconnect');
    }
    
    public function getStatus()
    {
        $status = $this->get($this->url . 'status');
        
        if ($status && !empty($status->connected))
        {
            return 'Conectado';    
        }
        
        return 'Não conectado';
    }
    
    public function getNumber()
    {
        $device = $this->get($this->url . 'device');
        
        if (!empty($device->phone))
        {
            return $device->phone;    
        }
        
        return null;
    }
    
    public function testarEnvio($to)
    {
        if (! $to)
        {
            throw new Exception('Não conseguimos identificar o número');
        }
        
        $this->send(
            $to,
            'teste',
            'Mensagem de teste'
        );
    }
    
    public function send($to, $message, $title = null, $acoes = [])
    {
        $to = str_replace([' ', '(', ')', '-', '+'], ['','','','',''], $to);
        $to = strlen($to) > 11 ? $to : '55'. $to; 
        
        if ($title)
        {
            $message = "*{$title}*\n{$message}";
        }
        
        $params = ['phone' => $to, 'message' => $message];

        if ($acoes)
        {
            $params["buttonActions"] = [];
            
            $index = 0;
            
            foreach ($acoes as $url => $label)
            {
                $params["buttonActions"][] = [
                    'id' => ++$index,
                    "type" => "URL",
                    "url" => $url,
                    "label" => $label
                ];
            }
        }

        $this->post($this->url . 'send-button-actions', $params);
    }
    
    public function get($url)
    {
        return BuilderHttpClientService::get($url);
    }
    
    public function post($url, $data)
    {
        return BuilderHttpClientService::post($url, $data, null, ['Content-Type: application/json']);
    }
}
