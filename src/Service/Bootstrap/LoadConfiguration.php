<?php
declare(strict_types=1);
namespace Zodream\Service\Bootstrap;

use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\Config\Repository;
use Zodream\Service\SystemConfig;

class LoadConfiguration {

    public function bootstrap(Application $app) {
        $items = [];
        if (is_file($cached = $this->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }
        $app->singleton(Repository::class, SystemConfig::class);
        $app->alias('config', Repository::class);
        $app->instance('config', $config = new SystemConfig($items));

        if (! isset($loadedFromCache)) {
        }

        date_default_timezone_set($config->get('app.timezone', 'PRC'));
        mb_internal_encoding('UTF-8');

    }

    protected function getCachedConfigPath() {
        return (string)app_path('data/cache_config.php');
    }
}