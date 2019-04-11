<?php
/**
 * Created by PhpStorm.
 * User: yanlong
 * Date: 2018/10/29
 * Time: 4:35 PM
 */

namespace Vanilla\Http;


use Vanilla\Exceptions\ValidateException;
use Vanilla\Validation\Validator;

class Controller
{
    public function validate($data, $rule, $messages = [])
    {
        $validator = Validator::make($data, $rule, $messages);
        $result = $validator->fails();
        if ($result) {
            throw new ValidateException($validator->messages());
        }
    }
}