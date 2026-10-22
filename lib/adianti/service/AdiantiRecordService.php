<?php
namespace Adianti\Service;

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

/**
 * Record rest service
 *
 * This class provides methods to load, store, delete, and list Active Records
 * using HTTP parameters. It supports operations like retrieving a single record,
 * deleting a record, storing a record, and listing multiple records based on filters.
 *
 * @version    7.5
 * @package    service
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiRecordService
{
    /**
     * Retrieves an Active Record by its ID.
     *
     * This method fetches a record from the database based on the provided ID and
     * returns it as an array.
     *
     * @param array $param HTTP parameters, must include 'id' as the primary key.
     *
     * @return array The Active Record as an associative array.
     * @throws Exception If the record is not found or database access fails.
     */
    public function load($param)
    {
        $database     = static::DATABASE;
        $activeRecord = static::ACTIVE_RECORD;
        
        TTransaction::open($database);
        
        $object = new $activeRecord($param['id'], FALSE);
        
        TTransaction::close();
        $attributes = defined('static::ATTRIBUTES') ? static::ATTRIBUTES : null;
        return $object->toArray( $attributes );
    }
    
    /**
     * Deletes an Active Record by its ID.
     *
     * This method removes a record from the database based on the provided ID.
     *
     * @param array $param HTTP parameters, must include 'id' as the primary key.
     *
     * @return void
     * @throws Exception If the record is not found or deletion fails.
     */
    public function delete($param)
    {
        $database     = static::DATABASE;
        $activeRecord = static::ACTIVE_RECORD;
        
        TTransaction::open($database);
        
        $object = new $activeRecord($param['id']);
        $object->delete();
        
        TTransaction::close();
        return;
    }
    
    /**
     * Stores or updates an Active Record in the database.
     *
     * If an ID is provided, the existing record is updated; otherwise, a new record is created.
     *
     * @param array $param HTTP parameters containing 'data' with record fields.
     *
     * @return array The stored record as an associative array.
     * @throws Exception If saving to the database fails.
     */
    public function store($param)
    {
        $database     = static::DATABASE;
        $activeRecord = static::ACTIVE_RECORD;
        
        TTransaction::open($database);
        
        $object = new $activeRecord;
        $pk = $object->getPrimaryKey();
        $param['data'][$pk] = $param['data']['id'] ?? NULL;
        $object->fromArray( (array) $param['data']);
        $object->store();
        
        TTransaction::close();
        return $object->toArray();
    }
    
    /**
     * Retrieves multiple Active Records based on filters.
     *
     * This method applies optional filters, pagination, and sorting options to return
     * a list of records.
     *
     * @param array $param HTTP parameters, supports:
     *  - 'offset' (int): Starting record for pagination.
     *  - 'limit' (int): Number of records to retrieve.
     *  - 'order' (string): Column name for sorting.
     *  - 'direction' (string): Sorting direction ('asc' or 'desc').
     *  - 'filters' (array): Conditions for filtering records.
     *
     * @return array List of Active Records as an associative array.
     * @throws Exception If database access fails.
     */
    public function loadAll($param)
    {
        $database     = static::DATABASE;
        $activeRecord = static::ACTIVE_RECORD;
        
        TTransaction::open($database);
        
        $criteria = self::criteriaSeguro($param, TRUE);
        
        $repository = new TRepository($activeRecord);
        $objects = $repository->load($criteria, FALSE);
        $attributes = defined('static::ATTRIBUTES') ? static::ATTRIBUTES : null;
        
        $return = [];
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $return[] = $object->toArray( $attributes );
            }
        }
        TTransaction::close();
        return $return;
    }
    
    /**
     * Deletes multiple Active Records based on filters.
     *
     * This method applies filters and removes all matching records from the database.
     *
     * @param array $param HTTP parameters, must include:
     *  - 'filters' (array): Conditions to select records for deletion.
     *
     * @return int Number of deleted records.
     * @throws Exception If deletion fails.
     */
    public function deleteAll($param)
    {
        $database     = static::DATABASE;
        $activeRecord = static::ACTIVE_RECORD;
        
        TTransaction::open($database);
        
        $criteria = self::criteriaSeguro($param, FALSE);
        
        $repository = new TRepository($activeRecord);
        $return = $repository->delete($criteria);
        TTransaction::close();
        return $return;
    }

    /**
     * Counts the number of Active Records that match given filters.
     *
     * This method applies filters and returns the count of matching records.
     *
     * @param array $param HTTP parameters, supports:
     *  - 'filters' (array): Conditions for filtering records.
     *
     * @return int Number of matching records.
     * @throws Exception If database access fails.
     */
    public function countAll($param)
    {
        $database     = static::DATABASE;
        $activeRecord = static::ACTIVE_RECORD;

        TTransaction::open($database);

        $criteria = self::criteriaSeguro($param, TRUE);

        $repository = new TRepository($activeRecord);
        $count = $repository->count($criteria, FALSE);

        TTransaction::close();
        return $count;
    }
    
    /**
     * Handles HTTP requests and dispatches them to the appropriate method.
     *
     * This method determines the HTTP request type (GET, POST, PUT, DELETE) and
     * calls the corresponding CRUD operation.
     *
     * @param array $param HTTP parameters containing request data.
     *
     * @return mixed The result of the respective method call.
     */
    public function handle($param)
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        
        unset($param['class']);
        unset($param['method']);
        $param['data'] = $param;
        
        switch( $method )
        {
            case 'GET':
                if (!empty($param['id']))
                {
                    return self::load($param);
                }
                else
                {
                    return self::loadAll($param);
                }
                break;
            case 'POST':
                return self::store($param);
                break;
            case 'PUT':
                return self::store($param);
                break;        
            case 'DELETE':
                if (!empty($param['id']))
                {
                    return self::delete($param);
                }
                else
                {
                    return self::deleteAll($param);
                }
                break;
        }
    }

    /**
     * Curciol (seguranca 22/10/2026): monta o criterio de loadAll/countAll/deleteAll validando o que vem da
     * requisicao. Coluna e order so nomes de coluna; operador de uma lista fixa; valor nunca como SQL cru
     * (TFilter trata "(SELECT" e "NOESC:" como SQL). Antes coluna, operador e order iam crus para o SQL.
     */
    private static function criteriaSeguro($param, $paginacao)
    {
        $criteria = new TCriteria;
        if ($paginacao)
        {
            if (isset($param['offset']))
            {
                $criteria->setProperty('offset', (int) $param['offset']);
            }
            if (isset($param['limit']))
            {
                $criteria->setProperty('limit', (int) $param['limit']);
            }
            if (isset($param['order']))
            {
                if (!TCriteria::ordemValida($param['order']))
                {
                    throw new \Exception('Ordenação inválida');
                }
                $criteria->setProperty('order', $param['order']);
            }
            if (isset($param['direction']))
            {
                $criteria->setProperty('direction', strtolower((string) $param['direction']) == 'desc' ? 'desc' : 'asc');
            }
        }
        if (isset($param['filters']))
        {
            $operadores = ['=', '<>', '!=', '<', '<=', '>', '>=', 'like', 'ilike', 'not like', 'in', 'not in', 'is', 'is not'];
            foreach ((array) $param['filters'] as $filter)
            {
                $coluna   = $filter[0] ?? '';
                $operador = strtolower(trim((string) ($filter[1] ?? '')));
                $valor    = $filter[2] ?? null;
                if (!is_string($coluna) OR !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $coluna) OR !in_array($operador, $operadores, TRUE))
                {
                    throw new \Exception('Filtro inválido');
                }
                foreach ((array) $valor as $v)
                {
                    if (is_string($v) AND preg_match('/^\s*(\(\s*SELECT|NOESC:)/i', $v))
                    {
                        throw new \Exception('Filtro inválido');
                    }
                }
                $criteria->add(new TFilter($coluna, $operador, $valor));
            }
        }
        return $criteria;
    }
}
