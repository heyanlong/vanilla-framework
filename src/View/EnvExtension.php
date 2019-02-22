<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 下午5:15
 */

namespace Vanilla\View;


class EnvExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_Function('env', function ($key, $default = null) {
                return env($key, $default);
            })
        ];
    }
}