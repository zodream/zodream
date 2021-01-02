<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Response;

use Zodream\Infrastructure\Contracts\Http\Output;

/**
 * 需要在正式响应的时候先执行此程序
 * @package Zodream\Infrastructure\Interfaces
 */
interface PreResponse {

    public function ready(Output $response);
}