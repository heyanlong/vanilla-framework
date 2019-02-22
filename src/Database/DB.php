<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/9/18
 * Time: 上午10:44
 */

namespace Vanilla\Database;


use Medoo\Medoo;


class DB
{
    /**
     * The number of active transactions.
     *
     * @var int
     */
    protected static $transactions = 0;

    /**
     * connect
     * @var array
     */
    protected static $conns = [];

    /**
     * The dbServeName of current connect
     * @var string
     */
    protected static $dbServeName = '';


    public static function connection($name = 'default')
    {
        self::$dbServeName = $name;

        self::getInstance(self::$dbServeName);

        return new static();
    }

    public static function getInstance($name)
    {

        $config = (new static())->getDataBaseConfig($name);

        if (array_key_exists($name, self::$conns)) {
            return self::$conns[$name];
        } else {
            $db = new Medoo($config);
            if (isset($config['exec']) && !empty($config['exec'])) {
                foreach ($config['exec'] as $item) {
                    $db->pdo->exec($item);
                }
            }
            self::$conns[$name] = $db;
            return $db;
        }

    }

    /**
     * Start a new database transaction.
     * @return mixed
     * @throws \Exception
     */
    public function beginTransaction()
    {
        $pdo = (self::$conns[self::$dbServeName])->pdo;
        ++self::$transactions;
        if (count(self::$conns) == 1) {
            if (self::$transactions == 1) {
                try {
                    $pdo->beginTransaction();
                } catch (\Exception $e) {
                    --self::$transactions;
                    throw $e;
                }
            } elseif (self::$transactions > 1) {
                $pdo->exec('SAVEPOINT trans' . self::$transactions);
            }
        } else {
            $pdo->beginTransaction();
        }
        return $pdo;
    }

    public function transaction(\Closure $callback)
    {
        $pdo = $this->beginTransaction();

        try {
            $result = $callback();
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();

            throw $e;
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        return $result;
    }

    public function commit()
    {
        $pdo = (self::$conns[self::$dbServeName])->pdo;
        if (count(self::$conns) == 1) {
            if (self::$transactions == 1) {
                $pdo->commit();
            }
            self::$transactions = max(0, self::$transactions - 1);
        } else {
            $pdo->commit();
        }


    }

    public function rollBack()
    {
        $pdo = (self::$conns[self::$dbServeName])->pdo;
        if (count(self::$conns) == 1) {
            if (self::$transactions == 1) {
                $pdo->rollBack();
            } elseif (self::$transactions > 1) {
                $pdo->exec('ROLLBACK TO SAVEPOINT trans' . self::$transactions);
            }

            self::$transactions = max(0, self::$transactions - 1);
        } else {
            $pdo->rollBack();
        }
    }

    /**
     * 读取database配置
     * @param $name
     * @return array
     * @throws \Exception
     */
    private function getDataBaseConfig($name)
    {
        $databaseConfig = config('database');
        $config = is_array($databaseConfig['connections'][$name]) &&
        isset($databaseConfig['connections'][$name]) ? $databaseConfig['connections'][$name] : [];

        if (!$config) {
            throw new \Exception('配置不能为空');
        }

        if (!isset($config['database_type'])) {
            throw new \Exception('未指定database_type');
        }

        if (!isset($config['server'])) {
            throw new \Exception('未指定server');
        }

        return $config;
    }
}