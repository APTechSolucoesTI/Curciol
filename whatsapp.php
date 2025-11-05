<?php
require_once 'init.php';

$input    = json_decode(file_get_contents("php://input"), true);
$request  = array_merge($_REQUEST, (array) $input);

$headers  = AdiantiCoreApplication::getHeaders();


file_put_contents('log_whatsapp.txt', json_encode($headers) . PHP_EOL. PHP_EOL. json_encode($request). PHP_EOL. PHP_EOL, FILE_APPEND);

if (! empty($request['hub_challenge']) && $request['hub_verify_token'] == 'bescritorio_whatsapp_token_95' )
{
    echo $request['hub_challenge']??'';
}
else
{
    echo 'permission denied';
}
