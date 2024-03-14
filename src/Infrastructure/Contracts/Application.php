<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Application extends Container {

    public function boot();

    public function setLocale(string $locale): void;

    public function getLocale(): string;

    public function alias($abstract, $alias);

    public function singleton($abstract, $concrete = null);

    public function singletonIf($abstract, $concrete = null);

    public function instance(string $abstract, $instance);

    public function transient($abstract, $concrete = null);

    public function transientIf($abstract, $concrete = null);

    public function scoped($abstract, $concrete = null);

    public function scopedIf($abstract, $concrete = null);

    public function middleware(...$middlewares);

    public function listen(): void;

}