<?php
namespace Zodream\Infrastructure\Database;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/14
 * Time: 9:07
 */
use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Infrastructure\Database\Engine\BaseEngine;
use Zodream\Infrastructure\Database\Engine\Pdo;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Service\Factory;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Infrastructure\Traits\SingletonPattern;

class Command extends ConfigObject {

    use SingletonPattern;

    protected $table;

    protected $prefix;

    protected $allowCache = true;

    protected $cacheLife = 3600;

    protected $configKey = 'db';

    /**
     * @var BaseEngine[]
     */
    protected $engines = [];

    protected $currentName = '__default__';

    public function __construct() {
        Factory::timer()->record('dbInit');
        $this->loadConfigs();
        $this->getCurrentName();
        if (isset($this->table)) {
            $this->setTable($this->table);
        }
    }

    /**
     * ADD 2D ARRAY
     * @param array $args
     * @return $this
     */
    public function setConfigs(array $args) {
        if (!is_array(current($args))) {
            $args = [
                $this->currentName => $args
            ];
        }
        foreach ($args as $key => $item) {
            if (array_key_exists($key, $this->configs)) {
                $this->configs[$key] = array_merge($this->configs[$key], $item);
            } else {
                $this->configs[$key] = $item;
            }
        }
        return $this;
    }

    /**
     * @param string|array|BaseEngine $name
     * @param array|BaseEngine|null $configs
     * @return BaseEngine
     */
    public function addEngine($name, $configs = null) {
        if (!is_string($name) && !is_numeric($name)) {
            $configs = $name;
            $name = $this->currentName;
        }
        if (array_key_exists($name, $this->engines)) {
            $this->engines[$name]->close();
        }
        if ($configs instanceof BaseEngine) {
            return $this->engines[$name] = $configs;
        }
        if (!array_key_exists('driver', $configs) || !class_exists($configs['driver'])) {
            $configs['driver'] = Pdo::class;
        }
        $class = $configs['driver'];
        $this->engines[$name] = new $class($configs);
        Factory::timer()->record('dbEnd');
        return $this->engines[$name];
    }

    /**
     * GET DATABASE ENGINE
     * @param string $name
     * @return BaseEngine
     */
    public function getEngine($name = null) {
        Factory::timer()->record('dbGet');
        if (is_null($name)) {
            $name = $this->getCurrentName();
        }
        if (array_key_exists($name, $this->engines)) {
            return $this->engines[$name];
        }
        if ($this->hasConfig($name)) {
            return $this->addEngine($name, $this->getConfig($name));
        }
        throw new \InvalidArgumentException($name. ' DOES NOT HAVE CONFIG!');
    }

    public function getCurrentName() {
        if (!array_key_exists($this->currentName, $this->configs)) {
            $this->currentName = key($this->configs);
        }
        return $this->currentName;
    }

    public function getConfig($name = null) {
        if (is_null($name)) {
            $name = $this->getCurrentName();
        }
        return array_key_exists($name, $this->configs) ? $this->configs[$name] : [];
    }

    public function hasConfig($name = null) {
        if (is_null($name)) {
            return empty($this->configs);
        }
        return array_key_exists($name, $this->configs);
    }

    /**
     * ADD TABLE PREFIX
     * @param string $table
     * @return string
     */
    public function addPrefix($table) {
        if (strpos($table, '`') !== false) {
            return $table;
        }
        if (strpos($table, '!') === 0) {
            return '`'.substr($table, 1).'`';
        }
        $prefix = $this->getEngine()->getConfig('prefix');
        if (empty($prefix)) {
            return '`'.$table.'`';
        }
        return '`'.$prefix.StringExpand::firstReplace($table, $prefix).'`';
    }

    /**
     * 设置表
     * @param string $table
     * @return $this
     */
    public function setTable($table) {
        $this->table = $this->addPrefix($table);
        return $this;
    }

    /**
     * GET TABLE
     * @return string
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * 更改数据库
     * @param string $database
     * @return $this
     */
    public function changedDatabase($database) {
        $this->getEngine()->execute('use '.$database);
        return $this;
    }

    /**
     * 拷贝（未实现）
     */
    public function copy() {
        return $this->select(null, '* INTO table in db');
    }

    /**
     * @param string $sql
     * @return array null
     */
    public function getCache($sql) {
        if (!$this->allowCache) {
            return null;
        }
        $cache = Factory::cache()->get('data/'.md5($this->currentName.$sql));
        if (empty($cache)) {
            return null;
        }
        return unserialize($cache);
    }

    public function setCache($sql, $data) {
        if (!$this->allowCache || (defined('DEBUG') && DEBUG)) {
            return;
        }
        Factory::cache()->set('data/'.md5($this->currentName.$sql), serialize($data), 3600);
    }

    /**
     * 查询
     * @param string $sql
     * @param string $field
     * @param array $parameters
     * @return mixed
     */
    public function select($sql, $field = '*', $parameters = array()) {
        return $this->getArray("SELECT {$field} FROM {$this->table} {$sql}", $parameters);
    }

