<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


use Zodream\Helpers\Str;
use Zodream\Route\Route;
use Zodream\Route\Router;

class ModuleMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        $path = $payload['uri'];
        $modules = config('modules');
        foreach ($modules as $key => $module) {
            if (!$this->isMatch($path, $key)) {
                continue;
            }
            // 要记录当前模块所对应的路径
            url()->setModulePath($key);
            return $this->converterRoute($payload, Str::firstReplace($path, $key), $module);
        }
        // 默认模块
        if (array_key_exists('default', $modules)) {
            return $this->converterRoute($payload, $path, $modules['default']);
        }
        return $next($payload);
    }

    protected function converterRoute($payload, $path, $module) {
        return new Route($payload['uri'], function() use ($path, $module) {
            return app(Router::class)->invokeModule($path, $module);
        }, [$payload['method']]);
    }

    protected function isMatch($path, $module) {
        return strpos($path, $module) === 0;
    }
}