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
    const MSG_SEM_SAIDA = 'Ambiente de homologação: a Curciol não envia WhatsApp nem e-mails do sistema e não sincroniza contatos com o APChat. Só a produção envia.';

    /**
     * E-mail do escritorio fora da producao (usuario, 09/11/2026: liberado para testar, com o SMTP de teste dele): sai, mas
     * nunca para um e-mail cadastrado na Curciol (pessoa ou contato), que na homologacao sao os clientes reais.
     */
    const MSG_EMAIL_CADASTRADO = 'Ambiente de homologação: %s está cadastrado na Curciol e não recebe e-mail daqui. Use um e-mail de teste.';

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

    /**
     * Fora da producao, recusa destinatario cadastrado em pessoa ou pessoa_contato. Conexao propria: pode ser chamado com
     * ou sem transacao aberta.
     *
     * @param string[] $emails
     */
    public static function exigirEmailsDeTeste(array $emails): void
    {
        if (self::producao())
        {
            return;
        }

        $emails = array_values(array_unique(array_filter(array_map(function ($e) { return mb_strtolower(trim((string) $e)); }, $emails), 'strlen')));

        if (!$emails)
        {
            return;
        }

        $marcas = implode(',', array_fill(0, count($emails), '?'));
        $conn = TConnection::open('escritorio');
        $st = $conn->prepare("SELECT e FROM (SELECT lower(trim(email)) e FROM pessoa UNION SELECT lower(trim(email)) FROM pessoa_contato) x
                              WHERE e IN ({$marcas}) LIMIT 1");
        $st->execute($emails);
        $cadastrado = $st->fetchColumn();

        if ($cadastrado !== false)
        {
            throw new Exception(sprintf(self::MSG_EMAIL_CADASTRADO, $cadastrado));
        }
    }

    /** Lanca excecao fora da producao: chamar antes de qualquer envio que nao pode sair da homologacao (WhatsApp, e-mail do sistema, contato no APChat). */
    public static function exigirProducaoParaEnviar(): void
    {
        if (!self::producao())
        {
            throw new Exception(self::MSG_SEM_SAIDA);
        }
    }
}
