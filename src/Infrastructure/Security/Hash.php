<?php
namespace Zodream\Infrastructure\Security;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/14
 * Time: 17:23
 */
class Hash {
    public static $round = 10;

    /**
     * 生成hash值
     * @param string $value
     * @param array $option
     * @return bool|string
     */
    public static function make($value, $option = array()) {
        $cost = isset($option['rounds']) ? $option['rounds'] : self::$round;
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * 验证
     * @param string $value 与hash值对比的值
     * @param string $hash 已经hash过的值
     * @return bool
     */
    public static function verify($value, $hash) {
        return password_verify($value, $hash);
    }
}