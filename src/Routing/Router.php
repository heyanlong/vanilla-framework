<?php
declare(strict_types=1);

namespace Vanilla\Routing;

use FastRoute as BaseFastRoute;
use Vanilla\Application;
use Vanilla\Exceptions\MethodNotAllowedHttpException;
use Vanilla\Exceptions\NotFoundHttpException;
use Vanilla\Http\Request;
use Vanilla\Http\Response;

class Router
{
    /**
     * @var Application
     */
    private $app;
    private $request;
    private $mode = 'http';
    private $command = ''; // command mode only

    const MODE_HTTP = 'http';
    const MODE_COMMAND = 'command';

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;
    }

    public function setCommand(string $command)
    {
        $this->command = $command;
    }

    /**
     * @param $input Request
     * @return Response
     */
    public function dispatch()
    {
        if ($this->mode == self::MODE_COMMAND) {
            $uri = $this->command;
            $method = 'GET';
        } else {
            $this->request = $this->app['request'];
            $uri = $this->request->getRequestUri();
            $method = $this->request->getMethod();
        }
        $appHome = $this->app->getBasePath();

        $config = BaseFastRoute\simpleDispatcher(function (RouteCollector $r) use ($appHome) {
            if ($this->mode == self::MODE_COMMAND) {
                require $appHome . '/routes/command.php';
            } else {
                require $appHome . '/routes/web.php';
            }
        }, ['routeCollector' => 'Vanilla\\Routing\\RouteCollector'])->dispatch($method, $uri);

        switch ($config[0]) {
            case BaseFastRoute\Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException();
            case BaseFastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException('MethodNotAllowed');
            case BaseFastRoute\Dispatcher::FOUND:
                if ($this->mode == self::MODE_COMMAND) {
                    $controller = $config[1];
                    return (new $controller)->handle($this->app['argv']);
                } else {
                    $uses = $config[1]['uses'];
                    $middlewares = $config[1]['middleware'];
                    $next = null;
                    if (is_callable($uses)) {
                        $next = function ($request) use ($uses) {
                            return $uses($this->app['request']);
                        };
                    } else {
                        [$controller, $action] = explode('@', $uses);

                        $next = function ($request) use ($controller, $action) {
                            return (new $controller)->$action($this->app['request']);
                        };
                    }

                    foreach (array_reverse($middlewares) as $middleware) {
                        $next = function ($request) use ($middleware, $next) {
                            return $middleware($request, $next);
                        };
                    }

                    return $next($this->app['request']);
                }
        }
        return new Response();
    }
}