<?php

class Home extends TPage
{
    private $html;
    
    public function __construct($param)
    {
        parent::__construct();
        
        $this->html = new THtmlRenderer('app/resources/home.html');
        $this->html->enableSection('main');
        
        parent::add($this->html);
    }
    
    public function onDocumentos($param)
    {
        try
        {
            TTransaction::open('escritorio');
            
            if (! empty(TSession::getValue('portal_cliente_id')))
            {
                TApplication::loadPage('MeusDocumentoList', 'onShow', ['register_state' => 'false']);
            }
            else
            {
                if (empty($param['usuario']) &&  empty($param['senha']))
                {
                    $form = new BootstrapFormBuilder('input_form');
                    $form->setFieldSizes('100%');
            
                    $login = new TEntry('usuario');
                    $pass  = new TPassword('senha');
                    
                    $form->addFields( [new TLabel('Usuário'), $login]);
                    $form->addFields( [new TLabel('Senha'), $pass]);
                    
                    $form->addAction('Entrar', new TAction([__CLASS__, 'onDocumentos']), 'fa:sign-in-alt green');
                    
                    new TInputDialog('Informe o seu usuário e senha', $form);
                }
                else
                {
                    $pessoa = Pessoa::where('usuario', '=', $param['usuario'])->where('senha', '=', $param['senha'])->first();
                    
                    if (empty($pessoa))
                    {
                        throw new Exception('Você ainda não é um cliente registrado. Verifique os dados informado!');
                    }
                    
                    TSession::setValue('portal_cliente_id', $pessoa->id);
                    
                    TApplication::loadPage('MeusDocumentoList', 'onShow', ['register_state' => 'false']);
                }
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
    
    // função executa ao clicar no item de menu
    public function onShow($param = null)
    {
        
    }
}
