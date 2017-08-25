<?php 
namespace Zodream\Service;
/**
* 读写配置
* 
* @author Jason
*/
use Zodream\Infrastructure\Base\Config as BaseConfig;
use Zodream\Infrastructure\Traits\SingletonPattern;


class Config extends BaseConfig {
	
	use SingletonPattern;

	private function __construct($args = array()) {
		$this->reset($args);
	}

    /**
     * 重新加载配置
     * @param array $args
     * @return $this
     */
	public function reset($args = array()) {
	    $this->_data = $args;
	    $files = [__DIR__. '/config/config.php', 'config'];
		if (defined('APP_MODULE')) {
			$files[] = APP_MODULE;
		}
		return $this->mergeFiles($files);
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

	public function __call($method, $value) {
		$this->getMultidimensional($method, $value);
	}

	/**
	 * 静态方法获取
	 * @param null $key
	 * @param null $default
	 * @return array|string
	 */
	public static function getValue($key = null, $default = null) {
		return static::getInstance()
            ->get($key, $default);
	}

	public static function setValue($key, $value = null) {
        return static::getInstance()
            ->set($key, $value);
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