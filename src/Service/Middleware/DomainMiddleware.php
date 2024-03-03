<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Error\DomainException;

class DomainMiddleware implements MiddlewareInterface {

    public function handle(HttpContext $context, callable $next) {
        $input = $context['request'];
        if (!app()->isDebug() && !$this->isAllowDomain($input)) {
            throw new DomainException(__(
                '{domain} Domain Is Disallow, IP: {ip}', [
                    'domain' => $input->host(),
                    'ip' => $input->ip(),
                ]
            ));
        }
        return $next($context);
    }

    protected function isAllowDomain(Input $input): bool {
        $host = config('app.host');
        $realHost = $input->host();
        if ($host == '*' || empty($host)
            || $host === $realHost ||
            (is_array($host) && in_array($realHost, $host))) {
            return true;
        }
        // 允许www.默认域名
        return is_string($host) &&
            str_replace('www.', '', $host) ===
            str_replace('www.', '', $realHost);
    }
}