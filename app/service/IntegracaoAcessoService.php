<?php
/**
 * Acesso ao APChat e ao APMail pela Curciol, usuario a usuario (16/11/2026, pedido do usuario).
 *
 * Enquanto a integracao estiver sem URL/token no escritorio, fora do ar, sem responder ou com o token recusado, ou o usuario
 * nao estiver vinculado, nada abre: os icones do topo ficam limpos (sem contador, sem lista e sem levar a lugar nenhum) e
 * mostram so o motivo; o clique num item (TopoAbrir) e os botoes "Abrir no APChat/APMail" do historico tambem recusam.
 * Notificacoes desligadas pelo proprio usuario (avisos) so escondem o icone; nao bloqueiam os botoes do historico.
 * Usa a transacao aberta ('escritorio').
 */
class IntegracaoAcessoService
{
    const APCHAT = [
        'sem_integracao' => 'A integração com o APChat não está configurada (Escritório › Configurações › APChat). Notificações e acessos bloqueados.',
        'erro'           => 'O APChat não está respondendo. Notificações e acessos ficam bloqueados até a conexão voltar.',
        'sem_ligacao'    => 'Seu usuário não está vinculado a um usuário do APChat (Administração › Usuários das integrações).',
        'sem_atendente'  => 'O usuário do APChat vinculado a você não existe mais no APChat (Administração › Usuários das integrações).',
    ];

    const APMAIL = [
        'sem_integracao' => 'A integração com o APMail não está configurada (Escritório › Configurações › APMail). Notificações e acessos bloqueados.',
        'indisponivel'   => 'O APMail não está respondendo. Notificações e acessos ficam bloqueados até a conexão voltar.',
        'sem_email'      => 'Seu usuário não está vinculado a um usuário do APMail (Administração › Usuários das integrações).',
        'nao_liberado'   => 'Seu e-mail de usuário do APMail não está liberado no APMail para esta integração.',
    ];

    /** Motivo do bloqueio do APChat para a situacao do balao; null = liberado. */
    public static function motivoApchat(string $situacao): ?string
    {
        return $situacao === 'ok' ? null : (self::APCHAT[$situacao] ?? self::APCHAT['erro']);
    }

    /** Motivo do bloqueio do APMail para a situacao do envelope; null = liberado. */
    public static function motivoApmail(string $situacao): ?string
    {
        return $situacao === 'ok' ? null : (self::APMAIL[$situacao] ?? self::APMAIL['indisponivel']);
    }

    /** Situacao do APChat que nao depende do usuario: ok, sem_integracao ou erro (API sem responder ou token recusado). */
    public static function situacaoApchat(): string
    {
        if (!ApchatApiService::configurada() || !ApchatBancoService::configurado())
        {
            return 'sem_integracao';
        }
        return ApchatApiService::noAr() ? 'ok' : 'erro';
    }

    /** O usuario pode abrir o APChat (botoes do historico)? null = pode; senao o motivo. */
    public static function bloqueioApchat(int $systemUserId): ?string
    {
        if (!ApchatApiService::configurada() || !ApchatBancoService::configurado())
        {
            return self::APCHAT['sem_integracao'];
        }
        $ligacao = ApchatUsuarioService::doUsuario($systemUserId);
        if (!$ligacao || !$ligacao->apchat_usuario_id)
        {
            return self::APCHAT['sem_ligacao'];
        }
        return ApchatApiService::noAr() ? null : self::APCHAT['erro'];
    }

    /** O usuario pode abrir o APMail (botoes do historico)? null = pode; senao o motivo. Confere no APMail (cache curto). */
    public static function bloqueioApmail(int $systemUserId): ?string
    {
        return self::motivoApmail((string) (ApmailEnvelopeService::caixas($systemUserId, false, false, true)['situacao'] ?? 'indisponivel'));
    }

    /** Roda $f com a transacao 'escritorio' aberta (abre e fecha se ninguem abriu). Falha = o valor de $falha. */
    public static function naTransacao(callable $f, $falha)
    {
        $abriu = false;
        try
        {
            if (!TTransaction::get())
            {
                TTransaction::open('escritorio');
                $abriu = true;
            }
            $r = $f();
            if ($abriu) { TTransaction::close(); }
            return $r;
        }
        catch (Throwable $e)
        {
            if ($abriu) { TTransaction::rollback(); }
            return $falha;
        }
    }
}
