<?php
/**
 * SystemPermission
 *
 * @version    1.0
 * @package    model
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemPermission
{
    public static function checkPermission($action)
    {
        // Curciol (09/11/2026): o que vale sem permissao de grupo fica no AcessoPublicoService (nao no public_classes do ini)
        $programs = TSession::getValue('programs');
        $action = (string) $action;

        return ( (isset($programs[$action]) AND $programs[$action]) OR
                 (TSession::getValue('logged') ? AcessoPublicoService::logadoPode($action)
                                               : AcessoPublicoService::anonimoPode($action, AcessoPublicoService::portalLogado())) );
    } 
}
