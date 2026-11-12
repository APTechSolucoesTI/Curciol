<?php
/**
 * Formato do RG/CIN nos formularios de pessoa (04/11/2026; CIN 12/11/2026). O RG nao tem padrao nacional (cada estado tem o seu,
 * alguns com a sigla na frente, como MG-12.345.678) e a CIN (Carteira de Identidade Nacional) usa o numero do CPF (000.000.000-00).
 * Vale a forma comum a todos: sigla opcional de 1 ou 2 letras, de 5 a 13 digitos e, no fim, um digito ou letra de controle.
 * Pontuacao e espaco nao contam. So pessoa fisica: na juridica o campo e a inscricao estadual. CPF e CNPJ: PessoaLoginService e
 * os validadores dos formularios.
 */
class PessoaDocumentoService
{
    public static function rgValido($rg): bool
    {
        $limpo = preg_replace('/[\s.\-\/]/', '', (string) $rg);

        return preg_match('/^[A-Za-z]{0,2}\d{5,13}[0-9A-Za-z]?$/', $limpo) === 1 && preg_match('/[1-9]/', $limpo) === 1;
    }

    /** RG/CIN preenchido de pessoa fisica precisa ter formato valido (vazio pode). */
    public static function exigirRgValido($rg, $tipoPessoa): void
    {
        if ((string) $tipoPessoa === (string) TipoPessoa::FISICA && trim((string) $rg) !== '' && !self::rgValido($rg))
        {
            throw new Exception('RG/CIN inválido: confira os números digitados.');
        }
    }

    /**
     * Cadastro NOVO de cliente (12/11/2026): pessoa fisica salva com o CPF ou o RG/CIN (pelo menos um); juridica com o CNPJ.
     * Edicao de cadastro que ja existe nao passa por aqui (os antigos sem documento continuam salvando). O cadastro completo,
     * exigido para iniciar atendimento de agendamento, e AgendamentoService::pendenciasCadastro.
     */
    public static function exigirIdentificacaoNovo($id, $tipoPessoa, $cpfCnpj, $rg): void
    {
        if ((int) $id > 0) { return; }

        $temDocumento = preg_replace('/\D/', '', (string) $cpfCnpj) !== '';
        if ((string) $tipoPessoa === (string) TipoPessoa::FISICA && !$temDocumento && trim((string) $rg) === '')
        {
            throw new Exception('Informe o CPF ou o RG/CIN.');
        }
        if ((string) $tipoPessoa === (string) TipoPessoa::JURIDICA && !$temDocumento)
        {
            throw new Exception('Informe o CNPJ.');
        }
    }
}
