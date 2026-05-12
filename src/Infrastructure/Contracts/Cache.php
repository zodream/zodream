<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Cache {

    public function store(string $store): Cache;

    /**
     * @param $key
     * @param $callable
     * @param int|null $duration 当前时间加秒数
     * @param $dependency
     * @return mixed
     */
    public function getOrSet(mixed $key, callable $callable, int|null $duration = null, CacheDependency|null $dependency = null);

    public function get(mixed $key);

    public function set(mixed $key, mixed $value = null, int|null $duration = null, CacheDependency|null $dependency = null);

    public function add(mixed $key, mixed $value, int $duration);

    public function increment(mixed $key, int $value = 1);

    public function decrement(mixed $key, int $value = 1);

    public function has(mixed $key): bool;

    public function delete(mixed $key);

    public function flush();
}