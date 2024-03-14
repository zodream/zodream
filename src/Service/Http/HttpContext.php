<?php
declare(strict_types=1);
namespace Zodream\Service\Http;

use ArrayAccess;
use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\HttpContext as HttpContextInterface;
use Zodream\Infrastructure\Contracts\Route;
use Zodream\Route\ModuleRoute;

class HttpContext implements HttpContextInterface, ArrayAccess {

    protected array $instances = [];

    protected array $middlewares = [];
    /**
     * @var Application
     */
    protected Application $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function middleware(...$middlewares) {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * @param Input|string $request
     */
    public function input($request) {
        if ($request instanceof Input) {
            $this->instance('request', $request);
            $this->instance('path', $request->routePath());
            return $this;
        }
        return $this['request']->get($request);
    }

    public function instance(string $key, $instance) {
        $this->instances[$key] = $instance;
        return $this;
    }

    public function output($response) {
        return $this->instance('response', $response);
    }

    /**
     * 返回已通过url::decode过的路径
     * @return string
     */
    public function path(): string {
        return $this->instances['path'] ?? '';
    }

    public function handle(Route $route) {
        foreach ([
            Route::class, get_class($route)
                 ] as $key) {
            $this->instances[$key] = $route;
        }
        $this->output($this->make(Output::class));
        return $route->handle($this);
    }

    public function has(string $abstract): bool {
        return isset($this->instances[$abstract]) || $this->app->has($abstract);
    }

    public function flush(): void {
        $this->instances = [];
    }

    public function make(string $abstract, array $parameters = [])
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        return $this->app->make($abstract);
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->make($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->instance($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->instances[$offset]);
        $this->app->offsetUnset($offset);
    }

    /**
     * Dynamically access container services.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key) {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value) {
        $this[$key] = $value;
    }
}