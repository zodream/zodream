<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;

use Zodream\Infrastructure\Session\Session;
use Zodream\Infrastructure\Support\ServiceProvider;
use Zodream\Infrastructure\Contracts\Session as SessionInterface;

class SessionServiceProvider extends ServiceProvider {

    public function register(): void {
        $this->app->scopedIf(SessionInterface::class, Session::class);
        $this->app->alias(SessionInterface::class, 'session');
    }
}