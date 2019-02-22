<?php
namespace Vanilla\Log;


use Vanilla\Request;
use Vanilla\Response;

class Errorhalt
{

    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e
     *            异常对象
     */
    static public function appException($e)
    {
        $error = array();
        $error['message'] = $e->getMessage();
        $trace = $e->getTrace();
        if ('E' === $trace[0]['function']) {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        } else {
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
        $error['trace'] = $e->getTraceAsString();
        
        self::halt($error,'EMERG',$e);
    }

    /**
     * 自定义错误处理
     * @access public
     * @param int $errno
     *            错误类型
     * @param string $errstr
     *            错误信息
     * @param string $errfile
     *            错误文件
     * @param int $errline
     *            错误行数
     * @return void
     */
    static public function appError($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();
                $errorStr = "$errstr " . $errfile . " 第 $errline 行.";
                self::halt($errorStr);
                break;
            default:
                $errorStr = "[$errno] $errstr " . $errfile . " 第 $errline 行.";
                self::halt($errorStr, 'NOTIC');
                break;
        }
    }
    
    // 致命错误捕获
    static public function fatalError()
    {
        if ($e = error_get_last()) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    self::halt($e);
                    break;
            }
        }
        
        // 结束时触发保存日志
        Log::save();
    }

    /**
     * 错误输出
     * @param mixed $error
     *            错误
     * @return void
     */
    static public function halt($error, $level = 'EMERG',$exception=null)
    {
        $e = array();
        
        if (! is_array($error)) {
            $trace = debug_backtrace();
            $e['message'] = $error;
            $e['file'] = $trace[0]['file'];
            $e['line'] = $trace[0]['line'];
            ob_start();
            debug_print_backtrace();
            $e['trace'] = ob_get_clean();
        } else {
            $e = $error;
        }
        // 记录错误日志
        $errorMsg = $e['message'] . ', FILE: ' . $e['file'] . '(' . $e['line'] . '), ' . ($e['trace'] ?? '');
        Log::$level($errorMsg);

        //判断是哪种类型的错误跳转到不通页面
        try{
            if (!empty($exception)){
                if (is_object($exception) && method_exists($exception,'getStatusCode') && $exception->getStatusCode() == 404){
                    $exceptionContent = app('template')->render('404.twig');
                    return (new Response($exceptionContent, 404))->send();
                }else{
                    $exceptionContent = app('template')->render('error.twig');
                    return (new Response($exceptionContent, 500))->send();
                }
            }else{
                $exceptionContent = app('template')->render('error.twig');
                return (new Response($exceptionContent, 500))->send();
            }
        }catch (\Exception $e)
        {
            exit;
        }
    }
}
