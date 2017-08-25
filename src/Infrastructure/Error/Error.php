<?php 
namespace Zodream\Infrastructure\Error;
/**
* 错误信息类
* 
* @author Jason
*/
use Zodream\Domain\Response\ResponseResult;
use Zodream\Service\Factory;
use Zodream\Service\Routing\Url;
use Zodream\Infrastructure\Log;
use Zodream\Infrastructure\ObjectExpand\TimeExpand;

class Error{

	/**
	 * 注册的错误提示
	 * @param int $errorNo
	 * @param string $errorStr
	 * @param string $errorFile
	 * @param string $errorLine
	 */
	public static function outByError($errorNo, $errorStr, $errorFile, $errorLine) {
		if (function_exists('error_clear_last')) {
			error_clear_last();
		}
		self::out("{$errorStr}； 错误级别：{$errorNo} ", $errorFile, $errorLine);
	}

	/**
	 * 注册的程序结束事件
	 */
	public static function outByShutDown() {
		$error = error_get_last();
		if (empty($error)) {
			return;
		}
		self::out($error['message'], $error['file'], $error['line']);
	}

	/**
	 * 输出错误信息
	 * @param string $error
	 * @param string $file
	 * @param string $line
	 * @throws \Exception
	 */
	public static function out($error, $file = null, $line = null) {
		$errorInfo = "ERROR: {$error} , in {$file} on line {$line}, URL:".Url::to();
		if (!defined('APP_MODULE')) {   //作为插件使用时
			Factory::log()->info(TimeExpand::format().':'.$errorInfo);
			exit($errorInfo);
		}
		if (DEBUG) {
			throw (new Exception($error, '200'))->setFile($file)->setLine($line);
		}
	}
}