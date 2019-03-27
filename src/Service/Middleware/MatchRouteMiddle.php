<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


use Zodream\Route\Router;

class MatchRouteMiddle implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        $route = app(Router::class)->getRoute($payload['method'], $payload['uri']);
        if ($route !== false) {
            return $route;
        }
        return $next($payload);
    }
}