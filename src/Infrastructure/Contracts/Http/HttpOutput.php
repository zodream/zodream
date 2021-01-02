<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Http;

use Zodream\Disk\File;
use Zodream\Image\Image;

interface HttpOutput extends Output {

    public function contentType(string $type = 'html', string $option = 'utf-8'): Output;
    public function header(string $key, $value): Output;
    public function cookie(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): Output;

    public function json($data): Output;
    public function jsonP($data): Output;
    public function xml($data): Output;
    public function html($data): Output;
    public function str($data): Output;
    public function rss($data): Output;
    public function file(File $file, int $speed = 512): Output;
    public function image(Image $image): Output;
    public function custom($data, string $type): Output;
    public function redirect($url, int $time = 0): Output;
}