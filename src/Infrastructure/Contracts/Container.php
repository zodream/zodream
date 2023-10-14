<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Container {

    public function has(string $abstract): bool;

    public function flush(): void;

    public function make(string $abstract, array $parameters = []);
}