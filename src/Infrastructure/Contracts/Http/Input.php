<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Http;

use ArrayAccess;

interface Input extends ArrayAccess {

    public function has(string $key): bool;
    public function get(string $key = '', $default = null);
    public function post(string $key = '', $default = null);
    public function request(string $key = '', $default = null);
    public function cookie(string $key = '', $default = null);
    public function header(string $key = '', $default = null);
    public function server(string $key = '', $default = null);
    public function file(string $key = '', $default = null);
    public function input(): string;
    public function all(): array;
    public function append(array $data);
    public function replace(array $data);
    public function validate(array $rules);

    public function method(): string;
    public function url(): string;
    public function path(): string;
    public function host(): string;
    public function ip(): string;
    public function referrer(): string;
}