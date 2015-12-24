<?php
namespace Zodream\Infrastructure\Traits;
/**
 * 单例模式
 * @author Jason
 *
 */

trait SingletonPattern {
	/**
	 * @var static
	 */
	protected static $instance;
	/**
	 * 单例
	 */
	public static function getInstance() {
		if (is_null(static::$instance)) {
			static::$instance = new static;
		}
		return static::$instance;
	}
	
	public static function __callStatic($action, $arguments = array()) {
		return call_user_func_array(array(self::getInstance(), $action), $arguments);
	}
}