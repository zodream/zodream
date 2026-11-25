<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface HttpContext extends Container {

    public function middleware(mixed ...$middlewares);

    public function input(mixed $request);

    public function path(): string;

    public function instance(string $key, mixed $instance);

    public function handle(Route $route);
}