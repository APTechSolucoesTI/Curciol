<?php
/**
 * Conversas do APMail no historico do cliente (12/11/2026). So le o banco da Curciol (a sincronizacao grava): a tela do cliente
 * nunca chama o APMail. Usa a transacao aberta ('escritorio').
 *
 * - e-mails do cliente: o do cadastro e os dos contatos (minusculas); homologacao: so os de 'historico_somente_emails';
 * - conversas: as que tem algum desses e-mails entre os participantes, decidido na hora (cadastrar um e-mail traz as antigas);
 * - profissionais (tela da conversa): o responsavel atual e quem enviou, sem repetir (mesma regra do APChat: a lista mostra so o
 *   responsavel).
 */
class ApmailHistoricoService
{
    public static function emailsDoCliente(int $pessoaId): array
    {
        $st = TTransaction::get()->prepare("SELECT email FROM pessoa WHERE id = ? UNION ALL SELECT email FROM pessoa_contato WHERE pessoa_id = ?");
        $st->execute([$pessoaId, $pessoaId]);
        $emails = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $e)
        {
            $e = mb_strtolower(trim((string) $e));
            if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) { $emails[$e] = true; }
        }
        $emails = array_keys($emails);
        $somente = ApmailSincronizacaoService::somenteEmails();
        if ($somente !== null) { $emails = array_values(array_intersect($emails, $somente)); }
        sort($emails);
        return $emails;
    }

    /** [{id, thread_id, assunto, caixa_nome, inicio_em, fim_em, responsavel_nome, qtd_mensagens, ultima_mensagem_apmail_id}], mais recente primeiro. */
    public static function conversas(int $pessoaId): array
    {
        $emails = self::emailsDoCliente($pessoaId);
        if (!$emails) { return []; }
        $st = TTransaction::get()->prepare('SELECT c.id, c.thread_id, c.assunto, c.caixa_nome, c.inicio_em, c.fim_em, c.responsavel_nome, c.qtd_mensagens,
                                                   (SELECT m.apmail_id FROM apmail_mensagem m WHERE m.conversa_id = c.id ORDER BY m.mensagem_em DESC, m.id DESC LIMIT 1) AS ultima_mensagem_apmail_id
                                              FROM apmail_conversa c
                                             WHERE EXISTS (SELECT 1 FROM apmail_conversa_email e WHERE e.conversa_id = c.id AND e.email = ANY (?::text[]))
                                             ORDER BY c.inicio_em DESC NULLS LAST, c.id DESC LIMIT 500');
        $st->execute(['{' . implode(',', array_map(function ($e) { return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $e) . '"'; }, $emails)) . '}']);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** {conversa, mensagens (mais antiga primeiro), profissionais}. So conversa do proprio cliente. */
    public static function conversa(int $conversaId, int $pessoaId): array
    {
        $da = null;
        foreach (self::conversas($pessoaId) as $c)
        {
            if ((int) $c['id'] === $conversaId) { $da = $c; break; }
        }
        if (!$da)
        {
            throw new Exception('Esta conversa de e-mail não é deste cliente.');
        }
        $conversa = TTransaction::get()->query('SELECT * FROM apmail_conversa WHERE id = ' . (int) $conversaId)->fetch(PDO::FETCH_ASSOC);
        $conversa['ultima_mensagem_apmail_id'] = $da['ultima_mensagem_apmail_id'];
        $st = TTransaction::get()->prepare('SELECT * FROM apmail_mensagem WHERE conversa_id = ? ORDER BY mensagem_em, id');
        $st->execute([$conversaId]);
        $mensagens = $st->fetchAll(PDO::FETCH_ASSOC);
        $profissionais = [];
        foreach (array_merge([$conversa['responsavel_nome'] ?? null], array_column($mensagens, 'enviado_por_nome')) as $nome)
        {
            $nome = trim((string) $nome);
            if ($nome !== '') { $profissionais[$nome] = true; }
        }
        return ['conversa' => $conversa, 'mensagens' => $mensagens, 'profissionais' => array_keys($profissionais)];
    }
}
