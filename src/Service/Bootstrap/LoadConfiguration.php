<?php
declare(strict_types=1);
namespace Zodream\Service\Bootstrap;

use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\Config\Repository;
use Zodream\Service\SystemConfig;

class LoadConfiguration {

    public function bootstrap(Application $app)
    {
        $items = [];



        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise
        // we will need to spin through every configuration file and load them all.
        if (is_file($cached = $this->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        // Next we will spin through all of the configuration files in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this app.
        $app->instance('config', $config = new SystemConfig($items));

        if (! isset($loadedFromCache)) {
        }

        date_default_timezone_set($config->get('app.timezone', 'PRC'));
        mb_internal_encoding('UTF-8');
    }

    protected function getCachedConfigPath()
    {
        return (string)app_path('data/cache_config.php');
    }
}