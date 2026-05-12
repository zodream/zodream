<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Session {

    public function isActive(): bool;

    public function open();

    public function close();

    public function id(string $value = '');

    public function count(): int;

    public function get(string $key = '', mixed $defaultValue = null);

    public function set(string $key, mixed $value = null);

    public function delete(string $key);

    public function has(string $key): bool;

    public function flush();
}