<?php
// tests/email/EstruturaEmailTest.php
// Assinatura e anexos do e-mail (09/11/2026): tabelas, colunas, padroes e a pasta publica das imagens. Nao grava no banco.
require __DIR__ . '/../bootstrap.php';

TTransaction::open('escritorio');
$c = TTransaction::get();

$colunas = [
    'email_assinatura'          => ['id', 'nome_original', 'tipo', 'html', 'criado_em', 'system_user_id'],
    'email_anexo'               => ['id', 'nome_original', 'caminho', 'tamanho', 'criado_em', 'system_user_id'],
    'template_escritorio_anexo' => ['id', 'template_escritorio_id', 'email_anexo_id', 'ordem'],
    'mensagem_anexo'            => ['id', 'mensagem_id', 'email_anexo_id'],
    'mensagem_agendada_anexo'   => ['id', 'mensagem_agendada_id', 'email_anexo_id'],
    'email_config'              => ['assinatura_id'],
    'template_escritorio'       => ['assinatura_modo', 'assinatura_id', 'botao_confirmar_texto', 'botao_confirmar_cor', 'botao_cancelar_texto', 'botao_cancelar_cor'],
    'mensagem'                  => ['assinatura_id'],
    'mensagem_agendada'         => ['assinatura_id'],
];

$st = $c->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?");
foreach ($colunas as $tabela => $lista)
{
    foreach ($lista as $coluna)
    {
        $st->execute([$tabela, $coluna]);
        ok("coluna {$tabela}.{$coluna}", (bool) $st->fetchColumn());
    }
}

// template novo nasce com a assinatura padrao do escritorio e os botoes padrao
try
{
    $c->exec('SAVEPOINT estrutura');
    $esc = (int) $c->query('SELECT id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn();
    $id = (int) $c->query("INSERT INTO template_escritorio (escritorio_id, chave, descricao, habilitado, template, titulo, tipo_template, categoria)
                           VALUES ({$esc}, 'TESTE_ESTRUTURA', 'TESTE ESTRUTURA', 'T', 'x', 'x', 'EMAIL', 'CONFIRMACAO') RETURNING id")->fetchColumn();
    $t = $c->query("SELECT * FROM template_escritorio WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
    igual('template novo nasce com PADRAO', 'PADRAO', $t['assinatura_modo']);
    igual('texto padrao do botao confirmar', 'Confirmar presença', $t['botao_confirmar_texto']);
    igual('cor padrao do botao confirmar', '#2e7d32', $t['botao_confirmar_cor']);
    igual('texto padrao do botao cancelar', 'Cancelar agendamento', $t['botao_cancelar_texto']);
    igual('cor padrao do botao cancelar', '#c62828', $t['botao_cancelar_cor']);
    lanca('modo de assinatura invalido recusado', fn() => $c->exec("UPDATE template_escritorio SET assinatura_modo = 'X' WHERE id = {$id}"), 'check');
}
catch (Throwable $e)
{
    ok('insert de template', false, $e->getMessage());
}
TTransaction::rollback();

igual('constante PADRAO', 'PADRAO', defined('TemplateEscritorio::ASSINATURA_PADRAO') ? TemplateEscritorio::ASSINATURA_PADRAO : null);
igual('constante OUTRA', 'OUTRA', defined('TemplateEscritorio::ASSINATURA_OUTRA') ? TemplateEscritorio::ASSINATURA_OUTRA : null);
igual('constante NENHUMA', 'NENHUMA', defined('TemplateEscritorio::ASSINATURA_NENHUMA') ? TemplateEscritorio::ASSINATURA_NENHUMA : null);
foreach (['EmailAssinatura', 'EmailAnexo', 'TemplateEscritorioAnexo', 'MensagemAnexo', 'MensagemAgendadaAnexo'] as $modelo)
{
    ok("modelo {$modelo}", class_exists($modelo));
}

ok('pasta publica existe e e gravavel', is_dir('assinaturas') && is_writable('assinaturas'));
ok('pasta publica tem .htaccess', is_file('assinaturas/.htaccess'));

fim();
