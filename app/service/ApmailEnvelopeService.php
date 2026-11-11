<?php
/**
 * Envelope do APMail no topo da Curciol (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * Contador = conversas de e-mail pendentes (A Responder + Em Atendimento); cada caixa mostra pendentes e e-mails nao lidos (cada
 * e-mail conta, nao a conversa; contagens independentes, nao somar).
 * Carga (11/11/2026): o navegador pergunta a cada 10 s, so com a aba a vista; o servidor guarda a resposta de cada e-mail por 10 s
 * (o APMail aceita 1.200 consultas por minuto por token). Ao voltar para a aba da Curciol (fresco), busca direto no APMail - quem
 * leu ou concluiu um e-mail no APMail ve na hora -, mas nunca mais de uma vez a cada 3 s por e-mail. Falhas:
 *  - 404 (e-mail fora da integracao): "nao liberado", guardado por 5 min;
 *  - 429: mostra o que ja tinha e so tenta de novo depois do Retry-After;
 *  - 401/403 (token ou rota): "indisponivel" e 60 s sem tentar;
 *  - 5xx/sem resposta: mostra o que ja tinha (senao "indisponivel") e 60 s sem tentar.
 */
class ApmailEnvelopeService
{
    const CACHE_SEGUNDOS = 10;
    const FRESCO_MINIMO = 3;
    const NAO_LIBERADO_SEGUNDOS = 300;
    const FALHA_SEGUNDOS = 60;
    const ATUALIZAR_MS = 10000;

    /** @var int|null testes: relogio fixo */
    private static $relogio = null;

    public static function usarRelogio(?int $agora): void
    {
        self::$relogio = $agora;
    }

    private static function agora(): int
    {
        return self::$relogio ?? time();
    }

    /**
     * @return array {situacao: ok|sem_integracao|desligado|sem_email|nao_liberado|indisponivel, pendentes, nao_lidas, caixas}
     */
    public static function caixas(int $systemUserId, bool $fresco = false): array
    {
        $vazio = ['pendentes' => 0, 'nao_lidas' => 0, 'caixas' => []];
        $escritorio = ApmailApiService::escritorio();
        $token = $escritorio ? trim((string) $escritorio->token_apmail) : '';
        if ($token === '')
        {
            return ['situacao' => 'sem_integracao'] + $vazio;
        }
        if (!ApmailUsuarioService::avisos($systemUserId))
        {
            return ['situacao' => 'desligado'] + $vazio;
        }
        $email = ApmailUsuarioService::email($systemUserId);
        if ($email === '')
        {
            return ['situacao' => 'sem_email'] + $vazio;
        }

        $agora = self::agora();
        $arquivo = self::arquivo('email-' . md5($email));
        $cache = self::ler($arquivo);
        $recente = $cache && ($cache['em'] ?? 0) > $agora - self::FRESCO_MINIMO;
        if ($cache && ($cache['ate'] ?? 0) > $agora && (!$fresco || $recente))
        {
            return $cache['resultado'];
        }
        // integracao parada (token/rota/servico ou limite): ninguem tenta ate a hora marcada
        $pausa = self::ler(self::arquivo('pausa'));
        if ($pausa && ($pausa['ate'] ?? 0) > $agora)
        {
            return $cache && !empty($pausa['usar_cache']) ? $cache['resultado'] : ['situacao' => $pausa['situacao']] + $vazio;
        }

        try
        {
            $r = ApmailApiService::notificacoes(ApmailApiService::url($escritorio), $token, $email);
            $resultado = ['situacao' => 'ok'] + $r;
            self::gravar($arquivo, ['em' => $agora, 'ate' => $agora + self::CACHE_SEGUNDOS, 'resultado' => $resultado]);
            return $resultado;
        }
        catch (ApmailErro $e)
        {
            if ($e->getCode() === 404)
            {
                $resultado = ['situacao' => 'nao_liberado'] + $vazio;
                self::gravar($arquivo, ['em' => $agora, 'ate' => $agora + self::NAO_LIBERADO_SEGUNDOS, 'resultado' => $resultado]);
                return $resultado;
            }
            // 401/403: configuracao (token ou rota) - nao mostra contagem velha; 429 e 5xx: passageiro - mostra o que ja tinha
            $usarCache = !in_array($e->getCode(), [401, 403], true);
            $segundos = $e->getCode() === 429 ? max(1, (int) ($e->retryAfter ?: self::FALHA_SEGUNDOS)) : self::FALHA_SEGUNDOS;
            self::gravar(self::arquivo('pausa'), ['ate' => $agora + $segundos, 'situacao' => 'indisponivel', 'usar_cache' => $usarCache]);
            return $cache && $usarCache ? $cache['resultado'] : ['situacao' => 'indisponivel'] + $vazio;
        }
        catch (Throwable $e)
        {
            self::gravar(self::arquivo('pausa'), ['ate' => $agora + self::FALHA_SEGUNDOS, 'situacao' => 'indisponivel', 'usar_cache' => true]);
            return $cache ? $cache['resultado'] : ['situacao' => 'indisponivel'] + $vazio;
        }
    }

    public static function limparCache(): void
    {
        foreach (glob(self::arquivo('*')) ?: [] as $f) { @unlink($f); }
    }

    private static function arquivo(string $nome): string
    {
        return sys_get_temp_dir() . '/curciol-apmail-' . md5(__DIR__) . '-' . $nome . '.json';
    }

    private static function ler(string $arquivo): ?array
    {
        $dados = is_file($arquivo) ? json_decode((string) @file_get_contents($arquivo), true) : null;
        return is_array($dados) ? $dados : null;
    }

    /** Grava num arquivo novo e troca: quem le ao mesmo tempo nunca pega o arquivo pela metade. */
    private static function gravar(string $arquivo, array $dados): void
    {
        $tmp = $arquivo . '.tmp' . getmypid() . '-' . mt_rand();
        if (@file_put_contents($tmp, json_encode($dados)) !== false && !@rename($tmp, $arquivo))
        {
            @unlink($tmp);
        }
    }
}
