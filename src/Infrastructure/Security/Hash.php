<?php
namespace Zodream\Infrastructure\Security;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/14
 * Time: 17:23
 */
class Hash {

    protected static $rounds = 10;

    /**
     * 生成hash值
     * @param string $value
     * @param array $options
     * @return bool|string
     */
    public static function make($value, $options = array()) {
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => static::cost($options)]);
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

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string  $hashedValue
     * @param  array   $options
     * @return bool
     */
    public static function needsRehash($hashedValue, array $options = []) {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => static::cost($options),
        ]);
    }

    /**
     * Set the default password work factor.
     *
     * @param  int  $rounds
     * @return $this
     */
    public static function setRounds($rounds) {
        static::$rounds = (int) $rounds;
    }

    /**
     * Extract the cost value from the options array.
     *
     * @param  array  $options
     * @return int
     */
    protected static function cost(array $options = []) {
        return isset($options['rounds']) ? $options['rounds'] : static::$rounds;
    }
}