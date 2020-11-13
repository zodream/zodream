<?php
namespace Zodream\Service\Bootstrap;

use Zodream\Service\Application;

class BootProviders {
    public function bootstrap(Application $app)
    {
        $app->boot();
    }
}