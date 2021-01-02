<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Contracts\Router;

class MatchRouteMiddle implements MiddlewareInterface {

    public function handle(HttpContext $context, callable $next) {
        /** @var Router $router */
        $router = $context[Router::class];
        $route = $router->getRoute($context['request']->method(), $context->path());
        if ($route !== false) {
            return $route;
        }
        return $next($context);
    }
}