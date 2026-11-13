<?php
// tests/apchat/ApchatAcessoTest.php
// Acessos do APChat para a regra de visibilidade (13/11/2026): mudanca de acesso no APChat vale em ate 5 min (o ciclo do
// historico do APMail); so as notificacoes (balao, chat interno) sao conferidas a cada 5 s. APChat fora = ultimo conhecido.
require_once __DIR__ . '/../bootstrap.php';

$falso = new class {
    public $lidos = 0; public $config = 0; public $fora = false; public $filas = [1];
    public function acessos(int $a): array { if ($this->fora) { throw new Exception('fora'); } $this->lidos++; return ['canais' => [], 'filas' => $this->filas, 'perfil' => 'user', 'existe' => true]; }
    public function configuracoes(): array { if ($this->fora) { throw new Exception('fora'); } $this->config++; return ['nullTickets' => 'enabled']; }
};
ApchatBancoService::usarFalso($falso);
ApchatAcessoService::limpar();
$agora = time();
try
{
    igual('acessos: tempo de cache = 5 min (como o historico do APMail)', 300, ApchatAcessoService::SEGUNDOS);
    ApchatAcessoService::usarRelogio($agora);
    igual('primeira leitura', [1], ApchatAcessoService::acessos(9001)['filas']);
    $falso->filas = [2];
    ApchatAcessoService::usarRelogio($agora + 299);
    ApchatAcessoService::acessos(9001);
    ApchatAcessoService::configuracoes();
    ApchatAcessoService::usarRelogio($agora + 298);
    ApchatAcessoService::configuracoes();
    igual('antes de 5 min: nao rele', [1, 1], [$falso->lidos, $falso->config]);
    ApchatAcessoService::usarRelogio($agora + 301);
    igual('depois de 5 min: acesso novo', [2], ApchatAcessoService::acessos(9001)['filas']);
    $falso->fora = true;
    ApchatAcessoService::usarRelogio($agora + 1000);
    igual('APChat fora: o ultimo acesso conhecido', [2], ApchatAcessoService::acessos(9001)['filas']);
    ApchatAcessoService::limpar();
    lanca('APChat fora e nunca lido: excecao (nao mostra nada)', fn() => ApchatAcessoService::acessos(9001), 'fora');
}
finally
{
    ApchatAcessoService::usarRelogio(null);
    ApchatAcessoService::limpar();
    ApchatBancoService::usarFalso(null);
}

fim();
