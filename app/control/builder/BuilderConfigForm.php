<?php
/**
 * Curciol (09/11/2026): do formulario de configuracao do Mad Builder (editava arquivos de configuracao pelo navegador) fica so
 * o seletor de tema do usuario (BuilderTemplate.js chama setTheme).
 */
class BuilderConfigForm extends TPage
{
    public static function setTheme($param)
    {
        try
        {
            BuilderService::setTheme(TSession::getValue('userid'), $param['theme']);
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}
