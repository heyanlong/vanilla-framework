<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/9/19
 * Time: 上午11:03
 */

namespace Vanilla\Exceptions;

use Vanilla\Log\Log;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Handler
{
    public function report(\Exception $e)
    {
        // 不报告的
        // 记录错误日志
    }

    public function render($request, \Exception $e)
    {
        if ($e instanceof HttpException) {
            return response($e->getMessage(), $e->getStatusCode());
        }

        $run = new Run();
        if (env('APP_DEBUG', true)) {
            $handler = new PrettyPageHandler();
            $handler->setEditor('phpstorm');
            $run->pushHandler($handler);
        }
        $run->writeToOutput(false);
        $run->allowQuit(false);

        $run->pushHandler(function ($exception, $inspector, $run) {
            Log::error(['desc' => $exception->getMessage() . ', FILE: ' . $exception->getFile() . '(' . $exception->getLine() . ')', 'throwable' => $exception->getTrace()]);
        });

        $response = $run->handleException($e);
        if ($response == '') {
            $response = 'error';
        }

        return response($response, 500);
    }
}