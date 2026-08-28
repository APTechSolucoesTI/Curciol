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
    
    public static function onDocumentos($param)
    {
        try
        {
            TTransaction::open('escritorio');

            $usuario = trim($param['usuario'] ?? '');
            $senha   = trim($param['senha'] ?? '');

            if (empty($usuario) || empty($senha))
            {
                throw new Exception('Informe seu usuário e senha para acessar seus processos.');
            }

            $pessoa = Pessoa::where('usuario', '=', $usuario)
                            ->where('senha', '=', $senha)
                            ->first();

            if (empty($pessoa))
            {
                throw new Exception('Você ainda não é um cliente registrado. Verifique os dados informados!');
            }

            TSession::setValue('portal_cliente_id', $pessoa->id);

            TTransaction::close();

            TApplication::loadPage('ProcessosFormView', 'onShow', [
                'key' => $pessoa->id
            ]);
        }
        catch (Exception $e)
        {
            try {
                TTransaction::rollback();
            } catch (Exception $rollbackException) {
            }

            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onShow($param = null)
    {
        
    }
}