<?php
namespace App\Head;
/*
 * 自动加载功能
 *
 * @author Jason
 * @time 2015-12-1
 */

use App\Body\Object\Obj;

class Autoload extends Obj {
	
	protected $registered = false;

	protected static $instance;
	
	private function __construct() {
		
	}
	
	public static function getInstance() {
		if (is_null(static::$instance)) {
			return static::$instance = new static($aliases);
		}
		return static::$instance;
	}
	
	public function register() {
		if (!$this->registered) {
			spl_autoload_register(array($this, '_load'), true, true);
			$this->registered = true;
		}
	}
	
	protected function _load($alias) {
		if ($this->has($alias)) {
			return class_alias($this->get($alias), $alias);
		}
	}
	
	public function setError() {
		set_error_handler(array($this, '_error'));          //自定义错误输出
	}
	
	protected function _error($errno, $errstr, $errfile, $errline) {
		$str = '错误级别：'.$errno.'错误的信息：'.$errstr.'<br>发生在 '.$errfile.' 第 '.$errline.' 行！当前网址：'.Url::get();
		self::writeLog($str);
		if (!defined('DEBUG') || !DEBUG) {
			$str = '出错了！';
		}
		Response::getInstance()->show('404', array(
				'error' => $str
		));
	}
	
	public function shutDown() {
		register_shutdown_function(array('app', 'out'));   //程序结束时输出
	}
	
	protected function _shutDown() {
		$error = error_get_last();
		if (empty($error)) {
			return;
		}
		$str = '错误类型：'.$error['type'].'错误的信息：'.$error['message'].'<br>发生在 '.$error['file'].' 第 '.$error['line'].' 行！当前网址：'.Url::get();
		self::writeLog($str);
		if (!defined('DEBUG') || !DEBUG) {
			$str = '出错了！';
		}
		Response::getInstance()->show('404', array(
				'error' => $str
		));
	}
	
	private function __clone() {
		
	}
}