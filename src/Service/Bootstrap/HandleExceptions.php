<?php
declare(strict_types=1);
namespace Zodream\Service\Bootstrap;

use Exception;
use Throwable;
use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\ExceptionHandler;
use Zodream\Infrastructure\Error\ErrorException;
use Zodream\Infrastructure\Error\FatalErrorException;
use Zodream\Infrastructure\Exceptions\Handler;
use Zodream\Service\Console\Output;

class HandleExceptions {

    /**
     * @var Application
     */
    protected $app;

    public function bootstrap(Application $app)
    {
        $this->app = $app;
        $app->singletonIf(ExceptionHandler::class, Handler::class);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        if (! $app->isDebug()) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  array  $context
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param Throwable $e
     * @return void
     */
    public function handleException(Throwable $e)
    {
        try {
            $this->getExceptionHandler()->report($e);
        } catch (Exception $e) {
            //
        }

        if (false && request()->isCli()) {
            $this->renderForConsole($e);
        } else {
            $this->renderHttpResponse($e);
        }
    }

    /**
     * Render an exception to the console.
     *
     * @param Throwable $e
     * @return void
     */
    protected function renderForConsole(Throwable $e)
    {
        $this->getExceptionHandler()->renderForConsole($this->app->make(Output::class), $e);
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param Throwable $e
     * @return void
     */
    protected function renderHttpResponse(Throwable $e)
    {
        $this->getExceptionHandler()->render($e)->send();
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalErrorFromPhpError($error, 0));
        }
    }

    /**
     * Create a new fatal error instance from an error array.
     *
     * @param  array  $error
     * @param  int|null  $traceOffset
     */
    protected function fatalErrorFromPhpError(array $error, $traceOffset = null)
    {
        return new FatalErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
        );
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int  $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * @return ExceptionHandler
     */
    protected function getExceptionHandler()
    {
        return $this->app->make(ExceptionHandler::class);
    }
}