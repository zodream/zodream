<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Http;

interface Output {

    public function writeLine(mixed $messages);

    public function send();

    public function statusCode(int $code, string $statusText = ''): Output;

    public function allowCors();
}