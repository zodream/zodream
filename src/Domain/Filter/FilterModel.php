<?php
namespace Zodream\Domain\Filter;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/18
 * Time: 19:35
 */
use Zodream\Domain\Model;
use Zodream\Infrastructure\Traits\SingletonPattern;

class FilterModel extends Model {
    use SingletonPattern;

    /**
     * 获取总数
     * @param string $table
     * @param string $column
     * @param string|int $value
     */
    public static function getCount($table, $column, $value) {
        static::getInstance()->setTable($table)->count(array(
            "`{$column}` = ?"
        ), array($value));
    }
}