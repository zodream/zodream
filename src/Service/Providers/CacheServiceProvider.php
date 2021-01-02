<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;

use Zodream\Infrastructure\Caching\FileCache;
use Zodream\Infrastructure\Support\ServiceProvider;
use Zodream\Infrastructure\Contracts\Cache as CacheInterface;

class CacheServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->scopedIf(CacheInterface::class, FileCache::class);
        $this->app->alias(CacheInterface::class, 'cache');
    }
}