<?php
namespace Zodream\Infrastructure\Error;

/**
 * NotFoundHttpException.
 *
 */
class NotFoundHttpException extends \RuntimeException {
    /**
     * Constructor.
     *
     * @param string     $message  The internal exception message
     * @param \Exception $previous The previous exception
     * @param int        $code     The internal exception code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 404) {
        parent::__construct($message, $code, $previous);
    }
}