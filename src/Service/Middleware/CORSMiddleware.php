<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


class CORSMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        if (!app('request')->isPreFlight()) {
            return $next($payload);
        }
        return app('response')->allowCors();
    }
}