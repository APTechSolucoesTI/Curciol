<?php
// Consulta do CNPJ e preenchimento da pessoa. HTTP simulado, sem salvar cadastro.
require __DIR__ . '/../bootstrap.php';

$chamadas = [];
$resposta = ['status' => 200, 'corpo' => json_encode([
    'cnpj' => '00000000000191', 'razao_social' => 'EMPRESA DE TESTE SA', 'ddd_telefone_1' => '6134939002',
    'email' => ' CONTATO@EXEMPLO.INVALID ', 'data_inicio_atividade' => '1966-08-01',
    'cep' => '70040912', 'logradouro' => 'QUADRA DE TESTE', 'numero' => 'SN', 'bairro' => 'ASA NORTE',
    'complemento' => 'TORRE I', 'municipio' => 'BRASILIA', 'uf' => 'DF', 'codigo_municipio_ibge' => 5300108,
])];
BuilderCNPJService::usarTransporte(function (string $url) use (&$chamadas, &$resposta) {
    $chamadas[] = $url;
    return $resposta;
});
try
{
    $dados = BuilderCNPJService::get('00.000.000/0001-91');
    igual('BrasilAPI: uma chamada com CNPJ sem máscara e sem token', ['https://brasilapi.com.br/api/cnpj/v1/00000000000191'], $chamadas);
    igual('resposta mantém CNPJ e razão social', ['00000000000191', 'EMPRESA DE TESTE SA'], [$dados->cnpj, $dados->razao_social]);
    $form = ModalBuscarCNPJ::dadosFormulario($dados);
    igual('formulário: nome, CNPJ e abertura em pt-BR', ['EMPRESA DE TESTE SA', '00000000000191', '01/08/1966'], [$form->nome, $form->cpf_cnpj, $form->dt_nascimento_abertura]);
    igual('formulário: telefone e e-mail', ['6134939002', 'contato@exemplo.invalid'], [$form->telefone, $form->email]);
    igual('formulário: endereço da empresa', ['70040912', 'QUADRA DE TESTE', 'SN', 'ASA NORTE', 'TORRE I'], [
        $form->pessoa_endereco_pessoa_cep, $form->pessoa_endereco_pessoa_rua, $form->pessoa_endereco_pessoa_numero,
        $form->pessoa_endereco_pessoa_bairro, $form->pessoa_endereco_pessoa_complemento,
    ]);
    ok('inscrição estadual não é apagada pela busca', !property_exists($form, 'rg_ie'));
    $dados->email = null;
    $dados->ddd_telefone_1 = '';
    $dados->ddd_telefone_2 = '11987654321';
    $dados->data_inicio_atividade = '2026-02-30';
    $form = ModalBuscarCNPJ::dadosFormulario($dados);
    ok('e-mail ausente e data inválida preservam os campos digitados', !property_exists($form, 'email') && !property_exists($form, 'dt_nascimento_abertura'));
    igual('telefone secundário quando o primeiro não veio', '11987654321', $form->telefone);
    $dados->ddd_telefone_2 = null;
    ok('telefone ausente preserva o campo digitado', !property_exists(ModalBuscarCNPJ::dadosFormulario($dados), 'telefone'));
    $dados->cidade_id = 53;
    igual('cidade resolvida é enviada ao campo da pessoa', 53, ModalBuscarCNPJ::dadosFormulario($dados)->pessoa_endereco_pessoa_cidade_id);
    $antes = count($chamadas);
    foreach (['', '00000000000000', '00000000000190', '52998224725', '00000000000191<script>', ['cnpj']] as $invalido)
    {
        lanca('CNPJ inválido é recusado antes de consultar', function () use ($invalido) { BuilderCNPJService::get($invalido); }, 'CNPJ inválido');
    }
    igual('entrada inválida não gera HTTP', $antes, count($chamadas));
    foreach ([400 => 'CNPJ inválido', 404 => 'não encontrado', 429 => 'Limite', 503 => 'indisponível', 0 => 'indisponível'] as $status => $mensagem)
    {
        $resposta = ['status' => $status, 'corpo' => '{"message":"Detalhe técnico do provedor"}'];
        lanca('HTTP ' . $status . ': mensagem compreensível', function () { BuilderCNPJService::get('00000000000191'); }, $mensagem);
    }
    foreach (['<html>fora</html>', 'null', '{"cnpj":"00000000000191"}', '{"cnpj":"11222333000181","razao_social":"OUTRA EMPRESA"}'] as $corpo)
    {
        $resposta = ['status' => 200, 'corpo' => $corpo];
        lanca('resposta incompleta ou de outra empresa é recusada', function () { BuilderCNPJService::get('00000000000191'); }, 'resposta');
    }
}
finally
{
    BuilderCNPJService::usarTransporte(null);
}
fim();
