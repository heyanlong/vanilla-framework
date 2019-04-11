<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 下午1:47
 */

namespace Vanilla\Routing;


use FastRoute as BaseFastRoute;
use Vanilla\Exceptions\MethodNotAllowedHttpException;
use Vanilla\Exceptions\NotFoundHttpException;
use Vanilla\Http\Request;
use Vanilla\Http\Response;

class Router
{
    /**
     * @param $input Request
     * @return Response
     */
    public function dispatch($input)
    {
        $uri = $input->getRequestUri();
        $method = $input->getMethod();
        $appHome = app()->getBasePath();

        if (env('APP_ENV') != 'prod') {
            $config = BaseFastRoute\simpleDispatcher(function (BaseFastRoute\RouteCollector $r) use ($appHome) {
                if (env('PHPUNIT_MODE', false) == false && php_sapi_name() == 'cli') {
                    require $appHome . '/routes/command.php';
                } else {
                    require $appHome . '/routes/web.php';
                }
            })->dispatch($input->getMethod(), $uri);
        } else {
            $config = BaseFastRoute\cachedDispatcher(function (BaseFastRoute\RouteCollector $r) use ($appHome) {
                if (env('PHPUNIT_MODE', false) == false && php_sapi_name() == 'cli') {
                    require $appHome . '/routes/command.php';
                } else {
                    require $appHome . '/routes/web.php';
                }
            }, [
                'cacheFile' => $appHome . '/routes/cache-' . php_sapi_name() . '.php'
            ])->dispatch($method, $uri);
        }

        switch ($config[0]) {
            case BaseFastRoute\Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException();
            case BaseFastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException('MethodNotAllowed');
            case BaseFastRoute\Dispatcher::FOUND:
                if (env('PHPUNIT_MODE', false) == false && php_sapi_name() == 'cli') {
                    $controller = $config[1];
                    return (new $controller)->handle(app('request'));
                } else {
                    [$controller, $action] = $config[1];
                    return (new $controller)->$action(app('request'));
                }

        }
        return new Response();
    }
}