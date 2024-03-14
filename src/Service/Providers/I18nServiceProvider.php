<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;

use Zodream\Infrastructure\Contracts\Translator;
use Zodream\Infrastructure\I18n\PhpSource;
use Zodream\Infrastructure\Support\ServiceProvider;

class I18nServiceProvider extends ServiceProvider {

    public function register(): void {
        $this->app->singletonIf(Translator::class, PhpSource::class);
        $this->app->alias(Translator::class, 'i18n');
    }
}