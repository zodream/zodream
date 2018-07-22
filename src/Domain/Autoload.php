<?php
namespace Zodream\Domain;
/**
 * 自动加载功能
 *
 * @author Jason
 */
use Zodream\Infrastructure\Error\Error;
use Zodream\Infrastructure\Traits\SingletonPattern;
use Zodream\Service\Factory;

class Autoload {
	
	use SingletonPattern;
	
	protected $registerAlias = false;

	protected $aliases = [];
	
	public function __construct() {
		$this->aliases = (array)Factory::config('alias');
	}
	/**
	 * 注册别名
	 */
	public function registerAlias() {
		if (!$this->registerAlias) {
			spl_autoload_register(array($this, 'load'), true, true);
			$this->registerAlias = true;
		}
		return $this;
	}

	/**
	 * 设置别名
	 * @param string $alias
	 * @return bool
	 */
	protected function load($alias) {
		if (!class_exists($alias) && isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
		}
		return false;
	}

	/**
	 * 自定义错误输出
	 * @return $this
	 */
	public function bindError() {
	    $error = new Error();
	    $error->bootstrap();
		return $this;
	}
	
	private function __clone() {
		
	}
}