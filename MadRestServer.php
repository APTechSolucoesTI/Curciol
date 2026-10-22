<?php
// Curciol (seguranca 22/10/2026): servidor REST do Mad Builder desativado. O sistema nao usa mais o Mad Builder nem a API REST/mobile
// (nenhum acesso em producao desde dez/2025). Para reativar, remova este bloco.
http_response_code(404);
exit;
?>
<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

require_once 'init.php';

use Mad\Rest\Request;
use Mad\Rest\Router;
use Mad\Rest\Response;
use Mad\Rest\ResponseInterface;
use Mad\Rest\RouteServiceProvider;

class MadRestServer
{
    /**
     * Run the REST server
     *
     * @return mixed Response from the route
     */
    public static function run()
    {
        try
        {
            // Boot route service provider to load all routes
            $routeProvider = new RouteServiceProvider();
            $routeProvider->boot();
            
            $request = new Request;

            // Validate the request against registered routes
            $validationResult = Router::validate($request);
            
            // If middleware returned a response, return it
            if ($validationResult !== null) {
                if ($validationResult instanceof ResponseInterface) {
                    return $validationResult->parse();
                }
                return $validationResult;
            }

            // Execute the matched route
            $result = Router::execute($request);
            
            // Parse the result if it's a ResponseInterface
            if ($result instanceof ResponseInterface) {
                return $result->parse();
            }
            
            return $result;
        }
        catch (Exception $e)
        {
            $response = new Response();
            return $response->json(['error' => $e->getMessage()], $e->getCode() ?: 500)->parse();
        }
        catch (Error $e)
        {
            $response = new Response();
            return $response->json(['error' => $e->getMessage()], 500)->parse();
        }
    }
}

print MadRestServer::run();