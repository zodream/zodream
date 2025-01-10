<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Error;

use Throwable;

class DatabaseException extends RuntimeException {


    public function __construct(
        string $message = '',
        int $code = 0,
        Throwable|null $previous = null) {
        parent::__construct($message, $code, $previous);
        if ($previous) {
            $this->line = $previous->getLine();
            $this->file = $previous->getFile();
        }
    }

}