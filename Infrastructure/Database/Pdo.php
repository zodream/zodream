<?php 
namespace Zodream\Infrastructure\Database;
/**
* pdo
* 
* @author Jason
*/

class Pdo extends Database {
	
	protected function connect() {
		try {
			//$this->driver = new \PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $pwd ,
			//                     array(\PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES {$coding}"));
			$this->driver = new \PDO (
					'mysql:host='. $this->configs['host'].
					';port='.$this->configs['port'].
					';dbname='.$this->configs['database'], 
					$this->configs['user'], 
					$this->configs['password']
			);
			$this->driver->exec ('SET NAMES '.$this->configs['encoding']);
			$this->driver->query ( "SET character_set_client={$this->configs['encoding']}" );
			$this->driver->query ( "SET character_set_connection={$this->configs['encoding']}" );
			$this->driver->query ( "SET character_set_results={$this->configs['encoding']}" );
			$this->driver->setAttribute (\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch (\PDOException $ex) {
			$this->error = $ex->getMessage();
			return false;
		}
	}
	
	
	/**
	 * 获取最后修改的id
	 * @return string
	 */
	public function lastInsertId() {
		return $this->driver->lastInsertId();
	}
	
	public function rowCount() {
		return $this->result->rowCount();
	}
	
	/**
	 * 预处理
	 * @param unknown $sql
	 */
	public function prepare($sql) {
		$this->result = $this->driver->prepare($sql);
	}
	
	/**
	 * 绑定值
	 * @param unknown $param
	 */
	public function bind(array $param) {
		foreach ($param as $key => $value) {
			if (is_null($value)) {
				$type = PDO::PARAM_NULL;
			} else if (is_bool($value)) {
				$type = PDO::PARAM_BOOL;
			} else if (is_int($value)) {
				$type = PDO::PARAM_INT;
			} else {
				$type = PDO::PARAM_STR;
			}
			$this->result->bindParam(is_int($key) ? ++$key : $key, $value, $type);
		}
	}
	 
	/**
	 * 执行SQL语句
	 *
	 * @access public
	 *
	 * @param array|null $param 条件
	 * @return array 返回查询结果,
	 */
	public function execute($sql = null, $parameters = array()) {
		if (empty($sql)) {
			return;
		}
		try {
			if (!empty($sql)) {
				$this->result = $this->driver->prepare($sql);
				$this->bind($parameters);
			}
			$this ->result->execute();
		} catch (\PDOException  $ex) {
			$this->error = $ex->getMessage();
		}
		return $this->result;
	}
	
	/**
	 * 得到当前执行语句的错误信息
	 *
	 * @access public
	 *
	 * @return string 返回错误信息,
	 */
	public function getError() {
		return $this->error;
	}
	
	/**
	 * 获取Object结果集
	 * @param string $sql
	 * @return multitype:mixed
	 */
	public function getObject($sql = null) {
		$this->execute($sql);
		$result = array();
		while (!!$objs = $this->result->fetchObject()) {
			$result[] = $objs;
		}
		return $result;
	}
	
	/**
	 * 获取关联数组
	 * @param string $sql
	 */
	public function getArray($sql = null) {
		$this->execute($sql);
		return $this->result->fetchAll(\PDO::FETCH_ASSOC);
	}
}