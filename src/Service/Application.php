<?php
declare(strict_types = 1);

namespace Zodream\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zodream\Debugger\Domain\Debugger;
use Zodream\Domain\Access\Auth;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;
use ArrayAccess;
use Closure;
use ReflectionClass;
use Exception;
use ReflectionParameter;
use Zodream\Infrastructure\Http\UrlGenerator;
use Zodream\Route\Route;
use Zodream\Route\Router;
use Zodream\Template\ViewFactory;

class Application implements ArrayAccess, ContainerInterface {
    /**
     * @var Application
     */
    protected static $instance;

    const VERSION = '4.0';

    protected $basePath;

    protected $booted = false;


    /**
     * 访问句柄
     * @var array
     */
    protected $instances = [];

    /**
     * 对应的方法
     * @var array
     */
    protected $aliases = [];

    /**
     * 注册的类池，未初始化的类名
     * @var array
     */
    protected $bindings = [];

    public function __construct(string $base_path = null, string $module = 'Home') {
        if (!empty($base_path)) {
            $this->setBasePath($base_path);
        }
        $this->registerBaseBindings();
        $this->instance('app.module', $module);
        $this->register('request', Request::class);
        $this->register('response', Response::class);
        $this->register('auth', Auth::class);
        $this->register('url', UrlGenerator::class);
        $this->register('route', Route::class);
        $this->register('view', ViewFactory::class);
        $this->register('debugger', Debugger::class);
    }

    /**
     * @return string
     */
    public function version(): string {
        return static::VERSION;
    }

    public function isBooted() {
        return $this->booted;
    }

    public function boot() {
        if ($this->booted) {
            return;
        }

    }

    protected function registerBaseBindings() {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Application::class, $this);
    }

    public function singleton(string $abstract, $concrete = null) {
        $this->register($abstract, $concrete);
    }

    public function register(string $key, string $abstract = null) {
        $this->bindings[$key] = empty($abstract) ? $key : $abstract;
        if (!empty($abstract) && $key != $abstract) {
            $this->alias($abstract, $key);
        }
    }

    public function registerIf(string $abstract, $concrete = null) {
        if (! $this->has($abstract)) {
            $this->register($abstract, $concrete);
        }
    }


    public function instance(string $key, $instance): void {
        $this->alias($key, $key);
        $this->instances[$key] = $instance;
    }

    public function alias(string $abstract, string $alias) {
        $this->aliases[$alias] = $abstract;
    }

    public function getAlias(string $abstract): string {
        return isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;
    }

    public function has($key): bool {
        return isset($this->bindings[$key]) ||
            isset($this->instances[$key]) ||
            isset($this->aliases[$key]);
    }

    public function make(string $abstract) {
        $abstract = $this->getAlias($abstract);
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (!class_exists($abstract)) {
            return null;
        }
        $object = $this->build($abstract);
        $this->instance($abstract, $object);
        return $object;
    }

    public function build($concrete) {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }
        if (!class_exists($concrete)) {
            throw new Exception(
                __('Target {concrete} is not instantiable.', compact('concrete'))
            );
        }
        $reflector = new ReflectionClass($concrete);
        if (! $reflector->isInstantiable()) {
            throw new Exception(
                __('Target {concrete} is not instantiable.', compact('concrete'))
            );
        }
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies(
            $dependencies
        );
        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies) {
        $results = [];
        foreach ($dependencies as $dependency) {
            $results[] = is_null($class = $dependency->getClass())
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);
        }

        return $results;
    }

    protected function resolvePrimitive(ReflectionParameter $parameter) {
        if (! is_null($concrete = $this->make($parameter->name))) {
            return $concrete instanceof Closure ? $concrete($this) : $concrete;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        throw new Exception(
            __(
                'Unresolvable dependency resolving [{parameter}]', compact('parameter')
            )
        );
    }

    protected function resolveClass(ReflectionParameter $parameter) {
        try {
            return $this->make($parameter->getClass()->name);
        }
        catch (Exception $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }


    /**
     * @param mixed $basePath
     */
    public function setBasePath(string $basePath) {
        $this->basePath = $basePath;
    }

    public function basePath(): string {
        return $this->basePath;
    }

    public function isDebug(): bool {
        return defined('DEBUG') && DEBUG;
    }

    protected function formatUri(string $uri): string {
        return $uri;
    }

    public function handle(string $uri = ''): Response {
        /** @var Route $route */
        $route = $this[Router::class]->handle(
            $this['request']->method(),
            $this->formatUri($uri));
        $this->instance(Route::class, $route);
        $response = $route->handle($this['request'], $this['response']);
        return $response instanceof Response ? $response : $this['response'];
    }

    public function flush() {
        $this->aliases = [];
        $this->bindings = [];
        $this->instances = [];
    }

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance() {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public static function setInstance(Application $container = null) {
        return static::$instance = $container;
    }


    public function offsetExists($key) {
        return $this->has($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key) {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value) {
        $this->instance($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key) {
        unset($this->bindings[$key], $this->instances[$key]);
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
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value) {
        $this[$key] = $value;
    }


    public function get($id) {
        return $this->make($id);
    }
}