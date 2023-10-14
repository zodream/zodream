<?php
declare(strict_types=1);
namespace Zodream\Service\Console;

use Zodream\Domain\Composer\PackageManifest;
use Zodream\Infrastructure\Contracts\HttpContext as HttpContextInterface;
use Zodream\Service\Middleware\CacheMiddleware;
use Zodream\Infrastructure\Contracts\Http\Input as InputInterface;
use Zodream\Infrastructure\Contracts\Http\Output as OutputInterface;
use Zodream\Service\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {
    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected array $middleware = [
        CacheMiddleware::class
    ];

    protected function boot(): void {
        $this->syncRoutesToRouter();
        $this->app->scoped(OutputInterface::class, Output::class);
        $this->app->scoped(InputInterface::class, Input::class);
    }

    protected function syncRoutesToRouter(): void {
        $this->router->get('package:discover', function (HttpContextInterface $context) {
            $context->make(PackageManifest::class)->build();
            return $context['response']->str('complete');
        });
    }

    public function receive(): InputInterface {
        return Input::createFromGlobals();
    }
}