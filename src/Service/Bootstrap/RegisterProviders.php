<?php
declare(strict_types=1);
namespace Zodream\Service\Bootstrap;

use Zodream\Domain\Composer\PackageManifest;
use Zodream\Infrastructure\Contracts\Application;

class RegisterProviders {

    public function bootstrap(Application $app)
    {
        $this->registerConfiguredProviders($app);
    }

    private function registerConfiguredProviders(Application $app)
    {
        $providers = config('app.providers', []);
        $data = [[], $app->make(PackageManifest::class)->providers(), []];
        foreach ($providers as $provider) {
            $data[str_starts_with($provider, 'Zodream\\') ? 0 : 2][] = $provider;
        }
        $providers = array_merge(...$data);
        foreach ($providers as $provider) {
            $app->register($provider);
        }
    }


}