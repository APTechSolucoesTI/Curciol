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

            // login: usuario = CPF/CNPJ so com numeros (o campo pode vir com pontuacao)
            $documento = preg_replace('/\D/', '', (string) ($param['usuario'] ?? ''));
            $senha     = trim($param['senha'] ?? '');

            if ($documento === '' || $senha === '')
            {
                throw new Exception('Informe seu CPF e senha para acessar seus processos.');
            }

            PortalLoginLimite::exigirLiberado($documento);

            $pessoa = Pessoa::where('usuario', '=', $documento)
                            ->where('senha', '=', $senha)
                            ->orderBy('id')
                            ->first();

            if (empty($pessoa))
            {
                PortalLoginLimite::registrarFalha($documento);
                /*
                    "Nao e cliente registrado" so quando o usuario existe e
                    ainda nao tem senha cadastrada. Usuario digitado errado ou
                    senha errada caem na mensagem generica: antes, um erro de
                    digitacao fazia o cliente achar que nao tinha acesso.
                */
                $tem_senha = false;
                $existe    = false;

                foreach (Pessoa::where('usuario', '=', $documento)->load() as $cadastro)
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

                throw new Exception('CPF ou senha incorretos.');
            }

            PortalLoginLimite::limpar($documento);
            if (session_status() == PHP_SESSION_ACTIVE) { TSession::regenerate(); } // Curciol (seguranca 22/10/2026): sessao nova ao entrar no portal
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