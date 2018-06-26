<?php 
namespace Zodream\Infrastructure\Http;
/**
* http 请求信息获取类
* 
* @author Jason
*/
use Zodream\Infrastructure\Http\Input\BaseInput;
use Zodream\Infrastructure\Http\Input\Cookie;
use Zodream\Infrastructure\Http\Input\Files;
use Zodream\Infrastructure\Http\Input\Get;
use Zodream\Infrastructure\Http\Input\Header;
use Zodream\Infrastructure\Http\Input\Post;
use Zodream\Infrastructure\Http\Input\Server;
use Zodream\Infrastructure\Http\Input\Argv;
use Zodream\Helpers\Str;
use Zodream\Service\Config;
use Zodream\Service\Routing\Url;
use Zodream\Validate\ValidationException;
use Zodream\Validate\Validator;

defined('APP_SAFE') || define('APP_SAFE', Config::app('safe', false));

final class Request {

	private static $_instances = array(
		'cookie' => null,
		'files' => null,
		'get' => null,
		'post' => null,
		'header' => null,
		'request' => null,
		'server' => null,
		'other' => null,
        'argv' => null,
	);

	/**
	 * @param $name
	 * @return BaseInput
	 */
	private static function _getInstance($name) {
		$name = strtolower($name);
		if (!array_key_exists($name, self::$_instances)) {
			return null;
		}
		if (self::$_instances[$name] instanceof BaseInput) {
			return self::$_instances[$name];
		}
		$class = 'Zodream\\Infrastructure\\Http\\Input\\'.ucfirst($name);
		return self::$_instances[$name] = new $class;
	}

	/**
	 * @param $key
	 * @param string $name
	 * @param mixed $default
	 * @return array|string|BaseInput
	 */
	private static function getValue($key, $name = null, $default = null) {
		$instance = self::_getInstance($key);
		if (is_null($name) || $name === true) {
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
     * CLI ARGV
     * @param null $name
     * @param null $default
     * @return array|string|Argv
     */
	public static function argv($name = null, $default = null) {
        return self::getValue(__FUNCTION__, $name, $default);
    }

    /**
     * CLI 读取输入值
     * @return string
     */
    public static function read() {
        return trim(fgets(STDIN));
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
	 * @return array|string|\Zodream\Infrastructure\Http\Input\Request
	 */
	public static function request($name = null, $default = null) {
		return self::getValue(__FUNCTION__, $name, $default);
	}

    /**
     * 判断是否有值
     * @param string $key
     * @return bool
     */
	public static function has($key) {
	    return static::request(true)->has($key);
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

    /**
     *
     */
	public static function path() {
        $pattern = trim(static::server('PHP_SELF'), '/');
        return $pattern == '' ? '/' : $pattern;
    }

    /**
     *
     * @param array $rules
     * @return array
     * @throws ValidationException
     * @throws \Exception
     */
    public static function validate(array $rules) {
        $data = [];
        $validator = new Validator();
        foreach ($rules as $key => $rule) {
            $rule = $validator->converterRule($rule);
            $value = static::request($key);
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

    /**
     * 解密路径
     * @return string
     */
    public static function decodedPath() {
        return rawurldecode(static::path());
    }

    /**
     * 去除查询参数的路径
     * @return string
     */
    public static function url() {
        return rtrim(preg_replace('/\?.*/', '', static::fullUrl()), '/');
    }

    /**
     * 完整的路径
     * @return string
     */
    public static function fullUrl() {
        return Url::getCurrentUri();
    }

    /**
     * 判断是否网址
     * @return bool
     */
    public static function is() {
        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, static::decodedPath())) {
                return true;
            }
        }
        return false;
    }

	
	public static function isCli() {
		return !is_null(self::server('argv'));
	}

	public static function isLinux() {
        return DIRECTORY_SEPARATOR == '/';
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

	public static function isJson() {
        return static::header('CONTENT_TYPE') == 'application/json';
    }

	public static function isWeChat() {
        return strpos(self::server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
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

    /**
     * 判断是否期望返回JSON
     * @return bool
     */
    public static function expectsJson() {
        return (static::isAjax() && !static::isPjax()) || static::wantsJson();
    }

    /**
     * 请求头判断 接受类型为 JSON
     * @return bool
     */
	public static function wantsJson() {
	    $accept = static::header('ACCEPT');
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
	public static function isFlash() {
		$arg = self::server('HTTP_USER_AGENT', '');
		return stripos($arg, 'Shockwave') !== false || stripos($arg, 'Flash') !== false;
	}

    /**
     * 只能获取基础验证的账号密码
     * @return array [username, password]
     */
	public static function auth() {
        return self::other('auth');
    }

    /**
     * 获取 token
     * @return string|null
     */
    public static function bearerToken() {
        $header = static::header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}