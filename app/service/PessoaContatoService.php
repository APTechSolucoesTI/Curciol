<?php
/**
 * Contato principal da pessoa (26/10/2026; regras revistas em 27/10/2026).
 *
 * O "Telefone principal" e o e-mail dos Dados cadastrais (pessoa.telefone / pessoa.email) sao o telefone e o e-mail do
 * contato principal (pessoa_contato.principal = 'S'), nos dois sentidos. O principal nao e obrigatorio; cada pessoa tem
 * no maximo um e o mesmo telefone so pode ser principal de uma pessoa (indices unicos no banco). O mesmo telefone pode
 * estar nos contatos de varias pessoas como nao principal. Telefone valido = a regra do APChat
 * (APChatContactService::normalizarTelefone), gravado sem o 55.
 *
 * Uso nas telas: estadoAtual() no comeco do salvar (antes de gravar), aplicarPrincipal() depois de gravar a pessoa e a
 * grade de contatos, dentro da mesma transacao. As excecoes trazem a mensagem para o usuario (a tela desfaz tudo);
 * aplicarPrincipal() devolve um aviso quando o telefone dos Dados nao pode ser principal.
 */
class PessoaContatoService
{
    const MSG_TELEFONE_INVALIDO = 'Telefone inválido. Informe o DDD e o número (celular com 11 dígitos, começando com 9).';
    const MSG_CONTATO_VAZIO = 'Informe o telefone ou o e-mail do contato.';
    const MSG_VARIOS_PRINCIPAIS = 'Selecione apenas um contato como principal!';
    const MSG_TELEFONE_DE_OUTRO = 'O telefone %s já é o contato principal de %s (código %d). Ele pode ser salvo aqui só como contato não principal.';
    const MSG_SAIDA_TELEFONE_DE_OUTRO = 'O telefone %s já é o contato principal de %s (código %d). Ao salvar, ele fica na aba Contatos como não principal e o Telefone principal fica vazio.';
    const MSG_AVISO_TELEFONE_DE_OUTRO ='O telefone %s já é o contato principal de %s (código %d): foi salvo na aba Contatos como não principal e o Telefone principal ficou vazio.';

    /**
     * Telefone so com DDD + numero (sem o 55) quando valido; null quando invalido ou vazio.
     */
    public static function telefoneValido($telefone): ?string
    {
        // o APChat devolve com o 55 do Brasil; na Curciol o telefone fica so com DDD + numero
        $normal = APChatContactService::normalizarTelefone($telefone);

        return $normal === '' ? null : substr($normal, 2);
    }

