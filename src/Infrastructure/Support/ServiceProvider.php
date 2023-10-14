<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;


use Closure;
use Zodream\Infrastructure\Contracts\Application;

abstract class ServiceProvider {

    /**
     * All of the registered booting callbacks.
     *
     * @var array
     */
    protected array $bootingCallbacks = [];

    /**
     * All of the registered booted callbacks.
     *
     * @var array
     */
    protected array $bootedCallbacks = [];


    /**
     * Create a new service provider instance.
     *
     * @param Application $app
     */
    public function __construct(
        protected Application $app)
    {
    }

    public function register(): void {
        //
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booting(Closure $callback): void {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booted(Closure $callback): void {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     */
    public function callBootingCallbacks(): void {
        foreach ($this->bootingCallbacks as $callback) {
            BoundMethod::call($callback, $this->app);
        }
    }

    /**
     * Call the registered booted callbacks.
     *
     * @return void
     */
    public function callBootedCallbacks(): void {
        foreach ($this->bootedCallbacks as $callback) {
            BoundMethod::call($callback, $this->app);
        }
    }
}