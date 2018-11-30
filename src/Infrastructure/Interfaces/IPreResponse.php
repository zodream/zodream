<?php
namespace Zodream\Infrastructure\Interfaces;

use Zodream\Infrastructure\Http\Response;

/**
 * 需要在正式响应的时候先执行此程序
 * @package Zodream\Infrastructure\Interfaces
 */
interface IPreResponse {

    public function ready(Response $response);
}