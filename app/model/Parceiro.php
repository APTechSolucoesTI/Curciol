<?php

class Parceiro extends TRecord
{
    const TABLENAME  = 'parceiro';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private Pessoa $pessoa;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('pessoa_id');
        parent::addAttribute('percentual');
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
     * Method getAtendimentoProcedimentos
     */
    public function getAtendimentoProcedimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('parceiro_id', '=', $this->id));
        return AtendimentoProcedimento::getObjects( $criteria );
    }
    /**
     * Method getAgendamentoProcedimentos
     */
    public function getAgendamentoProcedimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('parceiro_id', '=', $this->id));
        return AgendamentoProcedimento::getObjects( $criteria );
    }
    /**
     * Method getEscritorioParceiros
     */
    public function getEscritorioParceiros()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('parceiro_id', '=', $this->id));
        return EscritorioParceiro::getObjects( $criteria );
    }
    /**
     * Method getProcedimentoPrecos
     */
    public function getProcedimentoPrecos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('parceiro_id', '=', $this->id));
        return ProcedimentoPreco::getObjects( $criteria );
    }

    public function set_atendimento_procedimento_parceiro_to_string($atendimento_procedimento_parceiro_to_string)
    {
        if(is_array($atendimento_procedimento_parceiro_to_string))
        {
            $values = Parceiro::where('id', 'in', $atendimento_procedimento_parceiro_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_procedimento_parceiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_procedimento_parceiro_to_string = $atendimento_procedimento_parceiro_to_string;
        }

        $this->vdata['atendimento_procedimento_parceiro_to_string'] = $this->atendimento_procedimento_parceiro_to_string;
    }

    public function get_atendimento_procedimento_parceiro_to_string()
    {
        if(!empty($this->atendimento_procedimento_parceiro_to_string))
        {
            return $this->atendimento_procedimento_parceiro_to_string;
        }
    
        $values = AtendimentoProcedimento::where('parceiro_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_procedimento_atendimento_to_string($atendimento_procedimento_atendimento_to_string)
    {
        if(is_array($atendimento_procedimento_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $atendimento_procedimento_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_procedimento_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_procedimento_atendimento_to_string = $atendimento_procedimento_atendimento_to_string;
        }

        $this->vdata['atendimento_procedimento_atendimento_to_string'] = $this->atendimento_procedimento_atendimento_to_string;
    }

    public function get_atendimento_procedimento_atendimento_to_string()
    {
        if(!empty($this->atendimento_procedimento_atendimento_to_string))
        {
            return $this->atendimento_procedimento_atendimento_to_string;
        }
    
        $values = AtendimentoProcedimento::where('parceiro_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_procedimento_procedimento_to_string($atendimento_procedimento_procedimento_to_string)
    {
        if(is_array($atendimento_procedimento_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $atendimento_procedimento_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_procedimento_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_procedimento_procedimento_to_string = $atendimento_procedimento_procedimento_to_string;
        }

        $this->vdata['atendimento_procedimento_procedimento_to_string'] = $this->atendimento_procedimento_procedimento_to_string;
    }

    public function get_atendimento_procedimento_procedimento_to_string()
    {
        if(!empty($this->atendimento_procedimento_procedimento_to_string))
        {
            return $this->atendimento_procedimento_procedimento_to_string;
        }
    
        $values = AtendimentoProcedimento::where('parceiro_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_agendamento_procedimento_agendamento_to_string($agendamento_procedimento_agendamento_to_string)
    {
        if(is_array($agendamento_procedimento_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $agendamento_procedimento_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->agendamento_procedimento_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_procedimento_agendamento_to_string = $agendamento_procedimento_agendamento_to_string;
        }

        $this->vdata['agendamento_procedimento_agendamento_to_string'] = $this->agendamento_procedimento_agendamento_to_string;
    }

    public function get_agendamento_procedimento_agendamento_to_string()
    {
        if(!empty($this->agendamento_procedimento_agendamento_to_string))
        {
            return $this->agendamento_procedimento_agendamento_to_string;
        }
    
        $values = AgendamentoProcedimento::where('parceiro_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_agendamento_procedimento_procedimento_to_string($agendamento_procedimento_procedimento_to_string)
    {
        if(is_array($agendamento_procedimento_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $agendamento_procedimento_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->agendamento_procedimento_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_procedimento_procedimento_to_string = $agendamento_procedimento_procedimento_to_string;
        }

        $this->vdata['agendamento_procedimento_procedimento_to_string'] = $this->agendamento_procedimento_procedimento_to_string;
    }

    public function get_agendamento_procedimento_procedimento_to_string()
    {
        if(!empty($this->agendamento_procedimento_procedimento_to_string))
        {
            return $this->agendamento_procedimento_procedimento_to_string;
        }
    
        $values = AgendamentoProcedimento::where('parceiro_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_agendamento_procedimento_parceiro_to_string($agendamento_procedimento_parceiro_to_string)
    {
        if(is_array($agendamento_procedimento_parceiro_to_string))
        {
            $values = Parceiro::where('id', 'in', $agendamento_procedimento_parceiro_to_string)->getIndexedArray('nome', 'nome');
            $this->agendamento_procedimento_parceiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_procedimento_parceiro_to_string = $agendamento_procedimento_parceiro_to_string;
        }

        $this->vdata['agendamento_procedimento_parceiro_to_string'] = $this->agendamento_procedimento_parceiro_to_string;
    }

    public function get_agendamento_procedimento_parceiro_to_string()
    {
        if(!empty($this->agendamento_procedimento_parceiro_to_string))
        {
            return $this->agendamento_procedimento_parceiro_to_string;
        }
    
        $values = AgendamentoProcedimento::where('parceiro_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    public function set_escritorio_parceiro_parceiro_to_string($escritorio_parceiro_parceiro_to_string)
    {
        if(is_array($escritorio_parceiro_parceiro_to_string))
        {
            $values = Parceiro::where('id', 'in', $escritorio_parceiro_parceiro_to_string)->getIndexedArray('nome', 'nome');
            $this->escritorio_parceiro_parceiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_parceiro_parceiro_to_string = $escritorio_parceiro_parceiro_to_string;
        }

        $this->vdata['escritorio_parceiro_parceiro_to_string'] = $this->escritorio_parceiro_parceiro_to_string;
    }

    public function get_escritorio_parceiro_parceiro_to_string()
    {
        if(!empty($this->escritorio_parceiro_parceiro_to_string))
        {
            return $this->escritorio_parceiro_parceiro_to_string;
        }
    
        $values = EscritorioParceiro::where('parceiro_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    public function set_escritorio_parceiro_escritorio_to_string($escritorio_parceiro_escritorio_to_string)
    {
        if(is_array($escritorio_parceiro_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $escritorio_parceiro_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->escritorio_parceiro_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_parceiro_escritorio_to_string = $escritorio_parceiro_escritorio_to_string;
        }

        $this->vdata['escritorio_parceiro_escritorio_to_string'] = $this->escritorio_parceiro_escritorio_to_string;
    }

    public function get_escritorio_parceiro_escritorio_to_string()
    {
        if(!empty($this->escritorio_parceiro_escritorio_to_string))
        {
            return $this->escritorio_parceiro_escritorio_to_string;
        }
    
        $values = EscritorioParceiro::where('parceiro_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_procedimento_preco_procedimento_to_string($procedimento_preco_procedimento_to_string)
    {
        if(is_array($procedimento_preco_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $procedimento_preco_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->procedimento_preco_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->procedimento_preco_procedimento_to_string = $procedimento_preco_procedimento_to_string;
        }

        $this->vdata['procedimento_preco_procedimento_to_string'] = $this->procedimento_preco_procedimento_to_string;
    }

    public function get_procedimento_preco_procedimento_to_string()
    {
        if(!empty($this->procedimento_preco_procedimento_to_string))
        {
            return $this->procedimento_preco_procedimento_to_string;
        }
    
        $values = ProcedimentoPreco::where('parceiro_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_procedimento_preco_parceiro_to_string($procedimento_preco_parceiro_to_string)
    {
        if(is_array($procedimento_preco_parceiro_to_string))
        {
            $values = Parceiro::where('id', 'in', $procedimento_preco_parceiro_to_string)->getIndexedArray('nome', 'nome');
            $this->procedimento_preco_parceiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->procedimento_preco_parceiro_to_string = $procedimento_preco_parceiro_to_string;
        }

        $this->vdata['procedimento_preco_parceiro_to_string'] = $this->procedimento_preco_parceiro_to_string;
    }

    public function get_procedimento_preco_parceiro_to_string()
    {
        if(!empty($this->procedimento_preco_parceiro_to_string))
        {
            return $this->procedimento_preco_parceiro_to_string;
        }
    
        $values = ProcedimentoPreco::where('parceiro_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    
}

