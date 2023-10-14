<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

use Zodream\Infrastructure\Contracts\Http\Input;

interface Kernel {

    public function getContainer(): Container;

    public function handle($request, array $middlewares = []): mixed;

    public function bootstrap(): void;

    public function terminate($request, $response): void;

    /**
     * 接收信息，并进行格式化
     * @return Input
     */
    public function receive(): Input;
}