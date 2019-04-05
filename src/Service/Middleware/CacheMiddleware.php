<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


class CacheMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        $urls = $this->formatUri(config('cache.uris'));
        if (empty($urls) || !array_key_exists($payload, $urls)) {
            return $next($payload);
        }
        $key = self::class.$payload.$this->getPath($urls[$payload]);
        if (($page = cache($key)) !== false) {
            return $this->formatPage($page, $urls[$payload]);
        }
        $page = $next($payload);
        cache()->set($key, $page, $urls[$payload]['time']);
        return $page;
    }

    private function formatPage($page, $args) {
        if (!isset($args['callback']) || !is_callable($args['callback'])) {
            return $page;
        }
        $arg = call_user_func($args['callback'], $page);
        return empty($arg) ? $page : $arg;
    }

    private function formatUri($uris) {
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

    private function getPath(array $args) {
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
            $data[] = sprintf('%s=%s', $item, app('request')->get($item));
        }
        return implode('-', $data);
    }
}