<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 上午11:38
 */

namespace Vanilla\Provider;


use Pimple\Container;

use Vanilla\Contracts\ServiceProviderInterface;
use Vanilla\View\EnvExtension;
use Vanilla\View\CommonExtension;
use Vanilla\View\PageExtension;
use Vanilla\View\PageTokenParser;

class TwigServiceProvider implements ServiceProviderInterface
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
        $path = $pimple['resources'] . DIRECTORY_SEPARATOR . 'views';
        $loader = new \Twig_Loader_Filesystem($path);
        $template = new \Twig_Environment($loader, [
            'cache' => $pimple['storage'] . DIRECTORY_SEPARATOR . 'views',
            'auto_reload' => true
        ]);
        $template->addExtension(new EnvExtension());
        $template->addExtension(new CommonExtension());
        $template->addExtension(new PageExtension());

        $pimple['template'] = $template;
    }
}