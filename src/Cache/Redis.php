<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/25
 * Time: 上午10:58
 */

namespace Vanilla\Cache;


use Predis\Client;
use Vanilla\Contracts\Cache\Cache;

class Redis implements Cache
{
    protected $redis;

    protected $prefix;

    public function __construct(Client $redis, $prefix = '')
    {
        $this->redis = $redis;
        $this->prefix = $prefix . ':';
    }

    public function get($key)
    {
        $value = $this->redis->get($this->getPrefix($key));

        if (null !== $value) {
            return is_numeric($value) ? $value : unserialize($value);
        }

        return null;
    }

    public function put($key, $value, $minutes)
    {
        $value = is_numeric($value) ? $value : serialize($value);

        return $this->redis->setex($this->getPrefix($key), (int)max(1, $minutes * 60), $value);
    }

    public function increment($key, $value = 1)
    {
        return $this->redis->incrby($this->getPrefix($key), $value);
    }

    public function decrement($key, $value = 1)
    {
        return $this->redis->decrby($this->getPrefix($key), $value);
    }

    public function forever($key, $value)
    {
        $value = is_numeric($value) ? $value : serialize($value);

        $this->redis->set($this->prefix . $key, $value);
    }

    public function forget($keys)
    {
        $delete = [];
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $delete[] = [$this->getPrefix($key)];
            }
        } else {
            $delete = [$this->getPrefix($keys)];
        }

        return (bool)$this->redis->del($delete);
    }

    protected function getPrefix($key = null)
    {
        return $this->prefix . (null !== $key ? $key : '');
    }

    public function getDrive()
    {
        return $this->redis;
    }

    public function __call($name, $arguments)
    {
        $cache = $this->redis;
        return call_user_func_array([$cache, $name], $arguments);
    }
}