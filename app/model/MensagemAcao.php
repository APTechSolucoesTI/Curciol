<?php

class MensagemAcao extends TRecord
{
    const TABLENAME  = 'mensagem_acao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Mensagem $mensagem;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('mensagem_id');
        parent::addAttribute('url');
        parent::addAttribute('label');
            
    }

    /**
     * Method set_mensagem
     * Sample of usage: $var->mensagem = $object;
     * @param $object Instance of Mensagem
     */
    public function set_mensagem(Mensagem $object)
    {
        $this->mensagem = $object;
        $this->mensagem_id = $object->id;
    }

    /**
     * Method get_mensagem
     * Sample of usage: $var->mensagem->attribute;
     * @returns Mensagem instance
     */
    public function get_mensagem()
    {
    
        // loads the associated object
        if (empty($this->mensagem))
            $this->mensagem = new Mensagem($this->mensagem_id);
    
        // returns the associated object
        return $this->mensagem;
    }

    
}

