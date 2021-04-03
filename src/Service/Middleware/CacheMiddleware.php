<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


use Zodream\Http\Uri;
use Zodream\Infrastructure\Contracts\Application;
use Zodream\Infrastructure\Contracts\HttpContext;

class CacheMiddleware implements MiddlewareInterface {

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(HttpContext $context, callable $next) {
        $fullUri = url()->decode();
        $cache = $this->getCacheOption($fullUri->getPath());
        if (empty($cache)) {
            return $next($context);
        }
        if (isset($cache['method'])) {
            $method = request()->method();
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
        $key = self::class.$fullUri->getScheme().$fullUri->getHost().$fullUri->getPath().$this->getPath($cache, $fullUri);
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

    private function getPath(array $args, Uri $current): string {
        if (!isset($args['params']) || empty($args['params'])) {
            return '';
        }
        if (is_callable($args['params'])) {
            return call_user_func($args['params'], $current);
        }
        $data = [];
        foreach ((array)$args['params'] as $item) {
            if (empty($item)) {
                continue;
            }
            $data[] = sprintf('%s=%s', $item, $this->getPathParam($item, $current));
        }
        return implode('-', $data);
    }

    private function getPathParam($item, Uri $current) {
        if ($item === '@language') {
            return trans()->getLanguage();
        }
        if ($item === '@user') {
            return auth()->id();
        }
        $value = $current->getData($item);
        if ($value !== false) {
            return $value;
        }
        return request()->get($item);
    }

    private function getCacheOption(string $path) {
        $uris = config('cache');
        if (empty($uris)) {
            return false;
        }
        foreach ((array)$uris as $uri => $time) {
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