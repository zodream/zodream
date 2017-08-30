<?php
namespace Zodream\Infrastructure\Http\Input;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
use Zodream\Helpers\Str;

class Header extends BaseInput {
    public function __construct() {
        foreach ($_SERVER as $key => $value) {
            if (Str::startsWith($key, 'http_')) {
                $this->set(Str::firstReplace($key, 'http_'), $value);
            }
        }
    }
}