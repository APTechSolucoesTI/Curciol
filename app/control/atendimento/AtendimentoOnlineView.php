<?php

class AtendimentoOnlineView extends TPage
{
    private $iframe;
    
    public function __construct($param)
    {
        parent::__construct();
        
        $this->iframe = new TElement('iframe');
        $this->iframe->style = 'width: 100%; height: calc(100vh - var(--header-height) - 30px );';
        
        parent::add($this->iframe);
    }
    
    public function onShow($param = null)
    {
        try
        {
            TTransaction::open('escritorio');
            
            if (empty($param['code']))
            {
                throw new Exception('Código não informado');
            }
            
            $agendamento = Agendamento::where('link_atendimento_online', '=', $param['code'])->first();
            
            if (empty($agendamento))
            {
                throw new Exception('Agendamento não encontrado');
            }
            
            $src = 'https://meet.jit.si/' . $agendamento->link_atendimento_online . "#userInfo.displayName=\"{$agendamento->cliente->nome}\"";
            
            $this->iframe->src = $src;
            
            TTransaction::rollback();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
            
            new TMessage('error', $e->getMessage());
        }
    }
}
