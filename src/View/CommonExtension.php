<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 下午5:15
 */

namespace Vanilla\View;


class CommonExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_Function('context', function ($key=null, $default = null) {
                return context($key, $default);
            }),
            new \Twig_Function('session', function ($key=null, $default = null) {
                return session($key, $default);
            }),
            new \Twig_Function('numberFormat', function ($input, $thousandsSep = ',') {
                return numberFormat($input, $thousandsSep);
            }),
            new \Twig_Function('php_function', function ($fname,...$params) {
                return php_function($fname,$params);
            }),
            new \Twig_Function('csrf_token', function () {
                return csrf_token();
            })
        ];
    }
}