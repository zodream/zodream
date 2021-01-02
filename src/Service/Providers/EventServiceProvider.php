<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;

use Zodream\Infrastructure\Event\EventManger;
use Zodream\Infrastructure\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider {


    public function register() {
        $this->app->singleton(EventManger::class);
        $this->app->alias(EventManger::class, 'events');
    }
}
