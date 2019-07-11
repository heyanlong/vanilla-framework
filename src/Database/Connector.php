<?php
declare(strict_types=1);

namespace Vanilla\Database;

use PDO;
use PDOException;

class Connector
{
    /**
     * @var array PDO
     */
    protected static $conns = [];

    /**
     * @param string $name
     * @return PDO
     */
    public static function getInstance($name = 'default')
    {
        if (array_key_exists($name, self::$conns)) {
            $conn = self::$conns[$name];
            if (php_sapi_name() == 'cli') {
                try {
                    $conn->query('SELECT 1');
                } catch (PDOException $e) {
                    $conn = static::connect($name);
                }
            }
            return $conn;
        } else {
            return static::connect($name);
        }
    }

    private static function connect($name)
    {
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
                self::$conns[$name] = new PDO($dsn, $username, $password, [
                    PDO::ATTR_TIMEOUT => env('PDO_ATTR_TIMEOUT', 30),
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

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

    private function __clone()
    {
    }
}