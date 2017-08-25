<?php
namespace Zodream\Domain\Model;
use Zodream\Infrastructure\Database\Schema\Table;
use Zodream\Service\Factory;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2017/3/31
 * Time: 19:41
 */
class AiModel extends Model {
    protected static $tableName;

    public static function tableName() {
        return static::$tableName;
    }

    public static function table($table) {
        return new static($table);
    }

    public function __construct($table = null) {
        if (empty($table)) {
            return;
        }
       static::$tableName = $table;
    }

    public function getTableField() {
        $key = 'table_'.static::tableName();
        $data = Factory::cache()->get($key);
        if (!empty($data)) {
            return unserialize($data);
        }
        $data = (new Table(static::tableName()))->getAllColumn(true);
        Factory::cache()->set($key, $data);
        return $data;
    }

}