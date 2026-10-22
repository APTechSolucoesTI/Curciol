<?php
// Curciol (seguranca 22/10/2026): instalador do Mad Builder desativado (sistema ja instalado; ultimo uso em 04/12/2025).
// Para reativar, remova este bloco.
http_response_code(404);
exit;
?>
<?php

require_once 'init.php';

new TSession;

$content = file_get_contents("install.html");

SystemPreferenceService::disable();

$content = BuilderTemplateParser::parse($content, 'theme3', 'install');

ob_start();


if(!TSession::getValue('database_install_logged'))
{ 
    $form = DatabaseInstall::getPasswordForm();
    $form->show();
}
else
{
    $form = new ExtensionsInstall();
    //$form->setIsWrapped(true);
    $form->show();
}

$formContent = $mainContent = ob_get_contents();
ob_end_clean();

$content  = str_replace('{$content}', $formContent, $content);

echo $content;