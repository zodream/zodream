<?php
namespace Zodream\Infrastructure\Database\Schema;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/11
 * Time: 14:50
 */
use Zodream\Infrastructure\Database\Query\Query;
use Zodream\Infrastructure\Database\Query\Record;

class Table extends BaseSchema {

    const MyISAM = 'MyISAM';
    const HEAP = 'HEAP';
    const MEMORY = 'MEMORY';
    const MERGE = 'MERGE';
    const MRG_MYISAM = 'MRG_MYISAM';
    const InnoDB = 'InnoDB';
    const INNOBASE = 'INNOBASE';
    /**
     * @var Column[]
     */
    protected $_data = array();

    protected $tableName;

    protected $charset = 'UTF8';

    protected $engine = 'MyIAM';

    protected $foreignKey = [];

    protected $checks = [];

    protected $aiBegin = 1;

    protected $index = [];

    protected $primaryKey;

    protected $comment = null;

    /**
     * @var Schema
     */
    protected $schema;

    public function __construct(
        $table,
        $data = [],
        $engine = self::MyISAM,
        $charset = 'UTF8'
    ) {
        $this->setTableName($table);
        $this->_data = $data;
        $this->engine = $engine;
        $this->charset = $charset;
    }

    public function setSchema(Schema $schema) {
        $this->schema = $schema;
        return $this;
    }

    /**
     * 原始
     * @return mixed
     */
    public function getTableName() {
        return $this->tableName;
    }

    /**
     * 正式表名，添加前缀
     * @return string
     */
    public function getTable() {
        return $this->addPrefix($this->tableName);
    }

    public function setTableName($table) {
        $this->tableName = $table;
        return $this;
    }

    /**
     * TABLE CHARSET, DEFAULT UTF8
     * @param string $arg
     * @return $this
     */
    public function setCharset($arg) {
        $this->charset = $arg;
        return $this;
    }

    /**
     * TABLE COMMENT
     * @param string $arg
     * @return $this
     */
    public function setComment($arg) {
        $this->comment = $arg;
        return $this;
    }

    /**
     * SET PRIMARY KEY
     * @param string $field
     * @return $this
     */
    public function pk($field) {
        $this->primaryKey = $field;
        return $this;
    }

    /**
     * SET TABLE ENGINE
     * @param string $arg
     * @return $this
     */
    public function setEngine($arg) {
        $this->engine = $arg;
        return $this;
    }

    /**
     * SET AUTO_INCREMENT BEGIN
     * @param string $arg
     * @return $this
     */
    public function setAI($arg) {
        $this->aiBegin = max($this->aiBegin, intval($arg));
        return $this;
    }

    /**
     * SET FOREIGN KEY
     * @param string $name
     * @param string $field
     * @param string $table
     * @param string $fkField
     * @param string $delete
     * @param string $update
     * @return $this
     */
    public function fk($name, $field, $table, $fkField, $delete = 'NO ACTION', $update = 'NO ACTION') {
        $this->foreignKey[$name] = [$field, $table, $fkField, $delete, $update];
        return $this;
    }

    /**
     * SET INDEX
     * @param string $name
     * @param string $field
     * @param string $order asc or desc
     * @return $this
     */
    public function index($name, $field, $order = null) {
        $this->index[$name] = [$field, $order];
        return $this;
    }

    /**
     * SET UNIQUE
     * @param string $name
     * @param string $field
     * @param string $order
     * @return $this
     */
    public function unique($name, $field, $order = null) {
        $this->index[$name] = [$field, $order, 'UNIQUE'];
        return $this;
    }


    /**
     * SET CHECK
     * @param string $name
     * @param string $arg
     * @return $this
     */
    public function checks($name, $arg = null) {
        if (empty($arg)) {
            $this->checks[] = $name;
        } else {
            $this->checks[$name] = $arg;
        }
        return $this;
    }

    /**
     * GET TABLE NAME
     * @return string
     */
    public function getName() {
        return $this->tableName;
    }

    /**
     * DROP TABLE
     * @return mixed
     */
    public function drop() {
        return $this->command()->execute($this->getDropSql());
    }

    /**
     * CREATE TABLE
     * @return mixed
     */
    public function create() {
        return $this->command()->execute($this->getSql());
    }

    /**
     * DROP AND CREATE TABLE
     * @return mixed
     */
    public function replace() {
        $this->drop();
        return $this->create();
    }

    /**
     * TRUNCATE TABLE
     * @return mixed
     */
    public function truncate() {
        return $this->command()->execute($this->getTruncateSql());
    }

    /**
     * ALERT TABLE
     * @return mixed
     */
    public function alert() {
        return $this->command()->execute($this->getAlertSql());
    }

