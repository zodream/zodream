<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Http;

use Zodream\Helpers\Arr;
use Zodream\Helpers\Str;
use Zodream\Http\Uri;
use Zodream\Infrastructure\Http\Input\Argv;
use Zodream\Infrastructure\Http\Input\Header;
use Zodream\Infrastructure\Http\Input\Other;
use Zodream\Validate\ValidationException;
use Zodream\Validate\Validator;

class Request {

    use Argv, Header, Other;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $cache_data = [];

    public function __construct() {
        if ($this->isJson()) {
            $_REQUEST = array_merge($_REQUEST, json_decode($this->input(), true));
        }
        $this->data = $this->cleanData($_REQUEST);
    }

    public function has(string $key): bool {
        return isset($this->data[$key]) || array_key_exists($key, $this->data);
    }

    public function get(string $key = null, $default = null) {
        return $this->getValueWithDefault($this->data, $key, $default);
    }

    public function append(array $data): Request {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function all(): array {
        return $this->data;
    }

    public function uri(): Uri {
        return $this->getCacheData(__FUNCTION__);
    }

    public function server(string $key, $default = null) {
        return $this->getValueWithDefault($_SERVER, $key, $default);
    }

    public function cookie(string $key, $default = null) {
        return $this->getValueWithDefault($_COOKIE, $key, $default);
    }

    public function header(string $key, $default = ''): string {
        return $this->getValueWithDefault($this->getCacheData(__FUNCTION__), $key, $default);
    }

    public function argv(string $key, $default = '') {
        return $this->getValueWithDefault($this->getCacheData(__FUNCTION__), $key, $default);
    }

    public function files($name = null) {
        return $this->getValueWithDefault($_FILES, $name);
    }

    /**
     * CLI 读取输入值
     * @return string
     */
    public function read(): string {
        return trim(fgets(STDIN));
    }

    public function input(): string {
        return file_get_contents('php://input');
    }

    public function ip(): string {
        return $this->getCacheData(__FUNCTION__);
    }

    /**
     *
     * @param array $rules
     * @return array
     * @throws ValidationException
     * @throws \Exception
     */
    public function validate(array $rules) {
        $data = [];
        $validator = new Validator();
        foreach ($rules as $key => $rule) {
            $rule = $validator->converterRule($rule);
            $value = $this->get($key);
            if ($validator->validateRule($key, $value, $rule['rules'], $rule['message'])) {
                $data[] = $value;
                continue;
            }
        }
        if ($validator->messages()->isEmpty()) {
            return $data;
        }
        throw new ValidationException($validator);
    }


    public function isCli(): bool {
        return !is_null($this->server('argv'));
    }

    public function isLinux(): bool {
        return DIRECTORY_SEPARATOR == '/';
    }

    public function os(): string {
        return $this->getCacheData(__FUNCTION__);
    }

    public function browser(): string {
        return $this->getCacheData(__FUNCTION__);
    }

    public function isMobile(): bool {
        return $this->getCacheData(__FUNCTION__);
    }

    public function isJson(): bool {
        return $this->header('CONTENT_TYPE') == 'application/json';
    }

    public function isWeChat(): bool {
        return strpos($this->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
    }

    public function method(): string {
        return $this->getCacheData('method');
    }

    public function isSSL(): bool {
        return $this->getCacheData(__FUNCTION__);
    }

    public function isGet(): bool {
        return $this->method() === 'GET';
    }

    public function isOptions(): bool {
        return $this->method() === 'OPTIONS';
    }

    public function isHead(): bool {
        return $this->method() === 'HEAD';
    }

    public function isPost(): bool {
        return $this->method() === 'POST';
    }

    public function isDelete(): bool {
        return $this->method() === 'DELETE';
    }

    public function isPut(): bool {
        return $this->method() === 'PUT';
    }

    public function isPatch(): bool {
        return $this->method() === 'PATCH';
    }

    public function isAjax(): bool {
        return $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function isPjax(): bool {
        return $this->isAjax() && !empty($this->server('HTTP_X_PJAX'));
    }

    /**
     * 判断是否期望返回JSON
     * @return bool
     */
    public function expectsJson(): bool {
        return ($this->isAjax() && !$this->isPjax()) || $this->wantsJson();
    }

    /**
     * 请求头判断 接受类型为 JSON
     * @return bool
     */
    public function wantsJson(): bool {
        $accept = $this->header('ACCEPT');
        if (empty($accept)) {
            return false;
        }
        $args = explode(';', $accept);
        return Str::contains($args[0], ['/json', '+json']);
    }

    /**
     * 是否是 flash
     * @return bool
     */
    public function isFlash(): bool {
        $arg = $this->server('HTTP_USER_AGENT', '');
        return stripos($arg, 'Shockwave') !== false || stripos($arg, 'Flash') !== false;
    }

    public function referrer(): string {
        return $this->server('HTTP_REFERER');
    }

    public function script(): string {
        return $this->server('SCRIPT_NAME');
    }

    /**
     * 只能获取基础验证的账号密码
     * @return array [username, password]
     */
    public function auth(): array {
        return $this->getCacheData('auth');
    }

    /**
     * 获取 token
     * @return string|null
     */
    public function bearerToken(): string {
        $header = $this->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return '';
    }

    /**
     * @param $name
     * @return string|array|bool|integer|mixed|null
     */
    protected function getCacheData($name) {
        if (isset($this->cache_data[$name])) {
            return $this->cache_data[$name];
        }
        $method = sprintf('create%s', Str::studly($name));
        if (!method_exists($this, $method)) {
            return null;
        }
        return $this->cache_data[$name]
            = call_user_func([$this, $method]);
    }

    protected function getValueWithDefault(array $data, string $key = null, $default = null) {
        if (empty($key)) {
            return $data;
        }
        if (isset($data[$key]) || array_key_exists($key, $data)) {
            return $data[$key];
        }
        if (strpos($key, ',') !== false) {
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
}