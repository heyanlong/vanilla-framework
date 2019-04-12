<?php
declare(strict_types=1);

namespace Vanilla\Process;

use Vanilla\Application;
use Vanilla\Command\Argv;
use Vanilla\Contracts\Process;
use Vanilla\Routing\Router;

class Cli implements Process
{
    private $app;

    private $argv;

    public function __construct(Application $app, array $argv)
    {
        $this->argv = $argv;
        $this->app = $app;
    }

    public function run()
    {
        if (count($this->argv) <= 1) {
            echo "e.g.\n";
            echo "php vanilla [your command]\n";
            echo "exit...\n";
            exit;
        } else {
            $command = $this->argv[1];
        }
        $this->app['argv'] = new Argv($this->argv);
        $router = new Router($this->app);
        $router->setMode(Router::MODE_COMMAND);
        $router->setCommand($command);
        $this->app['router'] = $router;
        $this->app['router']->dispatch();
    }
}