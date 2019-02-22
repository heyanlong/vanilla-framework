<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/9/17
 * Time: 下午7:30
 */

namespace Vanilla\Database;


class Model
{
    protected $query;
    protected $connect = 'default';
    protected $tableName = '';

    protected function newQuery()
    {
        $this->query = new Builder();
        $this->query->connect($this->connect);
        $this->query->table($this->tableName);
        return $this->query;
    }

    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return call_user_func_array([$this, $method], $parameters);
        }

        $query = $this->newQuery();

        return call_user_func_array([$query, $method], $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new static;
        return call_user_func_array([$instance, $method], $parameters);
    }
}