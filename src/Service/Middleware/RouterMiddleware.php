<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Contracts\Router;
use Zodream\Route\Route;

class RouterMiddleware implements MiddlewareInterface {

    public function handle(HttpContext $context, callable $next) {
        /** @var Router $router */
        $router = $context[Router::class];
        $route = $router->handle($context);
        $context->instance(Route::class, $route);
        $response = $route->handle($context);
        return $next($this->format($response, $context));
    }

    protected function format($response, HttpContext $context): Output {
        return $response instanceof Output ? $response : $context['response']->setParameter($response);
    }
}