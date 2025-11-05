<?php

class Material extends TRecord
{
    const TABLENAME  = 'material';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private UnidadeMedida $unidade_medida;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('unidade_medida_id');
        parent::addAttribute('nome');
        parent::addAttribute('estoque_minimo');
        parent::addAttribute('dt_vencimento');
        parent::addAttribute('estoque_atualizado');
        parent::addAttribute('lote');
        parent::addAttribute('ativo');
            
    }

    /**
     * Method set_unidade_medida
     * Sample of usage: $var->unidade_medida = $object;
     * @param $object Instance of UnidadeMedida
     */
    public function set_unidade_medida(UnidadeMedida $object)
    {
        $this->unidade_medida = $object;
        $this->unidade_medida_id = $object->id;
    }

    /**
     * Method get_unidade_medida
     * Sample of usage: $var->unidade_medida->attribute;
     * @returns UnidadeMedida instance
     */
    public function get_unidade_medida()
    {
    
        // loads the associated object
        if (empty($this->unidade_medida))
            $this->unidade_medida = new UnidadeMedida($this->unidade_medida_id);
    
        // returns the associated object
        return $this->unidade_medida;
    }

    /**
     * Method getAtendimentoMaterials
     */
    public function getAtendimentoMaterials()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('material_id', '=', $this->id));
        return AtendimentoMaterial::getObjects( $criteria );
    }
    /**
     * Method getMovimentacaos
     */
    public function getMovimentacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('material_id', '=', $this->id));
        return Movimentacao::getObjects( $criteria );
    }

    public function set_atendimento_material_material_to_string($atendimento_material_material_to_string)
    {
        if(is_array($atendimento_material_material_to_string))
        {
            $values = Material::where('id', 'in', $atendimento_material_material_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_material_material_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_material_material_to_string = $atendimento_material_material_to_string;
        }

        $this->vdata['atendimento_material_material_to_string'] = $this->atendimento_material_material_to_string;
    }

    public function get_atendimento_material_material_to_string()
    {
        if(!empty($this->atendimento_material_material_to_string))
        {
            return $this->atendimento_material_material_to_string;
        }
    
        $values = AtendimentoMaterial::where('material_id', '=', $this->id)->getIndexedArray('material_id','{material->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_material_atendimento_to_string($atendimento_material_atendimento_to_string)
    {
        if(is_array($atendimento_material_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $atendimento_material_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_material_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_material_atendimento_to_string = $atendimento_material_atendimento_to_string;
        }

        $this->vdata['atendimento_material_atendimento_to_string'] = $this->atendimento_material_atendimento_to_string;
    }

    public function get_atendimento_material_atendimento_to_string()
    {
        if(!empty($this->atendimento_material_atendimento_to_string))
        {
            return $this->atendimento_material_atendimento_to_string;
        }
    
        $values = AtendimentoMaterial::where('material_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_movimentacao_material_to_string($movimentacao_material_to_string)
    {
        if(is_array($movimentacao_material_to_string))
        {
            $values = Material::where('id', 'in', $movimentacao_material_to_string)->getIndexedArray('nome', 'nome');
            $this->movimentacao_material_to_string = implode(', ', $values);
        }
        else
        {
            $this->movimentacao_material_to_string = $movimentacao_material_to_string;
        }

        $this->vdata['movimentacao_material_to_string'] = $this->movimentacao_material_to_string;
    }

    public function get_movimentacao_material_to_string()
    {
        if(!empty($this->movimentacao_material_to_string))
        {
            return $this->movimentacao_material_to_string;
        }
    
        $values = Movimentacao::where('material_id', '=', $this->id)->getIndexedArray('material_id','{material->nome}');
        return implode(', ', $values);
    }

    public function set_movimentacao_system_user_to_string($movimentacao_system_user_to_string)
    {
        if(is_array($movimentacao_system_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $movimentacao_system_user_to_string)->getIndexedArray('name', 'name');
            $this->movimentacao_system_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->movimentacao_system_user_to_string = $movimentacao_system_user_to_string;
        }

        $this->vdata['movimentacao_system_user_to_string'] = $this->movimentacao_system_user_to_string;
    }

    public function get_movimentacao_system_user_to_string()
    {
        if(!empty($this->movimentacao_system_user_to_string))
        {
            return $this->movimentacao_system_user_to_string;
        }
    
        $values = Movimentacao::where('material_id', '=', $this->id)->getIndexedArray('system_user_id','{system_user->name}');
        return implode(', ', $values);
    }

    
}

