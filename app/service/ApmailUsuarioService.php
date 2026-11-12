<?php
/**
 * E-mail e avisos de cada usuario da Curciol no APMail (10/11/2026). Usa a transacao aberta ('escritorio').
 * Sem configuracao: o e-mail do cadastro do usuario e avisos ligados. E-mail igual ao do cadastro nao e guardado (segue o cadastro).
 */
class ApmailUsuarioService
{
    public static function doUsuario(int $systemUserId): ?ApmailUsuario
    {
        return ApmailUsuario::where('system_user_id', '=', $systemUserId)->first() ?: null;
    }

    public static function emailDoCadastro(int $systemUserId): string
    {
        $st = TTransaction::get()->prepare('SELECT email FROM system_users WHERE id = ?');
        $st->execute([$systemUserId]);
        return mb_strtolower(trim((string) $st->fetchColumn()));
    }

    /** E-mail usado no APMail: o informado na tela de usuarios das integracoes, senao o do cadastro. */
    public static function email(int $systemUserId): string
    {
        $l = self::doUsuario($systemUserId);
        $email = $l ? mb_strtolower(trim((string) $l->email)) : '';
        return $email !== '' ? $email : self::emailDoCadastro($systemUserId);
    }

    public static function avisos(int $systemUserId): bool
    {
        $l = self::doUsuario($systemUserId);
        return !$l || $l->avisos !== 'N';
    }

    public static function salvar(int $systemUserId, ?string $email, bool $avisos): void
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new Exception("O e-mail do APMail \"{$email}\" não é um e-mail válido.");
        }
        if ($email === self::emailDoCadastro($systemUserId))
        {
            $email = '';
        }
        $l = self::doUsuario($systemUserId) ?: new ApmailUsuario();
        $antes = mb_strtolower(trim((string) $l->email));
        $l->system_user_id = $systemUserId;
        $l->email = $email !== '' ? $email : null;
        if ($antes !== $email)
        {
            $l->situacao = null; // outro e-mail: a conferencia anterior nao vale mais
            $l->conferido_em = null;
        }
        $l->avisos = $avisos ? 'S' : 'N';
        $l->atualizado_em = date('Y-m-d H:i:s');
        $l->store();
    }

    /**
     * Ligar automaticamente (12/11/2026): testa no APMail (rota de notificacoes) cada usuario ativo, nesta ordem: o e-mail ja
     * escolhido, o do cadastro e o login (quando for e-mail). O primeiro reconhecido fica gravado (situacao S); nenhum = N.
     * 429, 401, 403 ou APMail fora do ar: para e lanca (nao grava errado). Usa a transacao aberta.
     * Quando o APMail tiver GET /api/integrations/users, trocar por casar pela lista (e-mail e nome), sem testar um a um.
     *
     * @param int[]|null $ids so estes usuarios (null = todos os ativos)
     * @return array {ligados, nao_reconhecidos}
     */
    public static function ligarAutomatico(?array $ids = null): array
    {
        $escritorio = ApmailApiService::escritorio();
        $token = $escritorio ? trim((string) $escritorio->token_apmail) : '';
        if ($token === '')
        {
            throw new Exception('Configure o token do APMail em Escritório antes de ligar os usuários.');
        }
        $url = ApmailApiService::url($escritorio);
        $sql = "SELECT id, login FROM system_users WHERE active = 'Y'" . ($ids !== null ? ' AND id IN (' . (implode(',', array_map('intval', $ids)) ?: '0') . ')' : '') . ' ORDER BY id';
        $r = ['ligados' => 0, 'nao_reconhecidos' => 0];
        foreach (TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $u)
        {
            $id = (int) $u['id'];
            $atual = self::doUsuario($id);
            $cadastro = self::emailDoCadastro($id);
            $candidatos = [];
            foreach ([$atual ? (string) $atual->email : '', $cadastro, (string) $u['login']] as $c)
            {
                $c = mb_strtolower(trim($c));
                if ($c !== '' && filter_var($c, FILTER_VALIDATE_EMAIL)) { $candidatos[$c] = true; }
            }
            $achou = null;
            foreach (array_keys($candidatos) as $c)
            {
                try
                {
                    ApmailApiService::notificacoes($url, $token, $c);
                    $achou = $c;
                    break;
                }
                catch (ApmailErro $e)
                {
                    if (!in_array((int) $e->getCode(), [400, 404], true)) { throw $e; } // so "nao e usuario daqui" passa para o proximo
                }
            }
            $l = $atual ?: new ApmailUsuario();
            $l->system_user_id = $id;
            if ($achou !== null)
            {
                $l->email = $achou !== $cadastro ? $achou : null;
            }
            $l->avisos = $l->avisos ?: 'S';
            $l->situacao = $achou !== null ? 'S' : 'N';
            $l->conferido_em = date('Y-m-d H:i:s');
            $l->atualizado_em = date('Y-m-d H:i:s');
            $l->store();
            $r[$achou !== null ? 'ligados' : 'nao_reconhecidos']++;
        }
        return $r;
    }
}
