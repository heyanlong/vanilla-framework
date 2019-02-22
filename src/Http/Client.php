<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/24
 * Time: 下午2:25
 */

namespace Vanilla\Http;

use GuzzleHttp\TransferStats;

class Client extends \GuzzleHttp\Client
{
    public function __construct(array $config = [])
    {
        $config = array_merge(['timeout' => 10, 'verify' => false], $config);
        parent::__construct($config);
    }

    public function request($method, $uri = '', array $options = [])
    {
        $log = [
            'serviceStart' => (new \DateTime())->format('Y-m-d H:i:s.u')
        ];
        $options = array_merge(
            [
                'on_stats' => function (TransferStats $stats) use (&$log, $uri) {
                    $log['elapsed'] = (int)bcmul($stats->getHandlerStat('total_time'), 1000);
                    $log['namelookupTime'] = $stats->getHandlerStat('namelookup_time');
                    $log['connectTime'] = $stats->getHandlerStat('connect_time');
                    $log['requestUri'] = $uri;
                },
                'force_ip_resolve' => 'v4'
            ]
            , $options);
        $response = parent::request($method, $uri, $options);

        if (isset($options['query'])) {
            $log['arguments']['query'] = $options['query'];
        }

        if (isset($options['headers'])) {
            $headers = $options['headers'];
            if (isset($headers['Authorization'])) {
                unset($headers['Authorization']);
            }
            $log['arguments']['headers'] = $headers;
        }

        if (isset($options['json'])) {
            $log['arguments']['json'] = $options['json'];
        }

        $log['status'] = $response->getStatusCode();
        $log['requestMethod'] = $method;
        $log['serviceEnd'] = (new \DateTime())->format('Y-m-d H:i:s.u');
        $log['result'] = $response->getBody()->getContents();
        $response->getBody()->rewind();
        info($log);
//        Log::writeRunLog('api calls logging', $log);
        return $response;
    }
}