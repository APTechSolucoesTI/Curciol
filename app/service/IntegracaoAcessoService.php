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
        'outra_empresa'  => 'A integração do Escritório (URL e token) não é da mesma empresa do banco do APChat ligado à Curciol. Notificações e acessos bloqueados até as duas configurações serem da mesma empresa.',
        'sem_conferencia' => 'Não foi possível conferir se a integração do Escritório é da mesma empresa do banco do APChat (as funções da Curciol no APChat precisam ser atualizadas). Notificações e acessos bloqueados.',
    ];

    const APMAIL = [
        'sem_integracao' => 'A integração com o APMail não está configurada (Escritório › Configurações › APMail). Notificações e acessos bloqueados.',
        'indisponivel'   => 'O APMail não está respondendo. Notificações e acessos ficam bloqueados até a conexão voltar.',
        'sem_email'      => 'Seu usuário não está vinculado a um usuário do APMail (Administração › Usuários das integrações).',
        'nao_liberado'   => 'Seu e-mail de usuário do APMail não está liberado no APMail para esta integração.',
    ];

    /**
     * Rotulo do icone bloqueado (16/11/2026, no lugar do "Ver todos" do sino): "Fora de servico" quando esta tudo configurado e
     * vinculado mas nao houve comunicacao (fora do ar, sem responder, token recusado); "Indisponivel" nos outros casos.
     */
    const FORA_DE_SERVICO = 'Fora de serviço';
    const INDISPONIVEL = 'Indisponível';

    public static function rotuloApchat(string $situacao): string
    {
        return $situacao === 'erro' ? self::FORA_DE_SERVICO : self::INDISPONIVEL;
    }

    public static function rotuloApmail(string $situacao): string
    {
        return $situacao === 'indisponivel' ? self::FORA_DE_SERVICO : self::INDISPONIVEL;
    }

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

    const EMPRESA_SEGUNDOS = 60;

    /**
     * Configuracao do APChat, que nao depende do usuario nem da API estar no ar: ok | sem_integracao (sem URL/token no Escritorio ou
     * sem o banco) | outra_empresa (a integracao da URL nao e da empresa do banco: 16/11/2026, trocar URL/token para outra empresa
     * nao pode mostrar o APChat da anterior) | sem_conferencia (o banco nao tem a funcao) | erro (banco fora, nao deu para conferir).
     * A conferencia da empresa fica guardada 1 min por URL e banco.
     */
    public static function configuracaoApchat(): string
    {
        $escritorio = ApchatApiService::escritorio();
        if (!ApchatApiService::configurada($escritorio) || !ApchatBancoService::configurado())
        {
            return 'sem_integracao';
        }
        $url = ApchatApiService::url($escritorio);
        $id = (string) substr(strrchr('/' . $url, '/'), 1);
        if (!preg_match('/^[0-9a-f-]{36}$/i', $id))
        {
            return 'outra_empresa'; // URL sem o codigo da integracao: nao da para ser desta empresa
        }
        $arquivo = sys_get_temp_dir() . '/curciol-apchat-empresa-' . md5(__DIR__) . '-' . ApchatBancoService::assinatura() . '-' . strtolower($id) . '.json';
        $guardado = ApchatBancoService::emTeste() || !is_file($arquivo) ? null : json_decode((string) @file_get_contents($arquivo), true);
        if (is_array($guardado) && ($guardado['ate'] ?? 0) > time() && is_string($guardado['r'] ?? null))
        {
            return $guardado['r'];
        }
        try
        {
            $r = ApchatBancoService::integracaoDaEmpresa($id) ? 'ok' : 'outra_empresa';
        }
        catch (Throwable $e)
        {
            // banco fora = fora de servico; banco no ar sem a funcao = falta atualizar as funcoes no APChat
            $r = (ApchatBancoService::emTeste() || ApchatBancoService::disponivel()) && stripos($e->getMessage(), 'f_integracao') !== false ? 'sem_conferencia' : 'erro';
        }
        if (!ApchatBancoService::emTeste())
        {
            $tmp = $arquivo . '.tmp' . getmypid() . '-' . mt_rand();
            if (@file_put_contents($tmp, json_encode(['r' => $r, 'ate' => time() + self::EMPRESA_SEGUNDOS])) !== false && !@rename($tmp, $arquivo)) { @unlink($tmp); }
        }
        return $r;
    }

    /** Situacao do APChat que nao depende do usuario: a configuracao e, com ela certa, a API no ar (senao erro). */
    public static function situacaoApchat(): string
    {
        $config = self::configuracaoApchat();
        if ($config !== 'ok')
        {
            return $config;
        }
        return ApchatApiService::noAr() ? 'ok' : 'erro';
    }

    /** O usuario pode abrir o APChat (botoes do historico)? null = pode; senao o motivo. */
    public static function bloqueioApchat(int $systemUserId): ?string
    {
        $config = self::configuracaoApchat();
        if ($config !== 'ok')
        {
            return self::motivoApchat($config);
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
