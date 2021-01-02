<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;

use Zodream\Domain\Access\Auth;
use Zodream\Infrastructure\Contracts\AuthObject;
use Zodream\Infrastructure\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->scopedIf(AuthObject::class, Auth::class);
        $this->app->alias(AuthObject::class, 'auth');
    }
}