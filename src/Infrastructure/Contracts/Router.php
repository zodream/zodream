<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Router {

    public function group(array $filters, mixed $cb): Router;
    public function get(string $uri, mixed $action = null): Route;
    public function post(string $uri, mixed $action = null): Route;
    public function head(string $uri, mixed $action = null): Route;
    public function options(string $uri, mixed $action = null): Route;
    public function delete(string $uri, mixed $action = null): Route;
    public function put(string $uri, mixed $action = null): Route;
    public function patch(string $uri, mixed $action = null): Route;
    public function any(string $uri, mixed $action = null): Route;

    public function middleware(mixed ...$middlewares): Router;

    public function findRoute(string $method, string $uri): ?Route;

    public function handle(HttpContext $context): Route;

    /**
     * 获取缓存的地址
     * @return string
     */
    public function cachePath(): string;

}