<?php

class CNPJService
{
    /** Usa a transação aberta de escritório para localizar a cidade, sem depender de outra consulta de CEP. */
    public static function get($cnpj): stdClass
    {
        $dados = BuilderCNPJService::get($cnpj);
        $dados->estado_id = null;
        $dados->cidade_id = null;
        $ibge = (string) ($dados->codigo_municipio_ibge ?? '');
        $cidade = preg_match('/^[0-9]{7}$/', $ibge) ? Cidade::fromIBGE($ibge) : null;
        if ($cidade)
        {
            $dados->cidade_id = (int) $cidade->id;
            $dados->estado_id = (int) $cidade->estado_id;
        }
        elseif (!empty($dados->municipio) && !empty($dados->uf))
        {
            $st = TTransaction::get()->prepare(
                'SELECT c.id, c.estado_id FROM cidade c JOIN estado e ON e.id = c.estado_id
                  WHERE upper(unaccent(c.nome)) = upper(unaccent(?)) AND upper(e.sigla) = ?
                  ORDER BY c.id LIMIT 1');
            $st->execute([trim((string) $dados->municipio), strtoupper(trim((string) $dados->uf))]);
            $cidade = $st->fetch(PDO::FETCH_ASSOC);
            if ($cidade)
            {
                $dados->cidade_id = (int) $cidade['id'];
                $dados->estado_id = (int) $cidade['estado_id'];
            }
        }
        return $dados;
    }

    public static function getFull($cnpj): stdClass
    {
        return BuilderCNPJService::getFull($cnpj);
    }
}