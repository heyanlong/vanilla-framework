<?php
declare(strict_types=1);

namespace Vanilla\Database;

use PDO;
use PDOException;

class Connector
{
    protected static $conns = [];

    /**
     * @param string $name
     * @return PDO
     */
    public static function getInstance($name = 'default')
    {
        if (array_key_exists($name, self::$conns)) {
            return self::$conns[$name];
        } else {

            $dsn = 'mysql:host=' . config('db.' . $name . '.host') . ';dbname=' . config('db.' . $name . '.database') . ';port=' . config('db.' . $name . '.port');
            $commands = config('db.' . $name . '.commands', 'SET autocommit=ON,SET NAMES utf8mb4');

            if (!empty($commands)) {
                $commands = explode(',', $commands);
            }

            $username = config('db.' . $name . '.username') ? config('db.' . $name . '.username') : null;
            $password = config('db.' . $name . '.password') ? config('db.' . $name . '.password') : null;

            try {
                self::$conns[$name] = new PDO($dsn, $username, $password);
                self::$conns[$name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conns[$name]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::ERRMODE_EXCEPTION);
                foreach ($commands as $value) {
                    self::$conns[$name]->exec($value);
                }
                return self::$conns[$name];
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage());
            }
        }
    }

    private function __clone()
    {
    }
}