<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Concerns;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/9
 * Time: 11:43
 */
trait ErrorTrait {
    protected array $errors = [];

    /**
     * HAS ERROR
     * @return bool
     */
    public function hasError(): bool {
        return !empty($this->errors);
    }

    /**
     * 获取所有错误消息
     * @param string $key
     * @return array
     */
    public function getError(mixed $key = null): array {
        if (empty($key)) {
            return $this->errors;
        }
        if (!array_key_exists($key, $this->errors)) {
            return [];
        }
        return $this->errors[$key];
    }

    /**
     * 获取第一条错误消息
     * @param string $key
     * @return mixed|null
     */
    public function getFirstError(mixed $key = null): mixed {
        if (empty($key)) {
            $key = key($this->errors);
        }
        if (!isset($this->errors[$key])) {
            return null;
        }
        return current($this->errors[$key]);
    }

    /**
     * SET ERROR
     * @param mixed $key
     * @param mixed $error
     * @return false
     */
    public function setError(mixed $key, mixed $error = null): bool {
        if (is_array($key) && is_null($error)) {
            $this->errors = array_merge($this->errors, $key);
            return false;
        }
        if (!array_key_exists($key, $this->errors)) {
            $this->errors[$key] = array();
        }
        $this->errors[$key][] = $error;
        return false;
    }

    /**
     * clear error
     * @return $this
     */
    public function clearError() {
        $this->errors = [];
        return $this;
    }

    /**
     * GET LAST ERROR
     * @return array
     */
    public function getLastError(): array {
        return array_slice($this->errors, -1);
    }
}