<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/20
 * Time: 下午7:55
 */

namespace Vanilla;

use App\Exceptions\Handler;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Predis\Client;
use Vanilla\Cache\Redis;
use Vanilla\Config\ArrayConfig;
use Vanilla\Contracts\Stream\Input;
use Vanilla\Exceptions\FatalThrowableError;
use Vanilla\Http\Kernel;
use Vanilla\Http\Request;
use Vanilla\Http\Response;
use Vanilla\Routing\Router;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Vanilla\Log\Log;

class Application extends Container
{
    private static $instance;
    /**
     * The base path of the application installation.
     *
     * @var string
     */
    protected $basePath;

    protected $binds = [];

    protected $router;

    protected $input;

    public function __construct($basePath = null)
    {
        define('BEGIN_TIME', microtime(TRUE));
        $this->basePath = $basePath;


        date_default_timezone_set('Asia/Shanghai');

        parent::__construct();

        $this->bootstrapContainer();
        $this->registerErrorHandling();

        static::$instance = $this;
    }

    public function handle(Input $input)
    {
        $this->input = $input;
        $this['request'] = $input;
        if($input instanceof Request) {
            $kernel = new Kernel($this);
            return $kernel->handle();
        }
    }

    public static function getInstance()
    {
        return static::$instance;
    }

    protected function bootstrapContainer()
    {
        $this['app'] = $this;
        $this['Vanilla\Application'] = $this;
        $this['path'] = $this->path();
        $this['path.config'] = $this->basePath() . DIRECTORY_SEPARATOR . 'config';
        $this['path.base'] = $this->basePath();
        $this['resources'] = $this->resources();
        $this['storage'] = $this->storage();
        $this['response'] = new Response();
        $this['config'] = new ArrayConfig($this);
        $this['cache'] = $this->cache();
        $this['router'] = new Router($this);
    }

    protected function cache()
    {
        $cacheConfig = $this['config']->get('cache');
        if (!empty($cacheConfig)) {

            if ($cacheConfig['type'] === 'redis') {
                $config = $cacheConfig['redis'];
                $config['host'] = explode(',', $config['host']);

                $options = ['replication' => 'sentinel', 'service' => $config['service']];
                $client = new Client($config['host'], $options);
                return new Redis($client, $config['prefix']);
            }

        }
    }

    protected function registerErrorHandling()
    {

        error_reporting(-1);
        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

//        register_shutdown_function([$this, 'handleShutdown']);

        register_shutdown_function('\Vanilla\Log\Log::save');
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function handleException($e)
    {
        if (!$e instanceof \Exception) {
            $e = new FatalThrowableError($e);
        }

        $handler = new Handler();
        try {

            $handler->report($e);
        } catch (Exception $e) {
            //
        }

        $response = $handler->render($this->input, $e);

        if ($response instanceof Response) {
            $response->send();
        } else {
            response($response, 500)->send();
        }
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @return string
     */
    public function path()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app';
    }

    public function basePath()
    {
        return $this->basePath;
    }

    public function resources()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'resources';
    }

    public function storage()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'storage';
    }

    public function getConfig($file)
    {
        static $configs = [];

        if (!isset($configs[$file])) {
            $configs[$file] = include $this->basePath . DIRECTORY_SEPARATOR . 'config/' . $file . '.php';
        }

        return $configs[$file];

    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        $params = $this->getMethodParams($callback[0], $parameters, $callback[1]);
        return call_user_func_array($callback, $params);
    }

    public function make($abstract, array $parameters = [])
    {
        $exist = false;
        $abstract = $this->alias($abstract);
        $abstract = $this->bind($abstract);

        try {
            $exist = $this->offsetGet($abstract);
        } catch (\Exception $e) {

        }

        if ($exist) {
            return $this[$abstract];
        } else {
            $params = $this->getMethodParams($abstract, $parameters);
            $class = (new \ReflectionClass($abstract))->newInstance(...$params);
            $this[$abstract] = $class;
            return $this[$abstract];
        }
    }

    protected function getMethodParams($className, $parameters, $methodsName = '__construct')
    {
        if (is_object($className)) {
            $class = new \ReflectionObject($className);
        } else {
            $class = new \ReflectionClass($className);
        }

        $dependencies = [];

        if ($class->hasMethod($methodsName)) {
            $method = $class->getMethod($methodsName);

            $params = $method->getParameters();
            foreach ($params as $name) {

                if (array_key_exists($name->name, $parameters)) {
                    $dependencies[] = $parameters[$name->name];
                    unset($parameters[$name->name]);
                } else if ($name->getClass()) {
                    $dependencies[] = $this->make($name->getClass()->name);
                } else if ($name->isDefaultValueAvailable()) {
                    $dependencies[] = $name->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            }
        }

        return $dependencies;
    }

    protected function alias($name)
    {
        $list = [
            Request::class => 'request'
        ];

        if (isset($list[$name])) {
            return $list[$name];
        } else {
            return $name;
        }
    }

    public function bind($name, $value = null)
    {

        if (null === $value) {
            if (isset($this->binds[$name])) {
                return $this->binds[$name];
            } else {
                return $name;
            }
        }

        $this->binds[$name] = $value;
    }
}