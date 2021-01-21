<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Router {

    public function group(array $filters, $cb): Router;
    public function get($uri, $action = null): Route;
    public function post($uri, $action = null): Route;
    public function head($uri, $action = null): Route;
    public function options($uri, $action = null): Route;
    public function delete($uri, $action = null): Route;
    public function put($uri, $action = null): Route;
    public function patch($uri, $action = null): Route;
    public function any($uri, $action = null): Route;

    public function middleware(...$middlewares): Router;

    public function handle(HttpContext $context): Route;

}