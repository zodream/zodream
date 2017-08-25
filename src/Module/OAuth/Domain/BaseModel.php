<?php
namespace Zodream\Module\OAuth\Domain;

use Zodream\Domain\Model\Model;
use Zodream\Infrastructure\Database\Schema\Table;

/**
 * Class OauthClientModel
 * @package Zodream\Module\OAuth\Domain
 */
abstract class BaseModel extends Model {

    public static function getTable() {
        return new Table(static::tableName());
    }

    abstract public static function createTable();

    public static function dropTable() {
        return static::getTable()->drop();
    }
}