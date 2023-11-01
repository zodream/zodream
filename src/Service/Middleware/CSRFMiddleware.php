<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Error\HttpException;

class CSRFMiddleware implements MiddlewareInterface {

    const HEADER_KEY = 'X-CSRF-TOKEN';
    const FORM_KEY = '_csrf';
    const COOKIE_KEY = 'XSRF-TOKEN';
    const SESSION_KEY = '_token';
    const SAFE_METHOD = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];
    /**
     * 允许/s
     * @var int
     */
    protected int $lifetime = 3600;

    public function handle(HttpContext $context, callable $next) {
        if (app()->isDebug()) {
            return $next($context);
        }
        /** @var Input $request */
        $request = $context['request'];
        if (!$this->isReading($request) && !$this->checkToken($request)) {
            throw new HttpException(400, __('Bad request'));
        }
        $updated = $this->shouldAddXsrfTokenCookie($request);
        if ($updated) {
            $this->updateToken();
        }
        $res = $next($context);
        if ($updated) {
            return $this->addCookieToResponse($request, $context['response']);
        }
        return $res;
    }

    protected function isReading(Input $request): bool {
        return in_array($request->method(), static::SAFE_METHOD);
    }

    protected function shouldAddXsrfTokenCookie(Input $input): bool {
        return true;
    }

    protected function addCookieToResponse(Input $request, mixed $response) {
        $token = session()->token();
        $output = $response instanceof Output ? $response : response();
        $output->cookie(static::COOKIE_KEY, $token, time() + $this->lifetime, '/', '', false, false);
        return $response;
    }

    protected function updateToken(): void {
        session()->regenerateToken();
    }

    protected function checkToken(Input $input): bool {
        return $this->getTokenFromRequest($input) === session()->token();
    }

    protected function getTokenFromRequest(Input $input): string {
        $token = $input->header(static::HEADER_KEY);
        if (!empty($token) && $token !== 'undefined') {
            return $token;
        }
        return (string)$input->request(static::FORM_KEY);
    }

    public static function get(): string {
        return session()->token();
    }
}