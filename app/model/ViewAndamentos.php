<?php

class ViewAndamentos extends TRecord
{
    const TABLENAME  = 'view_andamentos';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('titulo');
        parent::addAttribute('texto');
        parent::addAttribute('dt');
        parent::addAttribute('numero');
        parent::addAttribute('tipo_processo_id');
        parent::addAttribute('tipo_processo_nome');
        parent::addAttribute('jornal_tipo_id');
        parent::addAttribute('processo_id');
        parent::addAttribute('keyprocesso_id');
        parent::addAttribute('origem');
        parent::addAttribute('key_jornal_tipo');
        parent::addAttribute('jornal_tipo');
        parent::addAttribute('publicacao_etapa_id');
        parent::addAttribute('etapa_verificada');
    
    }

    public function get_titulo_formatado(){
        if($this->titulo){
            return str_replace(';','<br/>',$this->titulo);
        }
    }
    public function get_dt_formatada(){
        if($this->dt)
        {
            try
            {
                $date = new DateTime($this->dt);
                $diaSemana = DateService::getDayWeek($this->dt);
                $mes = DateService::getMonthName($this->dt);
            
                return $diaSemana.", ".$date->format('d')." de ". $mes . " de ".$date->format('Y');
            }
            catch (Exception $e)
            {
                return $this->dt;
            }
        }

        return $this->dt;
    }
    public function get_texto_caracteres(){
        if(strlen($this->texto)>500)
        {
            return substr($this->texto, 0, 500);
        }else{
            return $this->texto;
        }
    }

    /*
        Titulo do balao de hover da aba Andamentos: o nome da etapa, com o
        texto da publicacao/andamento logo abaixo. Sem etapa verificada
        continua "Texto", como era antes.
    */
    public function get_popover_titulo(){
        if(strtoupper(trim((string) $this->etapa_verificada)) == 'S' && !empty($this->publicacao_etapa_id))
        {
            $etapa = PublicacaoEtapa::find($this->publicacao_etapa_id);

            if($etapa && trim((string) $etapa->etapa_nome) !== '')
            {
                return htmlspecialchars(trim((string) $etapa->etapa_nome), ENT_QUOTES, 'UTF-8');
            }
        }

        return 'Texto';
    }

}

