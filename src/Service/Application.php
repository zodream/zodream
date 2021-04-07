<?php
declare(strict_types=1);
namespace Zodream\Service;

use ArrayAccess;
use Exception;
use Zodream\Domain\Composer\PackageManifest;
use Zodream\Infrastructure\Contracts\Application as ApplicationInterface;
use Zodream\Infrastructure\Contracts\Container;
use Zodream\Infrastructure\Contracts\Kernel as KernelInterface;
use Zodream\Infrastructure\Support\BoundMethod;
use Zodream\Infrastructure\Support\ServiceProvider;
use Zodream\Route\RoutingServiceProvider;
use Zodream\Service\Http\Kernel;
use Zodream\Service\Providers\AuthServiceProvider;
use Zodream\Service\Providers\CacheServiceProvider;
use Zodream\Service\Providers\EventServiceProvider;
use Zodream\Service\Providers\I18nServiceProvider;
use Zodream\Service\Providers\LogServiceProvider;
use Zodream\Service\Providers\SessionServiceProvider;

class Application implements ApplicationInterface, ArrayAccess {

    const VERSION = '5.0.0';

    /**
     * @var Application|null
     */
    protected static ?Application $instance;
    protected string $basePath;

    /**
     * @var array
     */
    protected array $instances = [];
    protected bool $booted = false;
    protected bool $hasBeenBootstrapped = false;
    /**
     * 对应的方法
     * @var array
     */
    protected array $aliases = [];

    /**
     * 注册的类池，未初始化的类名
     * @var array
     */
    protected array $bindings = [];

    protected array $middlewares = [];

    protected array $serviceProviders = [];
    protected array $loadedProviders = [];

    public function __construct(string $base_path = '')
    {
        require_once 'helpers.php';
        if (!empty($base_path)) {
            $this->setBasePath($base_path);
        }
        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    public function setBasePath(string $basePath) {
        $this->basePath = rtrim($basePath, '\/');
    }

    public function basePath(): string {
        return $this->basePath;
    }

    public function isDebug(): bool {
        return defined('DEBUG') && DEBUG;
    }

    public function version(): string {
        return static::VERSION;
    }

    public function boot()
    {
        if ($this->booted) {
            return;
        }
        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });
        $this->booted = true;
    }

    public function hasBeenBootstrapped(): bool  {
        return $this->hasBeenBootstrapped;
    }

    public function bootstrapWith(array $bootstrapperItems) {
        $this->hasBeenBootstrapped = true;
        foreach ($bootstrapperItems as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }

    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new Exception("[{$abstract}] is aliased to itself.");
        }
        $this->aliases[$alias] = $abstract;
        return $this;
    }

    public function bind($abstract, $concrete = null, $shared = false) {
        unset($this->instances[$abstract], $this->aliases[$abstract]);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');

        $abstract = $this->getAlias($abstract);
        if (isset($this->instances[$abstract])) {
            $this->make($abstract);
        }
        return $this;
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
        return $this;
    }

    public function singletonIf($abstract, $concrete = null)
    {
        if (!$this->has($abstract)) {
            $this->singleton($abstract, $concrete);
        }
        return $this;
    }

    public function instance(string $abstract, $instance)
    {
        unset($this->aliases[$abstract]);
        $this->instances[$abstract] = $instance;
        return $this;
    }

    public function transient($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, false);
        return $this;
    }

    public function transientIf($abstract, $concrete = null)
    {
        if (!$this->has($abstract)) {
            $this->transient($abstract, $concrete);
        }
        return $this;
    }

    public function scoped($abstract, $concrete = null)
    {
        $this->singleton($abstract, $concrete);
        return $this;
    }

    public function scopedIf($abstract, $concrete = null)
    {
        $this->singletonIf($abstract, $concrete);
        return $this;
    }

    public function register($provider, $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);;
        }

        $provider->register();

        // If there are bindings / singletons set as properties on the provider we
        // will spin through them and register them with the application, which
        // serves as a convenience layer while registering a lot of bindings.
        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }

        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by this developer's application logic.
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    public function getProvider($provider)
    {
        return array_values($this->getProviders($provider))[0] ?? null;
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param  ServiceProvider|string  $provider
     * @return array
     */
    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);
        return array_filter($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    public function middleware(...$middlewares)
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function listen()
    {
        $this->singletonIf(KernelInterface::class, Kernel::class);
        /** @var KernelInterface $kernel */
        $kernel = $this->make(KernelInterface::class);
        $request = $kernel->receive();
        $response = $kernel->handle($request, $this->middlewares);
        $response->send();
        $kernel->terminate($request, $response);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            isset($this->aliases[$abstract]);
    }

    public function flush()
    {
        $this->bindings = [];
    }

    public function make(string $abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        $concrete = $this->getConcrete($abstract);
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        if (is_string($concrete) && !class_exists($concrete)) {
            return null;
        }
        $object = BoundMethod::newClass($concrete, $this, $parameters);
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        return $object;
    }

    public function getAlias(string $abstract) {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function bootProvider(ServiceProvider $provider)
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            BoundMethod::call([$provider, 'boot'], $this);
        }

        $provider->callBootedCallbacks();
    }

    protected function registerBaseBindings() {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance('app::class', static::class);
        $this->instance(ApplicationInterface::class, $this);
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
        $this->singleton(PackageManifest::class, function () {
            return new PackageManifest(
                app_path(), app_path('data/services.php')
            );
        });
    }

    protected function registerBaseServiceProviders()
    {
        $this->register(EventServiceProvider::class);
        $this->register(LogServiceProvider::class);
        $this->register(RoutingServiceProvider::class);
        $this->register(I18nServiceProvider::class);
        $this->register(AuthServiceProvider::class);
        $this->register(CacheServiceProvider::class);
        $this->register(SessionServiceProvider::class);
    }

    protected function registerCoreContainerAliases()
    {
        foreach ([
            'app' => [self::class],
            ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($alias, $key);
            }
        }
    }

    public function offsetExists($key) {
        return $this->has($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param string $key
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
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value) {
        $this[$key] = $value;
    }


    public function get($id) {
        return $this->make($id);
    }

    public static function getInstance() {
        if (is_null(static::$instance)) {
            static::$instance = new static(defined('APP_DIR') ? APP_DIR : '');
        }
        return static::$instance;
    }

    public static function setInstance(Application $container = null) {
        return static::$instance = $container;
    }

}