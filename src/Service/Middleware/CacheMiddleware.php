<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\HttpContext;

class CacheMiddleware implements MiddlewareInterface {

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(HttpContext $context, callable $next) {
        $path = $context->path();
        /** @var Input $request */
        $request = $context['request'];
        $cache = $this->getCacheOption($path, $request);
        if (empty($cache)) {
            return $next($context);
        }
        if (isset($cache['method'])) {
            $method = $request->method();
            if (
            (is_array($cache['method']) && !in_array($method, $cache['method'])) ||
            (!is_array($cache['method']) && $method !== $cache['method'])) {
                return $next($context);
            }
        }
        if (isset($cache['before']) && is_callable($cache['before'])) {
            $res = call_user_func($cache['before'], $cache);
            if ($res === false) {
                return $next($context);
            }
            if (is_array($res)) {
                $cache = $res;
            }
        }
        $key = self::class.$this->app['app.module'].$request->host().$path
            .$this->getPath($cache, $path, $request);
        $cacheDriver = cache()->store('pages');
        if (($page = $cacheDriver->get($key)) !== false) {
            return $this->formatPage($page, $cache);
        }
        $page = $next($context);
        if (isset($cache['after']) && is_callable($cache['after'])) {
            $res = call_user_func($cache['after'], $page, $cache);
            if ($res === false) {
                return $page;
            }
            if (is_array($res)) {
                $cache = $res;
            }
        }
        $cacheDriver->set($key, $page, $cache['time']);
        return $page;
    }

    private function formatPage($page, $args) {
        if (!isset($args['callback']) || !is_callable($args['callback'])) {
            return $page;
        }
        $arg = call_user_func($args['callback'], $page);
        return empty($arg) ? $page : $arg;
    }

    private function getPath(array $args, string $path, Input $input): string {
        if (empty($args['params'])) {
            return '';
        }
        if (is_callable($args['params'])) {
            return call_user_func($args['params'], $path, $input);
        }
        $data = [];
        foreach ((array)$args['params'] as $item) {
            if (empty($item)) {
                continue;
            }
            $data[] = sprintf('%s=%s', $item, $this->getPathParam($item, $input));
        }
        return implode('-', $data);
    }

    private function getPathParam($item, Input $input) {
        if ($item === '@language') {
            return trans()->getLanguage();
        }
        if ($item === '@user') {
            return auth()->id();
        }
        if (!is_string($item)) {
            return $item;
        }
        return $input->get($item);
    }

    private function getCacheOption(string $path, Input $input) {
        $uris = config('cache');
        if (empty($uris)) {
            return false;
        }
        foreach ((array)$uris as $uri => $time) {
            if (is_array($time) &&
                isset($time['match']) &&
                is_callable($time['match']) &&
                call_user_func($time['match'], $path, $input) === true) {
                return $time;
            }
            if (is_integer($uri)) {
                list($uri, $time) = [$time, 0];
            }
            if (!is_array($time)) {
                $time = compact('time');
            }
            if (!isset($time['params'])) {
                $time['params'] = [];
            }
            $uri = trim($uri, '/');
            if ($path === $uri) {
                return $time;
            }
            if (substr($uri, 0, 1) !== '~') {
                continue;
            }
            if (preg_match(sprintf('#%s#i', substr($uri, 1)), $path, $match)) {
                return $time;
            }
        }
        return false;
    }
}