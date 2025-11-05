<?php
require_once 'init.php';
$theme  = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
$public = in_array($class, $ini['permission']['public_classes']);

AdiantiCoreApplication::setRouter(array('AdiantiRouteTranslator', 'translate'));

new TSession;
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );
BuilderTranslator::setLanguage( TSession::getValue('user_language'), true );

$content = BuilderTemplateParser::init('layout');
$content = ApplicationTranslator::translateTemplate($content);

echo $content;

if (TSession::getValue('logged') OR $public)
{
    if ($class)
    {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
        
        if(TSession::getValue('userid')){
            TTransaction::open('escritorio');
            $preferencia = PreferenciaSistema::where('system_users_id','=',TSession::getValue('userid'))->first();
            
            if(!$preferencia){
                $preferencia = new PreferenciaSistema();
                $preferencia->system_users_id = TSession::getValue('userid');
                $preferencia->zoom = 100;
                $preferencia->menu_fixado = 0;
                $preferencia->store();
            }
            TTransaction::close();
            
            $zoomPreferenciaUsuario = $preferencia->zoom;
            $menu_fixado = $preferencia->menu_fixado;
            
            
            TScript::create('$("body").css("zoom","'.$zoomPreferenciaUsuario.'%");');
            
            
            if(isset($menu_fixado) && $menu_fixado == 1)
            {
                 echo "
                <script>
                    var element = document.querySelector('.sidebar-mini');
                
                    element.classList.add('fixed');
                </script>
                ";
            }
        }

        AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);
    }
}
else
{
    if (isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1')
    {
        if (!empty($ini['general']['public_entry']))
        {
            AdiantiCoreApplication::loadPage($ini['general']['public_entry'], '', $_REQUEST);
        }
    }
    else
    {
        AdiantiCoreApplication::loadPage('LoginForm', '', $_REQUEST);
    }
}