    /**
     * 执行事务
     * @param array $args sql语句的数组
     * @return bool
     */
    public function transaction($args) {
        return $this->getEngine()->transaction($args);
    }

    /**
     * 开始执行事务
     * @return Database
     */
    public function beginTransaction() {
        $this->getEngine()->begin();
        return $this->getEngine();
    }

    /**
     * 插入
     * @param string $columns
     * @param string $tags
     * @param array $parameters
     * @return int
     */
    public function insert($columns, $tags, $parameters = array()) {
        if (!empty($columns) && strpos($columns, '(') === false) {
            $columns = '('.$columns.')';
        }
        $tags = trim($tags);
        if (strpos($tags, '(') !== 0) {
            $tags = '('.$tags.')';
        }
        return $this->getEngine()->insert("INSERT INTO {$this->table} {$columns} VALUES {$tags}", $parameters);
    }

    /**
     * 如果行作为新记录被insert，则受影响行的值为1；如果原有的记录被更新，则受影响行的值为2。 如果有多条存在则只更新最后一条
     * @param string $columns
     * @param string $tags
     * @param string $update
     * @param array $parameters
     * @return int
     */
    public function insertOrUpdate($columns, $tags, $update, $parameters = array()) {
        if (!empty($columns) && strpos($columns, '(') === false) {
            $columns = '('.$columns.')';
        }
        $tags = trim($tags);
        if (strpos($tags, '(') !== 0) {
            $tags = '('.$tags.')';
        }
        return $this->getEngine()->update("INSERT INTO {$this->table} {$columns} VALUES {$tags} ON DUPLICATE KEY UPDATE {$update}", $parameters);
    }

    /**
     *在执行REPLACE后，系统返回了所影响的行数，如果返回1，说明在表中并没有重复的记录，如果返回2，说明有一条重复记录，系统自动先调用了 DELETE删除这条记录，然后再记录用INSERT来insert这条记录。如果返回的值大于2，那说明有多个唯一索引，有多条记录被删除和insert。
     * @param string $columns
     * @param string $tags
     * @param array $parameters
     * @return int
     */
    public function insertOrReplace($columns, $tags, $parameters = array()) {
        if (!empty($columns) && strpos($columns, '(') === false) {
            $columns = '('.$columns.')';
        }
        $tags = trim($tags);
        if (strpos($tags, '(') !== 0) {
            $tags = '('.$tags.')';
        }
        return $this->getEngine()->update("REPLACE INTO {$this->table} {$columns} VALUES {$tags}", $parameters);
    }

    /**
     * 更新
     * @param string $columns
     * @param string $where
     * @param array $parameters
     * @return int
     */
    public function update($columns, $where, $parameters = array()) {
        if (strncasecmp(ltrim($where), 'where', 5) !== 0) {
            $where = 'WHERE '.$where;
        }
        return $this->getEngine()->update("UPDATE {$this->table} SET {$columns} {$where}", $parameters);
    }

    /**
     * 删除
     * @param string $where
     * @param array $parameters
     * @return int
     */
    public function delete($where = null, $parameters = array()) {
        $where = trim($where);
        if (!empty($where) && strncasecmp($where, 'where', 5) !== 0) {
            $where = 'WHERE '.$where;
        }
        return $this->getEngine()->delete("DELETE FROM {$this->table} {$where}", $parameters);
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @return mixed
     */
    public function execute($sql, $parameters = array()) {
        EventManger::getInstance()->run('executeSql', $sql);
        if (preg_match('/^(insert|delete|update|replace|drop|create)\s+/i', $sql)) {
            return $this->getEngine()->execute($sql, $parameters);
        }
        $args = empty($parameters) ? serialize($parameters) : null;
        if ($cache = $this->getCache($sql.$args)) {
            return $cache;
        }
        $result = $this->getEngine()->execute($sql, $parameters);
        $this->setCache($sql.$args, $result);
        return $result;
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @return array
     */
    public function getArray($sql, $parameters = array()) {
        $args = empty($parameters) ? serialize($parameters) : null;
        if ($cache = $this->getCache($sql.$args)) {
            return $cache;
        }
        $result = $this->getEngine()->getArray($sql, $parameters);
        $this->setCache($sql.$args, $result);
        return $result;
    }
    /**
     * @param string $sql
     * @param array $parameters
     * @return object
     */
    public function getObject($sql, $parameters = array()) {
        $args = empty($parameters) ? serialize($parameters) : null;
        if ($cache = $this->getCache($sql.$args)) {
            return $cache;
        }
        $result = $this->getEngine()->getObject($sql, $parameters);
        $this->setCache($sql.$args, $result);
        return $result;
    }

    /**
     * 获取错误信息
     */
    public function getError() {
        return $this->getEngine()->getError();
    }
}