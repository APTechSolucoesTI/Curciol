<?php
/**
 * Contato principal da pessoa (26/10/2026).
 *
 * O telefone dos Dados cadastrais (pessoa.telefone) e o telefone do contato principal (pessoa_contato.principal = 'S')
 * sao sempre o mesmo, nos dois sentidos. Cada pessoa tem no maximo um principal (indice unico no banco) e, se tem
 * contato com telefone valido, tem de ter um. Telefone valido = a regra do APChat (APChatContactService::normalizarTelefone),
 * gravado sem o 55.
 *
 * Uso nas telas: estadoAtual() no comeco do salvar (antes de gravar), aplicarPrincipal() depois de gravar a pessoa e a
 * grade de contatos, dentro da mesma transacao. As excecoes trazem a mensagem para o usuario (a tela desfaz tudo).
 */
class PessoaContatoService
{
    const MSG_TELEFONE_INVALIDO = 'Telefone inválido. Informe o DDD e o número (celular com 11 dígitos, começando com 9).';
    const MSG_PRINCIPAL_INVALIDO = 'O contato principal precisa ter um telefone válido (DDD e número; celular com 11 dígitos, começando com 9).';
    const MSG_VARIOS_PRINCIPAIS = 'Selecione apenas um contato como principal!';
    const MSG_SEM_PRINCIPAL = 'Selecione um contato como principal na aba Contatos!';
    const MSG_APAGAR_TELEFONE = 'Para tirar o telefone principal, exclua o contato principal na aba Contatos ou marque outro contato como principal.';

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
     * Situacao gravada antes do salvar: telefone dos Dados e o contato principal (id e telefone).
     */
    public static function estadoAtual($pessoaId): array
    {
        $estado = ['telefone' => '', 'principal_id' => null, 'principal_telefone' => ''];
        $pessoaId = (int) $pessoaId;

        if ($pessoaId <= 0)
        {
            return $estado;
        }

        $conn = TTransaction::get();
        $st = $conn->prepare('SELECT telefone FROM pessoa WHERE id = ?');
        $st->execute([$pessoaId]);
        $estado['telefone'] = (string) $st->fetchColumn();

        $st = $conn->prepare("SELECT id, telefone FROM pessoa_contato WHERE pessoa_id = ? AND principal = 'S'");
        $st->execute([$pessoaId]);
        if ($principal = $st->fetch(PDO::FETCH_ASSOC))
        {
            $estado['principal_id'] = (int) $principal['id'];
            $estado['principal_telefone'] = (string) $principal['telefone'];
        }

        return $estado;
    }

    /**
     * Antes de gravar a grade: tira a marca de principal de todos os contatos da pessoa, para a grade poder trocar o
     * principal sem bater no indice unico (cada linha e gravada separada). aplicarPrincipal() acerta no fim.
     */
    public static function desmarcarPrincipais($pessoaId): void
    {
        $st = TTransaction::get()->prepare("UPDATE pessoa_contato SET principal = 'N' WHERE pessoa_id = ? AND principal = 'S'");
        $st->execute([(int) $pessoaId]);
    }

