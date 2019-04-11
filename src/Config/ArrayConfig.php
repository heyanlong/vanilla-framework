<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/25
 * Time: 下午3:01
 */

namespace Vanilla\Config;


use Vanilla\Contracts\Config;

class ArrayConfig implements Config
{
    protected $config;

    public function __construct($app)
    {
        $dir = $app['path.config'];
        $handler = opendir($dir);
        $files = [];
        while (($filename = readdir($handler)) !== false) {
            if ($filename !== '.' && $filename !== '..') {
                $files[] = $filename;
            }
        }
        closedir($handler);

        foreach ($files as $file) {
            $this->config[basename($dir . DIRECTORY_SEPARATOR . $file, '.php')] = include $dir . DIRECTORY_SEPARATOR . $file;
        }
    }

    public function get($key = null, $default = null)
    {
        if (null === $key) {
            return $this->config;
        }
        $keys = explode('.', $key);

        $arr = $this->config;
        foreach ($keys as $k) {
            if (isset($arr[$k])) {
                $arr = $arr[$k];
            } else {
                $arr = null;
            }
        }
        return $arr ?: $default;
    }
}