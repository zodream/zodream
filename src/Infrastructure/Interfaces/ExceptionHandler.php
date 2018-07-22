<?php
namespace Zodream\Infrastructure\Interfaces;

use Exception;
use Zodream\Infrastructure\Http\Response;

interface ExceptionHandler {
    /**
     * Report or log an exception.
     *
     * @param  Exception  $e
     * @return void
     */
    public function report(Exception $e);

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Exception  $e
     * @return Response
     */
    public function render(Exception $e);

    /**
     * Render an exception to the console.
     *
     * @param $output
     * @param  \Exception $e
     * @return void
     */
    public function renderForConsole($output, Exception $e);
}