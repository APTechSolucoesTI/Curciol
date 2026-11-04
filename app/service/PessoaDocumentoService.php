<?php
/**
 * Formato do RG nos formularios de pessoa (04/11/2026). O RG nao tem padrao nacional (cada estado tem o seu e a CIN usa o CPF),
 * entao vale a forma comum a todos: de 5 a 13 digitos e, no fim, um digito ou letra de controle. Pontuacao e espaco nao contam.
 * So pessoa fisica: na juridica o campo e a inscricao estadual. CPF e CNPJ: PessoaLoginService e os validadores dos formularios.
 */
class PessoaDocumentoService
{
    public static function rgValido($rg): bool
    {
        $limpo = preg_replace('/[\s.\-\/]/', '', (string) $rg);

        return preg_match('/^\d{5,13}[0-9A-Za-z]?$/', $limpo) === 1 && preg_match('/[1-9]/', $limpo) === 1;
    }

    /** RG preenchido de pessoa fisica precisa ter formato valido (vazio pode). */
    public static function exigirRgValido($rg, $tipoPessoa): void
    {
        if ((string) $tipoPessoa === (string) TipoPessoa::FISICA && trim((string) $rg) !== '' && !self::rgValido($rg))
        {
            throw new Exception('RG inválido: confira os números digitados.');
        }
    }
}
