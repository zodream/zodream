<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Http\Response;
use Zodream\Route\Route;
use Zodream\Route\Router;

class RouterMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        /** @var Route $route */
        $route = app(Router::class)->handle(
            app('request')->method(),
            $payload);
        app()->instance(Route::class, $route);
        $response = $route->handle(app('request'), app('response'));
        return $next($this->format($response));
    }

    protected function format($response): Response {
        return $response instanceof Response ? $response : app('response')->setParameter($response);
    }
}