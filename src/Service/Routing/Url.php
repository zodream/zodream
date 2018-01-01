<?php
namespace Zodream\Service\Routing;

/**
 * url生成
 */
use Zodream\Helpers\Str;
use Zodream\Http\Uri;
use Zodream\Infrastructure\Http\Request;
use Zodream\Service\Config;

defined('APP_URL') || define('APP_URL', Url::getRoot());

class Url {

    private static $_host;

    public static function setHost($host) {
        self::$_host = $host;
    }

    /**
     * 获取host 包括域名和端口 80 隐藏
     * @return string
     */
    public static function getHost() {
        if (empty(self::$_host)) {
            // 出现配置循环 bug
            static::setHost(Config::app('host') ?: Request::host());
        }
        return self::$_host;
    }

	/**
	 * 上个页面网址
	 *
	 * @return string|bool 网址
	 */
	public static function referrer() {
		return Request::server('HTTP_REFERER');
	}

    /**
     * 产生网址
     * @param string|array|Uri $file
     * @param array|string|bool $extra
     * @param bool $complete
     * @return string|Uri
     */
	public static function to($file = null, $extra = null, $complete = true) {
        if (is_string($file) &&
            ($file === '#'
                || strpos($file, 'javascript:') === 0)) {
            return $file;
        }
	    if (!$file instanceof Uri) {
	        $file = static::createUri($file);
        }
        if (is_bool($extra)) {
            $complete = $extra;
            $extra = null;
        }
        if (!empty($extra)) {
            $file->addData($extra);
        }
        if ($complete && empty($file->getHost())) {
            $file->setScheme(self::isSsl() ? 'https' : 'http')
                ->setHost(self::getHost());
        }
        return $file;
	}

    /**
     * CREATE URI BY STRING OR ARRAY
     * @param array|string $file
     * @return Uri
     */
	public static function createUri($file) {
        $uri = new Uri();
        if (!is_array($file)) {
            return $uri->decode(static::getPath($file));
        }
        $path = null;
        $data = array();
        foreach ($file as $key => $item) {
            if (is_integer($key)) {
                $path = $item;
                continue;
            }
            $data[$key] = (string)$item;
        }
        return $uri->decode(static::addScript(static::getPath($path)))
            ->addData($data);
    }

	protected static function getPath($path) {
	    if (empty($path) || $path === '0') {
	        return self::getUri();
        }
        if ($path === -1 || $path === '-1') {
            return static::referrer();
        }
        if (!empty(parse_url($path, PHP_URL_HOST))) {
            return $path;
        }
        if (strpos($path, '//') !== false) {
            $path = preg_replace('#/+#', '/', $path);
        }
        return $path;
    }

    protected static function addScript($path) {
	    if (strpos($path, '.') > 0
            || strpos($path, '/') === 0) {
	        return $path;
        }
        $name = Request::server('script_name');
        if ($name === '/index.php') {
            return '/'.$path;
        }
        return $name.'/'.$path;
    }
	
	/**
	 * 获取根网址
	 * 
	 * @param boolean $withScript 是否带执行脚本文件
	 * @return string
	 */
	public static function getRoot($withScript = TRUE) {
		$root = (static::isSsl() ? 'https' : 'http'). '://'.static::getHost() . '/';
		$self = Request::server('script_name');
		if ($self !== '/index.php' && $withScript) {
			$root .= ltrim($self, '/');
		}
		return $root;
	}

    /**
     * GET CURRENT URI
     * @return string
     */
	public static function getCurrentUri() {
        return ltrim(static::getRoot(false), '/').static::getUri();
    }



	/**判断是否带url段
	 * @param string $search
	 * @return bool
	 */
	public static function hasUri($search = null) {
		$url = self::getUriWithoutParam();
		if (is_null($search) && $url == '/') {
			return true;
		}
		return strpos($url, '/'.trim($search, '/')) !== false;
	}

	/**
	 * 判断是否是url
	 * @param string $url
	 * @return bool
	 */
	public static function isUrl($url) {
		return trim(self::getUriWithoutParam(), '/') == trim($url, '/');
	}
	
	/**
	 * 获取网址
	 *
	 * @return string 真实显示的网址
	 */
	public static function getUri() {
		$uri = Request::server('REQUEST_URI');
		if (!is_null($uri)) {
			return $uri;
		}
		$argv = Request::server('argv');
		$self = Request::server('PHP_SELF');
		if (!is_null($argv)) {
			unset($argv[0]);
			return $self .'?'.implode('&', $argv);
		}
		return $self .'?'. Request::server('QUERY_STRING');
	}

    /**
     * @return string
     */
	public static function getUriWithoutParam() {
	    $arg = explode('?', self::getUri());
		return current($arg);
	}
	
	/**
	 * 判断是否SSL协议
	 * @return boolean
	 */
	public static function isSsl() {
		$https = Request::server('HTTPS');
		if ('1' == $https || 'on' == strtolower($https)) {
			return true;
		}
		return Request::server('SERVER_PORT') == 443;
	}
	
	/**
	 * 获取执行脚本的文件    /index.php
     * @return string
	 */
	public static function getScript() {
		return Request::server('SCRIPT_NAME');
	}

    /**
     * 获取网址中的虚拟路径
     * @return string
     */
	public static function getVirtualUri() {
	    $path = Request::server('PATH_INFO');
	    if (!empty($path)) {
	        // 在nginx 下虚拟路径无法获取
	        return $path;
        }
        $script = static::getScript();
	    if (strpos($script, '.php') > 0) {
	        $script = preg_replace('#/[^/]+\.php$#i', '', $script);
        }
        $path = static::getUriWithoutParam();
        if (!empty($script) && strpos($path, $script) === 0) {
            return substr($path, strlen($script));
        }
        return $path;
    }
}