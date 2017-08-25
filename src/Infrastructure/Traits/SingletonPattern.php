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
	 * @param array $args
	 * @return static
	 */
	public static function getInstance($args = array()) {
		if (is_null(static::$instance)) {
			static::$instance = new static($args);
		}
		return static::$instance;
	}
	
	public static function __callStatic($action, $arguments = array()) {
		return call_user_func_array(array(static::getInstance(), $action), $arguments);
	}
}