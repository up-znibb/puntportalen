<?php
namespace SITE\Controller;
use GuzzleHttp\Psr7\Request;
use Pimple\Container;
use SITE\Tools\Encoding;
/**
 * Alla Controllers ska extenda denna då har dom alltid tillgång till continer och parametrar
 * Eftersom dom extendar kan dom ropa med hjälp av $this->X
 * Exempel:
 * $this->params['min_post_variabel']
 * $this->container['X'] : vad nu containern har
 */
abstract class AbstractController
{
    protected $container; // Tillgänglig för alla som extendar denna Abstracta Controller
    protected $params; // Tillgänglig för alla som extendar denna Abstracta Controller
    public function __construct(Container $container, Request $request)
    {
        $this->container  = $container;
        $this->params     = $this->getRequestData($request);
        // Coors - Bara för att vi ska kunna komma åt dom från andra tjänster
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: X-Requested-With');
    }
    /**
     * Rendera en output i JSON
     * Tillgänglig för alla som extendar denna Abstracta Controller
     *
     * @param array $data
     */
    protected function renderJson($data = [])
    {
        if (!is_array($data)) {
            $data = [$data];
        }
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        $data = Encoding::toUTF8($data);
        echo json_encode($data);
        exit;
    }
    /**
     * Koller igenom all post/get/json-data som kommer med requesten och samlar allt på samma sätt
     * Endast denna class kan använda denna
     */
    private function getRequestData($request)
    {
        $body         = $request->getBody();
        $query_params = $request->getQueryParams() ?? [];
        $post_params  = json_decode($body, true)   ?? [];
        $post_parsed  = $request->getParsedBody()  ?? [];
        // För att tydligen så skickar den med under arrayer som json igen i nestade get requests
        foreach ($query_params as $key => $val) {
            if ($this->isJson($val)) {
                $query_params[$key] = json_decode($val, true);
            }
        }
        $merged = array_replace([], $query_params);
        $merged = array_replace($merged, $post_params);
        $merged = array_replace($merged, $post_parsed);
        $merged = Encoding::toLatin1($merged);
        return $merged;
    }
    /**
     * Används bara av ovan funktion för att avgöra om det är json eller inte
     * Endast denna class kan använda denna
     */
    private function isJson($string)
    {
        if (is_array($string)) { // En GET-request kan skicka in array
            return false;
        }
        $decoded = json_decode($string);
        if (!is_object($decoded) && !is_array($decoded)) {
            return false;
        }
        return json_last_error() == JSON_ERROR_NONE;
    }
}