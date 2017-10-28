<?php 
namespace Zodream\Infrastructure\Error;
/**
* 错误信息类
* 
* @author Jason
*/
use Exception;
use ErrorException;
use Zodream\Service\Config;
use Zodream\Service\Factory;

class Error {

    /**
     * 启动 如果有 xdebug 就用 xdebug
     */
    public function bootstrap() {
       if (Config::isDebug() && function_exists('xdebug_get_function_stack')) {
           error_reporting(E_ALL);
           return;
       }
        error_reporting(-1);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        if (!Config::isDebug()) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param  int $level
     * @param  string $message
     * @param  string $file
     * @param  int $line
     * @return void
     * @throws ErrorException
     * @internal param array $context
     */
    public function handleError($level, $message, $file = '', $line = 0) {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function handleException($e)
    {
        if (! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        $this->getExceptionHandler()->report($e);

        $this->renderHttpResponse($e);
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function renderForConsole(Exception $e) {
        // 命令行输出
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param Exception $e
     * @return void
     */
    protected function renderHttpResponse(Exception $e) {
        $this->getExceptionHandler()->render($e)->send();
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown() {
        $error = error_get_last();
        if (is_null($error)) {
            return;
        }
        if ($this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * @param  array $error
     * @param  int|null $traceOffset
     * @return FatalErrorException
     */
    protected function fatalExceptionFromError(array $error, $traceOffset = null) {
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
    protected function isFatal($type) {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    protected function getExceptionHandler() {
        return Factory::handler();
    }
}