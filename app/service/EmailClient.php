<?php

class EmailClient
{
    private $gatway;
    
    public function __construct(EmailConfig $emailConfig)
    {
        $this->gatway = new PHPMailerGatway($emailConfig);
    }
    
    public function send($tos, $subject, $body, $bodytype = 'html', $attachs = [])
    {
        $this->gatway->send($tos, $subject, $body, $bodytype, $attachs);
    }
    
    public function testarEnvio($to)
    {
        $this->gatway->testarEnvio($to);
    }
}
