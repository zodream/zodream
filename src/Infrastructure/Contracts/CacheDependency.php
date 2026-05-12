<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;


interface CacheDependency {

    public function evaluateDependency(Cache $cache): void;

    public function isChanged(Cache $cache): bool;
}