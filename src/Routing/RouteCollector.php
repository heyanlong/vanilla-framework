<?php


namespace Vanilla\Routing;


use FastRoute\DataGenerator;
use FastRoute\RouteParser;

class RouteCollector extends \FastRoute\RouteCollector
{

    protected $currentGroupMiddleware = [];

    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator)
    {
        $this->currentGroupMiddleware = [];
        parent::__construct($routeParser, $dataGenerator);
    }

    public function addGroup($prefix, callable $callback)
    {
        if (is_array($prefix) && isset($prefix['prefix'])) {

            if (isset($prefix['middleware'])) {
                $userMiddlewares = [];
                $middlewares = [];
                if (is_string($prefix['middleware'])) {
                    $userMiddlewares = [$prefix['middleware']];
                } else if (is_callable($prefix['middleware'])) {
                    $userMiddlewares = [$prefix['middleware']];
                } else if (is_array($prefix['middleware'])) {
                    $userMiddlewares = $prefix['middleware'];
                }

                foreach ($userMiddlewares as $middleware) {
                    if (is_string($middleware)) {
                        $config = config('middleware');
                        if ($config !== null) {
                            if (isset($config[$middleware])) {
                                $middlewareValue = $config[$middleware];
                                if (is_callable($middlewareValue)) {
                                    $middlewares[] = $middlewareValue;
                                } else if (is_string($middlewareValue)) {
                                    $middlewares[] = function ($request, $next) use ($middlewareValue) {
                                        $class = new $middlewareValue;
                                        return $class->handle($request, $next);
                                    };
                                }
                            }
                        }
                    } else if (is_callable($middleware)) {
                        $middlewares[] = $middleware;
                    }
                }

                $previousGroupMiddleware = $this->currentGroupMiddleware;
                $this->currentGroupMiddleware = array_merge($previousGroupMiddleware, $middlewares);
                parent::addGroup($prefix['prefix'], $callback);
                $this->currentGroupMiddleware = $previousGroupMiddleware;
            } else {
                parent::addGroup($prefix['prefix'], $callback);
            }
        } else {
            parent::addGroup($prefix, $callback);
        }
    }

    public function addRoute($httpMethod, $route, $handler)
    {
        $uses = '';
        $groupMiddleware = $this->currentGroupMiddleware;
        $middlewares = [];
        $userMiddlewares = [];
        if (is_array($handler) && count($handler) == 2 && !isset($handler['uses'])) {
            $uses = $handler[0] . '@' . $handler[1];
        } else if (is_string($handler) || is_callable($handler)) {
            $uses = $handler;
        } else if (is_array($handler) && isset($handler['uses'])) {
            $uses = $handler['uses'];
            if (isset($handler['middleware'])) {
                if (is_string($handler['middleware'])) {
                    $userMiddlewares = [$handler['middleware']];
                } else if (is_callable($handler['middleware'])) {
                    $userMiddlewares = [$handler['middleware']];
                } else if (is_array($handler['middleware'])) {
                    $userMiddlewares = $handler['middleware'];
                }

                foreach ($userMiddlewares as $middleware) {
                    if (is_string($middleware)) {
                        $config = config('middleware');
                        if ($config !== null) {
                            if (isset($config[$middleware])) {
                                $middlewareValue = $config[$middleware];
                                if (is_callable($middlewareValue)) {
                                    $middlewares[] = $middlewareValue;
                                } else if (is_string($middlewareValue)) {
                                    $middlewares[] = function ($request, $next) use ($middlewareValue) {
                                        $class = new $middlewareValue;
                                        return $class->handle($request, $next);
                                    };
                                }
                            }
                        }
                    } else if (is_callable($middleware)) {
                        $middlewares[] = $middleware;
                    }
                }
                $middlewares = array_merge($groupMiddleware, $middlewares);
            }
        }

        $handler = [
            'uses' => $uses,
            'middleware' => $middlewares
        ];
        parent::addRoute($httpMethod, $route, $handler); // TODO: Change the autogenerated stub
    }
}