    /**
     * Telefone para mostrar no formulario: (19) 99876-5432 / (19) 3234-5678; fora disso, so os digitos.
     */
    public static function formatarTelefone($telefone): string
    {
        $d = self::telefoneValido($telefone) ?? self::digitos($telefone);
        if (strlen($d) === 11) { return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)); }
        if (strlen($d) === 10) { return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)); }
        return $d;
    }

    /**
     * Bloqueia telefone preenchido e invalido (vazio pode).
     */
    public static function exigirTelefoneValido($telefone, string $mensagem = self::MSG_TELEFONE_INVALIDO): void
    {
        if (self::digitos($telefone) !== '' && self::telefoneValido($telefone) === null)
        {
            throw new Exception($mensagem);
        }
    }

    /**
     * Pessoa (outra que nao $exceto) que ja tem esse telefone como contato principal: ['id' => , 'nome' => ] ou null.
     */
    public static function donoDoPrincipal($telefone, $exceto = null): ?array
    {
        $numero = self::telefoneValido($telefone);
        if ($numero === null)
        {
            return null;
        }

        $st = TTransaction::get()->prepare(
            "SELECT p.id, p.nome FROM pessoa_contato c JOIN pessoa p ON p.id = c.pessoa_id
              WHERE c.principal = 'S' AND regexp_replace(COALESCE(c.telefone, ''), '\D', '', 'g') IN (?, ?) AND c.pessoa_id <> ?
              LIMIT 1");
        $st->execute([$numero, '55' . $numero, (int) $exceto]);
        $dono = $st->fetch(PDO::FETCH_ASSOC);

        return $dono ? ['id' => (int) $dono['id'], 'nome' => (string) $dono['nome']] : null;
    }

    /**
     * Mensagem de "telefone ja e principal de outra pessoa" para a aba Contatos.
     */
    public static function mensagemTelefoneDeOutro($telefone, array $dono, string $formato = self::MSG_TELEFONE_DE_OUTRO): string
    {
        return sprintf($formato, self::formatarTelefone($telefone), $dono['nome'], $dono['id']);
    }

    /**
     * Nome para sugerir no contato: o da pessoa que tem esse telefone como principal (vazio se ninguem tem).
     */
    public static function nomeDoTelefone($telefone): string
    {
        $dono = self::donoDoPrincipal($telefone);
        return $dono['nome'] ?? '';
    }

    /**
     * Situacao gravada antes do salvar: telefone e e-mail dos Dados e o contato principal.
     */
    public static function estadoAtual($pessoaId): array
    {
        $estado = ['novo' => true, 'telefone' => '', 'email' => '', 'principal_id' => null, 'principal_telefone' => '', 'principal_email' => ''];
        $pessoaId = (int) $pessoaId;

        if ($pessoaId <= 0)
        {
            return $estado;
        }

        // trava a pessoa ate o fim do salvar: dois salvamentos ao mesmo tempo (ex.: duplo clique no Salvar) ficam um
        // depois do outro, em vez de os dois criarem o contato principal e o segundo bater no indice unico
        $conn = TTransaction::get();
        $st = $conn->prepare('SELECT telefone, email FROM pessoa WHERE id = ? FOR UPDATE');
        $st->execute([$pessoaId]);
        if ($pessoa = $st->fetch(PDO::FETCH_ASSOC))
        {
            $estado['novo'] = false;
            $estado['telefone'] = (string) $pessoa['telefone'];
            $estado['email'] = (string) $pessoa['email'];
        }

        $st = $conn->prepare("SELECT id, telefone, email FROM pessoa_contato WHERE pessoa_id = ? AND principal = 'S'");
        $st->execute([$pessoaId]);
        if ($principal = $st->fetch(PDO::FETCH_ASSOC))
        {
            $estado['principal_id'] = (int) $principal['id'];
            $estado['principal_telefone'] = (string) $principal['telefone'];
            $estado['principal_email'] = (string) $principal['email'];
        }

        return $estado;
    }

    /**
     * Antes de gravar a grade: tira a marca de principal de todos os contatos da pessoa, para a grade poder trocar o
     * principal sem bater no indice unico (cada linha e gravada separada). aplicarPrincipal() acerta no fim.
     */
    public static function desmarcarPrincipais($pessoaId): void
    {
        $st = TTransaction::get()->prepare("UPDATE pessoa_contato SET principal = 'N', updated_at = now() WHERE pessoa_id = ? AND principal = 'S'");
        $st->execute([(int) $pessoaId]);
    }

    /**
     * Depois de gravar a pessoa e os contatos: deixa os Dados cadastrais e o contato principal iguais.
     *
     * - mudou o principal na grade (outro contato, outro telefone ou e-mail): os Dados passam a ser os dele;
     * - mudou so o telefone/e-mail dos Dados: o contato principal recebe os novos (apagar os dois tira o principal);
     * - principal excluido/desmarcado na grade: o telefone e o e-mail dos Dados saem junto;
     * - sem principal e com telefone/e-mail nos Dados: vira principal o contato com os mesmos dados (o que so falta
     *   completar), ou e criado um com o nome da pessoa;
     * - telefone dos Dados ja e principal de outra pessoa: fica num contato nao principal, o Telefone principal fica
     *   vazio e volta o aviso para mostrar ao usuario.
     *
     * @param array $antes resultado de estadoAtual() lido antes de gravar
     * @return string|null aviso para o usuario
     */
    public static function aplicarPrincipal($pessoaId, array $antes): ?string
    {
        $pessoaId = (int) $pessoaId;
        $pessoa = self::pessoa($pessoaId);
        self::exigirTelefoneValido($pessoa['telefone']);
        $telefone = self::telefoneValido($pessoa['telefone']) ?? '';
        $email = self::email($pessoa['email']);
        $emailDados = trim((string) $pessoa['email']);

        $contatos = self::contatos($pessoaId);
        $principais = array_values(array_filter($contatos, function ($c) { return $c['principal'] === 'S'; }));
        if (count($principais) > 1)
        {
            throw new Exception(self::MSG_VARIOS_PRINCIPAIS);
        }
        $principal = $principais[0] ?? null;

        $dadosMudou = self::chave($pessoa['telefone']) !== self::chave($antes['telefone'] ?? '')
            || $email !== self::email($antes['email'] ?? '');

        if ($principal)
        {
            $gradeMudou = (int) $principal['id'] !== (int) ($antes['principal_id'] ?? 0)
                || self::chave($principal['telefone']) !== self::chave($antes['principal_telefone'] ?? '')
                || self::email($principal['email']) !== self::email($antes['principal_email'] ?? '');

            if ($gradeMudou || !$dadosMudou)
            {
                // a escolha da grade vale (tambem quando os dois mudaram): os Dados ficam iguais ao principal
                self::exigirPrincipalValido($principal, $pessoaId);
                self::acertarPrincipal($principal, $pessoa);
                self::gravarDados($pessoaId, self::telefoneValido($principal['telefone']), trim((string) $principal['email']) ?: null);
                return null;
            }

            // mudou so nos Dados: o contato principal acompanha
            if ($telefone === '' && $email === '')
            {
                self::marcar((int) $principal['id'], 'N');
                return null;
            }
            if ($telefone === '' || !self::donoDoPrincipal($telefone, $pessoaId))
            {
                self::atualizarContato((int) $principal['id'], ['telefone' => $telefone ?: null, 'email' => $emailDados ?: null]);
                self::acertarPrincipal(['id' => $principal['id'], 'nome' => $principal['nome']], $pessoa);
                return null;
            }

            // o novo telefone e principal de outra pessoa: o principal antigo fica como contato nao principal
            self::marcar((int) $principal['id'], 'N');
            $contatos = self::contatos($pessoaId);
        }
        elseif (!empty($antes['principal_id']) && !$dadosMudou)
        {
            // o principal saiu da grade (excluido ou desmarcado) e os Dados nao foram mexidos: os Dados saem junto
            self::gravarDados($pessoaId, null, null);
            return null;
        }

        return self::principalPelosDados($pessoaId, $pessoa, $telefone, $emailDados, $contatos);
    }

    /**
     * Telefone e e-mail dos Dados cadastrais depois do salvar (a tela devolve ao formulario).
     */
    public static function dadosAtuais($pessoaId): array
    {
        $pessoa = self::pessoa((int) $pessoaId);

        return ['telefone' => (string) $pessoa['telefone'], 'email' => (string) $pessoa['email']];
    }

    /**
     * Contatos que vem dos representantes legais da pessoa (cadastro de Cliente): para cada representante com contato
     * principal, o contato da pessoa com o mesmo telefone ou e-mail passa a ser dele (nome do representante, nao
     * principal), ou e criado um (descricao = a do vinculo). Representante sem contato principal nao gera contato.
     * Os contatos de representantes desvinculados saem.
     */
    public static function sincronizarRepresentantes($pessoaId): void
    {
        $pessoaId = (int) $pessoaId;
        $conn = TTransaction::get();

        $st = $conn->prepare(
            "SELECT r.representante_id, r.descricao, p.nome, c.telefone, c.email
               FROM pessoa_representantes_legais r
               JOIN pessoa p ON p.id = r.representante_id
               LEFT JOIN pessoa_contato c ON c.pessoa_id = r.representante_id AND c.principal = 'S'
              WHERE r.pessoa_juridica_id = ? AND r.representante_id IS NOT NULL
              ORDER BY r.id");
        $st->execute([$pessoaId]);
        $representantes = $st->fetchAll(PDO::FETCH_ASSOC);

        $vinculados = [];
        foreach ($representantes as $rep)
        {
            $repId = (int) $rep['representante_id'];
            $vinculados[] = $repId;
            $repTelefone = self::telefoneValido($rep['telefone']) ?? '';
            $repEmail = self::email($rep['email']);
            if ($repTelefone === '' && $repEmail === '')
            {
                continue;
            }

            $contato = null;
            foreach (self::contatos($pessoaId) as $c)
            {
                if ((int) $c['representante_id'] === $repId)
                {
                    $contato = $c;
                    break;
                }
                if ($c['principal'] !== 'S' && empty($c['representante_id'])
                    && (($repTelefone !== '' && self::telefoneValido($c['telefone']) === $repTelefone) || ($repEmail !== '' && self::email($c['email']) === $repEmail)))
                {
                    $contato = $contato ?? $c;
                }
            }

            $descricao = trim((string) $rep['descricao']) !== '' ? trim((string) $rep['descricao']) : null;
            if ($contato)
            {
                self::atualizarContato((int) $contato['id'], [
                    'nome' => $rep['nome'],
                    'representante_id' => $repId,
                    'telefone' => $repTelefone !== '' ? $repTelefone : $contato['telefone'],
                    'email' => $repEmail !== '' ? $repEmail : $contato['email'],
                    'descricao' => trim((string) $contato['descricao']) !== '' ? $contato['descricao'] : $descricao,
                ]);
            }
            else
            {
                $conn->prepare("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, email, principal, representante_id, created_at, updated_at)
                                VALUES (?, ?, ?, ?, ?, 'N', ?, now(), now())")
                     ->execute([$pessoaId, $rep['nome'], $descricao, $repTelefone ?: null, $repEmail ?: null, $repId]);
            }
        }

        // representante desvinculado: o contato que veio dele sai
        $sql = "DELETE FROM pessoa_contato WHERE pessoa_id = ? AND representante_id IS NOT NULL AND principal = 'N'";
        $args = [$pessoaId];
        if ($vinculados)
        {
            $sql .= ' AND representante_id NOT IN (' . implode(',', array_fill(0, count($vinculados), '?')) . ')';
            $args = array_merge($args, $vinculados);
        }
        $conn->prepare($sql)->execute($args);
    }

    /**
     * Sem principal: o telefone/e-mail dos Dados viram o principal (contato com os mesmos dados ou um novo).
     */
    private static function principalPelosDados(int $pessoaId, array $pessoa, string $telefone, string $email, array $contatos): ?string
    {
        // $email: como esta nos Dados (gravado assim); a comparacao com os contatos e sem diferenca de maiusculas
        if ($telefone === '' && $email === '')
        {
            return null;
        }

        if ($telefone !== '' && ($dono = self::donoDoPrincipal($telefone, $pessoaId)))
        {
            // o numero ja e principal de outra pessoa: fica so como contato nao principal desta, com o nome do dono;
            // com e-mail nos Dados, o principal fica so com o e-mail
            $doTelefone = array_filter($contatos, function ($c) use ($telefone) { return self::telefoneValido($c['telefone']) === $telefone; });
            if (!$doTelefone)
            {
                self::inserir($pessoaId, $dono['nome'], $telefone, '', 'N');
            }
            foreach ($doTelefone as $c)
            {
                if (trim((string) $c['nome']) === '')
                {
                    self::atualizarContato((int) $c['id'], ['nome' => $dono['nome']]);
                }
            }
            if ($email !== '')
            {
                self::principalPelosDados($pessoaId, $pessoa, '', $email, self::contatos($pessoaId));
            }
            else
            {
                self::gravarDados($pessoaId, null, null);
            }
            return self::mensagemTelefoneDeOutro($telefone, $dono, self::MSG_AVISO_TELEFONE_DE_OUTRO);
        }

        $escolhido = self::contatoCompativel($contatos, $telefone, self::email($email));
        if ($escolhido)
        {
            self::atualizarContato((int) $escolhido['id'], ['telefone' => $telefone ?: $escolhido['telefone'], 'email' => $email ?: $escolhido['email']]);
            self::marcar((int) $escolhido['id'], 'S');
            self::acertarPrincipal(['id' => $escolhido['id'], 'nome' => $escolhido['nome']], $pessoa);
        }
        else
        {
            self::inserir($pessoaId, $pessoa['nome'], $telefone, $email, 'S');
        }

        self::gravarDados($pessoaId, $telefone ?: null, $email ?: null);
        return null;
    }

    /**
     * Contato que pode virar o principal dos Dados: tem o telefone ou o e-mail dos Dados e nada diferente deles (so
     * falta completar). Prefere o que tem os dois, depois o do telefone, depois o mais antigo.
     */
    private static function contatoCompativel(array $contatos, string $telefone, string $email): ?array
    {
        $candidatos = [];
        foreach ($contatos as $c)
        {
            if (!empty($c['representante_id']))
            {
                continue;
            }
            $ct = self::telefoneValido($c['telefone']) ?? self::digitos($c['telefone']);
            $ce = self::email($c['email']);
            $telOk = $ct === '' || ($telefone !== '' && $ct === $telefone);
            $mailOk = $ce === '' || ($email !== '' && $ce === $email);
            $bateTel = $telefone !== '' && $ct === $telefone;
            $bateMail = $email !== '' && $ce === $email;
            if ($telOk && $mailOk && ($bateTel || $bateMail))
            {
                $candidatos[] = [($bateTel ? 0 : 1) + ($bateMail ? 0 : 2), (int) $c['id'], $c];
            }
        }
        if (!$candidatos)
        {
            return null;
        }
        usort($candidatos, function ($a, $b) { return [$a[0], $a[1]] <=> [$b[0], $b[1]]; });

        return $candidatos[0][2];
    }

    private static function exigirPrincipalValido(array $principal, int $pessoaId): void
    {
        if (self::digitos($principal['telefone']) === '' && self::email($principal['email']) === '')
        {
            throw new Exception(self::MSG_CONTATO_VAZIO);
        }
        self::exigirTelefoneValido($principal['telefone']);
        if (self::digitos($principal['telefone']) !== '' && ($dono = self::donoDoPrincipal($principal['telefone'], $pessoaId)))
        {
            throw new Exception(self::mensagemTelefoneDeOutro($principal['telefone'], $dono));
        }
    }

    /** principal gravado com o telefone so com DDD + numero e, sem nome, com o nome da pessoa */
    private static function acertarPrincipal(array $principal, array $pessoa): void
    {
        $campos = [];
        if (isset($principal['telefone']) && ($normal = self::telefoneValido($principal['telefone'])) !== null && $normal !== $principal['telefone'])
        {
            $campos['telefone'] = $normal;
        }
        if (trim((string) ($principal['nome'] ?? '')) === '')
        {
            $campos['nome'] = $pessoa['nome'];
        }
        if ($campos)
        {
            self::atualizarContato((int) $principal['id'], $campos);
        }
    }

    private static function pessoa(int $pessoaId): array
    {
        $st = TTransaction::get()->prepare('SELECT nome, telefone, email FROM pessoa WHERE id = ?');
        $st->execute([$pessoaId]);

        return $st->fetch(PDO::FETCH_ASSOC) ?: ['nome' => '', 'telefone' => '', 'email' => ''];
    }

    private static function contatos(int $pessoaId): array
    {
        $st = TTransaction::get()->prepare('SELECT id, nome, descricao, telefone, email, principal, representante_id FROM pessoa_contato WHERE pessoa_id = ? ORDER BY id');
        $st->execute([$pessoaId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function inserir(int $pessoaId, ?string $nome, string $telefone, string $email, string $principal): void
    {
        TTransaction::get()->prepare("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, email, principal, created_at, updated_at) VALUES (?, ?, NULL, ?, ?, ?, now(), now())")
            ->execute([$pessoaId, $nome, $telefone ?: null, $email ?: null, $principal]);
    }

    private static function marcar(int $contatoId, string $principal): void
    {
        TTransaction::get()->prepare('UPDATE pessoa_contato SET principal = ?, updated_at = now() WHERE id = ?')->execute([$principal, $contatoId]);
    }

    private static function atualizarContato(int $contatoId, array $campos): void
    {
        $sets = [];
        foreach (array_keys($campos) as $campo)
        {
            $sets[] = "$campo = ?";
        }
        TTransaction::get()->prepare('UPDATE pessoa_contato SET ' . implode(', ', $sets) . ', updated_at = now() WHERE id = ?')
            ->execute(array_merge(array_values($campos), [$contatoId]));
    }

    private static function gravarDados(int $pessoaId, ?string $telefone, ?string $email): void
    {
        TTransaction::get()->prepare('UPDATE pessoa SET telefone = ?, email = ? WHERE id = ? AND (telefone IS DISTINCT FROM ? OR email IS DISTINCT FROM ?)')
            ->execute([$telefone, $email, $pessoaId, $telefone, $email]);
    }

    private static function digitos($telefone): string
    {
        return preg_replace('/\D/', '', (string) $telefone);
    }

    private static function email($email): string
    {
        return mb_strtolower(trim((string) $email));
    }

    /** chave de comparacao: o numero normalizado quando valido, senao os digitos */
    private static function chave($telefone): string
    {
        return self::telefoneValido($telefone) ?? self::digitos($telefone);
    }
}
