<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 下午2:40
 */

namespace Vanilla\Http;

use Vanilla\Http\Cookie;
use Vanilla\ParameterBag;

class Response
{
    protected $cookies = [];
    protected $content;
    protected $headers;
    protected $statusCode;

    public function __construct($content = '', $status = 200, array $headers = [])
    {
        $this->headers = new ParameterBag($headers);
        $this->statusCode = $status;
        $this->content = $content;
    }

    public function send()
    {
        foreach ($this->headers->all() as $key => $value) {
            header($key . ': ' . $value, false, $this->statusCode);
        }

        // status
        header(sprintf('HTTP/%s %s %s', '1.0', $this->statusCode, ''), true, $this->statusCode);

        foreach ($this->cookies as $cookie) {
            /**
             * @var $cookie Cookie
             */
            setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpire(), $cookie->getPath(), $cookie->getDomain(), $cookie->getSecure(), $cookie->getHttpOnly());
        }

        echo $this->content;
    }

    public function redirect($to, $code = 302)
    {
        $this->statusCode = $code;
        $this->headers->set('Location', $to);
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setHeaders($header)
    {
        foreach ($header as $key => $value) {
            $this->headers->set($key, $value);
        }

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setCookies($cookie)
    {
        $this->cookies[] = $cookie;
        return $this;
    }

    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function json($content, $code = 200)
    {
        $this->headers->set('Content-Type', 'application/json');
        $this->content = is_array($content)?json_encode($content):$content;
        $this->statusCode = $code;
        return $this;
    }
}