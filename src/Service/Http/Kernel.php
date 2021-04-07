<?php
declare(strict_types=1);
namespace Zodream\Service\Http;

use Throwable;
use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\Container;
use Zodream\Infrastructure\Contracts\ExceptionHandler;
use Zodream\Infrastructure\Contracts\Kernel as KernelInterface;
use Zodream\Infrastructure\Contracts\Router;
use Zodream\Infrastructure\Pipeline\MiddlewareProcessor;
use Zodream\Service\Bootstrap\BootProviders;
use Zodream\Service\Bootstrap\HandleExceptions;
use Zodream\Service\Bootstrap\LoadConfiguration;
use Zodream\Service\Bootstrap\RegisterProviders;
use Zodream\Service\Events\RequestHandled;
use Zodream\Service\Middleware\CacheMiddleware;
use Zodream\Service\Middleware\CORSMiddleware;
use Zodream\Service\Middleware\DomainMiddleware;
use Zodream\Service\Middleware\GZIPMiddleware;
use Zodream\Service\Middleware\MatchRouteMiddle;
use Zodream\Infrastructure\Contracts\HttpContext as HttpContextInterface;

class Kernel implements KernelInterface {

    /**
     * @var Application|Container
     */
    protected $app;

    /**
     * @var Router
     */
    protected $router;

    protected array $bootstrapper = [
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
    protected array $middleware = [
        GZIPMiddleware::class,
        DomainMiddleware::class,
        CORSMiddleware::class,
        CacheMiddleware::class,
    ];

    protected array $routeMiddleware = [
        MatchRouteMiddle::class,
    ];

    public function __construct(Application $app, Router $router) {
        $this->app = $app;
        $this->router = $router;
        $this->syncMiddlewareToRouter();
    }

    public function getContainer(): Container
    {
        return $this->app;
    }

    public function handle($request, array $middlewares = [])
    {
        if (!empty($middlewares)) {
            $this->middleware = array_merge($middlewares, $this->middleware);
        }
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

    public function bootstrap()
    {
        $this->getContainer()->bootstrapWith($this->bootstrapper);
    }

    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        $this->bootstrap();
        /** @var HttpContextInterface $context */
        $context = $this->app->make(HttpContextInterface::class);
        $context->input($request);
        return (new MiddlewareProcessor($context))
            ->send($context)->through($this->middleware)
            ->then($this->dispatchToRouter());
    }

    protected function dispatchToRouter()
    {
        return function (HttpContextInterface $context) {
            return $context->handle($this->router->handle($context));
        };
    }

    protected function syncMiddlewareToRouter() {
        $this->router->middleware(...$this->routeMiddleware);
    }

    protected function reportException(Throwable $e)
    {
        $this->app->make(ExceptionHandler::class)->report($e);
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
        return $this->app->make(ExceptionHandler::class)->render($e);
    }

    public function terminate($request, $response)
    {
        // TODO: Implement terminate() method.
    }

    public function receive()
    {
        return Request::createFromGlobals();
    }
}