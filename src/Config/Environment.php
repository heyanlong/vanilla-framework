<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/9/11
 * Time: 下午1:57
 */

namespace Vanilla\Config;


use Predis\Client;

class Environment
{
    public static function load($path, $merge = [])
    {
        $file = dirname(__DIR__) . '/../../../../.redis';
        if (file_exists($file)) {
            $uri = trim(file_get_contents($file));
            $masterName = 'mymaster';
            $password = '';
            if (strpos($uri, '|') !== false) {
                [$masterName, $uri] = explode('|', $uri);
                if (strpos($masterName, '@') !== false) {
                    $password = substr($masterName, strpos($masterName, '@') + 1, strlen($masterName));
                    $masterName = substr($masterName, 0, strpos($masterName, '@'));
                }
            }

            $uri = explode(',', $uri);

            $options = ['replication' => 'sentinel', 'service' => $masterName];

            if ($password != '') {
                $options['parameters']['password'] = $password;
            }

            $count = 0;
            $retryMax = 3;
            $retry = false;

            do {
                try {
                    $redisServer = new Client($uri, $options);
                    $redisServer->ping();
                    $retry = false;
                } catch (\Exception $e) {
                    $retry = true;
                }
                ++$count;
            } while ($retry && $count < $retryMax);
            if ($retry) {
                throw new \Exception("failed to connect redis!");
            }

            $env = $redisServer->hgetall(trim($path));

            $env = array_merge($merge, $env);

            foreach ($env as $name => $value) {
                self::setEnvironmentVariable($name, $value);
            }
        }
    }

    protected static function setEnvironmentVariable($name, $value)
    {
        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            putenv("$name=$value");
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
