<?php
declare(strict_types = 1);

namespace Zodream\Service;

use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;

class Application {

    const VERSION = '4.0';

    protected $basePath;

    protected $config;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

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

    /**
     * @return string
     */
    public function version(): string {
        return static::VERSION;
    }

    public function register(string $key, mixed $abstract = null) {
        $this->bindings[$key] = empty($abstract) ? $key : $abstract;
    }


    public function instance(string $key, mixed $instance): void {
        $this->alias($key, $key);
        $this->instances[$key] = $instance;
    }

    public function alias(string $abstract, string $alias) {
        $this->aliases[$alias] = $abstract;
    }


    /**
     * @param mixed $basePath
     */
    public function setBasePath(string $basePath) {
        $this->basePath = $basePath;
    }


}