<?php
declare(strict_types=1);
namespace Zodream\Service\Http;

use RuntimeException;
use Zodream\Infrastructure\Contracts\Http\Output;

class HttpResponseException extends RuntimeException {
    /**
     * The underlying response instance.
     *
     * @var Output
     */
    protected $response;

    /**
     * Create a new HTTP response exception instance.
     *
     * @param  Output  $response
     * @return void
     */
    public function __construct(Output $response) {
        $this->response = $response;
        parent::__construct();
    }

    /**
     * Get the underlying response instance.
     *
     * @return Response
     */
    public function getResponse() {
        return $this->response;
    }
}