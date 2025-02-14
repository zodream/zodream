<?php
declare(strict_types = 1);

use Psr\Log\LoggerInterface;
use Zodream\Disk\Directory;
use Zodream\Disk\File;
use Zodream\Disk\FileException;
use Zodream\Infrastructure\Caching\Cache;
use Zodream\Infrastructure\Contracts\Config\Repository;
use Zodream\Infrastructure\Contracts\Database;
use Zodream\Infrastructure\Contracts\Http\HttpOutput;
use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Contracts\UrlGenerator;
use Zodream\Infrastructure\I18n\I18n;
use Zodream\Infrastructure\Session\Session;
use Zodream\Route\Exception\NotFoundHttpException;
use Zodream\Service\Application;
use Zodream\Domain\Access\Auth;
use Zodream\Domain\Access\Token;
use Zodream\Domain\Access\JWTAuth;
use Zodream\Debugger\Domain\Timer;
use Zodream\Disk\FileObject;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Template\ViewFactory;
use Zodream\Infrastructure\Error\HttpException;


if (! function_exists('app')) {
    /**
     * @param string|null $abstract
     * @return Application|mixed
     * @throws Exception
     */
    function app(string|null $abstract = null) {
        if (empty($abstract)) {
            return Application::getInstance();
        }
        return Application::getInstance()->make($abstract);
    }
}

if (! function_exists('app_call')) {
    /**
     * @param string $abstract
     * @param callable $cb
     * @param mixed $default
     * @return mixed
     * @throws Exception
     */
    function app_call(string $abstract, callable $cb, mixed $default = null): mixed {
        $instance = app($abstract);
        if (empty($instance)) {
            return $default;
        }
        return call_user_func($cb, $instance);
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

if (! function_exists('db')) {

    /**
     * @return Database
     * @throws Exception
     */
    function db() {
        return app('db');
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
    function abort(int $code, string $message = '', array $headers = []) {
        if ($code === 404) {
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
     * @return Directory|File
     * @throws Exception
     */
    function app_path(string $path = '') {
        $app = app();
        $key = 'root';
        if (!$app->has($key)) {
            $app->instance($key, new Directory($app->basePath()));
        }
        if (empty($path)) {
            return $app->make($key);
        }
        return $app->make($key)->file($path);
    }
}

if (! function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we'll assume you want to put to the cache.
     * @param mixed ...$args
     * @return Cache|mixed
     * @throws Exception
     */
    function cache(...$args) {
        return app_call('cache', function (Cache $instance) use ($args) {
            if (empty($args)) {
                return $instance;
            }
            if (count($args) == 1) {
                return $instance->get($args[0]);
            }
            return $instance->set($args[0], $args[1], $args[2] ?? 0);
        });
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
     * @return mixed|Repository|string
     */
    function config(array|string $key = '', mixed $default = null) {
        return app_call('config', function (Repository $repository) use ($key, $default) {
            if (empty($key)) {
                return $repository;
            }
            return $repository->get($key, $default);
        }, $default);
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
    function csrf_token(): string {
        return \session()->token();
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
    function info(string $message, array $context = []): void {
        logger()->info($message, $context);
    }
}

if (! function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param string|null $message
     * @param array $context
     * @return LoggerInterface|void
     * @throws Exception
     */
    function logger(mixed $message = null, array $context = []) {
        return app_call('log', function (LoggerInterface $logger) use ($message, $context) {
            if (is_null($message)) {
                return $logger;
            }
            $logger->debug($message, $context);
            return null;
        });
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return FileObject|Directory|File
     * @throws Exception
     */
    function public_path(string $path = '') {
        $app = app();
        $key = 'public_path';
        if (!$app->has($key)) {
            $folder = config('app.public');
            $app->instance($key, empty($folder) ?
                new Directory(request()
                    ->server('DOCUMENT_ROOT'))
                : app_path()->directory($folder));
        }
        if (empty($path)) {
            return $app->make($key);
        }
        return $app->make($key)->file($path);
    }
}

if (! function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param array|string|null $key
     * @param mixed $default
     * @return array|string|Input
     * @throws Exception
     */
    function request(array|string|null $key = null, mixed $default = null) {
        return app_call('request', function (Input $input) use ($key, $default) {
            if (empty($key)) {
                return $input;
            }
            return $input->get($key, $default);
        });
    }
}

if (! function_exists('response')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @return Output|HttpOutput
     * @throws Exception
     */
    function response() {
        return app_call(HttpContext::class, function (HttpContext $context) {
            return $context['response'];
        });
    }
}

if (! function_exists('session')) {
    /**
     * @param array|string|null $key
     * @param null $default
     * @return mixed|Session
     * @throws Exception
     */
    function session(array|string|null $key = null, mixed $default = null) {
        return app_call('session', function (Session $session) use ($key, $default) {
            if (empty($key)) {
                return $session;
            }
            if (is_array($key)) {
                $session->set($key);
                return null;
            }
            return $session->get($key, $default);
        });
    }
}

if (! function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param string|null $key
     * @param array $replace
     * @param string|null $locale
     * @return string|array|I18n
     * @throws Exception
     */
    function trans(string|null $key = null, array $replace = [], string|null $locale = null) {
        return app_call('i18n', function (I18n $i18n) use ($key, $replace, $locale) {
            if (empty($key)) {
                return $i18n;
            }
            return $i18n->translate($key, $replace, $locale);
        });
    }
}

if (! function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param string|null $key
     * @param array $replace
     * @param string|null $locale
     * @return string|array
     * @throws Exception
     */
    function __(string|null $key = null, array $replace = [], string|null $locale = null) {
        return trans($key, $replace, $locale);
    }
}

if (! function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * @param null $path
     * @param mixed $parameters
     * @param bool|null $secure
     * @param bool $encode 是否允许对url进行编码
     * @return string|UrlGenerator
     * @throws Exception
     */
    function url(mixed $path = null, array|bool $parameters = [], bool|null $secure = null, bool $encode = true) {
        $args = func_get_args();
        return app_call(UrlGenerator::class, function (UrlGenerator $generator) use ($args) {
            if (empty($args)) {
                return $generator;
            }
            return $generator->to(...$args);
        });
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
    function view(mixed $path = null, array $data = []) {
        return app_call('view', function (ViewFactory $factory) use ($path, $data) {
            if (empty($path)) {
                return $factory;
            }
            return $factory->render($path, $data);
        });
    }
}

if (! function_exists('timer')) {
    /**
     * @param string $name
     * @return Timer
     * @throws Exception
     */
    function timer(string $name = ''): Timer {
        return app_call('timer', function (Timer $timer) use ($name) {
            if (empty($name)) {
                return $timer;
            }
            $timer->record($name);
            return $timer;
        });
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
        return app_call('events', function (EventManger $manger) use ($args) {
            if (empty($args)) {
                return $manger;
            }
            $manger->dispatch(...$args);
            return null;
        });
    }
}

if (! function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param  object|string  $class
     * @return array
     */
    function class_uses_recursive(mixed $class): array {
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
    function trait_uses_recursive(string $trait): array
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}