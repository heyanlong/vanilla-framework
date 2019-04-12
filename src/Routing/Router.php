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

        if (env('APP_ENV') != 'prod') {
            $config = BaseFastRoute\simpleDispatcher(function (BaseFastRoute\RouteCollector $r) use ($appHome) {
                if ($this->mode == self::MODE_COMMAND) {
                    require $appHome . '/routes/command.php';
                } else {
                    require $appHome . '/routes/web.php';
                }
            })->dispatch($method, $uri);
        } else {
            $config = BaseFastRoute\cachedDispatcher(function (BaseFastRoute\RouteCollector $r) use ($appHome) {
                if ($this->mode == self::MODE_COMMAND) {
                    require $appHome . '/routes/command.php';
                } else {
                    require $appHome . '/routes/web.php';
                }
            }, [
                'cacheFile' => $appHome . '/routes/cache-' . $this->mode . '.php'
            ])->dispatch($method, $uri);
        }

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
                    [$controller, $action] = $config[1];
                    return (new $controller)->$action($this->app['request']);
                }
        }
        return new Response();
    }
}