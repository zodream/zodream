<?php
namespace Zodream\Infrastructure\Database\Schema;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/25
 * Time: 10:53
 */
class Column {

    const KIND = 0;

    const NULL = 1;

    const AI = 2;

    const _DEFAULT_ = 3;

    const COMMENT = 4;

    protected $data = [];

    protected $field;

    protected $oldField;

    protected $afterColumn;

    /**
     * @var Table
     */
    protected $table;

    public function __construct(Table $table, $field) {
        $this->setTable($table)->setField($field)->int();
    }

    public function setTable(Table $table) {
        $this->table = $table;
        return $this;
    }

    public function setField($field) {
        if (!is_array($field)) {
            $this->field = $field;
        } elseif (count($field) > 1) {
            list($this->oldField, $this->field) = $field;
        } else {
            $this->oldField = key($field);
            $this->field = current($field);
        }
        return $this;
    }

    public function setOldField($field) {
        $this->oldField = $field;
        return $this;
    }

    public function setAfter($column) {
        $this->afterColumn = $column;
        return $this;
    }

    public function getAfter() {
        return $this->afterColumn;
    }

    public function getOldField() {
        return $this->oldField;
    }

    public function getField() {
        return $this->field;
    }

    protected function addData($index, $arg) {
        $this->data[$index] = $arg;
        return $this;
    }

    public function null() {
        return $this->addData(self::NULL, 'NULL');
    }

    public function notNull() {
        return $this->addData(self::NULL, 'NOT NULL');
    }

    public function smallInt($arg) {
        return $this->addData(self::KIND, 'SMALLINT('.intval($arg).')');
    }

    public function bigInt($arg) {
        return $this->addData(self::KIND, 'BIGINT('.intval($arg).')');
    }

    public function float($size, $d) {
        return $this->addData(self::KIND, 'FLOAT('.intval($size).','.intval($d).')');
    }

    public function double($size, $d) {
        return $this->addData(self::KIND, 'DOUBLE('.intval($size).','.intval($d).')');
    }

    public function decimal($size, $d) {
        return $this->addData(self::KIND, 'DECIMAL('.intval($size).','.intval($d).')');
    }

    public function int($arg = null) {
        $sql = 'INT';
        if (!empty($arg)) {
            $sql .= '('.intval($arg).')';
        }
        return $this->addData(self::KIND, $sql);
    }

    public function tinyint($arg = 1) {
        return $this->addData(self::KIND, 'TINYINT('.intval($arg).')');
    }

    public function bool() {
        return $this->tinyint(1);
    }

    public function char($arg) {
        return $this->addData(self::KIND, 'CHAR('.intval($arg).')');
    }

    public function varchar($arg = 255) {
        return $this->addData(self::KIND, 'VARCHAR('.min(255, intval($arg)).')');
    }

    /***
     * 65535
     * @return Column
     */
    public function text() {
        return $this->addData(self::KIND, 'TEXT');
    }

    /**
     * 16,777,215
     * @return Column
     */
    public function mediumtext() {
        return $this->addData(self::KIND, 'MEDIUMTEXT');
    }

    /**
     * 4,294,967,295
     * @return Column
     */
    public function longtext() {
        return $this->addData(self::KIND, 'LONGTEXT');
    }

    public function blob() {
        return $this->addData(self::KIND, 'BLOB');
    }

    public function mediumblob() {
        return $this->addData(self::KIND, 'MEDIUMBLOB');
    }

    public function longblob() {
        return $this->addData(self::KIND, 'LONGBLOB');
    }

    public function dateTime() {
        return $this-$this->addData(self::KIND, 'DATETIME');
    }

    public function date() {
        return $this-$this->addData(self::KIND, 'DATE');
    }

    public function time() {
        return $this-$this->addData(self::KIND, 'TIME');
    }

    /**
     * 时间戳
     * @param integer $arg
     * @return Column
     */
    public function timestamp($arg = null) {
        $sql = 'TIMESTAMP';
        if (!empty($arg)) {
            $sql .= '('.intval($arg).')';
        }
        return $this->addData(self::KIND, $sql);
    }

    public function year() {
        return $this-$this->addData(self::KIND, 'YEAR');
    }

    public function enum(array $args) {
        return $this->addData(self::_DEFAULT_, 'ENUM(\''.implode("', '", $args)."')");
    }


    /**
     * @param $arg
     * @return Column
     */
    public function defaultVal($arg) {
        if (is_string($arg)) {
            $arg = "'{$arg}'";
        }
        return $this->addData(self::_DEFAULT_, 'DEFAULT '.$arg);
    }

    public function comment($arg) {
        return $this->addData(self::COMMENT, "COMMENT '{$arg}'");
    }

    public function ai($begin = null) {
        $this->table->setAI($begin);
        return $this->addData(self::AI, 'AUTO_INCREMENT');
    }

    public function pk() {
        if (!isset($this->data[self::KIND])) {
            $this->int();
        }
        $this->notNull()->table->pk($this->field);
        return $this;
    }

    public function fk($name, $table, $field) {
        $this->table->fk($name, $this->field, $table, $field);
        return $this;
    }

    public function unique($name = null, $order = null) {
        if (empty($name)) {
            $name = $this->field;
        }
        $this->table->unique($name, $this->field, $order);
        return $this;
    }

    public function index($name = null, $order = null) {
        if (empty($name)) {
            $name = $this->field;
        }
        $this->table->index($name, $this->field, $order);
        return $this;
    }

    public function check($name, $arg = null) {
        $this->table->checks($name, $arg);
        return $this;
    }

    public function getSql() {
        $sql = implode(' ', $this->data);
        if (!empty($this->field)) {
            $sql = "`{$this->field}` ".$sql;
        }
        return $sql;
    }

    public function __toString() {
        return $this->getSql();
    }

    public function getAlterSql() {
        $sql = empty($this->oldField) ? 'ADD COLUMN ' : "CHANGE COLUMN `{$this->oldField}` ";
        $sql .= $this->getSql();
        if (!empty($this->afterColumn)) {
            $sql .= " after `{$this->afterColumn}`";
        }
        return $sql;
    }

    public function getDropSql() {
        return "DROP COLUMN `{$this->field}`";
    }
}