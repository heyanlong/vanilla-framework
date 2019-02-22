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
    return $app[$make];
}
if (! function_exists('env')) {

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
    return app('response')->setStatusCode($status)->setContent($content)->setHeaders($headers);
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

function delSession($key)
{
    unset($_SESSION[$key]);
}

/**
 * 删除session
 * @return mixed
 */
function clearSession()
{
//    if (!isset($_SESSION)) {
//        session_start();
//    }
//    // 删除所有 Session 变量
//    $_SESSION = [];
//    //判断 cookie 中是否保存 Session ID
//    if (isset($_COOKIE[session_name()])) {
//        setcookie(session_name(), '', time() - 3600, '/');
//    }
    //彻底销毁 Session
    $_SESSION = [];
}

/**
 * 设置全局变量
 * @param $key
 * @param null $value 不传为获取
 * @return mixed|null
 */
function context($key=null, $value=null)
{
    static $parameter;
    if (empty($parameter)){
        $parameter = new \Vanilla\ParameterBag([]);
    }
    if ($value === null){
        if ($key === null){
            return $parameter->all();
        }
        return $parameter->get($key);
    }else {
        $parameter->set($key,$value);
    }
}

/**
 * 模板调用php函数
 * @param $fname 要调用的方法名
 * @param $params 函数所需要的参数
 */
function php_function($fname,$params)
{
    return call_user_func_array($fname,$params);
}

/**
 * 获取客户端IP地址
 * @param integer $type
 *            返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv
 *            是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = null;
    if ($ip !== null) {
        return $ip[$type];
    }

    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array(
        $ip,
        $long
    ) : array(
        '0.0.0.0',
        0
    );
    return $ip[$type];
}

function csrf_token()
{
    if (!empty($ses = session('_token'))){
        return $ses;
    }
    $csrf = md5(mt_rand(1, 999999) . mt_rand(1, 999999) . mt_rand(1, 999999) . microtime(true));
    session(['_token'=>$csrf]);
    return $csrf;
}

function ApipaySubmit($sParaTemp)
{
    return App\Repository\Ys\ApipaySubmitRepository::buildRequest($sParaTemp, 'post', '确认');
}

/**
 * 按type 获取 验证消息
 * @param string $type
 * @return array|bool|null
 */
function getFeedbackMsgByType($type = 'errors')
{
    $erMsg = session('feedback.' . $type);
    if ($erMsg) {
        delSession('feedback.' . $type);
        return $erMsg;
    }
    return null;
}

/**
 * 获取请求id
 */
function getRequestId()
{
    static $requestId = null;

    if ($requestId) {
        return $requestId;
    }

    // 定义REQUESTID，如果请求头中存在则使用传递的值，如果请求头中不存在则创建
    if (isset($_SERVER['HTTP_REQUESTID'])) {
        $requestId = $_SERVER['HTTP_REQUESTID'];
    } else {
        $requestId = createId();
    }

    return $requestId;
}

/**
 * 生成唯一UUID
 */
function createId(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }
}

function access(array $info)
{
    \Vanilla\Log\Log::access($info);
}

function info($format, ...$args)
{
    \Vanilla\Log\Log::info($format, ...$args);
}

function debug($format, ...$args)
{
    \Vanilla\Log\Log::debug($format, ...$args);
}

function error($format, ...$args)
{
    \Vanilla\Log\Log::error($format, ...$args);
}