<?php
namespace Zodream\Infrastructure\Exceptions;

use Exception;
use Zodream\Database\Model\ModelNotFoundException;
use Zodream\Domain\Access\AuthorizationException;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Contracts\Debugger;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\Response\Responsible;
use Zodream\Infrastructure\Error\HttpException;
use Zodream\Route\Exception\NotFoundHttpException;
use Zodream\Service\Http\HttpResponseException;
use Zodream\Validate\ValidationException;
use Zodream\Domain\Access\AuthenticationException;
use Zodream\Infrastructure\Contracts\ExceptionHandler;
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
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $e) {
        if ($this->shouldntReport($e)) {
            return;
        }

        if (method_exists($e, 'report')) {
            return $e->report();
        }

        logger()->error(
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
    public function shouldReport(Throwable $e) {
        return ! $this->shouldntReport($e);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function shouldntReport(Throwable $e) {
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
     * @param  Throwable $e
     * @return Output
     * @throws Exception
     */
    public function render(Throwable $e) {
        if (method_exists($e, 'render') && $response = $e->render()) {
            return $response instanceof Output ? $response
                : app('response')->setParameter($response);
        } elseif ($e instanceof Responsible) {
            return $e->toResponse();
        }
        $e = $this->prepareException($e);
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            return $this->unauthenticated($e);
        } elseif ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e);
        } elseif ($e instanceof NotFoundHttpException && $response = $this->notFound($e)) {
            return $response;
        }
        return $this->prepareResponse($e);
    }

    protected function notFound(NotFoundHttpException $e) {
        return Str::call(config('route.not-found'), [$e], false);
    }

    /**
     * Prepare exception for rendering.
     *
     * @param  Throwable  $e
     * @return \Exception
     */
    protected function prepareException(Throwable $e) {
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
     * @return Output
     */
    protected function convertValidationExceptionToResponse(ValidationException $e) {
//        if ($e->response) {
//            return $e->response;
//        }

        $errors = $e->validator->errors();

        if (request()->expectsJson()) {
            return response()->statusCode(422)
                ->json($errors);
        }


    }

    /**
     * Prepare response containing exception render.
     *
     * @param  Throwable $e
     * @return Output
     * @throws Exception|Throwable
     */
    protected function prepareResponse(Throwable $e){
        /** @var Debugger $debugger */
        $debugger = app('debugger');
        if (!$debugger) {
            throw $e;
        }
        $debugger->exceptionHandler($e, true);
        return app('response');
    }

    /**
     * Render the given HttpException.
     *
     * @param  HttpException  $e
     * @return Output
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
        return app('response')->redirect([config('auth.home'), 'redirect_uri' => url()->current()]);
    }

    /**
     * Render an exception to the console.
     *
     * @param \Zodream\Service\Console\Output $output
     * @param Throwable $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e) {
        do {
            $output->writeln(sprintf('%s in %s: %d', $e->getMessage(), $e->getFile(), $e->getLine()));
        } while ($e = $e->getPrevious());
    }
}
