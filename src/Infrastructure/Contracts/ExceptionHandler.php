<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

use Throwable;
use Zodream\Infrastructure\Http\Response;

interface ExceptionHandler {
    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     */
    public function report(Throwable $e);

    /**
     * Render an exception into an HTTP response.
     *
     * @param Throwable $e
     * @return Response
     */
    public function render(Throwable $e);

    /**
     * Render an exception to the console.
     *
     * @param $output
     * @param Throwable $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e);
}