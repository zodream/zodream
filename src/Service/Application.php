<?php
declare(strict_types = 1);

namespace Zodream\Service;

use Psr\Container\ContainerInterface;
use Zodream\Debugger\Debugger;
use Zodream\Debugger\Domain\Timer;
use Zodream\Infrastructure\Error\HandleExceptions;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;
use ArrayAccess;
use Closure;
use ReflectionClass;
use Exception;
use ReflectionParameter;
use Zodream\Infrastructure\Http\UrlGenerator;
use Zodream\Infrastructure\Pipeline\MiddlewareProcessor;
use Zodream\Route\Route;
use Zodream\Route\Router;
use Zodream\Service\Middleware\CacheMiddleware;
use Zodream\Service\Middleware\CORSMiddleware;
use Zodream\Service\Middleware\DefaultRouteMiddle;
use Zodream\Service\Middleware\DomainMiddleware;
use Zodream\Service\Middleware\GZIPMiddleware;
use Zodream\Service\Middleware\MatchRouteMiddle;
use Zodream\Service\Middleware\ModuleMiddleware;
use Zodream\Service\Middleware\RouterMiddleware;
use Zodream\Template\ViewFactory;

class Application implements ArrayAccess, ContainerInterface {
    /**
     * @var Application
     */
    protected static $instance;

    const VERSION = '4.0';

    protected $basePath;

    protected $booted = false;

    protected $hasBeenBootstrapped = false;


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

    protected $middlewares = [];

    /**
     * Application constructor.
     * @param string|null $base_path
     * @param string $module
     * @throws Exception
     */
    public function __construct(string $base_path = '', string $module = 'Home') {
        if (!empty($base_path)) {
            $this->setBasePath($base_path);
        }
        $this->registerBaseBindings();
        $this->instance('app.module', $module);
        $this->register('request', Request::class);
        $this->register('response', Response::class);
        $this->register('url', UrlGenerator::class);
        $this->register('route', Route::class);
        $this->register('view', ViewFactory::class);
        $this->register('events', EventManger::class);
        $this->singleton(Timer::class, 'timer');
        $this->singleton(Debugger::class, 'debugger');
        $this->registerConfigBindings();
        $this->registerCoreAliases();

    }

    /**
     * @return string
     */
    public function version(): string {
        return static::VERSION;
    }

    /**
     * 是否是， 允许更改
     * @param string $abstract
     * @return bool
     */
    public function is(string $abstract): bool {
        return $this['app::class'] == $abstract;
    }

    /**
     * 是否是API
     * @return bool
     */
    public function isApi(): bool {
        return $this->is(Api::class);
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
        $this->instance('app::class', static::class);
        $this->instance(Application::class, $this);
    }

    protected function registerConfigBindings() {
        if (empty(config())) {
            return;
        }
        foreach (config()->get() as $key => $item) {
            if (!is_array($item) || !isset($item['driver'])
                || !class_exists($item['driver'])
                || in_array($key, ['db', 'redis', 'queue'])) {
                continue;
            }
            $this->register($key, $item['driver']);
        }

    }

    public function registerCoreAliases() {
        foreach ([

                 ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    public function hasBeenBootstrapped(): bool  {
        return $this->hasBeenBootstrapped;
    }

    public function bootstrapWith(array $bootstrappers) {
        $this->hasBeenBootstrapped = true;
        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }


    /**
     * 注册并初始化实例
     * @param string $abstract
     * @param null $concrete
     * @return mixed|object
     * @throws Exception
     */
    public function singleton(string $abstract, $concrete = null) {
        if (empty($concrete)) {
            $concrete = $abstract;
        }
        $this->register($concrete, $abstract);
        $object = $this->build($abstract);
        $this->instance($abstract, $object);
        return $object;
    }

    /**
     * 注册
     * @param string $key
     * @param string|null $abstract
     */
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

    /**
     * 绑定实例
     * @param string $key
     * @param $instance
     */
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

    public function middleware(...$middlewares) {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * 获取实例或初始化并绑定
     * @param string $abstract
     * @return mixed|null|object
     * @throws Exception
     */
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

    /**
     * 初始化实例
     * @param $concrete
     * @return mixed|object
     * @throws Exception
     */
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

    public function isAllowDomain(): bool  {
        $host = config('app.host');
        $real_host = $this['request']->uri()->getHost();
        if ($host == '*' || empty($host) || $host == $real_host || (is_array($host) && in_array($real_host, $host))) {
            return true;
        }
        // 允许www.默认域名
        return is_string($host) && str_replace('www.', '', $host) == str_replace('www.', '', $real_host);
    }

    protected function formatUri(string $uri): string {
        return $uri;
    }

    public function handle(string $uri = ''): Response {
        timer('uri analysis');
        if (!$this->hasBeenBootstrapped()) {
            $this->bootstrapWith([
                HandleExceptions::class
            ]);
        }
        $middlewares = array_merge($this->middlewares, [RouterMiddleware::class]);
        $response = (new MiddlewareProcessor())
            ->process($this->formatUri($uri), ...$middlewares);
        if (!$response instanceof Response) {
            return $this['response']->setParameter($response);
        }
        return $response;
    }

    public function autoResponse() {
        $this->middleware(
                        GZIPMiddleware::class,
                        DomainMiddleware::class,
                        CORSMiddleware::class,
                        CacheMiddleware::class);
        $this[Router::class]->middleware(
                        MatchRouteMiddle::class,
                        ModuleMiddleware::class,
                        DefaultRouteMiddle::class);
        return $this->handle()->send();
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
            static::$instance = new static(defined('APP_DIR') ? APP_DIR : '');
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
     * @param  string $key
     * @return mixed
     * @throws Exception
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