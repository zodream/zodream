<?php
declare(strict_types=1);
namespace Zodream\Service\Events;

use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\Http\Output;

class RequestHandled {

    /**
     * @var Output
     */
    public $response;

    /**
     * @var Input
     */
    public $request;

    public function __construct($request, $response) {
        $this->response = $response;
        $this->request = $request;
    }
}