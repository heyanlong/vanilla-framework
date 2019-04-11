<?php

namespace Vanilla\Database;

use Vanilla\Exceptions\DBException;

class QuickTools
{

    /**
     * 新增
     * @param array $data
     * @return bool|$id
     */
    public static function insert($data = [])
    {
        if (count($data) == count($data, 1)) {
            $data = [$data];
        }

        $keys = array_keys($data[0]);

        $sql = 'INSERT INTO `' . static::$tableName . '` (`' . implode('`,`', $keys) . '`) values';

        $sqlBinds = [];
        foreach ($data as $item) {
            $sql .= '(';
            foreach ($keys as $key) {
                $bindRand = ':insert' . $key . rand();
                $sql .= $bindRand . ',';
                $sqlBinds[$bindRand] = $item[$key];
            }
            $sql = rtrim($sql, ',');
            $sql .= '),';
        }
        $sql = rtrim(trim($sql), ',');

        try {
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare($sql);//创建PDO预处理对象
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                return $pdo->lastinsertid();
            }
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage());
        }
    }

    /**
     * 修改
     * @param array $data
     * @param array|string $where
     * @param array $binds
     * @return bool
     */
    public static function update($data, $where, $binds = [])
    {

        try {
            $setBinds = [];
            $setSql = '';
            [$whereBinds, $whereSql] = static::_bindWhere($where, $binds);

            foreach ($data as $key => $value) {
                $bindKey = ':set_' . $key;
                $setSql .= '`' . $key . '` = ' . $bindKey . ', ';
                $setBinds[$bindKey] = $value;
            }
            $setSql = rtrim(trim($setSql), ',');

            $sql = ['update `' . static::$tableName . '` set', $setSql, $whereSql];
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare(implode(' ', $sql));
            $sqlBinds = array_merge($setBinds, $whereBinds);
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 对数据表某个字段减少某个值
     * @param array $data
     * @param array|string $where
     * @param array $binds
     * @param array $value
     * @return bool
     */
    public static function decrement($field,$where,$binds=[], $value=1)
    {
        try {
            $setBinds = [];
            $setSql = '';
            [$whereBinds, $whereSql] = static::_bindWhere($where, $binds);

            $bindKey = ':set_' . $field;
            $setBinds[$bindKey] = abs(intval($value));
            $setSql .= '`' . $field . '` = `'.$field.'` - ' . $bindKey . ', ';
            $setSql = rtrim(trim($setSql), ',');

            $sql = ['update `' . static::$tableName . '` set', $setSql, $whereSql];
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare(implode(' ', $sql));
            $sqlBinds = array_merge($setBinds, $whereBinds);
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 对数据表某个字段增加某个值
     * @param array $data
     * @param array|string $where
     * @param array $binds
     * @param array $value
     * @return bool
     */
    public static function increment($field,$where,$binds=[], $value=1)
    {
        try {
            $setBinds = [];
            $setSql = '';
            [$whereBinds, $whereSql] = static::_bindWhere($where, $binds);

            $bindKey = ':set_' . $field;
            $setBinds[$bindKey] = abs(intval($value));
            $setSql .= '`' . $field . '` = `'.$field.'` + ' . $bindKey . ', ';
            $setSql = rtrim(trim($setSql), ',');

            $sql = ['update `' . static::$tableName . '` set', $setSql, $whereSql];
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare(implode(' ', $sql));
            $sqlBinds = array_merge($setBinds, $whereBinds);
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 执行一个count
     * @param $where
     * @param array $binds
     * @return mixed
     */
    public static function count($where, $binds = [])
    {
        [$sqlBinds, $whereSql] = static::_bindWhere($where, $binds);
        $sql = ['select count(*) as _c from `' . static::$tableName . '`', $whereSql];
        try {
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare(implode(' ', $sql));
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute(); //执行新增操作
            return $stmt->fetch()['_c'];
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * 查询多条数据
     * @param $where
     * @param array $binds
     * @return array
     */
    public static function get($where, $binds = [], $fields = '*')
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }

        [$sqlBinds, $whereSql] = static::_bindWhere($where, $binds);
        $sql = ['select', $fields, 'from `' . static::$tableName . '`', $whereSql];
        try {
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare(implode(' ', $sql));
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute(); //执行新增操作
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @param $where
     * @param array $binds
     * @return array
     */
    public static function first($where, $binds = [], $fields = '*')
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }

        [$sqlBinds, $whereSql] = static::_bindWhere($where, $binds);
        $sql = ['select', $fields, 'from `' . static::$tableName . '`', $whereSql, 'limit 1'];
        try {
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare(implode(' ', $sql));
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute(); //执行新增操作
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * 根据主键查询一条数据
     * @param $id
     * @param string $key
     * @return mixed
     */
    public static function find($id, $key = 'id', $fields = '*')
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }

        $sql = ['select', $fields, 'from `' . static::$tableName . '`', 'where `' . $key . '` = :id'];
        try {
            $pdo = static::_getDrive();
            $stmt = $pdo->prepare(implode(' ', $sql));
            $stmt->bindValue(':id', $id);
            $stmt->execute(); //执行新增操作
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @param $where
     * @param array $binds
     * @return bool
     */
    public static function delete($where, $binds = [])
    {
        [$sqlBinds, $whereSql] = static::_bindWhere($where, $binds);
        $sql = ['delete from `' . static::$tableName . '`', $whereSql];
        try {
            $pdo = static::_getDrive();

            $stmt = $pdo->prepare(implode(' ', $sql));
            foreach ($sqlBinds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            return $stmt->execute(); //执行删除操作
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    private static function _bindWhere($where, $binds = [])
    {
        $sql = ' where ';
        $sqlBinds = [];
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                $bindKey = ':where_' . $key . rand();
                $sql .= '`' . $key . '` = ' . $bindKey . ' and ';
                $sqlBinds[$bindKey] = $value;
            }
            $sql = rtrim(trim($sql), 'and');
        } else if (is_string($where)) {
            if (empty($binds)) {
                throw new DBException('where not binds');
            }
            foreach ($binds as $key => $value) {
                if (is_array($value)) {
                    // in 解析
                    $whereIn = '';

                    foreach ($value as $in) {
                        $randInBindKey = ':where_in' . rand();
                        $whereIn .= $randInBindKey . ',';
                        $sqlBinds[$randInBindKey] = $in;
                    }
                    $whereIn = rtrim($whereIn, ',');
                    $where = str_replace($key, $whereIn, $where);
                } else {
                    $bindKey = ':where_' . trim($key, ':') . rand();
                    $sqlBinds[$bindKey] = $value;
                    $where = str_replace($key, $bindKey, $where);
                }

            }
            $sql .= $where;
        } else {
            throw new DBException('where not string or array');
        }
        return [$sqlBinds, $sql];
    }

    /**
     * @return \PDO
     */
    private static function _getDrive()
    {
        if (property_exists(static::class, 'database')) {
            return DB::getInstance(static::$database);
        } else {
            return DB::getInstance();
        }
    }
}