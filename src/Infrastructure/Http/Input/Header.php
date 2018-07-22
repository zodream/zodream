<?php
namespace Zodream\Infrastructure\Http\Input;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
use Zodream\Helpers\Str;

trait Header {
    protected function createHeader() {
        $data = [];
        foreach ($_SERVER as $key => $value) {
            if (Str::startsWith($key, 'HTTP_')) {
                $data[Str::firstReplace($key, 'HTTP_')] = $value;
            }
        }
        return $data;
    }
}