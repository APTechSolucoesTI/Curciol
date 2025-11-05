<?php

class Mensagem extends TRecord
{
    const TABLENAME  = 'mensagem';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'enviado_em';

    private Agendamento $agendamento;
    private TemplateEscritorio $template_escritorio;
    private SystemUsers $system_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agendamento_id');
        parent::addAttribute('template_escritorio_id');
        parent::addAttribute('system_user_id');
        parent::addAttribute('titulo');
        parent::addAttribute('template');
        parent::addAttribute('enviado_em');
        parent::addAttribute('tipo_mensagem');
    
    }

    /**
     * Method set_agendamento
     * Sample of usage: $var->agendamento = $object;
     * @param $object Instance of Agendamento
     */
    public function set_agendamento(Agendamento $object)
    {
        $this->agendamento = $object;
        $this->agendamento_id = $object->id;
    }

    /**
     * Method get_agendamento
     * Sample of usage: $var->agendamento->attribute;
     * @returns Agendamento instance
     */
    public function get_agendamento()
    {
    
        // loads the associated object
        if (empty($this->agendamento))
            $this->agendamento = new Agendamento($this->agendamento_id);
    
        // returns the associated object
        return $this->agendamento;
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
    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_system_user(SystemUsers $object)
    {
        $this->system_user = $object;
        $this->system_user_id = $object->id;
    }

    /**
     * Method get_system_user
     * Sample of usage: $var->system_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_system_user()
    {
    
        // loads the associated object
        if (empty($this->system_user))
            $this->system_user = new SystemUsers($this->system_user_id);
    
        // returns the associated object
        return $this->system_user;
    }

    /**
     * Method getMensagemAcaos
     */
    public function getMensagemAcaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('mensagem_id', '=', $this->id));
        return MensagemAcao::getObjects( $criteria );
    }

    public function set_mensagem_acao_mensagem_to_string($mensagem_acao_mensagem_to_string)
    {
        if(is_array($mensagem_acao_mensagem_to_string))
        {
            $values = Mensagem::where('id', 'in', $mensagem_acao_mensagem_to_string)->getIndexedArray('id', 'id');
            $this->mensagem_acao_mensagem_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_acao_mensagem_to_string = $mensagem_acao_mensagem_to_string;
        }

        $this->vdata['mensagem_acao_mensagem_to_string'] = $this->mensagem_acao_mensagem_to_string;
    }

    public function get_mensagem_acao_mensagem_to_string()
    {
        if(!empty($this->mensagem_acao_mensagem_to_string))
        {
            return $this->mensagem_acao_mensagem_to_string;
        }
    
        $values = MensagemAcao::where('mensagem_id', '=', $this->id)->getIndexedArray('mensagem_id','{mensagem->id}');
        return implode(', ', $values);
    }

    public function get_template_formatado()
    {
        $result = '';
    
        if ($this->titulo)
        {
            $result = "<b>{$this->titulo}</b><br/>";
        }
    
        if ($this->template)
        {
            $result .= nl2br($this->template);
        }
    
        return $result;
    }

    public function get_acoes_formatada()
    {
        $acoes = $this->getMensagemAcaos();
    
        if ($acoes)
        {
            $acoes_formatada = [];
        
            foreach($acoes as $acao)
            {
                $a = TElement::tag('a', '', ['href' => '#', 'style' => 'color: #007bff; pointer-events: none; display: flex; flex-direction: row; gap: 4px; align-items: center; width: fit-content;']);
                $a->add("<i class='fa fa-external-link-alt'></i>");
                $a->add($acao->label);
             
                $acoes_formatada[] = $a->getContents();
            }
        
            $acoes = implode('', $acoes_formatada);
        
            return '<div style="display: flex; gap: 2px; flex-direction: row; margin: 20px 0px;">'.$acoes.'</div>';
        }
    
        return '';
    }
            
}

