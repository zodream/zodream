<?php 
namespace Zodream\Infrastructure\ObjectExpand;

/**
* time 的扩展
* 
* @author Jason
*/
use Zodream\Service\Config;
use Zodream\Service\Factory;

class TimeExpand {

	/**
	 * 将时间转换成字符串格式
	 * @param null $time
	 * @param string $format
	 * @return string
	 */
	public static function format($time = null, $format = null) {
	    if (empty($format)) {
	        $format = Config::getValue('formatter.datetime');
        }
		if (!is_numeric($time)) {
			$format = $time;
			$time = time();
		}
		if (is_null($time)) {
			$time = time();
		}
		return date($format, $time);
	}

	const TODAY = 0;
	const YESTERDAY = 1;
	const WEEK = 2;
	const LAST_WEEK = 3;
	const MONTH = 4;
	const LAST_MONTH = 5;
	const YEAR = 6;
	const LAST_YEAR = 7;

	/**
	 * 获取开始时间和结束时间
	 * @param int $kind 类型
	 * @return array 包含开始时间可结束时间
	 */
	public static function getBeginAndEndTime($kind = 0) {
		switch ($kind) {
			case self::TODAY:
				return array(
					mktime(0, 0, 0, date('m'), date('d'), date('Y')),
					mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1
				);
			case self::YESTERDAY:
				return array(
					mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')),
					mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1
				);
			case self::WEEK:
				// 周一作为第一天
				$week = date('w');
				if ($week == 0) {
					$week = 7;
				}
				return [
					mktime(0, 0, 0, date('m'), date('d') - $week + 1, date('Y')),
					mktime(0, 0, 0, date('m'), date('d') + 7 - $week, date('Y')),
				];
			case self::LAST_WEEK:
				$week = date('w');
				if ($week == 0) {
					$week = 7;
				}
				return array(
					mktime(0, 0, 0, date('m'), date('d') - $week - 6, date('Y')),
					mktime(23, 59, 59, date('m'), date('d') - $week, date('Y'))
				);
			case self::MONTH:
				return array(
					mktime(0, 0, 0, date('m'), 1, date('Y')),
					mktime(23, 59, 59, date('m'), date('t'), date('Y'))
				);
			case self::LAST_MONTH:
				return array(
					mktime(0, 0, 0, date('m') - 1, 1, date('Y')),
					mktime(23, 59, 59, date('m'), 0, date('Y'))
				);
			case self::YEAR:
				return array(
					mktime(0, 0, 0, 1, 1, date('Y')),
					mktime(23, 59, 59, 12, 31, date('Y'))
				);
			case self::LAST_YEAR:
				return array(
					mktime(0, 0, 0, 1, 1, date('Y') - 1),
					mktime(23, 59, 59, 12, 31, date('Y') - 1)
				);
            default:
                return array(
                    0,
                    time()
                );
		}
	}

	/**
	 * 获取时间是多久以前
	 * @param $time
	 * @return int|string
	 */
	public static function isTimeAgo($time){
		if (empty($time)) {
			return null;
		}
		$differ = time() - $time;
		if ($differ < 1) {
			$differ = 1;
		}
		$tokens = array (
				31536000 => '{time} year ago',
				2592000  => '{time} month ago',
				604800   => '{time} week ago',
				86400    => '{time} day ago',
				3600     => '{time} hour ago',
				60       => '{time} minute ago',
				1        => '{time} second ago'
		);
	
		foreach ($tokens as $unit => $text) {
			if ($differ < $unit) continue;
			$numberOfUnits = floor($differ / $unit);
			return Factory::i18n()->translate($text, ['time' => $numberOfUnits]);
		}
		return self::format($time);
	}

    /**
     * GET NOW WITH microtime
     * @return float
     */
	public static function millisecond() {
        list($tmp1, $tmp2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
    }
}