<?php
declare(strict_types=1);
namespace Zodream\Service\Console;

use Zodream\Infrastructure\Contracts\Http\Input as InputInterface;
use Zodream\Service\Console\Concerns\Argv;
use Zodream\Service\Http\BaseInput;

class Input extends BaseInput implements InputInterface {

    use Argv;

    public function __construct()
    {
        $data = $this->getCacheData('argv');
        $this->data = $data['options'];
    }

    public function has(string $key): bool {
        if (strlen($key) !== 1) {
            return isset($this->data[$key]) || array_key_exists($key, $this->data);
        }
        $data = $this->getCacheData('argv');
        return in_array($key, $data['flags'], true);
    }

    /**
     * CLI 读取输入值
     * @param string|null $key
     * @param string|null $default
     * @return string|null
     */
    public function post(string $key = null, $default = null)
    {
        if (!empty($key)) {
            echo $key;
        }
        $input = trim(fgets(STDIN));
        return $input === '' ? $default : $input;
    }

    public function request(string $key = '', $default = null)
    {
        return $this->getValueWithDefault($this->getCacheData('argv'), $key, $default);
    }

    public function cookie(string $key = null, $default = null)
    {
        return $this->getValueWithDefault([], $key, $default);
    }

    public function header(string $key = null, $default = null)
    {
        return $this->getValueWithDefault([], $key, $default);
    }

    public function server(string $key = null, $default = null)
    {
        return $this->getValueWithDefault($_SERVER, $key, $default);
    }

    public function file(string $key = null, $default = null)
    {
        return $this->getValueWithDefault([], $key, $default);
    }

    public function input(): string
    {
        return '';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function url(): string {
        return sprintf('http://%s/%s', $this->host(), $this->path());
    }

    public function path(): string {
        return $this->routePath();
    }

    public function routePath(): string {
        return $this->getCacheData('path');
    }

    public function host(): string
    {
        return '127.0.0.1';
    }

    public function ip(): string
    {
        return '127.0.0.1';
    }

    public function referrer(): string
    {
        return '';
    }

    public function isAjax(): bool {
        return true;
    }

    public function isCli(): bool {
        return true;
    }

    public function isMobile(): bool {
        return false;
    }

    public function script(): string {
        return '';
    }

    public static function createFromGlobals() {
        return new static();
    }
}