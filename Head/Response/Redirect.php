<?php
namespace App\Head\Response;
/**
 * 跳转
 * @author Jason
 * @final 2015-12-2
 */
use App\Head\Url;

class Redirect {
	/**
	 * 跳转页面
	 *
	 * @access globe
	 *
	 * @param string $url 要跳转的网址
	 * @param int $time 停顿的时间
	 * @param string $msg 显示的消息.
	 * @param string $code 显示的代码标志.
	 */
	public static function to($urls, $time = 0, $msg = '', $code = '') {
		$url = '';
		if (is_array($urls)) {
			foreach ($urls as $value) {
				$url .= Url::to($value);
			}
		} else {
			$url = HUrl::to($urls);
		}
	
		if (empty($msg)) {
			$msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
		}
		if (!headers_sent()) {
			if (0 === $time) {
				header('Location: ' . $url);
			} else {
				header("refresh:{$time};url={$url}");
			}
		} else {
			$str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
			self::$response->set('meta', $str);
		}
		(array(
				'title' => "出错了！",
				'code'  => $code,
				'error' => $msg
		));
	}
}