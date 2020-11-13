<?php
namespace Zodream\Service;

use Throwable;
use Zodream\Infrastructure\Error\HandleExceptions;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;
use Zodream\Infrastructure\Interfaces\ExceptionHandler;
use Zodream\Infrastructure\Pipeline\MiddlewareProcessor;
use Zodream\Infrastructure\Pipeline\Pipeline;
use Zodream\Route\Router;
use Zodream\Service\Bootstrap\BootProviders;
use Zodream\Service\Bootstrap\LoadConfiguration;
use Zodream\Service\Bootstrap\RegisterProviders;
use Zodream\Service\Events\RequestHandled;
use Zodream\Service\Middleware\CacheMiddleware;
use Zodream\Service\Middleware\CORSMiddleware;
use Zodream\Service\Middleware\DefaultRouteMiddle;
use Zodream\Service\Middleware\DomainMiddleware;
use Zodream\Service\Middleware\GZIPMiddleware;
use Zodream\Service\Middleware\MatchRouteMiddle;
use Zodream\Service\Middleware\ModuleMiddleware;
use Zodream\Service\Middleware\RouterMiddleware;

class Kernel {

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Router
     */
    protected $router;

    protected $bootstrapper = [
        LoadConfiguration::class,
        HandleExceptions::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        GZIPMiddleware::class,
        DomainMiddleware::class,
        CORSMiddleware::class,
        CacheMiddleware::class
    ];

    protected $routeMiddleware = [
        MatchRouteMiddle::class,
        ModuleMiddleware::class,
        DefaultRouteMiddle::class
    ];

    public function __construct(Application $app, Router $router) {
        $this->app = $app;
        $this->router = $router;
        $this->syncMiddlewareToRouter();
    }

    public function handle($request) {
        try {
            $response = $this->sendRequestThroughRouter($request);
        } catch (Throwable $e) {
            $this->reportException($e);
            $response = $this->renderException($request, $e);
        }
        $this->app['events']->dispatch(
            new RequestHandled($request, $response)
        );

        return $response;
    }

    public function bootstrap() {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrapper);
        }
    }

    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        $this->bootstrap();
        $middlewares = array_merge($this->middleware, [RouterMiddleware::class]);;
        return (new MiddlewareProcessor())
            ->process($request, ...$middlewares);
    }

    protected function dispatchToRouter()
    {
        return function ($request) {
            $this->app->instance('request', $request);

            return $this->router->handle($request);
        };
    }

    protected function syncMiddlewareToRouter() {
        $this->router->middleware($this->routeMiddleware);
    }

    protected function reportException(Throwable $e)
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the exception to a response.
     *
     * @param  Request  $request
     * @param Throwable $e
     * @return Response
     */
    protected function renderException($request, Throwable $e)
    {
        return $this->app[ExceptionHandler::class]->render($request, $e);
    }
}