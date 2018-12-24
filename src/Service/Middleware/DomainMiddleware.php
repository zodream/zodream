<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;


class DomainMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        if (!app()->isDebug() && !app()->isAllowDomain()) {
            throw new DomainException(__(
                '{domain} Domain Is Disallow', [
                    'domain' => app('request')->uri()->getHost()
                ]
            ));
        }
        return $next($payload);
    }
}