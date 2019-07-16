<?php
declare(strict_types=1);

namespace Vanilla\Process;

use Vanilla\Application;
use Vanilla\Contracts\Process;
use Vanilla\Http\Request;
use Vanilla\Http\Response;
use Vanilla\Routing\Router;

class Http implements Process
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function run()
    {
        if (!env('PHPUNIT_MODE', false)) {
            $this->app['request'] = Request::capture();
        }

        $this->app['router'] = new Router($this->app);
        $response = $this->app['router']->dispatch();

        if (is_string($response)) {
            $response = new Response(200, [], $response);
        } else if ($response === null) {
            $response = new Response(200, [], '');
        }

        if (env('PHPUNIT_MODE')) {
            return $response;
        }
        $response->send();
    }
}