<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Concerns;
/**
 * 单例模式
 * @author Jason
 *
 */

trait SingletonPattern {
	/**
	 * @var static
	 */
	protected static mixed $instance = null;

	/**
	 * 单例
	 * @param array $args
	 * @return static
	 */
	public static function getInstance(array $args = array()): mixed {
		if (is_null(static::$instance)) {
            static::$instance = false; // 初始化未完成
			static::$instance = new static($args);
		}
		return static::$instance;
	}
}