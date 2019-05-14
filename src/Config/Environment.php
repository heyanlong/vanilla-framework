<?php
declare(strict_types=1);

namespace Vanilla\Config;

use Dotenv\Dotenv;
use Predis\Client;

class Environment
{
    public static function load(string $path)
    {
        if (file_exists($path . DIRECTORY_SEPARATOR . '.env')) {
            $dotenv = Dotenv::create($path);
            $dotenv->load();

            $redisEnvSupport = $_ENV['REDIS_ENV_SUPPORT'] ?? '';

            if ($redisEnvSupport == 'enable') {
                $redisEnvCache = $_ENV['REDIS_ENV_CACHE'] ?? 'enable';
                // is enable file cache
                $cacheFile = $path . DIRECTORY_SEPARATOR . '.env.cache';
                $cacheWrite = false;
                if ($redisEnvCache == 'enable') {
                    // check file
                    if (file_exists($cacheFile)) {
                        $dotenv = Dotenv::create($path, '.env.cache');
                        $dotenv->load();
                        $lastWriteTime = filemtime($cacheFile);
                        if (time() - $lastWriteTime > 5 * 60) {
                            $cacheWrite = true;
                        }
                    } else {
                        $cacheWrite = true;
                    }
                } else {
                    $env = self::getRedisEnvironmentVariable();
                    foreach ($env as $name => $value) {
                        self::setEnvironmentVariable($name, $value);
                    }
                }

                if ($cacheWrite) {
                    // create cache and write
                    $cacheFileHandle = fopen($cacheFile, 'w');
                    if (flock($cacheFileHandle, LOCK_EX | LOCK_NB)) {
                        $env = self::getRedisEnvironmentVariable();
                        $envContent = '';
                        foreach ($env as $name => $value) {
                            $envContent .= $name . '=' . $value . "\r\n";
                        }
                        fwrite($cacheFileHandle, $envContent);
                        flock($cacheFileHandle, LOCK_UN);
                    }
                }
            }
        } else {
            throw new \Exception(".env not found");
        }
    }

    protected static function getRedisEnvironmentVariable()
    {
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

        return $redisServer->hgetall(trim($redisPath));
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
