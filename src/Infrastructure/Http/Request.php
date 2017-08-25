<?php 
namespace Zodream\Infrastructure\Http;
/**
* http 请求信息获取类
* 
* @author Jason
*/
use Zodream\Infrastructure\Http\Requests\BaseRequest;
use Zodream\Infrastructure\Http\Requests\Cookie;
use Zodream\Infrastructure\Http\Requests\Files;
use Zodream\Infrastructure\Http\Requests\Get;
use Zodream\Infrastructure\Http\Requests\Header;
use Zodream\Infrastructure\Http\Requests\Post;
use Zodream\Infrastructure\Http\Requests\Server;
use Zodream\Service\Config;
defined('APP_SAFE') || define('APP_SAFE', Config::getInstance()->get('app.safe', true));

final class Request {

	private static $_instances = array(
		'cookie' => null,
		'files' => null,
		'get' => null,
		'post' => null,
		'header' => null,
		'request' => null,
		'server' => null,
		'other' => null
	);

	/**
	 * @param $name
	 * @return BaseRequest
	 */
	private static function _getInstance($name) {
		$name = strtolower($name);
		if (!array_key_exists($name, self::$_instances)) {
			return null;
		}
		if (self::$_instances[$name] instanceof BaseRequest) {
			return self::$_instances[$name];
		}
		$class = 'Zodream\\Infrastructure\\Http\\Requests\\'.ucfirst($name);
		return self::$_instances[$name] = new $class;
	}

	/**
	 * @param $key
	 * @param string $name
	 * @param mixed $default
	 * @return array|string|BaseRequest
	 */
	private static function getValue($key, $name = null, $default = null) {
		$instance = self::_getInstance($key);
		if (true === $name) {
			return $instance;
		}
		return $instance->get($name, $default);
	}

	/**
	 * $_GET
	 * @param string $name
	 * @param string $default
	 * @return array|string|Get
	 */
	public static function get($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

	/**
	 * $_POST
	 * @param string $name
	 * @param string $default
	 * @return array|string|Post
	 */
	public static function post($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

	/**
	 * $_FILES
	 * @param string $name
	 * @param string $default
	 * @return array|string|Files
	 */
	public static function files($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

	/**
	 * $_REQUEST
	 * @param string $name
	 * @param string $default
	 * @return array|string|\Zodream\Infrastructure\Http\Requests\Request
	 */
	public static function request($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

	/**
	 * $_COOKIE
	 * @param string $name
	 * @param string $default
	 * @return array|string|Cookie
	 */
	public static function cookie($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}
	
	/**
	 * PHP://INPUT
	 * @return string
	 */
	public static function input() {
		return file_get_contents('php://input');
	}

	/**
	 * $_SERVER
	 * @param string $name
	 * @param string $default
	 * @return array|string|Server
	 */
	public static function server($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

	/**
	 * @param string $name
	 * @param string $default
	 * @return array|string|Header
	 */
	public static function header($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

	/**
	 * 一些手动添加的
	 * @param null $name
	 * @param null $default
	 * @return array|string
	 */
	public static function other($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

	
	public static function isCli() {
		return !is_null(self::server('argv'));
	}


	public static function ip() {
		return self::Other(__FUNCTION__);
	}

    public static function host() {
        return self::Other(__FUNCTION__);
    }
	
	public static function os() {
		return self::Other(__FUNCTION__);
	}
	
	public static function browser() {
		return self::Other(__FUNCTION__);
	}
	
	public static function isMobile() {
		return self::Other(__FUNCTION__);
	}

	public static function isWeChat() {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
    }
	
	public static function method() {
		return self::other('method');
	}
	
	public static function isGet() {
		return self::method() === 'GET';
	}
	
	public static function isOptions() {
		return self::method() === 'OPTIONS';
	}
	
	public static function isHead() {
		return self::method() === 'HEAD';
	}
	
	public static function isPost() {
		return self::method() === 'POST';
	}
	
	public static function isDelete() {
		return self::method() === 'DELETE';
	}
	
	public static function isPut() {
		return self::method() === 'PUT';
	}
	
	public static function isPatch() {
		return self::method() === 'PATCH';
	}
	
	public static function isAjax() {
		return self::server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
	}
	
	public static function isPjax() {
		return self::isAjax() && !empty(self::server('HTTP_X_PJAX'));
	}
	
	public static function isFlash() {
		$arg = self::server('HTTP_USER_AGENT', '');
		return stripos($arg, 'Shockwave') !== false || stripos($arg, 'Flash') !== false;
	}
}