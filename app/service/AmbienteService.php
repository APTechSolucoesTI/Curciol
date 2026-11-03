<?php
/**
 * Producao ou ambiente de teste (homologacao, maquina local).
 *
 * So e producao quando app/config/ambiente.php (fora do git, um por servidor) diz 'producao'. Sem o arquivo, ou com
 * outro valor, e teste: na duvida o sistema fica do lado seguro (03/11/2026, homologacao com copia dos dados reais:
 * mensagem nunca vai para o cliente de verdade).
 */
class AmbienteService
{
    const ARQUIVO = 'app/config/ambiente.php';

    /** Testes: forca producao (true) ou teste (false); null volta ao arquivo. */
    private static $forcado = null;

    public static function forcarProducao(?bool $producao): void
    {
        self::$forcado = $producao;
    }

    public static function producao(): bool
    {
        if (self::$forcado !== null)
        {
            return self::$forcado;
        }

        if (!is_file(self::ARQUIVO))
        {
            return false;
        }

        $config = include self::ARQUIVO;

        return is_array($config) && ($config['ambiente'] ?? '') === 'producao';
    }
}
