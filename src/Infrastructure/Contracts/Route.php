<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Route {

    public function middleware(mixed ...$middlewares): Route;
    public function method(array $methods): Route;

    public function handle(HttpContext $context);
}