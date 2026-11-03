<?php
/**
 * Acoes da tela "Contatos sem vinculo" do APChat (03/11/2026): ligar um numero que mandou mensagem no APChat a um
 * cadastro da Curciol. Mesmas regras da aba Contatos (PessoaContatoService): um principal por pessoa, telefone principal
 * de uma pessoa so, Dados cadastrais = contato principal. Depois de gravar, o envio ao APChat (APChatContactService)
 * atualiza o contato la e grava o idapchat - e ele sai da lista.
 */
class ApchatVinculoService
{
    private static $database = 'escritorio';

    /**
     * Poe o numero na aba Contatos de um cadastro existente (ou atualiza o contato da pessoa que ja tem esse numero).
     * @return object resultado do envio ao APChat
     */
    public static function vincular(int $pessoaId, string $numero, ?string $nome, ?string $descricao, bool $principal): object
    {
        $telefone = PessoaContatoService::telefoneValido($numero);
        if (!$telefone) { throw new Exception(PessoaContatoService::MSG_TELEFONE_INVALIDO); }

        TTransaction::open(self::$database);
        try
        {
            $pessoa = Pessoa::find($pessoaId);
            if (!$pessoa) { throw new Exception('Cadastro não encontrado.'); }
            $antes = PessoaContatoService::estadoAtual($pessoaId);
            $conn = TTransaction::get();

            if ($principal)
            {
                if ($dono = PessoaContatoService::donoDoPrincipal($telefone, $pessoaId))
                {
                    throw new Exception(PessoaContatoService::mensagemTelefoneDeOutro($telefone, $dono));
                }
                PessoaContatoService::desmarcarPrincipais($pessoaId);
            }

            $nome = trim((string) $nome) !== '' ? mb_strtoupper(trim((string) $nome)) : ($principal ? mb_strtoupper(trim((string) $pessoa->nome)) : (PessoaContatoService::nomeDoTelefone($telefone) ?: null));
            $descricao = trim((string) $descricao) !== '' ? mb_strtoupper(trim((string) $descricao)) : null;

            // a pessoa ja tem esse numero: so completa esse contato
            $st = $conn->prepare("SELECT id FROM pessoa_contato WHERE pessoa_id = ? AND regexp_replace(COALESCE(telefone, ''), '\\D', '', 'g') IN (?, ?) ORDER BY id LIMIT 1");
            $st->execute([$pessoaId, $telefone, '55' . $telefone]);
            if ($id = $st->fetchColumn())
            {
                $conn->prepare("UPDATE pessoa_contato SET nome = COALESCE(?, nome), descricao = COALESCE(?, descricao), principal = CASE WHEN ? THEN 'S' ELSE principal END, updated_at = now() WHERE id = ?")
                     ->execute([$nome, $descricao, $principal ? 'true' : 'false', (int) $id]);
            }
            else
            {
                $conn->prepare("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, email, principal, created_at, updated_at) VALUES (?, ?, ?, ?, NULL, ?, now(), now())")
                     ->execute([$pessoaId, $nome, $descricao, $telefone, $principal ? 'S' : 'N']);
            }

            PessoaContatoService::aplicarPrincipal($pessoaId, $antes);
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            TTransaction::rollbackAll();
            throw $e;
        }

        return APChatContactService::sincronizarPessoa($pessoaId, [], ApchatContatoLog::ORIGEM_TELA);
    }

    /**
     * Troca o numero de um contato que ja existe pelo numero do APChat. No APChat o historico do numero antigo passa
     * para este contato e o antigo e excluido (APChatContactService::processarTrocas). Pedir a confirmacao antes
     * (PessoaContatoService::mensagemTrocaDeNumero).
     * @return object resultado do envio ao APChat
     */
    public static function trocarNumero(int $pessoaContatoId, string $numero): object
    {
        $telefone = PessoaContatoService::telefoneValido($numero);
        if (!$telefone) { throw new Exception(PessoaContatoService::MSG_TELEFONE_INVALIDO); }

        TTransaction::open(self::$database);
        try
        {
            $conn = TTransaction::get();
            $st = $conn->prepare('SELECT pessoa_id, telefone, principal FROM pessoa_contato WHERE id = ?');
            $st->execute([$pessoaContatoId]);
            $contato = $st->fetch(PDO::FETCH_ASSOC);
            if (!$contato) { throw new Exception('Contato não encontrado.'); }
            $pessoaId = (int) $contato['pessoa_id'];
            $antes = PessoaContatoService::estadoAtual($pessoaId);

            if ($contato['principal'] === 'S' && ($dono = PessoaContatoService::donoDoPrincipal($telefone, $pessoaId)))
            {
                throw new Exception(PessoaContatoService::mensagemTelefoneDeOutro($telefone, $dono));
            }

            $conn->prepare('UPDATE pessoa_contato SET telefone = ?, idapchat = NULL, updated_at = now() WHERE id = ?')->execute([$telefone, $pessoaContatoId]);
            PessoaContatoService::aplicarPrincipal($pessoaId, $antes);

            $mudancas = PessoaContatoService::trocasDeNumero($antes['contatos'], PessoaContatoService::numerosDosContatos($pessoaId));
            PessoaContatoService::limparIdApchat(array_column($mudancas['trocas'], 2));
            APChatContactService::registrarTrocas($pessoaId, $mudancas['trocas'], $mudancas['removidos']);
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            TTransaction::rollbackAll();
            throw $e;
        }

        return APChatContactService::sincronizarPessoa($pessoaId, [], ApchatContatoLog::ORIGEM_TELA);
    }

