<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Route\ModuleRoute;

class DefaultRouteMiddle implements MiddlewareInterface {

    public function handle(HttpContext $context, callable $next) {
        return $context->make(ModuleRoute::class);
    }
}