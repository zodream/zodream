<?php
namespace Zodream\Domain\Filter;

use Zodream\Infrastructure\Interfaces\FilterObject;
use Zodream\Infrastructure\ObjectExpand\StringExpand;

defined('PHP_INT_MIN') || define('PHP_INT_MIN', 0);
defined('PHP_INT_MAX') || define('PHP_INT_MAX', 99999);

class DataFilter {
    protected static $filterMap = array(
        'confirm',
        'email',
        'float',
        'enum',
        'int',
        'number',
        'ip',
        'phone',
        'raw',
        'required',
        'same',
        'string',
        'time',
        'unique',
        'url'
    );

    protected static $error = array();

    public static function clearError() {
        static::$error = [];
    }
    /**
     * GET ERRORS WHO VALIDATE FAIL
     * @param string $key
     * @return array
     */
    public static function getError($key = null) {
        if (empty($key)) {
            return self::$error;
        }
        if (!array_key_exists($key, self::$error)) {
            return array();
        }
        return self::$error[$key];
    }

    public static function getFirstError($key) {
        if (!array_key_exists($key, self::$error)) {
            return null;
        }
        return current(self::$error[$key]);
    }
    private static function setError($key, $error) {
        if (!array_key_exists($key, self::$error)) {
            self::$error[$key] = array();
        }
        self::$error[$key][] = $error;
    }
    /**
     *
     *
     *
     *
     * @param string|array $args
     * @param string|array $option
     * example:
     * 1. 123, int
     * 2. [
     *      123,
     *      44
     * ], [
     *      int,
     *      string
     * ]
     * 3. [
     *      a => 123,
     *      b => fgff
     * ], [
     *      a => int|string,
     *      b => string
     * ]
     * @return array
     */
    public static function filter($args, $option) {
        $args = (array)$args;
        static::clearError();
        foreach ((array)$option as $key => $rule) {
            if (is_integer($key) && is_array($rule)) {
                $key = array_shift($rule);
            }
            $args = static::filterOne($args, $key, static::getFilters($rule));
        }
        return $args;
    }
    /**
     * 验证
     * @param array $args
     * @param array $option
     * @return bool
     */
    public static function validate($args, $option) {
        $args = (array)$args;
        static::clearError();
        $result = true;
        foreach ((array)$option as $key => $rule) {
            if (is_integer($key) && is_array($rule)) {
                $key = array_shift($rule);
            }
            $result = $result && static::validateOne($args, $key, static::getFilters($rule));
        }
        return true;
    }

    /**
     * @param array $args
     * @param array|string $keys
     * @param FilterObject[] $filters
     * @return bool
     */
    public static function filterOne($args, $keys, array $filters) {
        foreach ((array)$keys as $key) {
            foreach ($filters as $val) {
                if (array_key_exists($key, $args)) {
                    $args[$key] = $val->filter($args[$key]);
                }
            }
        }
        return $args;
    }

    /**
     * @param array $args
     * @param array|string $keys
     * @param FilterObject[] $filters
     * @return bool
     */
    public static function validateOne($args, $keys, array $filters) {
        $result = true;
        foreach ((array)$keys as $key) {
            foreach ($filters as $val) {
                if (!$val->validate(isset($args[$key]) ? $args[$key] : null, $args)) {
                    static::setError($key, $val->getError());
                    $result = false;
                }
            }
        }
        return $result;
    }


    protected static function getFiltersFromOne($option) {
        if (is_string($option)) {
            $option = explode('|', $option);
        }
        $filters = array();
        foreach ((array)$option as $value) {
            if (empty($value)) {
                continue;
            }
            $filter = self::createFilter($value);
            $filters[] = $filter;
        }
        return $filters;
    }

    /**
     * @param string $arg
     * @return FilterObject
     * @throws \ErrorException
     */
    public static function createFilter($arg) {
        list($filter, $option) = StringExpand::explode($arg, ':', 2);
        $filter = strtolower($filter);
        if (in_array($filter, static::$filterMap)) {
            $class = 'Zodream\\Domain\\Filter\\Filters\\'.ucfirst($filter).'Filter';
            return new $class($option);
        }
        throw new \ErrorException(sprintf(' %s is error filter', $filter));
    }

    /**
     * @param string $arg
     * @return FilterObject|string
     */
    protected static function getFilter($arg) {
        $message = null;
        $arg = (array)$arg;
        if (array_key_exists('message', $arg)) {
            $message = $arg['message'];
        }
        $filter = static::createFilter($arg[0]);
        if (empty($filter)) {
            return $arg[0];
        }
        $filter->setError($message);
        return $filter;
    }

    /**
     * @param array|string $arg
     * @return array
     */
    public static function getFilters($arg) {
        if (is_array($arg)) {
            return [static::getFilter($arg)];
        }
        return self::getFiltersFromOne($arg);
    }
}