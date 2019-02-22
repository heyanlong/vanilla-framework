<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 上午11:22
 */

namespace Vanilla\Http;


use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;
use Vanilla\Exceptions\HttpException;
use Vanilla\Exceptions\MethodNotAllowedHttpException;
use Vanilla\Exceptions\NotFoundHttpException;

class Kernel implements \Vanilla\Contracts\Kernel
{
    protected $app;

    protected $request;

    protected $middleware = [
        'request' => [],
        'response' => []
    ];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function handle()
    {
        $this->request = $this->app['request'];
        return $this->dispatch($this->request);
    }

    public function dispatch($request)
    {
        try {
            if (empty($this->request)) {
                $this->request = $request;
            }
            $method = $request->getMethod();
            return $this->handleDispatcherResponse(
                simpleDispatcher(function ($r) {
                    foreach ($this->app['router']->getRoutes() as $route) {
                        $r->addRoute($route['method'], $route['uri'], $route['action']);
                    }
                })->dispatch($method, '/' . trim($request->getPathInfo(), '/'))
            );
        } catch (HttpException $e) {
            throw new HttpException($e->getStatusCode(), $e->getMessage());
        }
    }

    protected function handleDispatcherResponse($routeInfo)
    {
        $this->app['router']->setCurrentRoute($routeInfo);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException();
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException('MethodNotAllowed');
            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }

    protected function handleFoundRoute($routeInfo)
    {
        $this->app['request']->setContext('currentRoute', $routeInfo);
        $action = $routeInfo[1];

        if (isset($action['middleware'])) {
            $this->handleMiddleware($action['middleware']);
        }

        $response = $this->callActionOnRoute($routeInfo);

        if ($response instanceof Response) {
            return $response;
        } else {
            return $this->app['response']->setContent($response);
        }
    }

    protected function handleMiddleware($middlewares)
    {
        foreach ($middlewares as $middleware) {
            $class = new \ReflectionClass($middleware);
            $method = $class->getMethod('handle');
            $parameters = $method->getParameters();
            if (isset($parameters[0])) {
                if ($parameters[0]->getClass()->name == Request::class) {
                    $this->middleware['request'][] = new $middleware;
                } else if ($parameters[0]->getClass()->name == Response::class) {
                    $this->middleware['response'][] = new $middleware;
                }
            }
        }
    }

    protected function callActionOnRoute($routeInfo)
    {
        $action = $routeInfo[1];
        // run
        if (!empty($this->middleware['request'])) {
            foreach ($this->middleware['request'] as $item) {
                $status = $item->handle($this->request);

                if ($status === null) {
                    continue;
                } else if ($status instanceof Response) {
                    return $status;
                } else if ($status === false) {
                    return response('middleware fail');
                }
            }
        }

        if (isset($action['uses'])) {
            $resopnse = $this->callControllerAction($routeInfo);
            if (!empty($this->middleware['response'])) {
                foreach ($this->middleware['response'] as $item) {
                    $resopnse = $item->handle($resopnse);
                }
            }
            return $resopnse;
        }
    }

    protected function callControllerAction($routeInfo)
    {
        $uses = $routeInfo[1]['uses'];

        if (is_string($uses) && strpos($uses, '@') === false) {
            $uses .= '@__invoke';
        }

        list($controller, $method) = explode('@', $uses);

        if (!method_exists($instance = $this->app->make($controller), $method)) {
            throw new NotFoundHttpException;
        }

        return $this->app->call([$instance, $method], $routeInfo[2]);
    }
}