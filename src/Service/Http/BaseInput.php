<?php
declare(strict_types=1);
namespace Zodream\Service\Http;


use Zodream\Helpers\Arr;
use Zodream\Helpers\Str;
use Zodream\Validate\ValidationException;
use Zodream\Validate\Validator;

abstract class BaseInput {

    protected array $cacheItems = [];
    protected array $data = [];

    public function has(string $key): bool {
        return isset($this->data[$key]) || array_key_exists($key, $this->data);
    }

    public function get(string $key = '', $default = null) {
        return $this->getValueWithDefault($this->data, $key, $default);
    }

    public function bool(string $key, bool $default = false): bool {
        return Str::toBool($this->get($key, $default));
    }
    public function int(string $key, int $default = 0): int {
        $val = $this->get($key, $default);
        return is_int($val) ? $val : intval($val);
    }
    public function float(string $key, float $default = 0): float {
        $val = $this->get($key, $default);
        return is_float($val) ? $val : floatval($val);
    }
    public function string(string $key, string $default = ''): string {
        $val = $this->get($key, $default);
        return is_string($val) ? $val : sprintf('%s', $val);
    }

    public function append(array $data) {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function replace(array $data) {
        $this->data = $data;
        return $this;
    }

    public function all(): array
    {
        return $this->data;
    }

    /**
     *
     * @param array $rules
     * @return array
     * @throws ValidationException
     * @throws \Exception
     */
    public function validate(array $rules): array {
        return Validator::filter($this, $rules);
    }

    /**
     * @param $name
     * @return string|array|bool|integer|mixed|null
     */
    protected function getCacheData($name) {
        if (isset($this->cacheItems[$name])) {
            return $this->cacheItems[$name];
        }
        $method = sprintf('create%s', Str::studly($name));
        if (!method_exists($this, $method)) {
            return null;
        }
        return $this->cacheItems[$name]
            = call_user_func([$this, $method]);
    }

    protected function getValueWithDefault(array $data, string $key = null, $default = null) {
        if (empty($key)) {
            return $data;
        }
        if (isset($data[$key]) || array_key_exists($key, $data)) {
            return $data[$key];
        }
        if (str_contains($key, ',')) {
            $result = Arr::getValues($key, $data, $default);
        } else {
            $result = Arr::getChild($key, $data, is_object($default) ? null : $default);
        }
        if (is_callable($default)) {
            return $default($result);
        }
        return $result;
    }

    /**
     * 格式化
     * @param array|string $data
     * @return array|string
     */
    protected function cleanData($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);
                $data[$this->cleanData($key)] = $this->cleanData($value);
            }
        } else if (defined('APP_SAFE') && APP_SAFE){
            $data = htmlspecialchars($data, ENT_COMPAT);
        }
        return $data;
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->append([
            $offset => $value
        ]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

}