<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 上午11:44
 */

function app($make = null)
{
    if (is_null($make)) {
        return \Vanilla\Application::getInstance();
    }
    $app = \Vanilla\Application::getInstance();
    if (isset($app[$make])) {
        return $app[$make];
    }
    return null;
}

function json(array $data, $status = 200)
{
    return app('response')
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json')
        ->withBody(json_encode($data));
}

if (!function_exists('env')) {

    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default instanceof Closure ? $default() : $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return;
        }

//        if (Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
//            return substr($value, 1, -1);
//        }

        return $value;

    }
}


/**
 * @param $template
 * @param null $params
 * @return \Vanilla\Response
 */
function view($template, $params = [])
{
    $content = app('template')->render(str_replace('.', '/', $template) . '.twig', $params);
    return app('response')->setContent($content);
}

/**
 * @param string $content
 * @param int $status
 * @param array $headers
 * @return \Vanilla\Response
 */
function response($content = '', $status = 200, array $headers = array())
{
    return app('response')->withStatus($status)->withBody($content)->withHeader($headers);
}


/**
 * 格式化小数点
 * @param int $input
 * @param string $thousandsSep 千分位符号
 * @return float|string
 */
function numberFormat($input, $thousandsSep = ',')
{
    setlocale(LC_MONETARY, 'zh_CN');
    return (string)number_format($input, 2, '.', $thousandsSep);
}

function config($key = null, $default = null)
{
    if (null === $key) {
        return app('config')->get();
    }

    return app('config')->get($key, $default);
}

function session($key = null, $default = null)
{
    if (null === $key) {
        return $_SESSION;
    }

    if (is_array($key)) {
        $_SESSION = array_merge($_SESSION, $key);

        foreach ($key as $k => $v) {

            if (null === $v) {
                if (array_key_exists($k, $_SESSION)) {
                    unset($_SESSION[$k]);
                }
            }
        }
        return true;
    }

    return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;

}

function uuid($version = 'v4', $request = false)
{
    if (isset($_SERVER[strtoupper('HTTP_X_Ca_Traceid')]) && $request) {
        return $_SERVER[strtoupper('HTTP_X_Ca_Traceid')];
    }

    $uuid = \Ramsey\Uuid\Uuid::uuid4();
    return $uuid->toString();
}

function debug($message, array $context = array())
{
    app('log')->debug($message, $context);
}

function info($message, array $context = array())
{
    app('log')->info($message, $context);
}

function notice($message, array $context = array())
{
    app('log')->notice($message, $context);
}

function warning($message, array $context = array())
{
    app('log')->warning($message, $context);
}

function error($message, array $context = array())
{
    app('log')->error($message, $context);
}

function critical($message, array $context = array())
{
    app('log')->critical($message, $context);
}

function alert($message, array $context = array())
{
    app('log')->alert($message, $context);
}

function emergency($message, array $context = array())
{
    app('log')->emergency($message, $context);
}