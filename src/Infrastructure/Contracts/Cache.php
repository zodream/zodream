<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Cache {

    public function store(string $store): Cache;

    public function getOrSet($key, $callable, $duration = null, $dependency = null);

    public function get($key);

    public function set($key, $value = null, $duration = null, $dependency = null);

    public function add($key, $value, $duration);

    public function increment($key, int $value = 1);

    public function decrement($key, int $value = 1);

    public function has($key): bool;

    public function delete($key);

    public function flush();
}