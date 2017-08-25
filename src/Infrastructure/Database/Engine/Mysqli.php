<?php 
namespace Zodream\Infrastructure\Database\Engine;

use Zodream\Service\Factory;
/**
* mysqli 
* 
* @author Jason
*/

class Mysqli extends BaseEngine {

	/**
	 * @var \mysqli
	 */
	protected $driver = null;

	/**
	 * @var \mysqli_stmt
	 */
	protected $result;

	/**
	 * 连接数据库
	 *
	 */
	protected function connect() {
		if (empty($this->configs)) {
			die ('Mysql host is not set');
		}
		$host = $this->configs['host'];
		if ($this->configs['persistent'] === true) {
			$host = 'p:'.$host;
		}
		$this->driver = new \mysqli(
				$host,
				$this->configs['user'], 
				$this->configs['password'], 
				$this->configs['database'], 
				$this->configs['port']
		)
		or die('There was a problem connecting to the database');
		/* check connection */
		/*if (mysqli_connect_errno()) {
		 printf("Connect failed: %s\n", mysqli_connect_error());
		 exit();
		}*/
		if (isset($this->configs['encoding'])) {
			$this->driver->set_charset($this->configs['encoding']);
		}
	}
	
	/**
	 * 预处理
	 * @param string $sql
*/
	public function prepare($sql) {
		$this->result = $this->driver->prepare($sql);
	}
	
	/**
	 * 绑定值 只支持 ？ 绑定
	 * @param array $param
	 */
	public function bind(array $param) {
		$ref    = new \ReflectionClass('mysqli_stmt');
		$method = $ref->getMethod("bind_param");
		$method->invokeArgs($this->result, $param);
	}

	/**
	 * 获取Object结果集
	 * @param string $sql
	 * @param array $parameters
	 * @return object
	 */
	public function getObject($sql = null, $parameters = array()) {
		$this->execute($sql);
		$result = array();
		while (!!$objs = mysqli_fetch_object($this->result) ) {
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
	public function getArray($sql = null, $parameters = array()) {
		$this->execute($sql);
		$result = array();
		while (!!$objs = mysqli_fetch_assoc($this->result) ) {
			$result[] = $objs;
		}
		return $result;
	}
	
	/**
	 * 返回上一步执行受影响的行数
	 *
	 * @access public
	 *
	 */
	public function rowCount() {
		return mysqli_affected_rows($this->driver);
	}
	
	/**
	 * 返回上一步执行INSERT生成的id
	 *
	 * @access public
	 *
	 */
	public function lastInsertId() {
		return mysqli_insert_id($this->driver);
	}

	/**
	 * 执行SQL语句
	 *
	 * @access public
	 *
	 * @param string $sql 多行查询语句
	 * @param array $parameters
	 * @return bool|null
	 */
	public function execute($sql = null, $parameters = array()) {
		if (empty($sql)) {
			return null;
		}
		$this->prepare($sql);
		$this->bind($parameters);
        Factory::log()->info(sprintf('MYSQLI: %s => %s', $sql,
            $this->getError()));
		return $this->result->execute();
	}

	/**
	 * 执行多行SQL语句
	 *
	 * @access public
	 *
	 * @param string $query 多行查询语句
	 * @return array
	 */
	public function multi_query($query)  {
		$result = array();
		if (mysqli_multi_query($this->driver, $query)) {                                           //执行多个查询
			do {
				if ($this->result = mysqli_store_result($this->driver)) {
					$result[] = $this->getList();
					mysqli_free_result($this->result);
				}
				/*if (mysqli_more_results($this_mysqli)) {
				 echo ("-----------------<br>");                   //连个查询之间的分割线
				 }*/
			} while (mysqli_next_result($this->driver));
		}
		$this->close();
		return $result;
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
			mysqli_free_result($this->result);
		}
		mysqli_close($this->driver);
		parent::close();
	}
	
	public function getError() {
		return mysqli_error($this->driver);
	}
	
	public function __destruct() {
		$this->close();
	}

	/**
	 * 事务开始
	 * @return bool
	 */
	public function begin() {
		return $this->driver->autocommit(false);
	}

	/**
	 * 执行事务
	 * @param array $args
	 * @return bool
	 * @throws \Exception
	 */
	public function commit($args = array()) {
		foreach ($args as $item) {
			$this->driver->query($item);
		}
		if ($this->driver->errno > 0) {
			throw new \Exception('事务执行失败!');
		}
		return $this->driver->commit();
	}

	/**
	 * 事务回滚
	 * @return bool
	 */
	public function rollBack() {
		return $this->driver->rollback();
	}
}