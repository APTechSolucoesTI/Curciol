<?php
/**
 * Conciliacao dos contatos do APChat (10/11/2026), no cron da fila (a cada 5 min). Rede de seguranca da trava de contato novo
 * do APChat: contato do tenant 21 fora do espelho cujo numero e da Curciol passa a ser ligado e trancado.
 *
 * - mesmo numero de um contato da Curciol: sincroniza a pessoa pelo caminho de sempre (atualiza no APChat, grava o espelho e o
 *   idapchat do contato);
 * - variante (com/sem o 9): segundo contato do mesmo cliente (APChatContactService::ligarVariante), sem trocar o idapchat;
 * - numero que nao e da Curciol: fica como esta ("Contatos sem vinculo").
 *
 * Fora da producao so conta (a trava de 09/11 nao deixa nada ir ao APChat). APChat fora: devolve ok = false e nao quebra o cron.
 */
class ApchatConciliacaoService
{
    const LIMITE = 200;

    public static function rodada(): array
    {
        $r = ['ok' => true, 'ligados' => 0, 'variantes' => 0, 'sem_vinculo' => 0, 'pendentes' => 0, 'erros' => 0];

        try
        {
            if (!ApchatBancoService::configurado() || !ApchatBancoService::disponivel())
            {
                return ['ok' => false] + $r;
            }
            $fora = ApchatBancoService::contatosForaDoEspelho(self::LIMITE);
        }
        catch (Throwable $e)
        {
            return ['ok' => false, 'erro' => $e->getMessage()] + $r;
        }

        if (!$fora)
        {
            return $r;
        }

        [$exatos, $porCanonico] = self::numerosDaCurciol();

        $pessoas = [];   // pessoa => quantos contatos do APChat com o numero igual
        $variantes = []; // [contato, numero no APChat, numero da Curciol, pessoa]
        foreach ($fora as $c)
        {
            $numero = preg_replace('/\D/', '', (string) $c['number']);
            if (isset($exatos[$numero]))
            {
                $pessoas[$exatos[$numero]] = ($pessoas[$exatos[$numero]] ?? 0) + 1;
            }
            elseif (isset($porCanonico[APChatContactService::numeroCanonico($numero)]))
            {
                [$pessoa, $numeroCurciol] = $porCanonico[APChatContactService::numeroCanonico($numero)];
                $variantes[] = [(int) $c['id'], $numero, $numeroCurciol, $pessoa];
            }
            else
            {
                $r['sem_vinculo']++;
            }
        }

        if (!AmbienteService::producao())
        {
            $r['pendentes'] = array_sum($pessoas) + count($variantes);
            return $r;
        }

        foreach ($pessoas as $pessoa => $quantos)
        {
            $res = APChatContactService::sincronizarPessoa($pessoa, [], ApchatContatoLog::ORIGEM_CONCILIACAO);
            $res->situacao === ApchatContatoLog::SITUACAO_ERRO ? $r['erros'] += $quantos : $r['ligados'] += $quantos;
        }
        foreach ($variantes as [$contato, $numero, $numeroCurciol, $pessoa])
        {
            $res = APChatContactService::ligarVariante($contato, $numero, $numeroCurciol, $pessoa, ApchatContatoLog::ORIGEM_CONCILIACAO);
            $res->situacao === ApchatContatoLog::SITUACAO_SUCESSO ? $r['variantes']++ : $r['erros']++;
        }

        return $r;
    }

    /** Numeros dos contatos da Curciol: [55+numero => pessoa] e [canonico => [pessoa, 55+numero]] (principal primeiro). */
    private static function numerosDaCurciol(): array
    {
        TTransaction::open('escritorio');
        $linhas = TTransaction::get()->query("SELECT pessoa_id, telefone FROM pessoa_contato WHERE COALESCE(telefone, '') <> ''
                                               ORDER BY (principal = 'S') DESC, id")->fetchAll(PDO::FETCH_ASSOC);
        TTransaction::close();

        $exatos = [];
        $porCanonico = [];
        foreach ($linhas as $l)
        {
            $n = APChatContactService::normalizarTelefone($l['telefone']);
            if ($n === '') { continue; }
            $n = substr($n, 0, 2) === '55' && strlen($n) >= 12 ? $n : '55' . $n;
            $exatos[$n] = $exatos[$n] ?? (int) $l['pessoa_id'];
            $porCanonico[APChatContactService::numeroCanonico($n)] = $porCanonico[APChatContactService::numeroCanonico($n)] ?? [(int) $l['pessoa_id'], $n];
        }
        return [$exatos, $porCanonico];
    }
}
