<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/25
 * Time: 上午10:53
 */

namespace Vanilla\Contracts\Cache;


interface SessionDrive
{
    public function get($key);

    public function put($key, $value);

    public function delete($key);
}