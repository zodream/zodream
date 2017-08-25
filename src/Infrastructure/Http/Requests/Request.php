<?php
namespace Zodream\Infrastructure\Http\Requests;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
class Request extends BaseRequest {
    public function __construct() {
        $this->setValues($_REQUEST);
    }
}