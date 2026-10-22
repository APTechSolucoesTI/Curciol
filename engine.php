<?php
require_once 'init.php';

AdiantiCoreApplication::setRouter(array('AdiantiRouteTranslator', 'translate'));

class TApplication extends AdiantiCoreApplication
{
    public static function run($debug = null)
    {
        new TSession;
        ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true ); // multi-lang
        BuilderTranslator::setLanguage( TSession::getValue('user_language'), true ); // multi-lang
        
        

        if ($_REQUEST)
        {
            $ini = AdiantiApplicationConfig::get();
            
            $class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
            // Curciol (seguranca 22/10/2026): telas do instalador desativadas (sistema ja instalado). Continuam em
            // public_classes no application.ini, que e preservado em producao; por isso o bloqueio fica aqui.
            if (in_array($class, ['DatabaseInstall', 'ExtensionsInstall', 'PathInstall', 'SummaryDatabaseInstall']))
            {
                http_response_code(404);
                exit;
            }
            $public = in_array($class, $ini['permission']['public_classes']);
            $public_mobile = in_array($class, array_keys($ini['user_public_pages']??[]));
            $debug  = is_null($debug)? $ini['general']['debug'] : $debug;
            if (TSession::getValue('logged')) // logged
            {
                $programs = (array) TSession::getValue('programs'); // programs with permission
                $programs = array_merge($programs, self::getDefaultPermissions());
                
                if( isset($programs[$class]) OR $public OR $public_mobile)
                {
                    // aba aberta de novo pelo menu (depois de fechada): a tela comeca sem filtros (curciol-navegacao.js);
                    // restaurar as abas guardadas (adianti_reload_tab) mantem os filtros
                    if (!empty($_REQUEST['curciol_limpar_filtros']) && empty($_REQUEST['adianti_reload_tab']))
                    {
                        FiltroLista::limparSessao($class);
                    }
                    parent::run($debug);
                }
                else
                {
                    http_response_code(401);
                    new TMessage('error', _t('Permission denied') );
                }
            }
            else if ($class == 'LoginForm' OR $public )
            {
                // Curciol (seguranca 22/10/2026): parte das classes publicas e da equipe (abertas dentro de outras telas)
                // ou do portal do cliente. Sem login, as da equipe sao recusadas e as do portal exigem cliente logado.
                // Para quem esta logado nada muda.
                $somente_equipe = ['ProcessoPublicacoesForm', 'PublicacaoAlterEtapaForm', 'AndamentoAlterEtapaForm',
                                   'ContratoDateForm', 'ParceirosHeaderList', 'RequisicaoPagamentoVisualizacaoAba',
                                   'RequisicaoPagamentoRelatorioDinamico', 'Teste471',
                                   'SystemModulesCheckView', 'SystemRegistrationForm'];
                $somente_portal = ['ProcessosFormView', 'ProcessoViewHeaderList', 'ProcessosFormViewInterno',
                                   'ProcessoPublicacoesTimeLine', 'MeusDocumentoList'];
                if (in_array($class, $somente_equipe) OR (in_array($class, $somente_portal) AND empty(TSession::getValue('portal_cliente_id'))))
                {
                    http_response_code(401);
                    new TMessage('error', _t('Permission denied'), new TAction(array('LoginForm','onLogout')) );
                    return;
                }
                parent::run($debug);
            }
            else
            {
                http_response_code(401);
                new TMessage('error', _t('Permission denied'), new TAction(array('LoginForm','onLogout')) );
            }
        }
    }
    
    /**
     * Return default programs for logged users
     */
    public static function getDefaultPermissions()
    {
        // Curciol (seguranca 22/10/2026): telas de sincronizacao/diff do Mad Builder sairam desta lista (o sistema nao usa
        // mais o Mad Builder). BuilderConfigForm fica pelo seletor de tema (setTheme).
        return array('Adianti\Base\TStandardSeek' => TRUE,
                     'LoginForm' => TRUE,
                     'AdiantiMultiSearchService' => TRUE,
                     'AdiantiUploaderService' => TRUE,
                     'AdiantiAutocompleteService' => TRUE,
                     'EmptyPage' => TRUE,
                     'MessageList' => TRUE,
                     'SystemDocumentUploaderService' => TRUE,
                     'NotificationList' => TRUE,
                     'SearchBox' => TRUE,
                     'SearchInputBox' => TRUE,
                     'BuilderConfigForm' => TRUE,
                     'BuilderCustomCssService' => TRUE);
    } 
}

TApplication::run();
