<?php

namespace Vanilla\Log;

class Log
{

    protected static $access = [];

    protected static $run = [];

    protected static $error = [];

    // 日志信息
    protected static $log = [];

    // 接口响应状态
    protected static $logStatus = 200;

    // 日志文件大小限制，单位：M
    protected static $logFileSize = 20;

    public static function access(array $info)
    {
        static::$access = array_merge(static::$access, $info);
    }

    public static function debug($format, ...$args)
    {
        static::$log[] = [
            'level' => 'debug',
            'type' => 'run',
            'desc' => static::_format($format, ...$args)
        ];
    }

    public static function info($format, ...$args)
    {
        static::$log[] = [
            'level' => 'info',
            'type' => 'run',
            'desc' => static::_format($format, ...$args)
        ];
    }

    public static function error($format, ...$args)
    {
        static::$error['desc'] = static::_format($format, ...$args);
    }

    private static function _format($format, ...$args)
    {
        if (!empty($args)) {
            foreach ($args as $key => $arg) {
                switch (gettype($arg)) {
                    case 'string':
                        $format = preg_replace('/\{\}/', '%s', $format, 1);
                        break;
                    case 'boolean':
                        $args[$key] = $arg ? 'true' : 'false';
                        $format = preg_replace('/\{\}/', '%s', $format, 1);
                        break;
                    case 'array':
                        $args[$key] = json_encode($arg, JSON_UNESCAPED_UNICODE);
                        $format = preg_replace('/\{\}/', '%s', $format, 1);
                        break;
                    case 'object':
                        if ($arg instanceof \Exception) {
                            $args[$key] = json_encode(['code' => $arg->getCode(), 'message' => $arg->getMessage(), 'trace' => $arg->getTrace()], JSON_UNESCAPED_UNICODE);
                        } else {
                            try {
                                $args[$key] = serialize($arg);
                            } catch (\Exception $e) {
                                $args[$key] = json_encode((array)$arg, JSON_UNESCAPED_UNICODE);
                            }
                        }
                        $format = preg_replace('/\{\}/', '%s', $format, 1);
                        break;
                    case 'resource':
                        $args[$key] = 'resource';
                        $format = preg_replace('/\{\}/', '%s', $format, 1);
                        break;
                    case 'double':
                        $format = preg_replace('/\{\}/', '%f', $format, 1);
                        break;
                    case 'integer':
                        $format = preg_replace('/\{\}/', '%d', $format, 1);
                        break;
                    case 'float':
                        $format = preg_replace('/\{\}/', '%f', $format, 1);
                        break;
                    case 'NULL':
                        $args[$key] = 'NULL';
                        $format = preg_replace('/\{\}/', '%s', $format, 1);
                        break;
                }
            }
        }
        if (is_string($format)) {
            return sprintf($format, ...$args);
        }
        return $format;
    }

