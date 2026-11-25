<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Application extends Container {

    public function boot();

    public function setLocale(string $locale): void;

    public function getLocale(): string;

    public function alias(string $abstract, string $alias);

    public function singleton(mixed $abstract, mixed $concrete = null);

    public function singletonIf(mixed $abstract, mixed $concrete = null);

    public function instance(string $abstract, mixed $instance);

    public function transient(mixed $abstract, mixed $concrete = null);

    public function transientIf(mixed $abstract, mixed $concrete = null);

    public function scoped(mixed $abstract, mixed $concrete = null);

    public function scopedIf(mixed $abstract, mixed $concrete = null);

    public function middleware(mixed ...$middlewares);

    public function listen(): void;

}