    /**
     * Depois de gravar a pessoa e os contatos: deixa o telefone dos Dados e o contato principal iguais.
     *
     * - mudou o principal na grade (outro contato ou outro telefone): o telefone dos Dados passa a ser o dele;
     * - mudou so o telefone dos Dados: o contato principal recebe o novo telefone (apagar o telefone bloqueia);
     * - sem principal e com telefone nos Dados: vira principal o contato com o mesmo numero (o mais antigo sem
     *   descricao), ou e criado um;
     * - principal excluido/desmarcado na grade: o telefone dos Dados sai junto;
     * - sobrou contato com telefone valido e nenhum principal: bloqueia.
     *
     * @param array $antes resultado de estadoAtual() lido antes de gravar
     */
    public static function aplicarPrincipal($pessoaId, array $antes): void
    {
        $pessoaId = (int) $pessoaId;
        $conn = TTransaction::get();

        $st = $conn->prepare('SELECT telefone FROM pessoa WHERE id = ?');
        $st->execute([$pessoaId]);
        $telefoneDados = (string) $st->fetchColumn();
        self::exigirTelefoneValido($telefoneDados);
        $telefone = self::telefoneValido($telefoneDados) ?? '';

        $st = $conn->prepare('SELECT id, descricao, telefone, principal FROM pessoa_contato WHERE pessoa_id = ? ORDER BY id');
        $st->execute([$pessoaId]);
        $contatos = $st->fetchAll(PDO::FETCH_ASSOC);

        $principais = array_values(array_filter($contatos, function ($c) { return $c['principal'] === 'S'; }));
        if (count($principais) > 1)
        {
            throw new Exception(self::MSG_VARIOS_PRINCIPAIS);
        }

        $telefoneMudou = self::chave($telefoneDados) !== self::chave($antes['telefone'] ?? '');
        $principal = $principais[0] ?? null;

        if ($principal)
        {
            $telefonePrincipal = self::telefoneValido($principal['telefone']);
            if ($telefonePrincipal === null)
            {
                throw new Exception(self::MSG_PRINCIPAL_INVALIDO);
            }

            $gradeMudou = (int) $principal['id'] !== (int) ($antes['principal_id'] ?? 0)
                || $telefonePrincipal !== self::telefoneValido($antes['principal_telefone'] ?? '');

            if (!$gradeMudou && $telefoneMudou)
            {
                if ($telefone === '')
                {
                    throw new Exception(self::MSG_APAGAR_TELEFONE);
                }
                self::gravarTelefoneContato((int) $principal['id'], $telefone);
            }
            elseif ($telefonePrincipal !== $telefone || $telefoneDados !== $telefonePrincipal)
            {
                // a escolha da grade vale (tambem quando os dois mudaram)
                self::gravarTelefonePessoa($pessoaId, $telefonePrincipal);
            }
            return;
        }

        // sem principal: se ele existia e o telefone dos Dados nao foi mexido, saiu da grade e leva o telefone junto
        if (!empty($antes['principal_id']) && !$telefoneMudou && $telefone !== '')
        {
            self::gravarTelefonePessoa($pessoaId, null);
            $telefone = '';
        }

        if ($telefone !== '')
        {
            $iguais = array_values(array_filter($contatos, function ($c) use ($telefone) { return self::telefoneValido($c['telefone']) === $telefone; }));
            usort($iguais, function ($a, $b) { return [trim((string) $a['descricao']) !== '', (int) $a['id']] <=> [trim((string) $b['descricao']) !== '', (int) $b['id']]; });

            if ($iguais)
            {
                $conn->prepare("UPDATE pessoa_contato SET principal = 'S' WHERE id = ?")->execute([(int) $iguais[0]['id']]);
            }
            else
            {
                $conn->prepare("INSERT INTO pessoa_contato (pessoa_id, descricao, telefone, principal) VALUES (?, '', ?, 'S')")->execute([$pessoaId, $telefone]);
            }

            if ($telefoneDados !== $telefone)
            {
                self::gravarTelefonePessoa($pessoaId, $telefone);
            }
            return;
        }

        foreach ($contatos as $contato)
        {
            if (self::telefoneValido($contato['telefone']) !== null)
            {
                throw new Exception(self::MSG_SEM_PRINCIPAL);
            }
        }
    }

    /**
     * Telefone dos Dados cadastrais depois do salvar (a tela devolve ao formulario quando a grade mandou).
     */
    public static function telefoneDados($pessoaId): string
    {
        $st = TTransaction::get()->prepare('SELECT telefone FROM pessoa WHERE id = ?');
        $st->execute([(int) $pessoaId]);

        return (string) $st->fetchColumn();
    }

    private static function gravarTelefonePessoa(int $pessoaId, ?string $telefone): void
    {
        TTransaction::get()->prepare('UPDATE pessoa SET telefone = ? WHERE id = ?')->execute([$telefone, $pessoaId]);
    }

    private static function gravarTelefoneContato(int $contatoId, string $telefone): void
    {
        TTransaction::get()->prepare('UPDATE pessoa_contato SET telefone = ? WHERE id = ?')->execute([$telefone, $contatoId]);
    }

    private static function digitos($telefone): string
    {
        return preg_replace('/\D/', '', (string) $telefone);
    }

    /** chave de comparacao: o numero normalizado quando valido, senao os digitos */
    private static function chave($telefone): string
    {
        return self::telefoneValido($telefone) ?? self::digitos($telefone);
    }
}
