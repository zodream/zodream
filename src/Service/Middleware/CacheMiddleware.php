<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


class CacheMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        $urls = $this->formatUri(config()->getConfigByFile('cache'));
        if (empty($urls) || !array_key_exists($payload, $urls)) {
            return $next($payload);
        }
        $cache = $urls[$payload];
        if (isset($cache['before']) && is_callable($cache['before'])) {
            $res = call_user_func($cache['before'], $cache);
            if ($res === false) {
                return $next($payload);
            }
            if (is_array($res)) {
                $cache = $res;
            }
        }
        $key = self::class.$payload.$this->getPath($cache);
        if (($page = cache($key)) !== false) {
            return $this->formatPage($page, $urls[$payload]);
        }
        $page = $next($payload);
        if (isset($cache['after']) && is_callable($cache['after'])) {
            $res = call_user_func($cache['after'], $page, $cache);
            if ($res === false) {
                return $page;
            }
            if (is_array($res)) {
                $cache = $res;
            }
        }
        cache()->set($key, $page, $cache['time']);
        return $page;
    }

    private function formatPage($page, $args) {
        if (!isset($args['callback']) || !is_callable($args['callback'])) {
            return $page;
        }
        $arg = call_user_func($args['callback'], $page);
        return empty($arg) ? $page : $arg;
    }

    private function formatUri($uris): array {
        if (empty($uris)) {
            return [];
        }
        $data = [];
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
            $data[trim($uri, '/')] = $time;
        }
        return $data;
    }

    private function getPath(array $args): string {
        if (!isset($args['params']) || empty($args['params'])) {
            return '';
        }
        if (is_callable($args['params'])) {
            return call_user_func($args['params']);
        }
        $data = [];
        foreach ((array)$args['params'] as $item) {
            if (empty($item)) {
                continue;
            }
            $data[] = sprintf('%s=%s', $item, $this->getPathParam($item));
        }
        return implode('-', $data);
    }

    private function getPathParam($item) {
        if ($item === '@language') {
            return trans()->getLanguage();
        }
        if ($item === '@user') {
            return auth()->id();
        }
        return app('request')->get($item);
    }
}