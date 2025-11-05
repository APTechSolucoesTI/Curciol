<?php
require_once 'init.php';
$theme  = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : 'Home';
$public = in_array($class, $ini['permission']['public_classes']);

new TSession;
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );
BuilderTranslator::setLanguage( TSession::getValue('user_language'), true );

$content = file_get_contents('app/resources/home_public.html');

echo $content;

$method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;

$query = AdiantiCoreApplication::buildHttpQuery($class, $method, $_REQUEST);
$query = str_replace('index.php', 'public.php', $query);

$_REQUEST['register_state'] = 'false';
AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);

AdiantiCoreApplication::registerPage($query);