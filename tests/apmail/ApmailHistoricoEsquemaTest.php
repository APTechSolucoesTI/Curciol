<?php
// tests/apmail/ApmailHistoricoEsquemaTest.php
// Conversas do APMail no historico do cliente (12/11/2026): tabelas, usuario de integracao, situacao da ligacao dos usuarios
// e permissao da tela da conversa (mesmos grupos e usuarios do cadastro do cliente).
require __DIR__ . '/../bootstrap.php';

function qe(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }

$tabelas = array_column(qe("SELECT table_name FROM information_schema.tables WHERE table_name IN ('apmail_conversa','apmail_mensagem','apmail_conversa_email','apmail_sincronizacao')"), 'table_name');
sort($tabelas);
igual('as 4 tabelas existem', ['apmail_conversa', 'apmail_conversa_email', 'apmail_mensagem', 'apmail_sincronizacao'], $tabelas);

$unicos = array_column(qe("SELECT tc.table_name || '.' || string_agg(kcu.column_name, ',' ORDER BY kcu.ordinal_position) AS c
                             FROM information_schema.table_constraints tc
                             JOIN information_schema.key_column_usage kcu ON kcu.constraint_name = tc.constraint_name AND kcu.table_name = tc.table_name
                            WHERE tc.constraint_type = 'UNIQUE' AND tc.table_name IN ('apmail_conversa','apmail_mensagem','apmail_conversa_email','apmail_sincronizacao')
                            GROUP BY tc.table_name, tc.constraint_name"), 'c');
ok('apmail_conversa.thread_id unico', in_array('apmail_conversa.thread_id', $unicos, true), json_encode($unicos));
ok('apmail_mensagem.apmail_id unico', in_array('apmail_mensagem.apmail_id', $unicos, true));
ok('apmail_conversa_email (conversa, e-mail) unico', in_array('apmail_conversa_email.conversa_id,email', $unicos, true));
ok('apmail_sincronizacao.chave unica', in_array('apmail_sincronizacao.chave', $unicos, true));
ok('indice por e-mail na apmail_conversa_email', (bool) qe("SELECT 1 FROM pg_indexes WHERE tablename = 'apmail_conversa_email' AND indexdef ILIKE '%(email)%'"));

$cols = function (string $t) { return array_column(qe("SELECT column_name FROM information_schema.columns WHERE table_name = '{$t}'"), 'column_name'); };
ok('escritorio.apmail_email_integracao', in_array('apmail_email_integracao', $cols('escritorio'), true));
ok('apmail_usuario.situacao e conferido_em', !array_diff(['situacao', 'conferido_em'], $cols('apmail_usuario')));
ok('apmail_mensagem com corpo, participantes e quem enviou', !array_diff(['conversa_id', 'direcao', 'assunto', 'de_nome', 'de_email', 'para', 'cc', 'mensagem_em',
   'enviado_por_nome', 'enviado_por_email', 'corpo_html', 'corpo_texto', 'qtd_anexos', 'qtd_imagens_corpo', 'tem_convite'], $cols('apmail_mensagem')));
ok('apmail_conversa com caixa, periodo e responsavel', !array_diff(['thread_id', 'assunto', 'caixa_id', 'caixa_nome', 'caixa_email', 'inicio_em', 'fim_em',
   'responsavel_nome', 'responsavel_email', 'situacao', 'qtd_mensagens'], $cols('apmail_conversa')));

$perm = qe("SELECT (SELECT count(*) FROM system_program WHERE controller = 'ApmailConversaView') programa,
                   (SELECT count(*) FROM system_group_program g JOIN system_program p ON p.id = g.system_program_id AND p.controller = 'ClienteForm'
                     WHERE NOT EXISTS (SELECT 1 FROM system_group_program g2 JOIN system_program p2 ON p2.id = g2.system_program_id AND p2.controller = 'ApmailConversaView'
                                        WHERE g2.system_group_id = g.system_group_id)) grupos_sem,
                   (SELECT count(*) FROM system_user_program u JOIN system_program p ON p.id = u.system_program_id AND p.controller = 'ClienteForm'
                     WHERE NOT EXISTS (SELECT 1 FROM system_user_program u2 JOIN system_program p2 ON p2.id = u2.system_program_id AND p2.controller = 'ApmailConversaView'
                                        WHERE u2.system_user_id = u.system_user_id)) usuarios_sem")[0];
ok('tela da conversa nas permissoes, com os mesmos grupos e usuarios do cadastro do cliente', (int) $perm['programa'] === 1 && (int) $perm['grupos_sem'] === 0 && (int) $perm['usuarios_sem'] === 0, json_encode($perm));

// Escritorio: usuario de integracao (so Admin muda; minusculas; invalido recusado)
$atual = (object) ['url_apchat' => 'u', 'token_apchat' => 't', 'url_tela_apchat' => null, 'url_apmail' => null, 'token_apmail' => null,
                   'apchat_abrir_app' => 'N', 'apmail_abrir_app' => 'N', 'apmail_email_integracao' => 'velho@x.com'];
$d = EscritorioForm::segredosAoSalvar((object) ['apmail_email_integracao' => ' Integra@Escritorio.COM '], $atual, true);
igual('admin: usuario de integracao gravado em minusculas', 'integra@escritorio.com', $d->apmail_email_integracao);
$d = EscritorioForm::segredosAoSalvar((object) ['apmail_email_integracao' => ''], $atual, true);
igual('admin: vazio = usar o do primeiro Admin', null, $d->apmail_email_integracao);
lanca('admin: e-mail invalido recusado', fn() => EscritorioForm::segredosAoSalvar((object) ['apmail_email_integracao' => 'nao-e-email'], $atual, true), 'usuário de integração');
$d = EscritorioForm::segredosAoSalvar((object) ['apmail_email_integracao' => 'outro@x.com'], $atual, false);
igual('nao admin: mantem o que estava', 'velho@x.com', $d->apmail_email_integracao);

// usuario de integracao sem o campo: o e-mail APMail do primeiro Admin
TTransaction::open('escritorio');
$esperado = '';
foreach (TTransaction::get()->query("SELECT u.id FROM system_users u JOIN system_user_group g ON g.system_user_id = u.id AND g.system_group_id = 1 WHERE u.active = 'Y' ORDER BY u.id")->fetchAll(PDO::FETCH_COLUMN) as $uid)
{
    if (($e = ApmailUsuarioService::email((int) $uid)) !== '') { $esperado = mb_strtolower($e); break; }
}
igual('usuario de integracao: vazio = o e-mail APMail do primeiro Admin', $esperado, ApmailSincronizacaoService::usuarioIntegracao(null));
igual('usuario de integracao: o do escritorio quando preenchido', 'integra@x.com', ApmailSincronizacaoService::usuarioIntegracao((function () { $e = new Escritorio(); $e->apmail_email_integracao = 'Integra@X.com'; return $e; })()));
TTransaction::close();

fim();
