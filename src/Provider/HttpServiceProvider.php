<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/24
 * Time: 下午3:11
 */

namespace Vanilla\Provider;


use Pimple\Container;
use Vanilla\Contracts\ServiceProviderInterface;
use Vanilla\Http\Session;
use Vanilla\Request;
use Vanilla\Response;
use Vanilla\Routing\Router;

class HttpServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $session = new Session($pimple['cache']);
        $session->register();
    }
}
