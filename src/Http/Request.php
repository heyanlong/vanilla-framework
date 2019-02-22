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

    public function getContent($asResource = false)
    {
        $currentContentIsResource = is_resource($this->content);

        if (true === $asResource) {
            if ($currentContentIsResource) {
                rewind($this->content);

                return $this->content;
            }

            // Content passed in parameter (test)
            if (is_string($this->content)) {
                $resource = fopen('php://temp', 'r+');
                fwrite($resource, $this->content);
                rewind($resource);

                return $resource;
            }

            $this->content = false;

            return fopen('php://input', 'rb');
        }

        if ($currentContentIsResource) {
            rewind($this->content);

            return stream_get_contents($this->content);
        }

        if (null === $this->content || false === $this->content) {
            $this->content = file_get_contents('php://input');
        }
        return $this->content;
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
            $this->requestUri = $this->server->get('REQUEST_URI');
        }
        return $this->requestUri;
    }

    public function setContext($key, $val)
    {
        $this->context[$key] = $val;
    }

    public function getContext($key, $default = null)
    {
        if (is_null($key)) {
            return $this->context;
        } elseif (isset($this->context[$key])) {
            return $this->context[$key];
        } elseif (!is_null($default)) {
            return $default;
        }
        return null;

    }

    /**
     * Gets the HTTP headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = array();
        $contentHeaders = array('CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true);
        $parameters = $this->server->all();

        foreach ($parameters as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } // CONTENT_* are not prefixed with HTTP_
            elseif (isset($contentHeaders[$key])) {
                $headers[$key] = $value;
            }
        }

        if (isset($parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($parameters['PHP_AUTH_PW']) ? $parameters['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */

            $authorizationHeader = null;
            if (isset($parameters['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }

            if (null !== $authorizationHeader) {
                if (0 === stripos($authorizationHeader, 'basic ')) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (2 == count($exploded)) {
                        list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                    }
                } elseif (empty($parameters['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest '))) {
                    // In some circumstances PHP_AUTH_DIGEST needs to be set
                    $headers['PHP_AUTH_DIGEST'] = $authorizationHeader;
                    $parameters['PHP_AUTH_DIGEST'] = $authorizationHeader;
                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     *      I'll just set $headers['AUTHORIZATION'] here.
                     *      http://php.net/manual/en/reserved.variables.server.php
                     */
                    $headers['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }

        if (isset($headers['AUTHORIZATION'])) {
            return $headers;
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        } elseif (isset($headers['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }

        return $headers;
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

        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[$key = str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
            } // drop HTTP_ prefixed;'_' to '-';strtolower
        }
        return $headers;
    }
}