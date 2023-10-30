<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Error;

use RuntimeException;

class HttpException extends RuntimeException {

    public function __construct(
        protected  int $statusCode, string $message = null, \Throwable $previous = null,
        protected array $headers = [], ?int $code = 0) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * 设置响应头
     *
     * @param array $headers Response headers
     */
    public function setHeaders(array $headers): void {
        $this->headers = $headers;
    }
}