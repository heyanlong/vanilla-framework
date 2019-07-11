<?php

namespace Vanilla\Database;

use PDO;
use PDOException;

class DB extends PDO
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

            $tryTimes = 3;
            do {
                try {
                    self::$conns[$name] = new PDO($dsn, $username, $password,
                        [
                            PDO::ATTR_TIMEOUT => env('PDO_ATTR_TIMEOUT', 30)
                        ]);
                    self::$conns[$name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    foreach ($commands as $value) {
                        self::$conns[$name]->exec($value);
                    }
                    $tryTimes = 0;
                } catch (PDOException $e) {
                    $tryTimes--;
                    if ($tryTimes < 1) {
                        throw new PDOException($e->getMessage());
                    }
                }
            } while ($tryTimes > 0);
            return self::$conns[$name];
        }
    }

    private function __clone()
    {
    }

}