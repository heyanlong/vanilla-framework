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
        if (file_exists(PROJECT_DIR . DIRECTORY_SEPARATOR . '.redis')) {
            $uri = trim(file_get_contents(PROJECT_DIR . DIRECTORY_SEPARATOR . '.redis'));
            $masterName = 'mymaster';
            if (strpos($uri, '|') !== false) {
                [$masterName, $uri] = explode('|', $uri);
            }

            $uri = explode(',', $uri);

            $redisServer = new Client($uri, ['replication' => 'sentinel', 'service' => $masterName]);
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