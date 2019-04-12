<?php
declare(strict_types=1);

namespace Vanilla\Process;

use Vanilla\Application;
use Vanilla\Contracts\Process;
use Vanilla\Http\Request;
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
        $this->app['request'] = Request::capture();
        $this->app['router'] = new Router($this->app);
        $response = $this->app['router']->dispatch();
        $response->send();
    }
}