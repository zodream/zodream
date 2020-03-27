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
                $data[substr($key, 5)] = $value;
            }
        }
        // 未知原因
        if (isset($_SERVER['CONTENT_TYPE']) && !isset($data['CONTENT_TYPE'])) {
            $data['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
        }
        return $data;
    }
}