<?php
declare(strict_types=1);
namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Error\HttpException;

class CSRFMiddleware implements MiddlewareInterface {

    const HEADER_KEY = 'X-CSRFToken';
    const FORM_KEY = '_csrf';
    const COOKIE_KEY = 'XSRF-TOKEN';
    const SESSION_KEY = '_token';

    public function handle(HttpContext $context, callable $next) {
        if (app()->isDebug()) {
            return $next($context);
        }
        /** @var Input $request */
        $request = $context['request'];
        if ($request->isPost() && !$this->checkToken($request)) {
            throw new HttpException(400, __('Bad request'));
        }
        $this->setToken($context['response']);
        return $next($context);
    }

    protected function setToken(Output $output): void {
        $sess = session();
        $sess->regenerateToken();
        $token = $sess->token();
        $output->cookie(static::COOKIE_KEY, $token);
    }

    protected function checkToken(Input $input): bool {
        return $this->getTokenFromRequest($input) === session()->token();
    }

    protected function getTokenFromRequest(Input $input): string {
        return (string)($input->header(static::HEADER_KEY) ?: $input->request(static::FORM_KEY));
    }

    public static function get(): string {
        return session()->token();
    }
}