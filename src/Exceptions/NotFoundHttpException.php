<?php
/**
 * Created by PhpStorm.
 * User: heyanlong
 * Date: 2018/7/23
 * Time: 下午3:59
 */

namespace Vanilla\Exceptions;


class NotFoundHttpException extends HttpException
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, array(), $code);
    }
}