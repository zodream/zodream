<?php
namespace Zodream\Service\Events;


use Zodream\Infrastructure\Http\Response;

class RequestHandled {

    /**
     * @var Response
     */
    public $response;

    public function __construct($response) {
        $this->response = $response;
    }
}