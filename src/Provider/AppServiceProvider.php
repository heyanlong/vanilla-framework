<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/25
 * Time: 下午1:45
 */

namespace Vanilla\Provider;


use Pimple\Container;
use Predis\Client;
use Vanilla\Cache\Redis;
use Vanilla\Config\ArrayConfig;
use Vanilla\Contracts\ServiceProviderInterface;

class AppServiceProvider implements ServiceProviderInterface
{

    /**
     * @param Container $pimple
     * @throws \Exception
     */
    public function register(Container $pimple)
    {
    }
}