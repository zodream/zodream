<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Response;

interface HtmlObject{
	/**
	 * 执行
	 * @param mixed $args
	 */
	public function execute($args);
}