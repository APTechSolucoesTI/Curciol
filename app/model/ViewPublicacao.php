<?php

class ViewPublicacao extends TRecord
{
    const TABLENAME  = 'view_publicacao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('numero_arquivo');
        parent::addAttribute('numero_publicacao');
        parent::addAttribute('titulo');
        parent::addAttribute('texto');
        parent::addAttribute('cabecalho');
        parent::addAttribute('rodape');
        parent::addAttribute('numero_unico_processo');
        parent::addAttribute('numero_processo_principal');
        parent::addAttribute('data_tratamento');
        parent::addAttribute('data_disponibilizacao');
        parent::addAttribute('termo_ref_data');
        parent::addAttribute('prazo');
        parent::addAttribute('confirma_prazo');
        parent::addAttribute('data_entrega');
        parent::addAttribute('tipo_processo');
        parent::addAttribute('numero_cnj_numero');
        parent::addAttribute('numero_outro');
        parent::addAttribute('data_distribuicao_protocolo');
        parent::addAttribute('valor_causa');
        parent::addAttribute('gratuidade_processual');
        parent::addAttribute('observacao');
        parent::addAttribute('responsavel');
        parent::addAttribute('jornal');
        parent::addAttribute('tribunal');
        parent::addAttribute('vara');
        parent::addAttribute('foro');
        parent::addAttribute('comarca');
        parent::addAttribute('orgao');
        parent::addAttribute('envolvimento');
        parent::addAttribute('area');
        parent::addAttribute('assunto');
        parent::addAttribute('status');
        parent::addAttribute('processo_id');
        parent::addAttribute('etapa');
        parent::addAttribute('etapa_verificada');
    
    }

    public function get_titulo_formatado(){
        if($this->titulo){
            return str_replace(';','<br/>',$this->titulo);
        }
    }
    public function get_data_disponibilizacao_formatada(){
        if($this->data_disponibilizacao)
        {
            try
            {
                $date = new DateTime($this->data_disponibilizacao);
                $diaSemana = DateService::getDayWeek($this->data_disponibilizacao);
                $mes = DateService::getMonthName($this->data_disponibilizacao);
            
                return $diaSemana.", ".$date->format('d')." de ". $mes . " de ".$date->format('Y');
            }
            catch (Exception $e)
            {
                return $this->data_disponibilizacao;
            }
        }

        return $this->data_disponibilizacao;
    }
    
}

