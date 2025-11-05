<?php

class TemplateAcao extends TRecord
{
    const TABLENAME  = 'template_acao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private TemplateEscritorio $template_escritorio;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('template_escritorio_id');
        parent::addAttribute('url');
        parent::addAttribute('label');
            
    }

    /**
     * Method set_template_escritorio
     * Sample of usage: $var->template_escritorio = $object;
     * @param $object Instance of TemplateEscritorio
     */
    public function set_template_escritorio(TemplateEscritorio $object)
    {
        $this->template_escritorio = $object;
        $this->template_escritorio_id = $object->id;
    }

    /**
     * Method get_template_escritorio
     * Sample of usage: $var->template_escritorio->attribute;
     * @returns TemplateEscritorio instance
     */
    public function get_template_escritorio()
    {
    
        // loads the associated object
        if (empty($this->template_escritorio))
            $this->template_escritorio = new TemplateEscritorio($this->template_escritorio_id);
    
        // returns the associated object
        return $this->template_escritorio;
    }

    
}

