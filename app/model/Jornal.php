<?php

class Jornal extends TRecord
{
    const TABLENAME  = 'jornal';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
    }

    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_criacao_user(SystemUsers $object)
    {
        $this->criacao_user = $object;
        $this->criacao_user_id = $object->id;
    }

    /**
     * Method get_criacao_user
     * Sample of usage: $var->criacao_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_criacao_user()
    {
    
        // loads the associated object
        if (empty($this->criacao_user))
            $this->criacao_user = new SystemUsers($this->criacao_user_id);
    
        // returns the associated object
        return $this->criacao_user;
    }
    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_modificacao_user(SystemUsers $object)
    {
        $this->modificacao_user = $object;
        $this->modificacao_user_id = $object->id;
    }

    /**
     * Method get_modificacao_user
     * Sample of usage: $var->modificacao_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_modificacao_user()
    {
    
        // loads the associated object
        if (empty($this->modificacao_user))
            $this->modificacao_user = new SystemUsers($this->modificacao_user_id);
    
        // returns the associated object
        return $this->modificacao_user;
    }

    /**
     * Method getPublicacaos
     */
    public function getPublicacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('jornal_id', '=', $this->id));
        return Publicacao::getObjects( $criteria );
    }

    public function set_publicacao_processo_to_string($publicacao_processo_to_string)
    {
        if(is_array($publicacao_processo_to_string))
        {
            $values = Processo::where('id', 'in', $publicacao_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->publicacao_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_processo_to_string = $publicacao_processo_to_string;
        }

        $this->vdata['publicacao_processo_to_string'] = $this->publicacao_processo_to_string;
    }

    public function get_publicacao_processo_to_string()
    {
        if(!empty($this->publicacao_processo_to_string))
        {
            return $this->publicacao_processo_to_string;
        }
    
        $values = Publicacao::where('jornal_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_publicacao_jornal_to_string($publicacao_jornal_to_string)
    {
        if(is_array($publicacao_jornal_to_string))
        {
            $values = Jornal::where('id', 'in', $publicacao_jornal_to_string)->getIndexedArray('nome', 'nome');
            $this->publicacao_jornal_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_jornal_to_string = $publicacao_jornal_to_string;
        }

        $this->vdata['publicacao_jornal_to_string'] = $this->publicacao_jornal_to_string;
    }

    public function get_publicacao_jornal_to_string()
    {
        if(!empty($this->publicacao_jornal_to_string))
        {
            return $this->publicacao_jornal_to_string;
        }
    
        $values = Publicacao::where('jornal_id', '=', $this->id)->getIndexedArray('jornal_id','{jornal->nome}');
        return implode(', ', $values);
    }

    public function set_publicacao_criacao_user_to_string($publicacao_criacao_user_to_string)
    {
        if(is_array($publicacao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_criacao_user_to_string = $publicacao_criacao_user_to_string;
        }

        $this->vdata['publicacao_criacao_user_to_string'] = $this->publicacao_criacao_user_to_string;
    }

    public function get_publicacao_criacao_user_to_string()
    {
        if(!empty($this->publicacao_criacao_user_to_string))
        {
            return $this->publicacao_criacao_user_to_string;
        }
    
        $values = Publicacao::where('jornal_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_modificacao_user_to_string($publicacao_modificacao_user_to_string)
    {
        if(is_array($publicacao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_modificacao_user_to_string = $publicacao_modificacao_user_to_string;
        }

        $this->vdata['publicacao_modificacao_user_to_string'] = $this->publicacao_modificacao_user_to_string;
    }

    public function get_publicacao_modificacao_user_to_string()
    {
        if(!empty($this->publicacao_modificacao_user_to_string))
        {
            return $this->publicacao_modificacao_user_to_string;
        }
    
        $values = Publicacao::where('jornal_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function get_data_disponibilizacao_formatada(){
        if($this->dt_venda)
        {
            try
            {
                $date = new DateTime($this->dt_venda);
                $diaSemana = DateService::getDayWeek($date);
                $mes = DateService::getMonthName($date);
            
                return $diaSemana.", ".$date->format('d')." de ". $mes . " de ".$date->format('Y');
            }
            catch (Exception $e)
            {
                return $this->dt_venda;
            }
        }

        return $this->dt_venda;
    }

}

