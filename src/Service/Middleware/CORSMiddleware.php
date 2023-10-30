<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\HttpContext;

class CORSMiddleware implements MiddlewareInterface {

    public function handle(HttpContext $context, callable $next) {
        if (!$context['request']->isPreFlight()) {
            return $next($context);
        }
        return $context['response']->allowCors();
    }
}