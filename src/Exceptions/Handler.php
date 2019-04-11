<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/9/19
 * Time: 上午11:03
 */

namespace Vanilla\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Vanilla\Log\Log;
use Whoops\Handler\JsonResponseHandler;
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
            return app('response')
                ->withStatus($e->getStatusCode())
                ->withBody($e->getMessage());
        }

        $run = new Run();

        if (env('APP_DEBUG', true)) {
            $handler = new JsonResponseHandler();
            $run->pushHandler($handler);
        }
        $run->writeToOutput(false);
        $run->allowQuit(false);

        $run->pushHandler(function ($exception, $inspector, $run) {
            if ($exception instanceof RequestException) {
                $request = $exception->getRequest();
                $response = $exception->getResponse();
                $log = [];
                $log['method'] = $request->getMethod();
                $log['uri'] = (string)$request->getUri();
                $log['headers'] = $request->getHeaders();
                $log['status'] = !empty($response) ? $response->getStatusCode() : 0;
                $log['response'] = !empty($response) ? $response->getBody()->getContents() : '';
                $log['message'] = $exception->getMessage();
                $log['file'] = $exception->getFile();
                $log['line'] = $exception->getLine();
                if (env('APP_DEBUG', true)) {
                    $log['throwable'] = $exception->getTrace();
                } else {
                    $log['throwable'] = '请开启debug模式';
                }
                warning(get_class($exception), $log);
            } else {
                $log['message'] = $exception->getMessage();
                $log['file'] = $exception->getFile();
                $log['line'] = $exception->getLine();
                $log['throwable'] = $exception->getTrace();
                error(get_class($exception), $log);
            }
        });

        $response = $run->handleException($e);
        if(env('APP_DEBUG', true)) {
            return json(array_merge(['code' => 99999, 'msg' => '系统异常，请稍后再试', 'warning' => '生产环境请设置 APP_DEBUG = false 来屏蔽此消息'], json_decode($response, true)), 500);
        }else{
            return json(['code' => 99999, 'msg' => '系统异常，请稍后再试'], 500);
        }
    }
}