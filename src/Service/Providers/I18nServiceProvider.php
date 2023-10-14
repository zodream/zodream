<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;

use Zodream\Infrastructure\I18n\I18n;
use Zodream\Infrastructure\I18n\PhpSource;
use Zodream\Infrastructure\Support\ServiceProvider;

class I18nServiceProvider extends ServiceProvider {

    public function register(): void {
        $this->app->singletonIf(I18n::class, PhpSource::class);
        $this->app->alias(I18n::class, 'i18n');
    }
}