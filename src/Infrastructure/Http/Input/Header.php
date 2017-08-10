<?php
namespace Zodream\Infrastructure\Http\Input;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
use Zodream\Infrastructure\Http\Request;
use Zodream\Helpers\Str;

class Header extends BaseInput {
    public function __construct() {
        $server = Request::server();
        foreach ($server as $key => $value) {
            if (Str::startsWith($key, 'http_')) {
                $this->set(Str::firstReplace($key, 'http_'), $value);
            }
        }
    }
}