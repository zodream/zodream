<?php
declare(strict_types=1);
namespace Zodream\Service\Http;


use Zodream\Helpers\Arr;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Contracts\Http\Input;
use Zodream\Service\Http\Concerns\Header;
use Zodream\Service\Http\Concerns\Other;
use Zodream\Validate\ValidationException;
use Zodream\Validate\Validator;

class Request implements Input {

    use Header, Other;

    protected $cacheItems = [];
    protected $data = [];

    public function __construct() {
        $data = $this->getTypeParser();
        if (!is_array($data)) {
            $data = null;
        }
        $this->data = $this->cleanData(empty($data)
            ? $_REQUEST : array_merge($_REQUEST, $data));
    }

    protected function getTypeParser() {
        if ($this->isJson()) {
            return json_decode($this->input(), true);
        }
        if ($this->isXml()) {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $data = simplexml_load_string($this->input());
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            return $data;
        }
        return null;
    }

    public function has(string $key): bool {
        return isset($this->data[$key]) || array_key_exists($key, $this->data);
    }

    public function get(string $key = '', $default = null)
    {
        return $this->getValueWithDefault($this->data, $key, $default);
    }

    public function post(string $key = '', $default = null)
    {
        return $this->getValueWithDefault($_POST, $key, $default);
    }

    public function request(string $key = '', $default = null)
    {
        return $this->getValueWithDefault($_REQUEST, $key, $default);
    }

    public function cookie(string $key = '', $default = null)
    {
        return $this->getValueWithDefault($_COOKIE, $key, $default);
    }

    public function header(string $key = '', $default = null)
    {
        $data = $this->getCacheData(__FUNCTION__);
        if (isset($data[$key])) {
            return $data[$key];
        }
        if (strpos($key, '-') !== false) {
            $key = str_replace('-', '_', $key);
        }
        $key = strtoupper($key);
        return $this->getValueWithDefault($data, $key, $default);
    }

    public function server(string $key = '', $default = null)
    {
        return $this->getValueWithDefault($_SERVER, $key, $default);
    }

    public function file(string $key = '', $default = null)
    {
        return $this->getValueWithDefault($_FILES, $key, $default);
    }

    public function input(): string
    {
        return file_get_contents('php://input');
    }

    public function append(array $data): Request {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function method(): string
    {
        return $this->getCacheData('method');
    }

    public function url(): string
    {
        return $this->getCacheData(__FUNCTION__);
    }

    public function host(): string
    {
        return $this->getCacheData(__FUNCTION__);
    }

    public function ip(): string
    {
        return $this->getCacheData(__FUNCTION__);
    }

    public function referrer(): string
    {
        return $this->server('HTTP_REFERER', '');
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
            if (is_null($value) && !isset($item['rules']['required'])) {
                continue;
            }
            if ($validator->validateRule($key, $value, $rule['rules'], $rule['message'])) {
                $data[$key] = $value;
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
        $type = $this->header('CONTENT_TYPE');
        return !empty($type)
            && Str::contains($type, ['/json', '+json']);
    }

    public function isXml(): bool {
        $type = $this->header('CONTENT_TYPE');
        return $type == 'application/xml' || $type == 'text/xml';
    }

    public function isHtml(): bool {
        return empty($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_SERVER['HTTP_X_TRACY_AJAX'])
            && PHP_SAPI !== 'cli'
            && !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()));
    }

    public function isWeChat(): bool {
        return strpos($this->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
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

    /**
     * 当前请求是不cors的预检请求
     * @return bool
     */
    public function isPreFlight(): bool {
        return $this->method() === 'OPTIONS'
            && !empty($this->header('Origin'));
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
     * Authorization: Basic
     * @return array
     */
    public function basicToken(): array {
        return $this->getCacheData('basic_token');
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

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->append([
            $offset => $value
        ]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public static function createFromGlobals() {
        return self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);
    }


    private static function createRequestFromFactory(
        array $query = [], array $request = [],
        array $attributes = [], array $cookies = [],
        array $files = [], array $server = [], $content = null) {
        return new static();
    }

}