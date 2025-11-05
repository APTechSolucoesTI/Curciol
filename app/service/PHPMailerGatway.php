<?php

class PHPMailerGatway implements EmailInterface
{
    private $config;
    
    public function __construct(EmailConfig $emailConfig)
    {
        $this->config = $emailConfig;
        
        if (
            ! $this->config->from_email ||
            ! $this->config->from_name ||
            ! $this->config->username ||
            ! $this->config->password ||
            ! $this->config->host ||
            ! $this->config->port
        ){
            throw new Exception('Preencha as configurções de e-mail da clínica');
        }
    }
    
    public function send($tos, $subject, $body, $bodytype = 'text', $attachs = [])
    {
        $mail = new TMail;
        $mail->setFrom( trim($this->config->from_email), $this->config->from_name);
        
        if (is_string($tos))
        {
            $tos = str_replace(',', ';', $tos);
            $tos = explode(';', $tos);
        }
        
        if (is_array($tos))
        {
            foreach ($tos as $to)
            {
                $mail->addAddress( $to );
            }
        }
        else
        {
            $mail->addAddress( $tos );
        }
        $mail->setSubject( $subject );
        
        if ($this->config->smtp_auth == 'T')
        {
            $mail->setUseSmtp( (!empty($this->config->username) && !empty($this->config->password) ) );
        }
        
        $mail->SetSmtpUser($this->config->username, $this->config->password);
        $mail->SetSmtpHost($this->config->host, $this->config->port);
        
        if (!empty($attachs))
        {
            foreach ($attachs as $attach)
            {
                $mail->addAttach($attach[0], (isset($attach[1]) ? $attach[1] : null));
            }
        }
        
        if ($bodytype == 'text')
        {
            $mail->setTextBody($body);
        }
        else
        {
            $mail->setHtmlBody($body);
        }
        
        $mail->send();
    }
    
    public function testarEnvio($to)
    {
        $this->send($to, 'Teste', 'Teste');
    }
}
