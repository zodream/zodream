<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Error\DomainException;

class DomainMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        if (!app()->isDebug() && !app()->isAllowDomain()) {
            throw new DomainException(__(
                '{domain} Domain Is Disallow, IP: {ip}', [
                    'domain' => app('request')->uri()->getHost(),
                    'ip' => app('request')->ip(),
                ]
            ));
        }
        return $next($payload);
    }
}