    /**
     * DROP COLUMN
     * @return mixed
     */
    public function dropColumn() {
        return $this->command()->execute($this->getDropColumnSql());
    }

    /**
     * 检查表
     * @return mixed
     */
    public function check() {
        return $this->command()->execute('CHECK TABLE '.$this->getTable().'');
    }

    /**
     * 优化表
     * @return mixed
     */
    public function optimize() {
        return $this->command()->execute('OPTIMIZE TABLE '.$this->getTable().'');
    }

    /**
     * 修复表
     * @return mixed
     */
    public function repair() {
        return $this->command()->execute('REPAIR TABLE '.$this->getTable().'');
    }

    /**
     * 分析表
     * @return mixed
     */
    public function analyze() {
        return $this->command()->execute('ANALYZE TABLE '.$this->getTable().'');
    }

    /**
     * @param bool $isFull 是否包含完整信息
     * @return array
     */
    public function getAllColumn($isFull = false) {
        if ($isFull) {
            return $this->command()->getArray('SHOW FULL COLUMNS FROM '.$this->getTable());
        }
        return $this->command()->getArray('SHOW COLUMNS FROM '.$this->getTable());
    }

    /**
     * 系统生成的创建表的语句
     * @return string
     */
    public function getCreateTableSql() {
        $data = $this->command()->getArray('SHOW CREATE TABLE '.$this->getTable());
        if (empty($data)) {
            return null;
        }
        return $data[0]['Create Table'];
    }


    /**
     * @param string $offset
     * @param null $default
     * @return bool|Column
     */
    public function get($offset = null, $default = null) {
        return parent::get($offset, false);
    }

    /**
     * @param $offset
     * @param $column
     * @return Column
     */
    public function set($offset, $column = null) {
        if (!$column instanceof Column) {
            $column = new Column($this, $offset);
        }
        return $this->_data[$offset] = $column;
    }

    /**
     * GET DROP AND CREATE TABLE SQL
     * @return string
     */
    public function getReplaceSql() {
        return $this->getDropSql().$this->getSql();
    }

    /**
     * GET TRUNCATE TABLE SQL
     * @return string
     */
    public function getTruncateSql() {
        return sprintf('TRUNCATE %s;', $this->getTable());
    }

    /**
     * GET ALERT TABLE SQL
     * @return string
     */
    public function getAlertSql() {
        $sql = [];
        foreach ($this->_data as $item) {
            $sql[] = $item->getAlterSql();
        }
        return sprintf('ALTER TABLE %s %s;',
            $this->getTable(),
            implode(',', $sql));
    }

    //DROP COLUMN
    public function getDropColumnSql() {
        $sql = [];
        foreach ($this->_data as $item) {
            $sql[] = $item->getDropSql();
        }
        return sprintf('ALTER TABLE %s %s;',
            $this->getTable(),
            implode(',', $sql));
    }
    /**
     * GET DROP TABLE SQL
     * @return string
     */
    public function getDropSql() {
        return sprintf('DROP TABLE IF EXISTS %s;', $this->getTable());
    }

    /**
     * GET CREATE TABLE SQL
     * @return string
     */
    public function getSql() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->getTable()} (";
        $column = $this->_data;
        if (!empty($this->primaryKey)) {
            $column[] = "PRIMARY KEY (`{$this->primaryKey}`)";
        }
        foreach ($this->checks as $key => $item) {
            $column[] = (!is_integer($key) ? "CONSTRAINT `{$key}` " : null)." CHECK ({$item})";
        }
        foreach ($this->index as $key => $item) {
            $column[] = (count($item) > 2 ? 'UNIQUE ': null). "INDEX `{$key}` (`{$item[0]}` {$item['1']})";
        }
        foreach ($this->foreignKey as $key => $item) {
            $column[] = "CONSTRAINT `{$key}` FOREIGN KEY (`{$item[0]}`) REFERENCES `{$item[1]}` (`{$item[2]}`) ON DELETE {$item[2]} ON UPDATE {$item[3]}";
        }
        $sql .= implode(',', $column).") ENGINE={$this->engine}";
        if ($this->aiBegin > 1) {
            $sql .= ' AUTO_INCREMENT='.$this->aiBegin;
        }
        return $sql." DEFAULT CHARSET={$this->charset} COMMENT='{$this->comment}';";
    }

    /**
     * @return array
     */
    public function getForeignKeys() {
        return (new Query())
            ->from('information_schema.key_column_usage')
            ->where([
                'CONSTRAINT_SCHEMA' => $this->schema->getSchema(),
                'TABLE_NAME' => $this->getTable()
            ])->all();
    }

    /**
     * @return Record
     */
    public function record() {
        return (new Record())->setTable($this->getTable());
    }
}