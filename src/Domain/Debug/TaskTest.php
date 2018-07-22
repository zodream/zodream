<?php
namespace Zodream\Domain\Debug;

class TaskTest {
	public static function debug() {
		debug_print_backtrace();
		//debug_backtrace()
	}
}