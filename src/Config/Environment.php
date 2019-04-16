<?php
declare(strict_types=1);

namespace Vanilla\Config;

use Predis\Client;

class Environment
{
    public static function load(string $config)
    {
        if (file_exists($config)) {
            $uri = trim(file_get_contents($config));
            $masterName = 'mymaster';
            $password = '';
            $path = '';
            if (strpos($uri, '|') !== false) {
                [$masterName, $uri] = explode('|', $uri);
                if (strpos($masterName, '@') !== false) {
                    $password = substr($masterName, strpos($masterName, '@') + 1, strlen($masterName));
                    $masterName = substr($masterName, 0, strpos($masterName, '@'));
                }
            }

            if (strpos($uri, '#') !== false) {
                [$uri, $path] = explode('#', $uri);
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

            foreach ($env as $name => $value) {
                self::setEnvironmentVariable($name, $value);
            }
        } else {
            throw new \Exception(".redis not found");
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
