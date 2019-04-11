<?php
/**
 * Created by PhpStorm.
 * User: yanlong
 * Date: 2019-02-28
 * Time: 11:46
 */
namespace Vanilla\Console;

class Command
{
    public function info($txt) {
        echo "\033[32m " . $txt . " \033[0m\r\n";
    }

    public function error($txt) {
        echo "\033[31m " . $txt . " \033[0m\r\n";
    }
}