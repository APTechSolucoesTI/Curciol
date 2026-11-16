<?php
// tests/apchat/ApchatServidorTest.php
// Banco do APChat escolhido pela integracao do Escritorio (16/11/2026): app/config/apchat.php com 'servidores' por host da URL da
// API; trocar a URL troca o banco (e os caches); servidor sem banco no arquivo = Indisponivel; formato antigo (um banco so) continua.
// Arquivos de configuracao temporarios; nenhum banco de verdade (host local fechado). Grava a URL do escritorio e devolve no fim.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function qs(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function xs(string $sql, array $p = []): void { TTransaction::open('escritorio'); TTransaction::get()->prepare($sql)->execute($p); TTransaction::close(); }

$escritorios = qs('SELECT id, system_unit_id, url_apchat, token_apchat FROM escritorio ORDER BY id');
TSession::setValue('userunitid', (int) $escritorios[0]['system_unit_id']);
$dir = sys_get_temp_dir() . '/apchat-servidor-teste-' . getmypid();
@mkdir($dir);
$lista = $dir . '/lista.php';
$antigo = $dir . '/antigo.php';
file_put_contents($lista, "<?php return ['servidores' => [
    'api.apchat.com.br'  => ['host' => '127.0.0.1', 'port' => '1', 'name' => 'oficial', 'user' => 'u_oficial', 'pass' => 'x'],
    'api1.apchat.com.br' => ['host' => '127.0.0.1', 'port' => '1', 'name' => 'app1', 'user' => 'u_app1', 'pass' => 'x'],
], 'historico_somente_numeros' => ['5519900000001']];");
file_put_contents($antigo, "<?php return ['host' => '127.0.0.1', 'port' => '1', 'name' => 'unico', 'user' => 'u', 'pass' => 'x'];");
$url = function (string $host) { xs('UPDATE escritorio SET url_apchat = ?, token_apchat = ?', ["https://{$host}/v2/api/external/00000000-0000-4000-8000-00000000c0de", 'tk-servidor']); };

try
{
    ApchatBancoService::usarFalso(null);
    ApchatBancoService::desligar(false);
    ApchatBancoService::usarConfig($lista);

    $url('api.apchat.com.br');
    igual('URL do oficial: servidor da integracao', 'api.apchat.com.br', ApchatBancoService::servidorDaIntegracao());
    ok('URL do oficial: banco configurado (o do oficial)', ApchatBancoService::configurado() && !ApchatBancoService::semServidor());
    $assOficial = ApchatBancoService::assinatura();

    $url('API1.apchat.com.br');
    ApchatBancoService::usarConfig($lista);
    ok('URL do app1 (maiusculas nao importam): banco configurado', ApchatBancoService::configurado() && ApchatBancoService::servidorDaIntegracao() === 'api1.apchat.com.br');
    ok('trocar a URL troca o banco (e a chave dos caches)', ApchatBancoService::assinatura() !== $assOficial && ApchatBancoService::assinatura() !== 'sem-banco');

    $url('api9.apchat.com.br');
    ApchatBancoService::usarConfig($lista);
    ok('servidor fora do arquivo: sem banco', !ApchatBancoService::configurado() && ApchatBancoService::semServidor() && ApchatBancoService::assinatura() === 'sem-banco');
    TTransaction::open('escritorio');
    igual('servidor fora do arquivo: APChat Indisponivel com o motivo', 'sem_servidor', IntegracaoAcessoService::configuracaoApchat());
    igual('servidor fora do arquivo: rotulo', IntegracaoAcessoService::INDISPONIVEL, IntegracaoAcessoService::rotuloApchat('sem_servidor'));
    TTransaction::close();
    ok('servidor fora do arquivo: nao conecta', !ApchatBancoService::disponivel() && strpos(ApchatBancoService::erro(), 'não configurada para a integração') !== false, ApchatBancoService::erro());
    igual('historico_somente_numeros continua lido do arquivo', ['5519900000001'], ApchatBancoService::historicoSomente());

    xs('UPDATE escritorio SET url_apchat = NULL, token_apchat = NULL');
    ApchatBancoService::usarConfig($lista);
    ok('sem URL no Escritorio: nenhum banco', !ApchatBancoService::configurado() && ApchatBancoService::servidorDaIntegracao() === '');

    // formato antigo: um banco so, para qualquer URL
    $url('qualquer.exemplo.invalid');
    ApchatBancoService::usarConfig($antigo);
    ok('formato antigo: o unico banco vale para qualquer URL', ApchatBancoService::configurado() && !ApchatBancoService::semServidor());
    ok('banco que nao responde: disponivel() falso, sem excecao', !ApchatBancoService::disponivel());
}
finally
{
    foreach ($escritorios as $e) { xs('UPDATE escritorio SET url_apchat = ?, token_apchat = ? WHERE id = ?', [$e['url_apchat'], $e['token_apchat'], $e['id']]); }
    ApchatBancoService::usarConfig(null);
    ApchatBancoService::desligar(); // como o bootstrap: testes nunca usam o banco real do APChat
    @unlink($lista); @unlink($antigo); @rmdir($dir);
}

fim();
