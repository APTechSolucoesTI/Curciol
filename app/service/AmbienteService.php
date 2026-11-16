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

    /** Homologacao com numero de teste: outro numero nao recebe WhatsApp daqui. */
    const MSG_SO_NUMERO_TESTE = 'Ambiente de homologação: o WhatsApp só sai para o número de teste %s.';

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

    /**
     * Numero de WhatsApp de teste (16/11/2026, usuario: testar com agendamentos reais da homologacao, sempre para o mesmo
     * numero). Fora da producao, com 'whatsapp_teste' no app/config/ambiente.php do servidor, todo WhatsApp do agendamento
     * vai para este numero e so para ele; qualquer outro numero continua sem sair. Na producao nao vale. Vazio = sem numero
     * de teste (nenhum WhatsApp sai). Com o ambiente forcado (testes), vale so o forcado por forcarWhatsappTeste().
     */
    public static function whatsappTeste(): string
    {
        if (self::producao())
        {
            return '';
        }

        if (self::$forcado !== null)
        {
            $numero = (string) self::$testeForcado;
        }
        else
        {
            $config = is_file(self::ARQUIVO) ? include self::ARQUIVO : null;
            $numero = is_array($config) ? (string) ($config['whatsapp_teste'] ?? '') : '';
        }

        $numero = APChatContactService::normalizarTelefone($numero);

        return $numero === '' ? '' : APChatContactService::numeroCanonico($numero);
    }

    /** O numero e o de teste (com ou sem o 9, com ou sem 55)? Sempre falso na producao ou sem numero de teste. */
    public static function ehWhatsappTeste(?string $numero): bool
    {
        $teste = self::whatsappTeste();
        $numero = APChatContactService::normalizarTelefone((string) $numero);

        return $teste !== '' && $numero !== '' && array_intersect(APChatContactService::variantes($numero), APChatContactService::variantes($teste)) !== [];
    }

    /** Testes: numero de teste usado enquanto o ambiente esta forcado. */
    private static $testeForcado = null;

    public static function forcarWhatsappTeste(?string $numero): void
    {
        self::$testeForcado = $numero;
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
