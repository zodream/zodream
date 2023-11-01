<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Http;

interface Output {

    public function writeLine(mixed $messages): void;

    public function send(): bool;

    public function statusCode(int $code, string $statusText = ''): Output;

    public function allowCors(): Output;
}