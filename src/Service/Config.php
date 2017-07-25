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
	    if (empty($value)) {
	        return $this->get($method);
        }
        $param = end($value);
	    if (!is_string($param)) {
	        $param = array_pop($value);
	        return $this->get($method.'.'.
                implode('.', $value),
                $param);
        }
        return $this->get($method.'.'.implode('.', $value));

    }

	/**
	 * @param string $method
	 * @param array $value
	 * @return mixed
	 */
	public static function __callStatic($method, $value) {
	    if (false === static::getInstance()) {
	        // 初始化未完成时
	        return null;
        }
        return call_user_func_array([
            static::getInstance(), $method], $value);
	}

    /**
     * 判断是否是调试模式
     * @return bool
     */
	public static function isDebug() {
	    return defined('DEBUG') && DEBUG;
    }
}