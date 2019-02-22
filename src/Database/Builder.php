<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/9/18
 * Time: 上午9:40
 */

namespace Vanilla\Database;


class Builder
{
    protected $connect;
    protected $table;
    protected $wheres = [];
    protected $columns = '*';
    protected $join;
    protected $query;

    public function join($table, $on, $type = 'left')
    {
        $map = [
            'left' => '[>]',
            'right' => '[<]',
            'full' => '[<>]',
            'inner' => '[><]',
        ];
        $this->join[$map[$type] . $table] = $on;
        return $this;
    }

    public function query($query)
    {
        $this->query = $query;
        return $this;
    }

    public function table($name)
    {
        $this->table = $name;
        return $this;
    }

    public function connect($connect)
    {
        $this->connect = $connect;
        return $this;
    }

    public function select($columns)
    {
        $this->columns = $columns;
        return $this;
    }

    public function where($where)
    {
        $this->wheres = $where;
        return $this;
    }

    public function order($order)
    {
        $this->wheres['ORDER'] = $order;
        return $this;
    }

    public function limit($limit)
    {
        $this->wheres['LIMIT'] = $limit;
        return $this;
    }

    public function group($group)
    {
        $this->wheres['GROUP'] = $group;
        return $this;
    }

    public function having($having)
    {
        $this->wheres['HAVING'] = $having;
        return $this;
    }

    public function insert($data)
    {
        $this->DB()->insert($this->table, $data);
        return $this->DB()->id();
    }

    public function delete()
    {
        $data = $this->DB()->delete($this->table, $this->wheres);
        return $data->rowCount();
    }

    public function update($data)
    {
        $data = $this->DB()->update($this->table, $data, $this->wheres);
        return $data->rowCount();
    }

    public function count()
    {
        $db = $this->DB();
        if ($this->columns === '*') {
            return $db->count($this->table, $this->wheres);
        } elseif ($this->columns !== '*' && $this->join !== null) {
            return $db->count($this->table, $this->join, $this->columns, $this->wheres);
        }
    }

    public function max()
    {
        $db = $this->DB();
        if ($this->join === null) {
            return $db->max($this->table, $this->columns, $this->wheres);
        } else {
            return $db->max($this->table, $this->join, $this->columns, $this->wheres);
        }
    }

    public function min()
    {
        $db = $this->DB();
        if ($this->join === null) {
            return $db->min($this->table, $this->columns, $this->wheres);
        } else {
            return $db->min($this->table, $this->join, $this->columns, $this->wheres);
        }
    }

    public function avg()
    {
        $db = $this->DB();
        if ($this->join === null) {
            return $db->avg($this->table, $this->columns, $this->wheres);
        } else {
            return $db->avg($this->table, $this->join, $this->columns, $this->wheres);
        }
    }

    public function sum()
    {
        $db = $this->DB();
        if ($this->join === null) {
            return $db->sum($this->table, $this->columns, $this->wheres);
        } else {
            return $db->sum($this->table, $this->join, $this->columns, $this->wheres);
        }
    }

    public function first()
    {
        if ($this->query === null) {
            // 查询
            $this->wheres['LIMIT'] = 1;

            if ($this->join === null) {
                $res = $this->DB()->select($this->table, $this->columns, $this->wheres);
            } else {
                $res = $this->DB()->select($this->table, $this->join, $this->columns, $this->wheres);
            }
        } else {
            try {
                if (substr(trim(strtoupper($this->query)), 0, 6) === 'SELECT') {
                    $res = $this->DB()->query($this->query)->fetchAll();
                }
            } catch (\Exception $e) {

            }
        }

        if (!empty($res)) {
            return $res[0];
        }

    }

    public function get()
    {
        if ($this->query === null) {
            if ($this->join === null) {
                $res = $this->DB()->select($this->table, $this->columns, $this->wheres);
            } else {
                $res = $this->DB()->select($this->table, $this->join, $this->columns, $this->wheres);
            }
        } else {
            try {
                if (substr(trim(strtoupper($this->query)), 0, 6) === 'SELECT') {
                    $res = $this->DB()->query($this->query)->fetchAll();
                }
            } catch (\Exception $e) {

            }
        }
        if (!empty($res)) {
            return $res;
        }
    }

    /**
     * 获取表名
     *
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    public function getLastSql()
    {
        return $this->DB()->last();
    }

    /**
     * 返回medoo连接实例
     *
     * @return array
     */
    private function DB()
    {
        return DB::getInstance($this->connect);
    }

}