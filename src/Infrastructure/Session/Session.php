<?php
namespace Zodream\Infrastructure\Session;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/6
 * Time: 9:56
 */
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Disk\Directory;
use Zodream\Infrastructure\Contracts\Session as SessionInterface;
use Zodream\Service\Middleware\CSRFMiddleware;

class Session extends ConfigObject implements SessionInterface, \ArrayAccess {

    protected string $configKey = 'session';

    protected array $configs = [
        'flashParam' => '__flash',
        'savePath' => false
    ];

    private array $_cookieParams = array(
        'httponly' => true
    );

    public function __construct() {
        $this->loadConfigs();
    }

    public function useCustomStorage() {
        return false;
    }

    /**
     * 判断session 是否启动
     * @return bool
     */
    public function isActive(): bool {
        return isset($_SESSION) || session_status() === PHP_SESSION_ACTIVE;
    }

    public function open() {
        if ($this->isActive()) {
            return;
        }
        register_shutdown_function(array($this, 'close'));
        $this->_setCookieParamsInternal();
        $this->useCookie(true);
        $this->useTransparentSessionID(false);
        if (isset($this->configs['directory']) && is_string($this->configs['directory']) &&
            is_dir($this->configs['directory'])) {
            $this->savePath($this->configs['directory']);
        }
        @session_start();
//        if (! $this->has('_token')) {
//            $this->regenerateToken();
//        }
    }

    protected function registerSessionHandler() {
        if ($this->useCustomStorage()) {
            @session_set_save_handler(
                [$this, 'openSession'],
                [$this, 'closeSession'],
                [$this, 'readSession'],
                [$this, 'writeSession'],
                [$this, 'destroySession'],
                [$this, 'gcSession']
            );
        }
    }

    public function openSession($savePath, $sessionName) {
        return true;
    }

    public function closeSession() {
        return true;
    }

    public function readSession($id) {
        return '';
    }

    public function writeSession($id, $data) {
        return true;
    }

    public function destroySession($id) {
        return true;
    }

    public function gcSession($maxLifetime) {
        return true;
    }

    public function close() {
        if ($this->isActive()) {
            @session_write_close();
        }
    }

    /**
     * gc自动执行的时间
     * @param $value
     */
    public function gcProbability($value) {
        if ($value >= 0 && $value <= 100) {
            ini_set('session.gc_probability', floor($value * 21474836.47));
            ini_set('session.gc_divisor', 2147483647);
        }
    }

    /**
     * gc执行时间限制
     * @param $value
     */
    public function timeout($value) {
        ini_set('session.gc_maxlifetime', $value);
    }

    public function getCookieParams() {
        return array_merge(session_get_cookie_params(), array_change_key_case($this->_cookieParams));
    }

    public function setCookieParams(array $value) {
        $this->_cookieParams = $value;
    }

    private function _setCookieParamsInternal()
    {
        $data = $this->getCookieParams();
        extract($data);
        if (isset($lifetime, $path, $domain, $secure, $httponly)) {
            session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        }
    }

    public function id(string $value = '') {
        if (empty($value)) {
            $this->open();
            return session_id();
        }
        $this->close();
        return session_id($value);
    }

    public function savePath($path = null) {
        if (null == $path) {
            return session_save_path();
        }
        if (!$path instanceof Directory) {
            $path = app_path()->childDirectory($path);
        }
        if ($path->exist()) {
            return session_save_path((string)$path);
        }
        return false;
    }

