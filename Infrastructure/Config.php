<?php 
namespace Zodream\Infrastructure;
/**
* 读写配置
* 
* @author Jason
*/
use Zodream\Domain\Generate\Generate;
use Zodream\Infrastructure\Traits\SingletonPattern;
use Zodream\Infrastructure\ObjectExpand\ArrayExpand;


class Config extends MagicObject {
	
	use SingletonPattern;
	
	protected static $path;
	
	public static function setPath($value = null) {
		if (!is_dir($value)) {
			if (defined('APP_DIR') && is_dir(APP_DIR)) {
				$value = APP_DIR.'/Service/config/';
			} else {
				$value = dirname(dirname(dirname(dirname(dirname(__FILE__))))). '/Service/config/';
			}
		}
		static::$path = $value;
	}

	public static function getPath() {
		if (!is_dir(static::$path)) {
			static::setPath();
		}
		return static::$path;
	}
	
	/**
	 * 判断配置文件是否存在
	 */
	public static function exist() {
		return is_file(static::getPath().APP_MODULE.'.php');
	}
	
	private function __construct($args = array()) {
		$this->reset($args);
	}

	/**
	 * 重新加载配置
	 * @param array $args
	 */
	public function reset($args = array()) {
		$configs = $this->_getConfig(dirname(dirname(__FILE__)). '/Service/config.php');
		$personal = array();
		if (defined('APP_MODULE')) {
			$personal = $this->_getConfig(static::getPath().APP_MODULE.'.php');
		}
		$common  = $this->_getConfig(static::getPath().'config.php');
		$this->set(ArrayExpand::merge2D((array)$configs, 
			(array)$common, (array)$personal), (array)$args);
	}

	/**
	 * 获取配置文件
	 * @param string $file
	 * @return array
     */
	private function _getConfig($file) {
		if (!is_file($file)) {
			return array();
		}
		$config = include($file);
		if (is_string($config)) {
			return $this->_getConfig($config);
		}
		return $config;
	}

	/**
	 * 根据方法换取多维中的一个值
	 * @param string $method
	 * @param array $value
	 * @return array|null|string
	 */
	public function getMultidimensional($method, array $value) {
		$length = count($value);
		if ($length < 1) {
			return $this->get($method);
		}
		if ($length > 1) {
			return $this->get($method . implode('.', $value));
		}
		if (!$this->has($method) || !isset($this->_data[$method][$value[0]])) {
			return null;
		}
		return $this->_data[$method][$value[0]];
	}

	/**
	 * 支持与默认合并参数
	 * @param string $key
	 * @param mixed $default
	 * @return array|null|string
	 */
	public function get($key = null, $default = null) {
		$args = parent::get($key, $default);
		if (!is_array($default)) {
			return $args;
		}
		if (empty($args)) {
			return $default;
		}
		return array_merge((array)$args, $default);
	}

	/**
	 * 支持根据键合并数组
	 * @param array|string $key
	 * @param mixed $value
	 */
	public function set($key, $value = null) {
		if ($this->has($key) && is_array($value)) {
			$this->_data[$key] = array_merge((array)$this->_data[$key], $value);
			return;
		}
		return parent::set($key, $value);
	}

	public function __call($method, $value) {
		$this->getMultidimensional($method, $value);
	}
	
	public static function save($name = null) {
		if (empty($name)) {
			$name = APP_MODULE;
		}
		if (!is_file($name)) {
			$name = static::getPath().$name.'.php';
		}
		$generate = new Generate();
		return $generate->setReplace(true)->makeConfig(static::getValue(), $name);
	}

	/**
	 * 静态方法获取
	 * @param null $key
	 * @param null $default
	 * @return array|string
	 */
	public static function getValue($key = null, $default = null) {
		return static::getInstance()->get($key, $default);
	}

	/**
	 *
	 * @param string $method
	 * @param array $value
	 * @return mixed
	 */
	public static function __callStatic($method, $value) {
		return static::getInstance()->getMultidimensional($method, $value);
	}
}