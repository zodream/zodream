<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

use Throwable;
use Zodream\Infrastructure\Contracts\Http\Output;

interface ExceptionHandler {
    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     */
    public function report(Throwable $e): void;

    /**
     * Render an exception into an HTTP response.
     *
     * @param Throwable $e
     * @return Output
     */
    public function render(Throwable $e): Output;

    /**
     * Render an exception to the console.
     *
     * @param Output $output
     * @param Throwable $e
     * @return void
     */
    public function renderForConsole(Output $output, Throwable $e): void;
}