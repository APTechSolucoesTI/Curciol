<?php

class Procedimento extends TRecord
{
    const TABLENAME  = 'procedimento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'max'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('cor');
        parent::addAttribute('ativo');
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
     * Method getAtendimentoProcedimentos
     */
    public function getAtendimentoProcedimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('procedimento_id', '=', $this->id));
        return AtendimentoProcedimento::getObjects( $criteria );
    }
    /**
     * Method getAgendas
     */
    public function getAgendas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('procedimento_id', '=', $this->id));
        return Agenda::getObjects( $criteria );
    }
    /**
     * Method getAgendamentoProcedimentos
     */
    public function getAgendamentoProcedimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('procedimento_id', '=', $this->id));
        return AgendamentoProcedimento::getObjects( $criteria );
    }
    /**
     * Method getDocumentos
     */
    public function getDocumentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('procedimento_id', '=', $this->id));
        return Documento::getObjects( $criteria );
    }
    /**
     * Method getProcedimentoPrecos
     */
    public function getProcedimentoPrecos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('procedimento_id', '=', $this->id));
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
    
        $values = AtendimentoProcedimento::where('procedimento_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
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
    
        $values = AtendimentoProcedimento::where('procedimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = AtendimentoProcedimento::where('procedimento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_escritorio_to_string($agenda_escritorio_to_string)
    {
        if(is_array($agenda_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $agenda_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_escritorio_to_string = $agenda_escritorio_to_string;
        }

        $this->vdata['agenda_escritorio_to_string'] = $this->agenda_escritorio_to_string;
    }

    public function get_agenda_escritorio_to_string()
    {
        if(!empty($this->agenda_escritorio_to_string))
        {
            return $this->agenda_escritorio_to_string;
        }
    
        $values = Agenda::where('procedimento_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_profissional_to_string($agenda_profissional_to_string)
    {
        if(is_array($agenda_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $agenda_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_profissional_to_string = $agenda_profissional_to_string;
        }

        $this->vdata['agenda_profissional_to_string'] = $this->agenda_profissional_to_string;
    }

    public function get_agenda_profissional_to_string()
    {
        if(!empty($this->agenda_profissional_to_string))
        {
            return $this->agenda_profissional_to_string;
        }
    
        $values = Agenda::where('procedimento_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_procedimento_to_string($agenda_procedimento_to_string)
    {
        if(is_array($agenda_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $agenda_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_procedimento_to_string = $agenda_procedimento_to_string;
        }

        $this->vdata['agenda_procedimento_to_string'] = $this->agenda_procedimento_to_string;
    }

    public function get_agenda_procedimento_to_string()
    {
        if(!empty($this->agenda_procedimento_to_string))
        {
            return $this->agenda_procedimento_to_string;
        }
    
        $values = Agenda::where('procedimento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_criacao_user_to_string($agenda_criacao_user_to_string)
    {
        if(is_array($agenda_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $agenda_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->agenda_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_criacao_user_to_string = $agenda_criacao_user_to_string;
        }

        $this->vdata['agenda_criacao_user_to_string'] = $this->agenda_criacao_user_to_string;
    }

    public function get_agenda_criacao_user_to_string()
    {
        if(!empty($this->agenda_criacao_user_to_string))
        {
            return $this->agenda_criacao_user_to_string;
        }
    
        $values = Agenda::where('procedimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_agenda_modificacao_user_to_string($agenda_modificacao_user_to_string)
    {
        if(is_array($agenda_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $agenda_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->agenda_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_modificacao_user_to_string = $agenda_modificacao_user_to_string;
        }

        $this->vdata['agenda_modificacao_user_to_string'] = $this->agenda_modificacao_user_to_string;
    }

    public function get_agenda_modificacao_user_to_string()
    {
        if(!empty($this->agenda_modificacao_user_to_string))
        {
            return $this->agenda_modificacao_user_to_string;
        }
    
        $values = Agenda::where('procedimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = AgendamentoProcedimento::where('procedimento_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
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
    
        $values = AgendamentoProcedimento::where('procedimento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
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
    
        $values = AgendamentoProcedimento::where('procedimento_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    public function set_documento_atendimento_to_string($documento_atendimento_to_string)
    {
        if(is_array($documento_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $documento_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->documento_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_atendimento_to_string = $documento_atendimento_to_string;
        }

        $this->vdata['documento_atendimento_to_string'] = $this->documento_atendimento_to_string;
    }

    public function get_documento_atendimento_to_string()
    {
        if(!empty($this->documento_atendimento_to_string))
        {
            return $this->documento_atendimento_to_string;
        }
    
        $values = Documento::where('procedimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_documento_modelo_documento_to_string($documento_modelo_documento_to_string)
    {
        if(is_array($documento_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $documento_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_modelo_documento_to_string = $documento_modelo_documento_to_string;
        }

        $this->vdata['documento_modelo_documento_to_string'] = $this->documento_modelo_documento_to_string;
    }

    public function get_documento_modelo_documento_to_string()
    {
        if(!empty($this->documento_modelo_documento_to_string))
        {
            return $this->documento_modelo_documento_to_string;
        }
    
        $values = Documento::where('procedimento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_documento_procedimento_to_string($documento_procedimento_to_string)
    {
        if(is_array($documento_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $documento_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_procedimento_to_string = $documento_procedimento_to_string;
        }

        $this->vdata['documento_procedimento_to_string'] = $this->documento_procedimento_to_string;
    }

    public function get_documento_procedimento_to_string()
    {
        if(!empty($this->documento_procedimento_to_string))
        {
            return $this->documento_procedimento_to_string;
        }
    
        $values = Documento::where('procedimento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_documento_criacao_user_to_string($documento_criacao_user_to_string)
    {
        if(is_array($documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_criacao_user_to_string = $documento_criacao_user_to_string;
        }

        $this->vdata['documento_criacao_user_to_string'] = $this->documento_criacao_user_to_string;
    }

    public function get_documento_criacao_user_to_string()
    {
        if(!empty($this->documento_criacao_user_to_string))
        {
            return $this->documento_criacao_user_to_string;
        }
    
        $values = Documento::where('procedimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_documento_modificacao_user_to_string($documento_modificacao_user_to_string)
    {
        if(is_array($documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_modificacao_user_to_string = $documento_modificacao_user_to_string;
        }

        $this->vdata['documento_modificacao_user_to_string'] = $this->documento_modificacao_user_to_string;
    }

    public function get_documento_modificacao_user_to_string()
    {
        if(!empty($this->documento_modificacao_user_to_string))
        {
            return $this->documento_modificacao_user_to_string;
        }
    
        $values = Documento::where('procedimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = ProcedimentoPreco::where('procedimento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
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
    
        $values = ProcedimentoPreco::where('procedimento_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    public function get_count_materiais()
    {
        return count($this->getProcedimentoMaterials());
    }
  

}

