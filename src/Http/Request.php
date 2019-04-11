<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 上午11:50
 */

namespace Vanilla\Http;


use Vanilla\Contracts\Stream\Input;
use Vanilla\ParameterBag;

class Request implements Input
{
    private $request;
    private $query;
    private $cookies;
    private $headers;
    private $server;
    private $context;
    private $content;
    private $json;

    private $method;

    private $requestUri;

    /**
     * @return Request
     */
    public static function capture()
    {
        return (new static)->createRequestFrom($_GET, $_POST, $_COOKIE, $_SERVER);
    }

    public function createRequestFrom(array $query = array(), array $request = array(), array $cookies = [], array $server = [], $content = null)
    {
        $this->query = new ParameterBag($query);
        $this->server = new ParameterBag($server);
        $this->request = new ParameterBag($request);
        $this->cookies = new ParameterBag($cookies);
        $this->content = $content;
        $this->json = new ParameterBag((array)json_decode($this->getContent(), true));
        $this->headers = new ParameterBag($this->buildHeader());
        return $this;
    }

    public function json($key = null, $default = null)
    {
        if (!isset($this->json)) {
            $this->json = new ParameterBag((array)json_decode($this->getContent(), true));
        }

        if (is_null($key)) {
            return $this->json;
        }

        return $this->json->all()[$key] ?? $default;
    }

    public function getContent()
    {
        if ($this->content) {
            return $this->content;
        }
        return file_get_contents('php://input');
    }

    public function get($key, $default = null)
    {
        if (null !== $result = $this->query->get($key)) {
            return $result;
        }

        if (null !== $result = $this->request->get($key)) {
            return $result;
        }
        if (null !== $result = $this->json->get($key)) {
            return $result;
        }

        return $default;
    }

    public function all()
    {
        return $this->query->all() + $this->request->all() + $this->json->all();
    }

    public function cookie($key = null, $default = null)
    {
        if (null === $key) {
            return $this->cookies->all();
        }

        return $this->cookies->get($key, $default);
    }

    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
        }
        return $this->method;
    }

    public function getPathInfo()
    {
        $requestUri = $this->getRequestUri();

        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        if ($requestUri !== '' && $requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }
        return $requestUri;
    }

    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            if (env('PHPUNIT_MODE', false) == false && php_sapi_name() == 'cli') {
                $this->requestUri = app('command');
            } else {
                $this->requestUri = $this->server->get('REQUEST_URI');
            }
        }
        return explode('?', $this->requestUri)[0];
    }

    public function header($key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        return $this->headers->get(strtolower($key), $default);
    }

    public function isAjax()
    {
        $xmlHttp = $this->server->get('HTTP_X_REQUESTED_WITH', 'http');
        return strtolower($xmlHttp) === 'xmlhttprequest';
    }

    private function buildHeader()
    {
        $headers = [];

        foreach ($this->server->all() as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[$key = str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
            } // drop HTTP_ prefixed;'_' to '-';strtolower
        }
        return $headers;
    }
}