<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/24
 * Time: 下午5:09
 */

namespace Vanilla\Http;


class Cookie
{
    protected $name;
    protected $value;
    protected $expire;
    protected $path;
    protected $domain;
    protected $secure;
    protected $httpOnly;

    public function __construct($name, $value, $expire = 60 * 30, $path = '/', $domain = '', $secure = false, $httpOnly = true)
    {
        $this->name = $name;
        $this->value = $value;
        $this->expire = $expire;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getExpire()
    {
        return time() + $this->expire;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getSecure()
    {
        return $this->secure;
    }

    public function getHttpOnly()
    {
        return $this->httpOnly;
    }
}