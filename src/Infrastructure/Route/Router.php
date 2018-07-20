<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Route;

use Closure;
use Zodream\Infrastructure\Http\Response;
use Exception;

class Router {
    /**
     * @var Route[]
     */
    protected $staticRouteMap = [];

    public function group(array $args, Closure $closure): Router {
        $closure($this);
        return $this;
    }

    /**
     * 手动注册路由
     * @param $method
     * @param $uri
     * @param $action
     * @return Route
     */
    public function addRoute(array $method, $uri, $action): Route {
        $route = new Route($uri, is_callable($action) ? $action : function() use ($action) {
            return $this->makeResponse($action);
        }, $method);
        foreach ($route->getMethods() as $item) {
            $this->staticRouteMap[$item][$uri] = $route;
        }
        return $route;
    }

    public function get($uri, $action = null) {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return Route
     */
    public function post($uri, $action = null) {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return Route
     */
    public function put($uri, $action = null) {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return Route
     */
    public function patch($uri, $action = null) {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return Route
     */
    public function delete($uri, $action = null) {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return Route
     */
    public function options($uri, $action = null) {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Register a new route responding to all verbs.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return Route
     */
    public function any($uri, $action = null) {
        $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];
        return $this->addRoute($verbs, $uri, $action);
    }

    /**
     * Register a new route with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return Route
     */
    public function match($methods, $uri, $action = null) {
        return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
    }


    public function handle(string $method, $url): Route {
        if ($url instanceof Uri) {
            $url = $url->getPath();
        }
        if (isset($this->staticRouteMap[$method][$url])) {
            return $this->staticRouteMap[$method][$url];
        }
        if (array_key_exists($method, $this->staticRouteMap)) {
            foreach ($this->staticRouteMap[$method] as $item) {
                /** @var $item Route */
                if ($item->match($url)) {
                    return $item;
                }
            }
        }
        return new Route($url, function() use ($url) {
            return $this->makeResponse($url);
        }, [$method]);
    }

    protected function makeResponse(string $action): Response {
        if (strpos($action, '@') === false) {
            return $this->invokeAutoAction($action);
        }
        return $this->invokeRegisterAction($action);
    }


    /**
     * 执行动态方法
     * @param $arg
     * @return mixed
     * @throws \Exception
     */
    protected function invokeRegisterAction($arg) {
        list($class, $action) = explode('@', $arg);
        if (!class_exists($class)) {
            return $this->invokeController('Service\\'.APP_MODULE.'\\'.$class, $action);
        }
        $reflectionClass = new \ReflectionClass( $class );
        $method = $reflectionClass->getMethod($action);

        $parameters = $method->getParameters();
        $arguments = array();
        foreach ($parameters as $param) {
            $arguments[] = app('request')->get($param->getName());
        }
        return call_user_func_array(array(new $class, $action), $arguments);
    }

    /**
     *
     * 执行自动解析的方法
     * @param $path
     * @return mixed
     * @throws \Exception
     */
    protected function invokeAutoAction($path) {
        $modules = config('modules');
        foreach ($modules as $key => $module) {
            if (!$this->isMatch($path, $key)) {
                continue;
            }
            // 要记录当前模块所对应的路径
            URL::setModulePath($key);
            return $this->invokeModule(Str::firstReplace($path, $key), $module);
        }
        // 默认模块
        if (array_key_exists('default', $modules)) {
            return $this->invokeModule($path, $modules['default']);
        }
        list($class, $action) = $this->getClassAndAction($path, 'Service\\'.APP_MODULE);
        return $this->invokeClass($class, $action);
    }

    protected function isMatch($path, $module) {
        return strpos($path, $module) === 0;
    }

    /**
     * @param $module
     * @return string
     * @throws \Exception
     */
    protected function getRealModule($module) {
        if (class_exists($module)) {
            return $module;
        }
        $module = rtrim($module, '\\').'\Module';
        if (class_exists($module)) {
            return $module;
        }
        throw new Exception($module.
            __(' Module no exist!'));
    }

    /**
     * 执行已注册模块
     * @param $path
     * @param $module
     * @return mixed
     * @throws \Exception
     */
    protected function invokeModule($path, $module) {
        $module = $this->getRealModule($module);
        $module = new $module();
        if (!$module instanceof Module) {
            return $this->invokeClass($module, $path);
        }
        $module->boot();
        view()->setDirectory($module->getViewPath());
        // 允许模块内部进行自定义路由解析
        if (method_exists($module, 'invokeRoute')) {
            return $module->invokeRoute($path);
        }
        $baseName = $module->getControllerNamespace();
        list($class, $action) = $this->getClassAndAction($path, $baseName);
        return $this->invokeClass($class, $action);
    }

    /**
     * @param $class
     * @param $action
     * @return mixed
     * @throws \Exception
     */
    protected function invokeController($class, $action) {
        if (!Str::endWith($class, APP_CONTROLLER)) {
            $class .= APP_CONTROLLER;
        }
        if (!class_exists($class)) {
            throw new Exception($class.
                __(' class no exist!'));
        }
        return $this->invokeClass($class, $action);
    }

    /**
     * 执行控制器，进行初始化并执行方法
     * @param $instance
     * @param $action
     * @return mixed
     * @throws \Exception
     */
    protected function invokeClass($instance, $action) {
        if (is_string($instance)) {
            $instance = new $instance;
        }
        if (method_exists($instance, 'init')) {
            $instance->init();
        }
        if (method_exists($instance, 'invokeMethod')) {
            return call_user_func(array($instance, 'invokeMethod'), $action, app('request'));
        }
        throw new Exception(
            __('UNKNOWN CLASS')
        );
    }

    protected function getClassAndAction($path, $baseName) {
        $baseName = rtrim($baseName, '\\').'\\';
        $path = trim($path, '/');
        if (empty($path)) {
            return [$baseName.'Home'.APP_CONTROLLER, 'index'];
        }
        $args = array_map(function ($arg) {
            return Str::studly($arg);
        }, explode('/', $path));
        return $this->getControllerAndAction($args, $baseName);
    }

    protected function getControllerAndAction(array $paths, $baseName) {
//        1.匹配全路径作为控制器 index 为方法,
        $class = $baseName.implode('\\', $paths). APP_CONTROLLER;
        if (class_exists($class)) {
            return [$class, 'index'];
        }
//        2.匹配最后一个作为 方法
        $count = count($paths);
        if ($count > 1) {
            $action = array_pop($paths);
            $class = $baseName.implode('\\', $paths). APP_CONTROLLER;
            if (class_exists($class)) {
                return [$class, lcfirst($action)];
            }
        }
//        3.匹配作为文件夹
        $class = $baseName.implode('\\', $paths).'\\Home'. APP_CONTROLLER;
        if (class_exists($class)) {
            return [$class, 'index'];
        }
//        4.一个时匹配 home 控制器 作为方法
        if ($count == 1) {
            return [$baseName.'Home'.APP_CONTROLLER, lcfirst($paths[0])];
        }
        $action = array_pop($paths);
        $class = $baseName.implode('\\', $paths). '\\Home'. APP_CONTROLLER;
        if (class_exists($class)) {
            return [$class, lcfirst($action)];
        }
        throw new Exception(
            __('UNKNOWN URI')
        );
    }
}