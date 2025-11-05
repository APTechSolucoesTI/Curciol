<?php

class DocumentoBaseContrato extends TRecord
{
    const TABLENAME  = 'documento_base_contrato';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Area $area;
    private ModeloDocumento $modelo_documento;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('area_id');
        parent::addAttribute('modelo_documento_id');
            
    }

    /**
     * Method set_area
     * Sample of usage: $var->area = $object;
     * @param $object Instance of Area
     */
    public function set_area(Area $object)
    {
        $this->area = $object;
        $this->area_id = $object->id;
    }

    /**
     * Method get_area
     * Sample of usage: $var->area->attribute;
     * @returns Area instance
     */
    public function get_area()
    {
    
        // loads the associated object
        if (empty($this->area))
            $this->area = new Area($this->area_id);
    
        // returns the associated object
        return $this->area;
    }
    /**
     * Method set_modelo_documento
     * Sample of usage: $var->modelo_documento = $object;
     * @param $object Instance of ModeloDocumento
     */
    public function set_modelo_documento(ModeloDocumento $object)
    {
        $this->modelo_documento = $object;
        $this->modelo_documento_id = $object->id;
    }

    /**
     * Method get_modelo_documento
     * Sample of usage: $var->modelo_documento->attribute;
     * @returns ModeloDocumento instance
     */
    public function get_modelo_documento()
    {
    
        // loads the associated object
        if (empty($this->modelo_documento))
            $this->modelo_documento = new ModeloDocumento($this->modelo_documento_id);
    
        // returns the associated object
        return $this->modelo_documento;
    }

    
}

