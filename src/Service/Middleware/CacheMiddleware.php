<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


class CacheMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        $urls = $this->formatUri(config('cache.uris'));
        if (empty($urls) || !array_key_exists($payload, $urls)) {
            return $next($payload);
        }
        return cache()->getOrSet(self::class.$payload, function () use ($payload, $next) {
            return $next($payload);
        }, $urls[$payload]);
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
            $data[trim($uri, '/')] = $time;
        }
        return $data;
    }
}