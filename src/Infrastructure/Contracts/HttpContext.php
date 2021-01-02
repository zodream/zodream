<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface HttpContext extends Container {

    public function middleware(...$middlewares);

    public function input($request);

    public function path(): string;

    public function instance(string $key, $instance);

    public function handle(Route $route);
}