<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Error;

class HttpException extends RuntimeException {

    public function __construct(
        protected  int $statusCode, string|null $message = null, \Throwable|null $previous = null,
        protected array $headers = [], int|null $code = 0) {
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