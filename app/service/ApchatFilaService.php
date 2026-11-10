<?php
/**
 * Fila de reenvio ao APChat (03/11/2026). O cadastro sempre salva; o que nao foi para o APChat (APChat ou o banco dele
 * fora do ar, erro) e as passagens de historico da troca de numero que falharam entram aqui e sao refeitos sozinhos,
 * com os dados lidos na hora (vai sempre a versao mais nova). Sem acerto manual.
 *
 * Tarefa agendada (cron do servidor, como www, a cada 5 minutos):
 *   cd <pasta do sistema> && php cmd.php "class=ApchatFilaService&method=processar"
 * Intervalo entre tentativas: 5, 10, 20, 40 minutos e depois de hora em hora.
 */
class ApchatFilaService
{
    const ENVIO = 'ENVIO';
    const TRANSFERENCIA = 'TRANSFERENCIA';

    const POR_RODADA = 200;

    private static $database = 'escritorio';

    /** Poe (ou atualiza) na fila. Usa a transacao aberta, se houver. */
    public static function enfileirar(string $motivo, string $numero, ?int $pessoa_id, ?int $contatoAntigo, string $erro, ?string $numeroAntigo = null): void
    {
        self::naTransacao(function (PDO $c) use ($motivo, $numero, $pessoa_id, $contatoAntigo, $erro, $numeroAntigo) {
            $st = $c->prepare('SELECT id FROM apchat_fila WHERE motivo = ? AND numero = ? AND COALESCE(contato_antigo_id, 0) = ?');
            $st->execute([$motivo, $numero, (int) $contatoAntigo]);
            if ($id = $st->fetchColumn())
            {
                $c->prepare('UPDATE apchat_fila SET ultimo_erro = ?, pessoa_id = COALESCE(?, pessoa_id), numero_antigo = COALESCE(?, numero_antigo), atualizado_em = now() WHERE id = ?')
                  ->execute([mb_substr($erro, 0, 2000), $pessoa_id, $numeroAntigo, (int) $id]);
                return;
            }
            $c->prepare("INSERT INTO apchat_fila (motivo, numero, pessoa_id, contato_antigo_id, numero_antigo, ultimo_erro, proxima_tentativa)
                         VALUES (?, ?, ?, ?, ?, ?, now() + interval '5 minutes')")
              ->execute([$motivo, $numero, $pessoa_id, $contatoAntigo, $numeroAntigo, mb_substr($erro, 0, 2000)]);
        });
    }

    /** Deu certo: sai da fila. */
    public static function concluir(string $motivo, string $numero, ?int $contatoAntigo = null): void
    {
        self::naTransacao(function (PDO $c) use ($motivo, $numero, $contatoAntigo) {
            $sql = 'DELETE FROM apchat_fila WHERE motivo = ? AND numero = ?';
            $args = [$motivo, $numero];
            if ($contatoAntigo !== null) { $sql .= ' AND COALESCE(contato_antigo_id, 0) = ?'; $args[] = $contatoAntigo; }
            $c->prepare($sql)->execute($args);
        });
    }

    /** Resumo para a tela de integracao: [pendentes, proxima tentativa, itens] */
    public static function resumo(int $limite = 10): array
    {
        return self::naTransacao(function (PDO $c) use ($limite) {
            $total = (int) $c->query('SELECT count(*) FROM apchat_fila')->fetchColumn();
            $itens = $c->query('SELECT motivo, numero, tentativas, proxima_tentativa, ultimo_erro FROM apchat_fila ORDER BY proxima_tentativa LIMIT ' . (int) $limite)->fetchAll(PDO::FETCH_ASSOC);
            return [$total, $itens];
        });
    }

    /** Cron: processa o que esta na hora. */
    public static function processar($param = null): void
    {
        TTransaction::open(self::$database);
        $itens = TTransaction::get()->query('SELECT * FROM apchat_fila WHERE proxima_tentativa <= now() ORDER BY proxima_tentativa LIMIT ' . self::POR_RODADA)->fetchAll(PDO::FETCH_ASSOC);
        TTransaction::close();

        $ok = 0;
        $falhou = 0;
        foreach ($itens as $item)
        {
            $r = APChatContactService::reprocessar($item['motivo'], $item['numero'], $item['contato_antigo_id'] !== null ? (int) $item['contato_antigo_id'] : null, $item['numero_antigo']);
            TTransaction::open(self::$database);
            $c = TTransaction::get();
            if ($r->situacao === ApchatContatoLog::SITUACAO_SUCESSO && empty($r->refazer))
            {
                $c->prepare('DELETE FROM apchat_fila WHERE id = ?')->execute([(int) $item['id']]);
                $ok++;
            }
            else
            {
                $tentativas = (int) $item['tentativas'] + 1;
                $minutos = min(60, 5 * (2 ** min(4, $tentativas)));
                $c->prepare("UPDATE apchat_fila SET tentativas = ?, ultimo_erro = ?, proxima_tentativa = now() + (? || ' minutes')::interval, atualizado_em = now() WHERE id = ?")
                  ->execute([$tentativas, mb_substr((string) $r->mensagem, 0, 2000), (string) $minutos, (int) $item['id']]);
                $falhou++;
            }
            TTransaction::close();
        }

        if (PHP_SAPI === 'cli')
        {
            echo date('d/m/Y H:i:s') . " fila do APChat: {$ok} resolvidos, {$falhou} continuam na fila\n";
        }

        // usuarios novos da Curciol ligados ao atendente do APChat pelo e-mail ou pelo nome (10/11/2026)
        try
        {
            TTransaction::open(self::$database);
            ApchatUsuarioService::ligarAutomatico();
            // webhook (10/11/2026): eventos da campainha do balao ficam 3 dias
            ApchatWebhookService::limparAntigos();
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            TTransaction::rollback();
        }

        // entrega do WhatsApp da Curciol (10/11/2026): 2 tiques = entregue; 24 h sem eles = nao entregue + aviso no sino
        $entrega = MensagemEntregaService::rodada();
        if (PHP_SAPI === 'cli')
        {
            echo date('d/m/Y H:i:s') . ' entrega do WhatsApp: ' . json_encode($entrega) . "\n";
        }

        // conciliacao (10/11/2026): contato do APChat fora do espelho que e da Curciol passa a ser ligado e trancado
        $conciliacao = ApchatConciliacaoService::rodada();
        if (PHP_SAPI === 'cli')
        {
            echo date('d/m/Y H:i:s') . ' conciliacao do APChat: ' . json_encode($conciliacao) . "\n";
        }
    }

    private static function naTransacao(callable $fn)
    {
        $aberta = TTransaction::get();
        if ($aberta) { return $fn($aberta); }
        TTransaction::open(self::$database);
        try
        {
            $r = $fn(TTransaction::get());
            TTransaction::close();
            return $r;
        }
        catch (Throwable $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }
}
