<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


use Zodream\Route\Route;
use Zodream\Route\Router;

class DefaultRouteMiddle implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        $path = $payload['uri'];
        return new Route($payload['uri'], function() use ($path) {
            return app(Router::class)->invokePath($path, 'Service\\'.app('app.module'));
        }, [$payload['method']]);
    }
}