<?php
namespace Zodream\Infrastructure\Exceptions;

use Exception;
use HttpException;
use Zodream\Database\Model\ModelNotFoundException;
use Zodream\Domain\Validation\ValidationException;
use Zodream\Domain\Access\AuthenticationException;
use Zodream\Infrastructure\Error\NotFoundHttpException;
use Zodream\Infrastructure\Http\HttpResponseException;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;
use Zodream\Infrastructure\Interfaces\ExceptionHandler;
use Zodream\Service\Factory;

class Handler implements ExceptionHandler {

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [];

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

        Factory::log()->error($e);
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
        $dontReport = array_merge($this->dontReport, [HttpResponseException::class]);
        foreach ($dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render an exception into a response.
     *
     * @param  \Exception  $e
     * @return Response
     */
    public function render(Exception $e) {
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

        if (Request::expectsJson()) {
            return Factory::response()->setStatusCode(422)
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
        $status = $e->getCode();
        Factory::response()->setStatusCode(404);
        if (Factory::view()->exist("errors/{$status}")) {
            return Factory::response()
                ->view("errors/{$status}", ['exception' => $e]);
        }
        if (property_exists($e, '')) {
            return Factory::response()->html($e->xdebug_message);
        }
        return Factory::response()->html($e->getMessage());
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
        return Factory::response()->redirect([Config::auth('home'), 'redirect_uri' => Url::to()]);
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
