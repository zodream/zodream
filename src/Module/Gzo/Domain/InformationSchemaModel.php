<?php
namespace Zodream\Module\Gzo\Domain;

use Zodream\Infrastructure\Database\Query\Query;

class InformationSchemaModel extends Query {
    protected function addPrefix($table) {
        return sprintf('`information_schema`.`%s`', $table);
    }

    /**
     * 查询关于数据库的信息
     * @return static
     */
    public static function schema() {
        return (new static())->from('SCHEMATA');
    }

    /**
     * 查询关于数据库中的表的信息
     * @return static
     */
    public static function table() {
        return (new static())->from('TABLES');
    }

    /**
     * 查询表中的列信息
     * @return static
     */
    public static function column() {
        return (new static())->from('COLUMNS');
    }
}