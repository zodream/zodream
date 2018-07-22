<?php
namespace Zodream\Infrastructure\Interfaces;

interface HtmlObject{
	/**
	 * 执行
	 * @param mixed $args
	 */
	static function execute($args);
}