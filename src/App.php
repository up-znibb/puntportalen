<?php

namespace SITE;

use FastRoute\RouteCollector;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Pimple\Container;

/**
 * Mini app
 * Använder router som alltid skickar vidare container
 * Container innehåller dom classer som ofta användas av flera funktioner och därmed startas upp när dom behövs,
 * och bara en gång istället för att behöva ropa in dom överallt
 */

class App
{
    private $container;
    private $request;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request   = ServerRequest::fromGlobals();
    }

    /**
     * Routning för denna site
     */
    public function routes()
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            // Routning utan grupp (URL: /startsida)
            $r->addRoute('GET', '/', ['SITE\Controller\PageController', 'home']);

            /**
             * Exempel på URL som läser ut del av URL som ett ID.
             * Accepterar bara siffror (URL: /test/123)
             * Regex kan användas för att bestämma vad en URL får ta emot
             * Exempel på regex:
             * /test/{type:[a-zA-Z]+} : Accepterar bara bokstäver, tex. /test/HejHej - blir parametern function($type) till funktionen
             * /test/{type:[A-z]+}/{id:[0-9]+} : tex. /test/mintyp/123 - blir parametrana function($type, $id) till funktionen
             * /test/{path:.*} : Acccepetrar vad som helst som sista del av URL
             */
        });
    }

    /**
     * Standardfunktion för att hantera routningen
     * Inget här ska behöva ändras, om det inte ska vara api-key-krav
     */
    public function run()
    {
        // Krav på API-KEY
        // $this->checkApiKey();

        // Matcha mot inlagda Routes
        $status       = false;
        $dispatcher   = $this->routes();
        $uri          = rawurldecode($this->request->getUri()->getPath());
        $route        = $dispatcher->dispatch($this->request->getMethod(), $uri);

        if ($this->request->getMethod() == 'OPTIONS') {
            header('Access-Control-Allow-Headers: *');
            header('Access-Control-Allow-Origin: *');
            header('HTTP/1.1 200 OK');
            exit;
        }

        switch ($route[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                http_response_code(404);

                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                http_response_code(405);

                break;
            case \FastRoute\Dispatcher::FOUND:
                $controller = $route[1][0];
                $method     = $route[1][1];
                $parameters = $route[2];

                $response      = new Response();
                $this->request = $this->request->withAttribute('Controller', $controller);
                $this->request = $this->request->withAttribute('Method', $method);
                $this->request = $this->request->withAttribute('Parameters', implode(',', $parameters));

                try {
                    $app    = (new $controller($this->container, $this->request));
                    $status = $app->{$method}(...array_values($parameters));
                } catch (\Exception $e) {
                    http_response_code(500);
                    echo json_encode([
                        'error'   => true,
                        'message' => $e->getMessage(),
                    ]);
                }
                if ($status === 404) {
                    http_response_code(404);
                }

                break;
        }
    }
}
