<?php
declare(strict_types=1);

namespace Vanilla\Config;

use Dotenv\Dotenv;
use Predis\Client;

class Environment
{
    private static $redisEnv = null;

    public static function load(string $path)
    {
        if (file_exists($path . DIRECTORY_SEPARATOR . '.env')) {
            $dotenv = Dotenv::create($path);
            $dotenv->overload();

            $systemEnv = get_cfg_var('vanilla.env');
            if ($systemEnv !== false) {
                if (file_exists($path . DIRECTORY_SEPARATOR . '.env.' . $systemEnv)) {
                    $dotenv = Dotenv::create($path, '.env.' . $systemEnv);
                    $dotenv->overload();
                }
            }

            $redisEnvSupport = $_ENV['REDIS_ENV_SUPPORT'] ?? '';

            if ($redisEnvSupport == 'enable') {
                $env = self::getRedisEnvironmentVariable();
                foreach ($env as $name => $value) {
                    self::setEnvironmentVariable($name, $value);
                }
            }
        } else {
            throw new \Exception(".env not found");
        }
    }

    protected static function getRedisEnvironmentVariable()
    {
        if (self::$redisEnv === null) {
            $redisType = $_ENV['REDIS_ENV_TYPE'] ?? '';
            $uri = $_ENV['REDIS_ENV_URI'] ?? '';
            $masterName = $_ENV['REDIS_ENV_MASTER_NAME'] ?? '';
            $redisPath = $_ENV['REDIS_ENV_PATH'] ?? '';
            $password = $_ENV['REDIS_ENV_PASS'] ?? '';

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
            self::$redisEnv = $redisServer->hgetall(trim($redisPath));
        }

        return self::$redisEnv;
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
