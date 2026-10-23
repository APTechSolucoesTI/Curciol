<?php
// tests/portal/CpfSoNumerosTest.php
// CPF/CNPJ gravado so com numeros (Pessoa::onBeforeStore) e checagem de cadastro repetido pelos digitos
// (um CPF antigo gravado com pontuacao tambem e achado). Tudo numa transacao desfeita no fim.
require __DIR__ . '/../bootstrap.php';

TTransaction::open('escritorio');
try {
    $p = new Pessoa;
    $p->nome = 'TESTE CPF SO NUMEROS';
    $p->tipo_pessoa_id = TipoPessoa::FISICA;
    $p->cpf_cnpj = '529.982.247-25';
    $p->store();
    $gravado = TTransaction::get()->query('SELECT cpf_cnpj FROM pessoa WHERE id = ' . (int) $p->id)->fetchColumn();
    igual('gravado so com numeros', '52998224725', $gravado);

    // cadastro antigo com pontuacao (direto no banco, como os dados de antes da mudanca)
    TTransaction::get()->exec("UPDATE pessoa SET cpf_cnpj = '529.982.247-25' WHERE id = " . (int) $p->id);
    $achados = Pessoa::where("regexp_replace(COALESCE(cpf_cnpj, ''), '\\D', '', 'g')", '=', '52998224725')->where('id', '=', $p->id)->count();
    igual('checagem de repetido acha o CPF antigo com pontuacao', 1, $achados);

    // filtro das listas: digitando com ou sem pontuacao
    foreach (['529.982', '52998', '247-25'] as $busca) {
        $n = Pessoa::where("regexp_replace(COALESCE(cpf_cnpj, ''), '\\D', '', 'g')", 'ilike', '%' . preg_replace('/\D/', '', $busca) . '%')->where('id', '=', $p->id)->count();
        igual("filtro da lista acha digitando \"{$busca}\"", 1, $n);
    }

    // CPF vazio continua vazio (nao vira outra coisa)
    $p->cpf_cnpj = '';
    $p->store();
    igual('CPF vazio continua vazio', '', (string) TTransaction::get()->query('SELECT cpf_cnpj FROM pessoa WHERE id = ' . (int) $p->id)->fetchColumn());
} finally {
    TTransaction::rollback();
}
fim();
