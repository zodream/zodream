<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

use InvalidArgumentException;
use Zodream\Http\Uri;

interface UrlGenerator {

    public function full(): string;
    /**
     * Get the current URL for the request.
     *
     * @return string
     */
    public function current(): string;

    /**
     * Get the URL for the previous request.
     *
     * @param  bool  $fallback
     * @return string
     */
    public function previous(bool $fallback = false): string;

    /**
     * Generate an absolute URL to the given path.
     *
     * @param string|array $path
     * @param mixed $extra
     * @param bool|null $secure
     * @param bool $encode
     * @return string
     */
    public function to(mixed $path, array $extra = [], ?bool $secure = null, bool $encode = true): string;

    /**
     * Generate a secure, absolute URL to the given path.
     *
     * @param  string|array  $path
     * @param  array  $parameters
     * @return string
     */
    public function secure(mixed $path, array $parameters = []): string;

    /**
     * Generate the URL to an application asset.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function asset(string $path, ?bool $secure = null): string;

    /**
     * Get the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string;

    /**
     * Get the URL to a controller action.
     *
     * @param  string|array  $action
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     */
    public function action(string|array $action, array $parameters = [], bool $absolute = true): string;

    /**
     * 解码url，对一些路由重写插件有用
     * @param string $url 为空则获取当前路由
     * @return Uri
     */
    public function decode(string $url = ''): Uri;

    /**
     * 进行编码
     * @param Uri $url
     * @return Uri
     */
    public function encode(Uri $url): Uri;

    public function hasUri(?string $search = null): bool;

    public function isUrl(string $url): bool;
    /**
     * 需要更新一些数据
     */
    public function sync();
}