<?php 
namespace Zodream\Infrastructure\Database\Engine;
/**
* mysql 
* 
* @author Jason
*/
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Service\Factory;

class Mysql extends BaseEngine {
    /**
     * 连接数据库
     *
     */
    protected function connect() {
        if (empty($this->configs)) {
            die ('Mysql host is not set');
        }
        if ($this->configs['persistent'] === true) {
            $this->driver = mysql_pconnect(
                $this->configs['host']. ':'. $this->configs['port'],
                $this->configs['user'],
                $this->configs['password']
            ) or die('There was a problem connecting to the database');;
        } else {
            $this->driver = mysql_connect(
                $this->configs['host'] . ':' . $this->configs['port'],
                $this->configs['user'],
                $this->configs['password']
            ) or die('There was a problem connecting to the database');
        }

        mysql_select_db($this->configs['database'], $this->driver)
        or die ("Can't use {$this->configs['database']} : " . mysql_error());
        if (isset($this->configs['encoding'])) {
            mysql_query('SET NAMES '.$this->configs['encoding'], $this->driver);
        }
    }

    public function rowCount() {
        return mysql_affected_rows($this->driver);
    }

    /**
     * 获取Object结果集
     * @param string $sql
     * @param array $parameters
     * @return object
     */
    public function getObject($sql, $parameters = array()) {
        $this->execute($sql);
        $result = array();
        while (!!$objs = mysql_fetch_object($this->result) ) {
            $result[] = $objs;
        }
        return $result;
    }

    /**
     * 获取关联数组
     * @param string $sql
     * @param array $parameters
     * @return array
     */
    public function getArray($sql, $parameters = array()) {
        $this->execute($sql);
        $result = array();
        while (!!$objs = mysql_fetch_assoc($this->result) ) {
            $result[] = $objs;
        }
        return $result;
    }

    /**
     * 返回上一步执行INSERT生成的id
     *
     * @access public
     *
     */
    public function lastInsertId() {
        return mysql_insert_id($this->driver);
    }

    /**
     * 执行SQL语句
     *
     * @access public
     *
     * @param string $sql 多行查询语句
     * @param array $parameters
     * @return null|resource
     */
    public function execute($sql = null, $parameters = array()) {
        if (empty($sql)) {
            return null;
        }
        foreach ($parameters as $key => $item) {
            StringExpand::bindParam($sql, $key + 1, $item, is_numeric($item) ? 'INT' : 'STR');
        }
        $this->result = mysql_query($sql, $this->driver);
        Factory::log()->info(sprintf('MYSQL: %s => %s', $sql,
            $this->getError()));
        return $this->result;
    }

    /**
     * 关闭和清理
     *
     * @access public
     *
     *
     */
    public function close() {
        if (!empty($this->result) && !is_bool($this->result)) {
            mysql_free_result($this->result);
        }
        mysql_close($this->driver);
        parent::close();
    }

    public function getError() {
        return mysql_error($this->driver);
    }

    public function __destruct() {
        $this->close();
    }

    /**
     * 事务开始
     * @return bool
     */
    public function begin() {
        $arg = mysql_query('START TRANSACTION', $this->driver);
        return empty($arg);
    }

    /**
     * 执行事务
     * @param array $args
     * @return bool
     * @throws \Exception
     */
    public function commit($args = array()) {
        foreach ($args as $item) {
            $res = mysql_query($item, $this->driver);
            if (!$res) {
                throw new \Exception('事务执行失败！');
            }
        }
        $arg = mysql_query('COMMIT');
        $result = empty($arg);
        mysql_query('END', $this->driver);
        return $result;
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function rollBack() {
        $arg = mysql_query('ROLLBACK', $this->driver);
        $result = empty($arg);
        mysql_query('END', $this->driver);
        return $result;
    }
}