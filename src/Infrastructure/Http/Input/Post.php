<?php
namespace Zodream\Infrastructure\Http\Input;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
class Post extends BaseInput {
    public function __construct() {
        if ($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json') {
            $_POST = array_merge($_POST, json_decode(file_get_contents('php://input'), true));
        }
        $this->setValues($_POST);
    }
}