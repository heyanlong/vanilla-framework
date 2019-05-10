<?php

namespace Vanilla\Database;


/**
 * @method static Builder where($query, ...$values)
 * @method static Builder order($value)
 * @method static Builder update(...$attrs)
 * @method static Builder updates($values)
 * @method static Builder begin()
 * @method static bool commit()
 * @method static bool rollBack()
 * @method static Builder limit($limit)
 * @method static Builder offset($offset)
 * @method static first(...$where)
 * Class Model
 * @package Vanilla\Database
 */
class Model
{
    const SOFT_ROLE_DELETE = 0;
    const SOFT_ROLE_ACTIVE = 1;

    private $query;
    protected $connection = 'default';
    protected $tableName = '';
    protected $primaryKey = 'id';
    public $exists = false;
    protected $attributes;
    protected $original;

    public $softDelete = null;
    public $softDeleteRole = [
        self::SOFT_ROLE_DELETE => 1,
        self::SOFT_ROLE_ACTIVE => 0
    ];

    protected function newQuery()
    {
        $this->query = new Builder();
        $this->query->setModel($this);
        return $this->query;
    }

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @param string $connection
     */
    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function setAttributes(array $attr)
    {
        $this->attributes = $attr;
        $this->original = $attr;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getOriginal()
    {
        return $this->original;
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        return;
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

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }
}