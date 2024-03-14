<?php
declare(strict_types=1);
namespace Zodream\Service\Bootstrap;

use Zodream\Infrastructure\Contracts\Application;

class BootProviders {

    public function bootstrap(Application $app) {
        $app->boot();
    }
}