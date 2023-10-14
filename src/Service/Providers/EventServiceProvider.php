<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;

use Zodream\Infrastructure\Event\EventManger;
use Zodream\Infrastructure\Support\BoundMethod;
use Zodream\Infrastructure\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider {


    public function register(): void {
        $this->app->singleton(EventManger::class, function () {
            /** @var EventManger $instance */
            $instance = BoundMethod::newClass(EventManger::class, $this->app);
            $this->registerListeners($instance);
            return $instance;
        });
        $this->app->alias(EventManger::class, 'events');
    }

    protected function registerListeners(EventManger $manger) {
        $events = config('event');
        if (empty($events)) {
            return;
        }
        foreach ($events as $event => $items) {
            foreach ((array)$items as $item) {
                $manger->listen($event, $item);
            }
        }
    }
}
