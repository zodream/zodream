<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Error;

use RuntimeException;

class HttpException extends RuntimeException {
    private $statusCode;
    private $headers;

    public function __construct(int $statusCode, string $message = null, \Throwable $previous = null, array $headers = [], ?int $code = 0) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode() {
        return $this->statusCode;
    }

    public function getHeaders() {
        return $this->headers;
    }

    /**
     * 设置响应头
     *
     * @param array $headers Response headers
     */
    public function setHeaders(array $headers) {
        $this->headers = $headers;
    }
}