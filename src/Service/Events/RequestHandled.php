<?php
namespace Zodream\Service\Events;


use Zodream\Infrastructure\Http\Response;

class RequestHandled {

    /**
     * @var Response
     */
    public $response;

    public $request;

    public function __construct($request, $response) {
        $this->response = $response;
        $this->request = $request;
    }
}