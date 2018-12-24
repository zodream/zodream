<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


class ResponseBodyMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        return $next($payload);
    }
}