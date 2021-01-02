<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Session {

    public function isActive(): bool;

    public function open();

    public function close();

    public function id(string $value = '');

    public function count(): int;

    public function get(string $key = '', $defaultValue = null);

    public function set($key, $value = null);

    public function delete($key);

    public function has($key): bool;

    public function flush();
}