<?php
namespace Zodream\Infrastructure\Http\Input;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */

class Get extends BaseInput {
    public function __construct() {
        $this->setValues($_GET);
    }
}