<?php
// tests/seguranca/FiltrosSegurosTest.php
// Valores vindos da requisicao nao viram SQL: SqlSeguro, ordenacao (TCriteria::ordemValida) e filtros do servico REST.
require __DIR__ . '/../bootstrap.php';

use Adianti\Database\TCriteria;
use Adianti\Service\AdiantiRecordService;

igual('id numerico', 15, SqlSeguro::id('15'));
igual('id invalido vira 0', 0, SqlSeguro::id('1 or 1=1'));
igual('ids filtra', '1,2', SqlSeguro::ids(['1', 'x', '2']));
igual('texto dobra aspas', "maria d''ajuda", SqlSeguro::texto("maria d'ajuda"));
igual('data BR', '2026-10-22', SqlSeguro::data('22/10/2026'));
igual('data invalida', null, SqlSeguro::data('2026-02-31'));

ok('ordem coluna', TCriteria::ordemValida('pessoa.nome desc, id'));
ok('ordem com expressao recusada', !TCriteria::ordemValida('id; select 1'));
ok('ordem com parenteses recusada', !TCriteria::ordemValida('(select 1)'));

$m = new ReflectionMethod(AdiantiRecordService::class, 'criteriaSeguro');
$m->setAccessible(true);
$aceita = function ($param) use ($m) { try { $m->invoke(null, $param, TRUE); return true; } catch (Exception $e) { return false; } };
ok('filtro normal aceito', $aceita(['filters' => [['nome', 'like', '%ana%'], ['id', 'in', [1, 2]]], 'order' => 'nome', 'direction' => 'desc']));
ok('coluna com expressao recusada', !$aceita(['filters' => [['1=1 or nome', '=', 'x']]]));
ok('operador fora da lista recusado', !$aceita(['filters' => [['nome', '= 1 or', 'x']]]));
ok('valor subconsulta recusado', !$aceita(['filters' => [['id', 'in', '(SELECT id FROM pessoa)']]]));
ok('valor NOESC recusado', !$aceita(['filters' => [['id', '=', 'NOESC:1']]]));
ok('ordem invalida recusada', !$aceita(['order' => 'id desc; x']));
fim();
