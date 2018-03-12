<?php
namespace Zodream\Infrastructure\Interfaces;


use Zodream\Infrastructure\Http\Response;

interface Responsable {
    /**
     * Create an HTTP response that represents the object.
     *
     * @return Response
     */
    public function toResponse();
}
