<?php
namespace Zodream\Infrastructure\Exceptions;

use Exception;
use HttpException;
use Zodream\Database\Model\ModelNotFoundException;
use Zodream\Domain\Access\AuthorizationException;
use Zodream\Infrastructure\Http\HttpResponseException;
use Zodream\Infrastructure\Interfaces\Responsable;
use Zodream\Route\Router;
use Zodream\Service\Config;
use Zodream\Validate\ValidationException;
use Zodream\Domain\Access\AuthenticationException;
use Zodream\Infrastructure\Error\NotFoundHttpException;
use Zodream\Infrastructure\Http\Response;
use Zodream\Infrastructure\Interfaces\ExceptionHandler;
use Zodream\Service\Factory;
use Throwable;

class Handler implements ExceptionHandler {

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [];

    protected $internalDontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $e) {
        if ($this->shouldntReport($e)) {
            return;
        }

        if (method_exists($e, 'report')) {
            return $e->report();
        }


        Factory::log()->error(
            $e->getMessage(),
            array_merge($this->context(), ['exception' => $e]
            ));
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Exception  $e
     * @return bool
     */
    public function shouldReport(Exception $e) {
        return ! $this->shouldntReport($e);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function shouldntReport(Exception $e) {
        $dontReport = array_merge($this->dontReport, $this->internalDontReport);
        foreach ($dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the default context variables for logging.
     *
     * @return array
     */
    protected function context() {
        try {
            return array_filter([
                'userId' => auth()->id(),
                'email' => auth()->user() ? auth()->user()->email : null,
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Render an exception into a response.
     *
     * @param  \Exception $e
     * @return Response
     * @throws Exception
     */
    public function render(Exception $e) {
        if (method_exists($e, 'render') && $response = $e->render()) {
            return $response instanceof Response ? $response
                : app('response')->setParameter($response);
        } elseif ($e instanceof Responsable) {
            return $e->toResponse();
        }
        $e = $this->prepareException($e);
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            return $this->unauthenticated($e);
        } elseif ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e);
        }
        return $this->prepareResponse($e);
    }

    /**
     * Prepare exception for rendering.
     *
     * @param  \Exception  $e
     * @return \Exception
     */
    protected function prepareException(Exception $e) {
        if ($e instanceof ModelNotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new HttpException(403, $e->getMessage());
        }
        return $e;
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  ValidationException  $e
     * @return Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e) {
        if ($e->response) {
            return $e->response;
        }

        $errors = $e->validator->errors()->getMessages();

        if (app('request')->expectsJson()) {
            return app('response')->setStatusCode(422)
                ->json($errors);
        }


    }

    /**
     * Prepare response containing exception render.
     *
     * @param  \Exception $e
     * @return Response
     */
    protected function prepareResponse(Exception $e){
        app('debugger')->exceptionHandler($e, true);
        return app('response');
    }

    /**
     * Render the given HttpException.
     *
     * @param  HttpException  $e
     * @return Response
     */
    protected function renderHttpException(HttpException $e) {

    }

    /**
     * Determine if the given exception is an HTTP exception.
     *
     * @param  Exception  $e
     * @return bool
     */
    protected function isHttpException(Exception $e) {
        return $e instanceof HttpException;
    }

    public function unauthenticated(AuthenticationException $e) {
        return app('response')->redirect([Config::auth('home'), 'redirect_uri' => url()->current()]);
    }

    /**
     * Render an exception to the console.
     *
     * @param $output
     * @param  \Exception $e
     * @return void
     */
    public function renderForConsole($output, Exception $e) {
        // TODO: Implement renderForConsole() method.
    }
}
