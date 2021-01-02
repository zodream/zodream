<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;


use Closure;
use Zodream\Infrastructure\Contracts\Application;

abstract class ServiceProvider {

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * All of the registered booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * All of the registered booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];


    /**
     * Create a new service provider instance.
     *
     * @param    $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register()
    {
        //
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booting(Closure $callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booted(Closure $callback)
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     */
    public function callBootingCallbacks()
    {
        foreach ($this->bootingCallbacks as $callback) {
            BoundMethod::call($callback, $this->app);
        }
    }

    /**
     * Call the registered booted callbacks.
     *
     * @return void
     */
    public function callBootedCallbacks()
    {
        foreach ($this->bootedCallbacks as $callback) {
            BoundMethod::call($callback, $this->app);
        }
    }
}