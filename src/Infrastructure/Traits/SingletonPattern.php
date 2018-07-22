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
            static::$instance = false; // 初始化未完成
			static::$instance = new static($args);
		}
		return static::$instance;
	}
}