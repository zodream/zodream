<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Contracts\Route;
use Zodream\Infrastructure\Contracts\Router;
use Zodream\Route\RouteCompiler;

class MatchRouteMiddle implements MiddlewareInterface {

    public function handle(HttpContext $context, callable $next) {
        /** @var Router $router */
        $router = $context[Router::class];
        $route = $router->findRoute($context['request']->method(), $context->path());
        if ($route) {
            return $route;
        }
        return $next($context);
    }

    protected function getRouteFromCache(Router $router, string $method, string $path): ?Route {
        if (!config('route.cacheable', false)) {
           return null;
        }
        $cached = $router->cachePath();
        $compiler = new RouteCompiler();
        if (is_file($cached)) {
            $routes = require $cached;
        } else {
            $routes = $this->setCacheRoutes($compiler, $cached);
        }
        if (empty($routes)) {
            return null;
        }
        return $compiler->match($method, $path, $routes);
    }

    protected function setCacheRoutes(RouteCompiler $compiler, string $file): array {
        $routes = $compiler->getAllRoute();
        file_put_contents($file, '<?php return '.var_export($routes, true).';');
        return $routes;
    }
}