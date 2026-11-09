<?php

class EmailClient
{
    private $gatway;

    /**
     * Substitui o envio por SMTP (testes, 09/11/2026). Recebe ($tos, $subject, $body, $bodytype, $attachs).
     *
     * @var callable|null
     */
    private static $transporte = null;

    public static function usarTransporte(?callable $transporte): void
    {
        self::$transporte = $transporte;
    }

    public function __construct(EmailConfig $emailConfig)
    {
        $this->gatway = new PHPMailerGatway($emailConfig);
    }

    public function send($tos, $subject, $body, $bodytype = 'html', $attachs = [])
    {
        // a trava da homologacao vale tambem com o transporte falso
        AmbienteService::exigirEmailsDeTeste(is_array($tos) ? $tos : explode(';', str_replace(',', ';', (string) $tos)));

        if (self::$transporte)
        {
            call_user_func(self::$transporte, $tos, $subject, $body, $bodytype, $attachs);
            return;
        }

        $this->gatway->send($tos, $subject, $body, $bodytype, $attachs);
    }

    public function testarEnvio($to)
    {
        $this->gatway->testarEnvio($to);
    }
}
