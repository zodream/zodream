<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Http;

class URL {

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([app('url'), $name], $arguments);
    }
}