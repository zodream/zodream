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
        $data = [];
        foreach ($_SERVER as $key => $value) {
            if (Str::startsWith($key, 'HTTP_')) {
                $data[Str::firstReplace($key, 'HTTP_')] = $value;
            }
        }
        $this->setValues($data);
    }
}