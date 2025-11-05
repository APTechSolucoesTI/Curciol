<?php

class UnidadeMedida extends TRecord
{
    const TABLENAME  = 'unidade_medida';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
            
    }

    /**
     * Method getMaterials
     */
    public function getMaterials()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('unidade_medida_id', '=', $this->id));
        return Material::getObjects( $criteria );
    }

    public function set_material_unidade_medida_to_string($material_unidade_medida_to_string)
    {
        if(is_array($material_unidade_medida_to_string))
        {
            $values = UnidadeMedida::where('id', 'in', $material_unidade_medida_to_string)->getIndexedArray('nome', 'nome');
            $this->material_unidade_medida_to_string = implode(', ', $values);
        }
        else
        {
            $this->material_unidade_medida_to_string = $material_unidade_medida_to_string;
        }

        $this->vdata['material_unidade_medida_to_string'] = $this->material_unidade_medida_to_string;
    }

    public function get_material_unidade_medida_to_string()
    {
        if(!empty($this->material_unidade_medida_to_string))
        {
            return $this->material_unidade_medida_to_string;
        }
    
        $values = Material::where('unidade_medida_id', '=', $this->id)->getIndexedArray('unidade_medida_id','{unidade_medida->nome}');
        return implode(', ', $values);
    }

    
}