    public function useCookie(mixed $value) {
        if ($value === false) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        } elseif ($value === true) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
        } else {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '0');
        }
    }

    public function useTransparentSessionID(bool $value) {
        ini_set('session.use_trans_sid', $value ? '1' : '0');
    }

    public function count(): int {
        $this->open();
        return count($_SESSION);
    }

    public function get(string $key = '', mixed $defaultValue = null) {
        $this->open();
        if (empty($key)) {
            return $_SESSION;
        }
        return $_SESSION[$key] ?? $defaultValue;
    }

    public function set($key, $value = null) {
        $this->open();
        if (!is_array($key)) {
            $_SESSION[$key] = $value;
            return;
        }
        foreach ($key as $k => $v) {
            $_SESSION[$k] = $v;
        }
    }

    protected function updateFlashCounters() {
        $counters = $this->get($this->configs['flashParam'], []);
        if (is_array($counters)) {
            foreach ($counters as $key => $count) {
                if ($count > 0) {
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($count == 0) {
                    $counters[$key]++;
                }
            }
            $_SESSION[$this->configs['flashParam']] = $counters;
        } else {
            // fix the unexpected problem that flashParam doesn't return an array
            unset($_SESSION[$this->configs['flashParam']]);
        }
    }

    public function getFlash($key, $defaultValue = null, $delete = false) {
        $counters = $this->get($this->configs['flashParam'], []);
        if (isset($counters[$key])) {
            $value = $this->get($key, $defaultValue);
            if ($delete) {
                $this->removeFlash($key);
            } elseif ($counters[$key] < 0) {
                // mark for deletion in the next request
                $counters[$key] = 1;
                $_SESSION[$this->configs['flashParam']] = $counters;
            }

            return $value;
        } else {
            return $defaultValue;
        }
    }

    public function getAllFlashes($delete = false) {
        $counters = $this->get($this->configs['flashParam'], []);
        $flashes = [];
        foreach (array_keys($counters) as $key) {
            if (array_key_exists($key, $_SESSION)) {
                $flashes[$key] = $_SESSION[$key];
                if ($delete) {
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($counters[$key] < 0) {
                    // mark for deletion in the next request
                    $counters[$key] = 1;
                }
            } else {
                unset($counters[$key]);
            }
        }

        $_SESSION[$this->configs['flashParam']] = $counters;

        return $flashes;
    }

    public function setFlash($key, $value = true, $removeAfterAccess = true) {
        $counters = $this->get($this->configs['flashParam'], []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$key] = $value;
        $_SESSION[$this->configs['flashParam']] = $counters;
    }

    public function delete($key) {
        $this->open();
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $value;
        }
        return null;
    }

    public function addFlash($key, $value = true, $removeAfterAccess = true) {
        $counters = $this->get($this->configs['flashParam'], []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$this->configs['flashParam']] = $counters;
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = [$value];
        } else {
            if (is_array($_SESSION[$key])) {
                $_SESSION[$key][] = $value;
            } else {
                $_SESSION[$key] = [$_SESSION[$key], $value];
            }
        }
    }

    public function removeFlash($key) {
        $counters = $this->get($this->configs['flashParam'], []);
        $value = isset($_SESSION[$key], $counters[$key]) ? $_SESSION[$key] : null;
        unset($counters[$key], $_SESSION[$key]);
        $_SESSION[$this->configs['flashParam']] = $counters;

        return $value;
    }

    public function removeAllFlashes() {
        $counters = $this->get($this->configs['flashParam'], []);
        foreach (array_keys($counters) as $key) {
            unset($_SESSION[$key]);
        }
        unset($_SESSION[$this->configs['flashParam']]);
    }

    public function hasFlash($key) {
        return $this->getFlash($key) !== null;
    }

    public function destroy() {
        if ($this->isActive()) {
            @session_unset();
            @session_destroy();
        }
    }
    
    public function flush() {
        $this->open();
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
        return true;
    }

    public function has($key): bool {
        $this->open();
        return isset($_SESSION[$key]);
    }

    public function name($value = null) {
        return session_name($value);
    }

    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    public function token(): string {
        return (string)$this->get(CSRFMiddleware::SESSION_KEY);
    }

    /**
     * Regenerate the CSRF token value.
     *
     * @return void
     */
    public function regenerateToken(): void {
        $this->set(CSRFMiddleware::SESSION_KEY, Str::random(40));
    }

    /**
     * Get the previous URL from the session.
     *
     * @return string|null
     */
    public function previousUrl() {
        return $this->get('_previous.url');
    }

    /**
     * Set the "previous" URL in the session.
     *
     * @param  string  $url
     * @return void
     */
    public function setPreviousUrl($url) {
        $this->set('_previous.url', $url);
    }

    public function offsetExists($offset): bool {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void {
        $this->delete($offset);
    }

}