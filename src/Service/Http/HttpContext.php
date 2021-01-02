<?php
declare(strict_types=1);
namespace Zodream\Service\Http;

use ArrayAccess;
use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\HttpContext as HttpContextInterface;
use Zodream\Infrastructure\Contracts\Route;

class HttpContext implements HttpContextInterface, ArrayAccess {

    protected array $instances = [];

    protected array $middlewares = [];
    /**
     * @var Application
     */
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function middleware(...$middlewares)
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * @param Request $request
     */
    public function input($request)
    {
        $this->instance('request', $request);
        $this->instance('path', trim($this->getVirtualUri($request), '/'));
        return $this;
    }

    public function instance(string $key, $instance)
    {
        $this->instances[$key] = $instance;
        return $this;
    }

    public function output($response) {
        return $this->instance('response', $response);
    }

    public function path(): string
    {
        return isset($this->instances['path']) ? $this->instances['path'] : '';
    }

    public function handle(Route $route)
    {
        $this->instances['route'] = $route;
        $this->output($this->make(Output::class));
        return $route->handle($this);
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || $this->app->has($abstract);
    }

    public function flush()
    {
        $this->instances = [];
    }

    public function make(string $abstract, array $parameters = [])
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        return $this->app->make($abstract);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->instance($offset, $value);
    }

    public function offsetUnset($offset)
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

    /**
     * 获取网址中的虚拟路径
     * @return string
     */
    protected function getVirtualUri(Input $request) {
        $path = $request->server('PATH_INFO');
        if (!empty($path)) {
            // 在nginx 下虚拟路径无法获取
            return $path;
        }
        $script = $request->script().'';
        $scriptFile = basename($script);
        $path = parse_url($request->url(), PHP_URL_PATH);
        if (strpos($scriptFile, $path) === 0) {
            $path = rtrim($path, '/'). '/'. $scriptFile;
        } elseif (strpos($script, '.php') > 0) {
            $script = preg_replace('#/[^/]+\.php$#i', '', $script);
        }
        // 判断是否是二级文件默认入口
        if (!empty($script) && strpos($path, $script) === 0) {
            return substr($path, strlen($script));
        }
        // 判断是否是根目录其他文件入口
        if (strpos($path, $scriptFile) === 1) {
            return '/'.substr($path, strlen($scriptFile) + 1);
        }
        return $path;
    }
}