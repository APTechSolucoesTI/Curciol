<?php
/** Erro da API do APMail: codigo HTTP (0 = sem resposta) e Retry-After (segundos), quando vier. */
class ApmailErro extends Exception
{
    public $retryAfter;

    public function __construct(string $mensagem, int $status, ?int $retryAfter = null)
    {
        parent::__construct($mensagem, $status);
        $this->retryAfter = $retryAfter;
    }
}