    /** Propaganda, engano: sai da lista sem cadastro. */
    public static function ignorar(int $contatoId, ?string $numero, ?string $nome): void
    {
        TTransaction::open(self::$database);
        TTransaction::get()->prepare('INSERT INTO apchat_contato_ignorado (contato_id, numero, nome, usuario_id) VALUES (?, ?, ?, ?) ON CONFLICT (contato_id) DO NOTHING')
            ->execute([$contatoId, $numero, $nome !== null ? mb_substr($nome, 0, 255) : null, TSession::getValue('userid') ?: null]);
        TTransaction::close();
    }

    public static function desfazerIgnorar(int $contatoId): void
    {
        TTransaction::open(self::$database);
        TTransaction::get()->prepare('DELETE FROM apchat_contato_ignorado WHERE contato_id = ?')->execute([$contatoId]);
        TTransaction::close();
    }

    /**
     * O que a Curciol tem para cruzar com o APChat: ids do APChat ja ligados, numeros (55 + DDD + numero, com a variante
     * do nono digito) e os ignorados.
     */
    public static function contextoCurciol(): array
    {
        TTransaction::open(self::$database);
        $conn = TTransaction::get();
        $ids = array_map('intval', $conn->query('SELECT DISTINCT idapchat FROM pessoa_contato WHERE idapchat IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN));
        $numeros = [];
        foreach ($conn->query("SELECT DISTINCT telefone FROM pessoa_contato WHERE COALESCE(telefone, '') <> ''")->fetchAll(PDO::FETCH_COLUMN) as $t)
        {
            $n = APChatContactService::normalizarTelefone($t);
            if ($n !== '') { foreach (APChatContactService::variantes($n) as $v) { $numeros[$v] = true; } }
        }
        $ignorados = array_map('intval', $conn->query('SELECT contato_id FROM apchat_contato_ignorado')->fetchAll(PDO::FETCH_COLUMN));
        TTransaction::close();

        return ['ids' => $ids, 'numeros' => array_keys($numeros), 'ignorados' => $ignorados];
    }

    /**
     * Cadastros da Curciol que ja tem este numero (ou este id do APChat): os vinculos ja feitos.
     * @return array linhas {pessoa_id, pessoa, contato_id, nome, descricao, principal}
     */
    public static function vinculos(int $contatoId, string $numero): array
    {
        $n = APChatContactService::normalizarTelefone($numero);
        $tels = [];
        if ($n !== '') { foreach (APChatContactService::variantes($n) as $v) { $tels[] = substr($v, 2); $tels[] = $v; } }
        TTransaction::open(self::$database);
        $sql = "SELECT p.id AS pessoa_id, p.nome AS pessoa, c.id AS contato_id, c.nome, c.descricao, c.principal
                  FROM pessoa_contato c JOIN pessoa p ON p.id = c.pessoa_id
                 WHERE c.idapchat = ?" . ($tels ? " OR regexp_replace(COALESCE(c.telefone, ''), '\\D', '', 'g') IN (" . implode(',', array_fill(0, count($tels), '?')) . ')' : '')
             . ' ORDER BY p.nome';
        $st = TTransaction::get()->prepare($sql);
        $st->execute(array_merge([$contatoId], $tels));
        $r = $st->fetchAll(PDO::FETCH_ASSOC);
        TTransaction::close();
        return $r;
    }

    /** O contato esta na lista de ignorados? */
    public static function ignorado(int $contatoId): bool
    {
        TTransaction::open(self::$database);
        $st = TTransaction::get()->prepare('SELECT 1 FROM apchat_contato_ignorado WHERE contato_id = ?');
        $st->execute([$contatoId]);
        $r = (bool) $st->fetchColumn();
        TTransaction::close();
        return $r;
    }
}