    /**
     * 日志保存
     */
    public static function save()
    {

        // 定义初始父id
        $parent = 'ROOT';

        // 时间拼接毫秒数
        $startTime = BEGIN_TIME;

        $endTime = microtime(true);

        // 获取请求信息
        $currentRoute = app('router')->getCurrentRoute();
        $uri = app('request')->getRequestUri();
        $uses = '';
        $method = '';

        if (!empty($currentRoute) && isset($currentRoute[1])) {
            $uses = $currentRoute[1]['uses'];
            list ($thisController, $method) = explode('@', $uses);
        } else {
            static::$logStatus = 404;
        }
        $log = static::buildAccess();
        $logs[] = static::afterBuild($log, 0, $parent, BEGIN_TIME, $endTime, $uses, $uri, $method);
        $parent = $log['spanId'];

        // build
        if (!empty(static::$log)) {
            foreach (static::$log as $key => $item) {
                $log = static::build(['desc' => $item['desc']], $item['level'], 'run');
                $logs[] = static::afterBuild($log, $key + 1, $parent, BEGIN_TIME, $endTime, $uses, $uri, $method);
                $parent = $log['spanId'];
            }
        }

        // 错误日志
        if (static::$error) {
            $log = static::build(static::$error, 'emerg', 'error');
            $logs[] = static::afterBuild($log, 1, $parent, BEGIN_TIME, $endTime, $uses, $uri, $method);
        }

        foreach ($logs as $value) {
            // 将日志记录在本地文件
            try {
                self::write($value['info'], $value['type']);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    /**
     * 将日志写入文件
     * 如果在零点的时候，临时文件都已经移动到日期目录了，这时还有前一天的日志文件没有写入文件，会写入到当天的文件里。
     */
    static private function write($log, $level)
    {
        // 定义日志文件类型
        switch ($level) {
            case 'access':
                $typeName = 'access';
                break;
            case 'run':
                $typeName = 'run';
                break;
            default:
                $typeName = 'error';
                break;
        }

        if (empty($module)) {
            $module = 'Default';
        }

        // 定义日志目录与日志文件路径
        $logPath = app()->basePath() . '/logs/' . date('Y-m-d') . '/';
        $destination = $logPath . $typeName . '.log';

        // 如果日志目录不存在，则创建日志目录
        $log_dir = dirname($destination);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        // 检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && floor(self::$logFileSize * 1048576) <= filesize($destination)) {
            $newname = $log_dir . '/' . basename($destination) . '.' . time();
            rename($destination, $newname);
        }

        error_log($log, 3, $destination);
    }

    /**
     * 获取请求数据
     */
    static private function getRequestData()
    {
        $log = [];
        $log['requestHost'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']; // 记录请求域名
        $log['referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $log['contentType'] = $_SERVER['CONTENT_TYPE'];
            $log['apiContentType'] = $_SERVER['CONTENT_TYPE'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = $_REQUEST;
        } else {
            if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false || strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
                $data = $_REQUEST;
            } elseif (strpos($_SERVER['CONTENT_TYPE'], 'application/octet-stream') !== false) {
                $data = 'Binary file stream';
            } else {
                $data = json_decode(file_get_contents("php://input"), true);
            }
        }
        if (!empty($data)) {
            $log['arguments']['data'] = $data;
            $log['apiArguments'] = $data;
        } else {
            $log['apiArguments'] = [];
        }

        return $log;
    }

    public static function buildAccess()
    {
        $system = static::build([], 'access', 'access');
        return array_merge($system, static::$access);
    }

    private static function build($params, $level, $type)
    {
        // 日志记录时间
        $nowTime = microtime(true);
        $nowWm = explode(".", $nowTime);
        $now = date('Y-m-d H:i:s', $nowWm[0]) . '.' . substr($nowWm[1], 0, 3);

        $log['nowTime'] = $now; // 记录日志时间
        $log['hostName'] = php_uname('n'); // 服务器名称
        $log['apiHostname'] = php_uname('n'); // 服务器名称
        $log['hostAddress'] = $_SERVER['SERVER_ADDR'] ?? ''; // 服务器ip地址
        $log['traceId'] = getRequestId(); // 链条id，请求id（例：一次请求十条日志，十条日志的traceid相同）
        $log['spanId'] = createId(); // 日志id（例：一次请求十条日志，十条日志的spanId不同）
        $log['clientIp'] = get_client_ip(0, true); // 客户端ip
        $log['logLevel'] = $level; // 日志级别
        $log['logType'] = $type; // 日志类型

        if ($type === 'access') {
            // 合并请求数据
            $log = array_merge($log, static::getRequestData());
        }

        // 自定义参数
        if (!empty($params)) {
            $log = array_merge($log, $params);
        }

//        if ($logType === 'error') {
//            self::$logStatus = 500;
//            $logMsg['throwable'] = $log; // 异常堆栈信息
//        } else {
//            $logMsg['desc'] = $log; // 自定义日志内容
//        }

        return $log;
    }

    private static function afterBuild($log, $index, $parent, $start, $end, $routePath, $uri, $method)
    {
        // 时间拼接毫秒数
        $startTime = $start;
        $startWm = explode(".", $startTime);
        $serviceStart = date('Y-m-d H:i:s', $startWm[0]) . '.' . substr($startWm[1], 0, 3);

        $endTime = $end;
        $endWm = explode(".", $endTime);
        $serviceEnd = date('Y-m-d H:i:s', $endWm[0]) . '.' . substr($endWm[1], 0, 3);

        // 统一记录请求信息
        $log['business'] = $routePath;
        $log['businessAlias'] = $routePath; // 业务描述
        $log['methodName'] = $method;
        $log['requestUri'] = $uri;
        $log['apiUrl'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $uri;
        $log['apiMethod'] = app('request')->getMethod();

        // 请求开始时间、结束时间
        if (!isset($log['serviceStart'])) {
            $log['serviceStart'] = $serviceStart;
            $log['apiStart'] = $serviceStart;
        } else {
            $log['apiStart'] = $log['serviceStart'];
        }

        if (!isset($log['serviceEnd'])) {
            $log['serviceEnd'] = $serviceEnd;
            $log['apiEnd'] = $serviceEnd;
        } else {
            $log['apiEnd'] = $log['serviceEnd'];
        }

        // 第一条为访问日志
        if ($log['logType'] === 'access') {
            // 计算总耗时
            $log['elapsed'] = intval(bcmul(bcsub($endTime, $startTime, 4), 1000));
            $log['apiElapsed'] = intval(bcmul(bcsub($endTime, $startTime, 4), 1000));

            // 接口状态、出参
            $log['status'] = self::$logStatus;
            $log['apiStatus'] = self::$logStatus;
            $log['result'] = '';
        }

        $log['level'] = $index; // 记录日志级别
        $log['parentId'] = $parent; // 记录父级日志id

        // 处理日志内容
        $logTime = $log['nowTime'];
        unset($log['nowTime']);
        ksort($log);
        return [
            'type' => $log['logType'],
            'info' => $logTime . " - ###" . json_encode($log, JSON_UNESCAPED_UNICODE) . '###' . PHP_EOL
        ];
    }
}
