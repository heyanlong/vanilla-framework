<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 下午3:59
 */

namespace Vanilla\Exceptions;


use Throwable;

class DBException extends \RuntimeException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, (int)$code, $previous);
    }
}