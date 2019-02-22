<?php
/**
 * Created by PhpStorm.
 * User: 210440
 * Date: 2018/9/4
 * Time: 9:48
 */

namespace Vanilla\Exceptions;

class MethodNotAllowedHttpException extends HttpException
{
    public function __construct($message = null, \Exception $previous = null, $code = 405)
    {
        parent::__construct(405, $message, $previous, array(), $code);
    }
}