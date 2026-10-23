<?php
/**
 * Login do portal do cliente: o usuario e o proprio CPF/CNPJ (so numeros).
 * CPF/CNPJ valido -> usuario = os numeros; vazio ou invalido -> usuario vazio (sem login ate corrigir o cadastro).
 * A senha continua sendo gerada aleatoriamente no cadastro (12 caracteres, ver senhaAleatoria).
 */
class PessoaLoginService
{
    /** So os numeros. */
    public static function digitos($v): string
    {
        return preg_replace('/\D/', '', (string) $v);
    }

    /** CPF (11) ou CNPJ (14) com digitos verificadores corretos. */
    public static function documentoValido($v): bool
    {
        $d = self::digitos($v);
        if (strlen($d) === 11) { return self::cpfValido($d); }
        if (strlen($d) === 14) { return self::cnpjValido($d); }
        return false;
    }

    /**
     * Usuario padrao do portal: os numeros do CPF/CNPJ se validos para o tipo de pessoa (fisica = CPF, juridica = CNPJ;
     * sem tipo, vale qualquer um dos dois); senao null (vazio). Regra da publicacao em producao (23/10/2026).
     */
    public static function usuarioPadrao($cpfCnpj, $tipoPessoaId = null): ?string
    {
        if (!self::documentoValido($cpfCnpj)) { return null; }
        $d = self::digitos($cpfCnpj);
        $tipo = (string) ($tipoPessoaId ?? '');
        if ($tipo === (string) TipoPessoa::FISICA && strlen($d) !== 11) { return null; }
        if ($tipo === (string) TipoPessoa::JURIDICA && strlen($d) !== 14) { return null; }
        return $d;
    }

    /**
     * Login e senha do portal no cadastro (nao se editam a mao): login = CPF/CNPJ valido para o tipo; senha aleatoria
     * so quando ha login e a pessoa ainda nao tem senha. A senha que ja existe fica como esta (mesmo sem login).
     * $senhaGravada = a senha do banco (o que veio do formulario nao vale).
     */
    public static function aplicarLogin($object, ?string $senhaGravada): void
    {
        $object->usuario = self::usuarioPadrao($object->cpf_cnpj ?? null, $object->tipo_pessoa_id ?? null);
        $object->senha = $senhaGravada;
        if ($object->usuario !== null && trim((string) $object->senha) === '') {
            $object->senha = self::senhaAleatoria();
        }
    }

    /** Senha gravada no banco para a pessoa (null para cadastro novo). Usa a transacao aberta. */
    public static function senhaGravada($pessoaId): ?string
    {
        if (empty($pessoaId)) { return null; }
        $st = TTransaction::get()->prepare('SELECT senha FROM pessoa WHERE id = ?');
        $st->execute([(int) $pessoaId]);
        $s = $st->fetchColumn();
        return ($s === false || $s === null) ? null : (string) $s;
    }

    /** Recusa CPF/CNPJ preenchido e invalido (antes de salvar). Vazio passa: a pessoa fica sem login no portal. */
    public static function exigirDocumentoValido($cpfCnpj): void
    {
        $d = self::digitos($cpfCnpj);
        if ($d === '') { return; }
        if (strlen($d) !== 11 && strlen($d) !== 14) {
            throw new Exception('CPF/CNPJ inválido: informe 11 dígitos (CPF) ou 14 dígitos (CNPJ).');
        }
        if (!self::documentoValido($d)) {
            throw new Exception((strlen($d) === 11 ? 'CPF' : 'CNPJ') . ' inválido: confira os números digitados.');
        }
    }

    /**
     * Senha aleatoria do portal (22/10/2026): 12 caracteres com pelo menos uma letra minuscula, uma maiuscula, um numero
     * e um caractere especial. Ficam de fora os caracteres que se confundem na leitura (0/O/o, 1/l/I), os que o
     * WhatsApp usa como formatacao (* _ ~ `) e os que atrapalham em e-mail HTML (< > & aspas).
     */
    public static function senhaAleatoria(): string
    {
        $grupos = [
            'abcdefghijkmnpqrstuvwxyz',
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            '23456789',
            '!@#%+=?-',
        ];
        $todos = implode('', $grupos);
        $senha = [];
        foreach ($grupos as $g) {
            $senha[] = $g[random_int(0, strlen($g) - 1)];
        }
        while (count($senha) < 12) {
            $senha[] = $todos[random_int(0, strlen($todos) - 1)];
        }
        // embaralha (Fisher-Yates) para os obrigatorios nao ficarem sempre no inicio
        for ($i = count($senha) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$senha[$i], $senha[$j]] = [$senha[$j], $senha[$i]];
        }
        return implode('', $senha);
    }

    private static function cpfValido(string $d): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $d)) { return false; }
        for ($t = 9; $t < 11; $t++) {
            $s = 0;
            for ($i = 0; $i < $t; $i++) { $s += (int) $d[$i] * (($t + 1) - $i); }
            $dv = ((10 * $s) % 11) % 10;
            if ((int) $d[$t] !== $dv) { return false; }
        }
        return true;
    }

    private static function cnpjValido(string $d): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $d)) { return false; }
        foreach ([12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], 13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $pos => $pesos) {
            $s = 0;
            foreach ($pesos as $i => $p) { $s += (int) $d[$i] * $p; }
            $r = $s % 11;
            $dv = ($r < 2) ? 0 : 11 - $r;
            if ((int) $d[$pos] !== $dv) { return false; }
        }
        return true;
    }
}
