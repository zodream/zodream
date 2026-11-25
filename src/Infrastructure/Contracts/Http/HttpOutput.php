<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Http;

use Zodream\Disk\File;
use Zodream\Image\Image;
use Zodream\Http\Uri;

interface HttpOutput extends Output {

    public function contentType(string $type = 'html', string $option = 'utf-8'): Output;
    public function header(string $key, mixed $value): Output;
    public function cookie(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): Output;

    public function json(mixed $data): Output;
    public function jsonP(mixed $data): Output;
    public function xml(mixed $data): Output;
    public function html(mixed $data): Output;
    public function str(mixed $data): Output;
    public function rss(mixed $data): Output;
    public function file(File $file, int $speed = 512): Output;
    public function image(Image $image): Output;
    public function custom(mixed $data, string $type): Output;
    public function redirect(string|Uri $url, int $time = 0): Output;
}