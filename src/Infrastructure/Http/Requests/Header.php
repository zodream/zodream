<?php
namespace Zodream\Infrastructure\Http\Requests;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\ObjectExpand\StringExpand;

class Header extends BaseRequest {
    public function __construct() {
        $server = Request::server();
        foreach ($server as $key => $value) {
            if (StringExpand::startsWith($key, 'http_')) {
                $this->set(StringExpand::firstReplace($key, 'http_'), $value);
            }
        }
    }
}