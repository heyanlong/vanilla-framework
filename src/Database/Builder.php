<?php

namespace Vanilla\Database;


use Vanilla\Exceptions\DBException;

class Builder
{
    private $model;

    protected $whereConditions = [];
    protected $orConditions;
    protected $notConditions;
    protected $havingConditions;
    protected $joinConditions;
    protected $initAttrs;
    protected $assignAttrs;
    protected $selects;
    protected $omits;
    protected $orders;
    protected $preload;
    protected $offset;
    protected $limit;
    protected $group;
    protected $tableName;
    protected $raw;
    protected $unscoped;
    protected $ignoreOrderQuery;

    /**
     * @var \PDO
     */
    private $transaction;

    private $vars = [];

    /**
     * @return void
     */
    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    /**
     * @return object
     */
    public function getModel(): object
    {
        return $this->model;
    }

    public function where($query, ...$values): Builder
    {
        $this->whereConditions[] = [
            'query' => $query,
            'args' => $values
        ];
        return $this;
    }

    public function order($value)
    {
        $this->orders[] = $value;
        return $this;
    }

    public function limit($limit): Builder
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset): Builder
    {
        $this->offset = $offset;
        return $this;
    }

    public function first(...$where)
    {
        try {
            $this->limit(1);
            $pdo = Connector::getInstance($this->getModel()->getConnection());
            $statement = $this->prepareQuerySQL();
            $parameters = $this->vars;
            $stmt = $pdo->prepare($statement);
            $start = microtime(true);
            $stmt->execute($parameters);
            $end = microtime(true);
            $res = $stmt->fetch();

            $newModel = null;
            if (!empty($res)) {
                $className = get_class($this->getModel());
                $newModel = new $className();
                $newModel->exists = true;
                $newModel->setAttributes($res);
            }

            info(sprintf($this->getLogFormat(), ($end - $start) * 1000, $res ? 1 : 0, $this->toSql($statement, $parameters)));
            return $newModel;
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    public function find(...$where)
    {
        try {
            $pdo = Connector::getInstance($this->getModel()->getConnection());
            $statement = $this->prepareQuerySQL();
            $parameters = $this->vars;
            $stmt = $pdo->prepare($statement);
            $start = microtime(true);
            $stmt->execute($parameters);
            $end = microtime(true);
            $res = $stmt->fetchAll();

            $models = [];
            if (!empty($res)) {
                foreach ($res as $item) {
                    $className = get_class($this->getModel());
                    $newModel = null;
                    $newModel = new $className();
                    $newModel->exists = true;
                    $newModel->setAttributes($item);
                    $models[] = $newModel;
                }
            }

            info(sprintf($this->getLogFormat(), ($end - $start) * 1000, $res ? count($res) : 0, $this->toSql($statement, $parameters)));
            return $models;
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    public function update(...$attrs)
    {
        $toSearchableMap;
        if (count($attrs) > 1) {
            if (is_string($attrs[0])) {
                $toSearchableMap[$attrs[0]] = $attrs[1];
            }
        }
        return $this->updates($toSearchableMap);
    }

    public function updates($values)
    {
        $sqls = [];
        $vars = [];
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $sqls[] = sprintf("%s = ?", $key);
                $vars[] = $value;
            }
        }
        $sql = implode(", ", $sqls);
        $sql = $this->addVars($sql, $vars);

        try {
            $pdo = Connector::getInstance($this->getModel()->getConnection());
            $statement = $this->prepareUpdateSQL($sql);
            $parameters = $this->vars;
            $stmt = $pdo->prepare($statement);
            $start = microtime(true);
            $stmt->execute($parameters);
            $end = microtime(true);
            $rowCount = $stmt->rowCount();

            info(sprintf($this->getLogFormat(), ($end - $start) * 1000, $rowCount, $this->toSql($statement, $parameters)));
            return $rowCount;
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    public function delete()
    {
        $model = $this->getModel();
        // object call
        if ($model->exists) {

        } else {
            if ($model->softDelete !== null) {
                // to update
                return $this->update($model->softDelete, $model->softDeleteRole[$model::SOFT_ROLE_DELETE]);
            } else {
                // to delete
                try {
                    if(count($this->whereConditions) <= 0) {
                        throw new DBException("Delete failed, need where condition", 0);
                    }
                    $pdo = Connector::getInstance($this->getModel()->getConnection());
                    $statement = $this->prepareDeleteSQL();
                    $parameters = $this->vars;
                    $stmt = $pdo->prepare($statement);
                    $start = microtime(true);
                    $stmt->execute($parameters);
                    $end = microtime(true);
                    $rowCount = $stmt->rowCount();

                    info(sprintf($this->getLogFormat(), ($end - $start) * 1000, $rowCount, $this->toSql($statement, $parameters)));
                    return $rowCount;
                } catch (\PDOException $e) {
                    throw new DBException($e->getMessage(), $e->getCode());
                }
            }
        }
    }

    public function save()
    {
        $model = $this->getModel();

        // update
        if ($model->exists) {
            $diff = [];
            $attrs = $model->getAttributes();
            $original = $model->getOriginal();

            foreach ($attrs as $key => $attr) {
                if (isset($original[$key]) && $attr != $original[$key]) {
                    $diff[$key] = $attr;
                }
            }

            if (!empty($diff)) {
                $updateModel = clone $model;
                if (isset($original[$model->getPrimaryKey()])) {
                    $updateModel = $updateModel->where(sprintf("%s = ?", $model->getPrimaryKey()), $original[$model->getPrimaryKey()]);
                } else {
                    throw new DBException("not found primary key.");
                }
                return $updateModel->updates($diff);
            }
            return 0;
        } else { // insert
            $attrs = $model->getAttributes();
            $sql = $this->prepareInsertSQL($attrs);
            $statement = $this->addVars($sql, [array_values($attrs)]);
        }
        $parameters = $this->vars;
        try {
            $pdo = Connector::getInstance($this->getModel()->getConnection());
            $stmt = $pdo->prepare($statement);
            $start = microtime(true);
            $stmt->execute($parameters);
            $end = microtime(true);
            $rowCount = $stmt->rowCount();

            info(sprintf($this->getLogFormat(), ($end - $start) * 1000, $rowCount, $this->toSql($statement, $parameters)));
            return $rowCount;
        } catch (\PDOException $e) {
            error(sprintf($this->getLogFormat(), 0, 0, $this->toSql($statement, $parameters)));
            throw new DBException($e->getMessage(), $e->getCode());
        }
    }

    private function prepareInsertSQL($attributes)
    {
        $sql = '`' . implode('`,`', array_keys($attributes)) . '`';
        return sprintf("INSERT INTO %s (%s) VALUES(?)", $this->getModel()->getTableName(), $sql);
    }

    private function prepareUpdateSQL($sql)
    {
        return sprintf("UPDATE %s SET %s%s", $this->getModel()->getTableName(), $sql, $this->addExtraSpaceIfExist($this->combinedConditionSql()));
    }

    private function prepareDeleteSQL() {
        return sprintf("DELETE FROM %s%s", $this->getModel()->getTableName(), $this->addExtraSpaceIfExist($this->combinedConditionSql()));
    }

    private function prepareQuerySQL()
    {
        return sprintf("SELECT %s FROM %s %s", $this->selectSQL(), $this->getModel()->getTableName(), $this->combinedConditionSql());
    }

    private function selectSQL()
    {
        return '*';
    }

    private function whereSQL(): string
    {
        $sql = '';
        $andConditions = [];

        foreach ($this->whereConditions as $key => $value) {
            $andConditions[] = $this->buildCondition($value, true);
        }

        $combinedSQL = implode(" AND ", $andConditions);

        if (strlen($combinedSQL) > 0) {
            $sql = "WHERE " . $combinedSQL;
        }
        return $sql;
    }

    private function buildCondition(array $clause, bool $include): string
    {
        $equalSQL = '=';
        $inSQL = 'IN';

        if (!$include) {
            $equalSQL = '<>';
            $inSQL = 'NOT IN';
        }

        switch (gettype($value = $clause['query'])) {
            case 'string':
                if ($value != '') {
                    if (!$include) {

                    } else {
                        $str = sprintf("(%s)", $value);
                    }
                }
                break;
        }

        $str = $this->addVars($str, $clause['args']);

        return $str;
    }

    private function addVars($str, $values)
    {
        $sql = '';
        $index = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str{$i} == '?') {
                if (isset($values[$index])) {
                    if (gettype($values[$index]) == 'array') {
                        foreach ($values[$index] as $value) {
                            $sql .= '?,';
                            $this->vars[] = $value;
                        }
                        $sql = rtrim($sql, ',');
                    } else {
                        $sql .= $str{$i};
                        $this->vars[] = $values[$index];
                    }
                    $index++;
                }
            } else {
                $sql .= $str{$i};
            }
        }
        return $sql;
    }

    private function combinedConditionSql(): string
    {
        $joinSQL = '';
        $whereSQL = $this->whereSQL();

        return $joinSQL . $whereSQL . $this->orderSQL() . $this->limitAndOffsetSQL();
    }

    private function orderSQL(): string
    {
        if (!empty($this->orders)) {
            $orders = [];
            foreach ($this->orders as $order) {
                $orders[] = $order;
            }
            return " ORDER BY " . implode(',', $orders);
        }
        return '';
    }

    private function limitAndOffsetSQL(): string
    {
        $sql = '';
        if ($this->limit != null) {
            $parsedLimit = intval($this->limit);
            if ($parsedLimit != null && $parsedLimit >= 0) {
                $sql .= sprintf(" LIMIT %d", $parsedLimit);

                if ($this->offset != null) {
                    $parsedOffset = intval($this->offset);
                    if ($parsedOffset != null && $parsedLimit >= 0) {
                        $sql .= sprintf(" OFFSET %d", $parsedOffset);
                    }
                }
            }
        }
        return $sql;
    }

    private function addExtraSpaceIfExist(string $sql): string
    {
        if ($sql != "") {
            return " " . $sql;
        }
        return "";
    }

    private function toSql($statement, $parameters)
    {
        $index = 0;
        $sql = '';
        for ($i = 0; $i < strlen($statement); $i++) {
            if ($statement{$i} == '?') {
                if (isset($parameters[$index])) {
                    $sql .= $parameters[$index];
                    $index++;
                }
            } else {
                $sql .= $statement{$i};
            }
        }
        return $sql;
    }

    private function getLogFormat()
    {
        if (env('APP_ENV') == 'prod') {
            return "[%fms, %d rows affected or returned] %s";
        } else {
            return "[\e[32m%fms\e[0m, %d rows affected or returned] \e[32m %s \e[0m";
        }
    }

    public function begin()
    {
        $this->transaction = Connector::getInstance($this->getModel()->getConnection());
        if (!$this->transaction->beginTransaction()) {
            throw new DBException("begin transaction error");
        }
        return $this;
    }

    public function commit()
    {
        return $this->transaction->commit();
    }

    public function rollBack()
    {
        return $this->transaction->rollBack();
    }
}