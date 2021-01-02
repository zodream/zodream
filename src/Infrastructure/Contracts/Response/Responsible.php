<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Response;

use Zodream\Infrastructure\Contracts\Http\Output;

interface Responsible {
    /**
     * Create an HTTP response that represents the object.
     *
     * @return Output
     */
    public function toResponse(): Output;
}
