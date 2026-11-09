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

    /**
     * Fora da producao nada sai da Curciol (usuario, 09/11/2026: homologacao com copia da producao, inclusive URL e
     * token do APChat): nenhum WhatsApp, nenhum e-mail e nenhuma sincronizacao de contatos com o APChat. Consultas a
     * API que nao alteram nada (ApchatApiService::ROTAS_CONSULTA) continuam liberadas.
     */
    const MSG_SEM_SAIDA = 'Ambiente de homologação: a Curciol não envia mensagens nem e-mails e não sincroniza contatos com o APChat. Só a produção envia.';

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

    /** Lanca excecao fora da producao: chamar antes de qualquer envio (WhatsApp, e-mail, contato no APChat). */
    public static function exigirProducaoParaEnviar(): void
    {
        if (!self::producao())
        {
            throw new Exception(self::MSG_SEM_SAIDA);
        }
    }
}
