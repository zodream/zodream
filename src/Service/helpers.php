<?php
declare(strict_types = 1);

use Psr\Log\LoggerInterface;
use Zodream\Disk\FileException;
use Zodream\Infrastructure\Caching\Cache;
use Zodream\Infrastructure\I18n\I18n;
use Zodream\Infrastructure\Session\Session;
use Zodream\Route\Router;
use Zodream\Service\Factory;
use Zodream\Service\Config;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Error\NotFoundHttpException;
use Zodream\Html\VerifyCsrfToken;
use Zodream\Service\Application;
use Zodream\Infrastructure\Http\Response;
use Zodream\Domain\Access\Auth;
use Zodream\Domain\Access\Token;
use Zodream\Domain\Access\JWTAuth;
use Zodream\Infrastructure\Http\UrlGenerator;
use Zodream\Debugger\Domain\Timer;
use Zodream\Disk\FileObject;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Template\ViewFactory;


if (! function_exists('app')) {
    /**
     * @param string|null $abstract
     * @return Application|Response|Request|UrlGenerator|Router|mixed
     * @throws Exception
     */
    function app(string $abstract = null) {
        if (empty($abstract)) {
            return Application::getInstance();
        }
        return Application::getInstance()->make($abstract);
    }
}

if (! function_exists('auth')) {

    /**
     * @return Auth|Token|JWTAuth
     * @throws Exception
     */
    function auth() {
        return app('auth');
    }
}

if (! function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return void
     * @throws HttpException
     */
    function abort($code, $message = '', array $headers = []) {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        }

        throw new HttpException($code, $message, null, $headers);
    }
}

if (! function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param string $path
     * @return string
     * @throws Exception
     */
    function app_path(string $path = '') {
        if (empty($path)) {
            return Factory::root();
        }
        return Factory::root()->file($path);
    }
}

if (! function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we'll assume you want to put to the cache.
     * @throws Exception
     * @return Cache|mixed
     */
    function cache() {
        return Factory::cache(...func_get_args());
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed|Config|string
     */
    function config($key = null, $default = null) {
        return Factory::config($key, $default);
    }
}

if (! function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    function csrf_token() {
        return VerifyCsrfToken::get();
    }
}


if (! function_exists('info')) {
    /**
     * Write some information to the log.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @throws Exception
     */
    function info($message, $context = []) {
        Factory::log()->info($message, $context);
    }
}

if (! function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param string $message
     * @param array $context
     * @return LoggerInterface|void
     * @throws Exception
     */
    function logger($message = null, array $context = []) {
        if (is_null($message)) {
            return Factory::log();
        }
        Factory::log()->debug($message, $context);
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return FileObject
     * @throws Exception
     */
    function public_path($path = '') {
        if (!$path) {
            return Factory::public_path();
        }
        return Factory::public_path()->file($path);
    }
}

if (! function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  array|string $key
     * @param  mixed $default
     * @return array|string|Request
     * @throws Exception
     */
    function request($key = null, $default = null) {
        return app('request')->get($key, $default);
    }
}

if (! function_exists('session')) {
    /**
     * @param null $key
     * @param null $default
     * @return mixed|Session
     * @throws Exception
     */
    function session($key = null, $default = null) {
        return Factory::session($key, $default);
    }
}

if (! function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string|I18n
     * @throws Exception
     */
    function trans($key = null, $replace = [], $locale = null) {
        return Factory::i18n($key, $replace, $locale);
    }
}

if (! function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param  string $key
     * @param  array $replace
     * @param  string $locale
     * @return string
     * @throws Exception
     */
    function __($key, $replace = [], $locale = null) {
        if (empty(Factory::i18n())) {
            return $key;
        }
        return Factory::i18n()->translate($key, $replace, $locale);
    }
}

if (! function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * @param  string $path
     * @param  mixed $parameters
     * @param  bool $secure
     * @param bool $rewrite
     * @return string| UrlGenerator
     * @throws Exception
     */
    function url($path = null, $parameters = [], $secure = true, $rewrite = true) {
        if (is_null($path) && empty($parameters) && $secure === true) {
            return app('url');
        }
        return app('url')->to($path, $parameters, $secure, $rewrite);
    }
}

if (! function_exists('view')) {
    /**
     * 显示页面
     * @param null $path
     * @param array $data
     * @return string|ViewFactory
     * @throws FileException
     * @throws Exception
     */
    function view($path = null, array $data = []) {
        if (empty($path)) {
            return Factory::view();
        }
        return Factory::view()->render($path, $data);
    }
}

if (! function_exists('timer')) {
    /**
     * @param null $name
     * @return Timer|mixed
     * @throws Exception
     */
    function timer($name = null) {
        if (is_null($name)) {
            return app('timer');
        }
        return app('timer')->record($name);
    }
}

if (! function_exists('event')) {
    /**
     * Dispatch an event and call the listeners.
     *
     * @param array $args
     * @return EventManger|null
     * @throws Exception
     */
    function event(...$args)
    {
        if (count($args) === 0) {
            return app('events');
        }
        return app('events')->dispatch(...$args);
    }
}

if (! function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param  object|string  $class
     * @return array
     */
    function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (! function_exists('trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     *
     * @param  string  $trait
     * @return array
     */
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}