<?php
namespace SITE\Controller;
use GuzzleHttp\Psr7\Request;
use Pimple\Container;
use SITE\Tools\Encoding;
/**
 * Alla Controllers ska extenda denna d� har dom alltid tillg�ng till continer och parametrar
 * Eftersom dom extendar kan dom ropa med hj�lp av $this->X
 * Exempel:
 * $this->params['min_post_variabel']
 * $this->container['X'] : vad nu containern har
 */
abstract class AbstractController
{
    protected $container; // Tillg�nglig f�r alla som extendar denna Abstracta Controller
    protected $params; // Tillg�nglig f�r alla som extendar denna Abstracta Controller
    public function __construct(Container $container, Request $request)
    {
        $this->container  = $container;
        $this->params     = $this->getRequestData($request);
        // Coors - Bara f�r att vi ska kunna komma �t dom fr�n andra tj�nster
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: X-Requested-With');
    }
    /**
     * Rendera en output i JSON
     * Tillg�nglig f�r alla som extendar denna Abstracta Controller
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
     * Koller igenom all post/get/json-data som kommer med requesten och samlar allt p� samma s�tt
     * Endast denna class kan anv�nda denna
     */
    private function getRequestData($request)
    {
        $body         = $request->getBody();
        $query_params = $request->getQueryParams() ?? [];
        $post_params  = json_decode($body, true)   ?? [];
        $post_parsed  = $request->getParsedBody()  ?? [];
        // F�r att tydligen s� skickar den med under arrayer som json igen i nestade get requests
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
     * Anv�nds bara av ovan funktion f�r att avg�ra om det �r json eller inte
     * Endast denna class kan anv�nda denna
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