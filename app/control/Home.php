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
                /*
                    "Nao e cliente registrado" so quando o usuario existe e
                    ainda nao tem senha cadastrada. Usuario digitado errado ou
                    senha errada caem na mensagem generica: antes, um erro de
                    digitacao fazia o cliente achar que nao tinha acesso.
                */
                $tem_senha = false;
                $existe    = false;

                foreach (Pessoa::where('usuario', '=', $usuario)->load() as $cadastro)
                {
                    $existe = true;

                    if (trim((string) $cadastro->senha) !== '')
                    {
                        $tem_senha = true;
                    }
                }

                if ($existe && !$tem_senha)
                {
                    throw new Exception('Você ainda não é um cliente registrado. Verifique os dados informados!');
                }

                throw new Exception('Usuário ou senha incorretos.');
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