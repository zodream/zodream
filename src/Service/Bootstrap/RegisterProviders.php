<?php
namespace Zodream\Service\Bootstrap;

use Zodream\Service\Application;

class RegisterProviders {

    public function bootstrap(Application $app)
    {
        $this->registerConfiguredProviders($app);
    }

    private function registerConfiguredProviders(Application $app)
    {
        $providers = config('app.providers');

    }


}