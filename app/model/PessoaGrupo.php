<?php

class PessoaGrupo extends TRecord
{
    const TABLENAME  = 'pessoa_grupo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Pessoa $pessoa;
    private Grupo $grupo;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('grupo_id');
        parent::addAttribute('cor');
            
    }

    /** Grupos que acompanham outro (regra do escritorio, 20/10/2026): Profissional e tambem Parceiro, Parceiro e tambem Fornecedor. */
    const GRUPOS_IMPLICITOS = [
        Grupo::PROFISSIONAL => [Grupo::PARCEIRO, Grupo::FORNECEDOR],
        Grupo::PARCEIRO     => [Grupo::FORNECEDOR],
    ];

    public function onAfterStore($object)
    {
        foreach (self::GRUPOS_IMPLICITOS[(string) ($object->grupo_id ?? '')] ?? [] as $grupoId) {
            if (self::where('pessoa_id', '=', $object->pessoa_id)->where('grupo_id', '=', $grupoId)->count() == 0) {
                $implicito = new self();
                $implicito->pessoa_id = $object->pessoa_id;
                $implicito->grupo_id = $grupoId;
                $implicito->store();
            }
        }
    }

    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_pessoa(Pessoa $object)
    {
        $this->pessoa = $object;
        $this->pessoa_id = $object->id;
    }

    /**
     * Method get_pessoa
     * Sample of usage: $var->pessoa->attribute;
     * @returns Pessoa instance
     */
    public function get_pessoa()
    {
    
        // loads the associated object
        if (empty($this->pessoa))
            $this->pessoa = new Pessoa($this->pessoa_id);
    
        // returns the associated object
        return $this->pessoa;
    }
    /**
     * Method set_grupo
     * Sample of usage: $var->grupo = $object;
     * @param $object Instance of Grupo
     */
    public function set_grupo(Grupo $object)
    {
        $this->grupo = $object;
        $this->grupo_id = $object->id;
    }

    /**
     * Method get_grupo
     * Sample of usage: $var->grupo->attribute;
     * @returns Grupo instance
     */
    public function get_grupo()
    {
    
        // loads the associated object
        if (empty($this->grupo))
            $this->grupo = new Grupo($this->grupo_id);
    
        // returns the associated object
        return $this->grupo;
    }

    
}

