<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/25
 * Time: 下午3:01
 */

namespace Vanilla\Contracts;


interface Config
{
    public function get($key, $default = null);
}