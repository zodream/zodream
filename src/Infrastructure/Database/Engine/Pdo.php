<?php 
namespace Zodream\Infrastructure\Database\Engine;

use Zodream\Service\Factory;
/**
* pdo
* 
* @author Jason
*/
class Pdo extends BaseEngine {

    const MYSQL = 'mysql';
    const MSSQL = 'dblib';
    const ORACLE = 'oci';
    const SQLSRV = 'sqlsrv';

	/**
	 * @var \PDO
	 */
	protected $driver = null;

	/**
	 * @var \PDOStatement
	 */
	protected $result;

	protected function connect() {
		try {
			//$this->driver = new \PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $pwd ,
			//                     array(\PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES {$coding}"));
			$this->driver = new \PDO (
				$this->getDsn(),
				$this->configs['user'],
				$this->configs['password'],
				array(
					\PDO::ATTR_PERSISTENT => $this->configs['persistent'] === true,
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, //默认是PDO::ERRMODE_SILENT, 0, (忽略错误模式)
    				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // 默认是PDO::FETCH_BOTH, 4
				)
			);
			if ($this->getType() == self::MYSQL) {
                $this->driver->exec ('SET NAMES '.$this->configs['encoding']);
                $this->driver->query ( "SET character_set_client={$this->configs['encoding']}" );
                $this->driver->query ( "SET character_set_connection={$this->configs['encoding']}" );
                $this->driver->query ( "SET character_set_results={$this->configs['encoding']}" );
            }
		} catch (\PDOException $ex) {
			throw $ex;
		}
	}

	public function getDsn() {
	    if ($this->getType() == self::SQLSRV) {
	        return sprintf('sqlsrv:server=%s;Database=%s',
                $this->configs['host'],
                $this->configs['database']
            );
        }
        return sprintf('%s:host=%s;port=%s;dbname=%s',
            $this->getType(),
            $this->configs['host'],
            $this->configs['port'],
            $this->configs['database']
        );
    }

    /**
     * 获取连接数据库的类型
     * @return string
     */
	public function getType() {
	    if (!array_key_exists('type', $this->configs)
            || empty($this->configs['type'])) {
	        return self::MYSQL;
        }
        return strtolower($this->configs['type']);
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
	 * @param string $sql
	 */
	public function prepare($sql) {
		$this->result = $this->driver->prepare($sql);
	}
	
	/**
	 * 绑定值
	 * @param array $param
	 */
	public function bind(array $param) {
		foreach ($param as $key => $value) {
			if (is_null($value)) {
				$type = \PDO::PARAM_NULL;
			} else if (is_bool($value)) {
				$type = \PDO::PARAM_BOOL;
			} else if (is_int($value)) {
				$type = \PDO::PARAM_INT;
			} else {
				$type = \PDO::PARAM_STR;
			}
			$this->result->bindValue(is_int($key) ? ++$key : $key, $value, $type);
		}
	}

	/**
	 * 加上引号
	 * @param string $arg
	 * @param int $parameterType
	 * @return string
	 */
	public function quote($arg, $parameterType = \PDO::PARAM_STR) {
		return $this->driver->quote($arg, $parameterType);
	}

	/**
	 * 执行SQL语句
	 *
	 * @access public
	 *
	 * @param null $sql
	 * @param array $parameters
	 * @return \PDOStatement 返回查询结果,
	 */
	public function execute($sql = null, $parameters = array()) {
		if (empty($sql)) {
			return null;
		}
		try {
			if (!empty($sql)) {
				$this->prepare($sql);
				$this->bind($parameters);
			}
			$this->result->execute();
		} catch (\PDOException  $ex) {
			$this->error = $ex->getMessage();
		}
		Factory::log()->info(sprintf('PDO: %s => %s', $sql, $this->error));
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
	 * @param array $parameters
	 * @return object
	 */
	public function getObject($sql = null, $parameters = array()) {
		$this->execute($sql, $parameters);
		$result = array();
		while (!!$objects = $this->result->fetchObject()) {
			$result[] = $objects;
			
		}
		return $result;
	}

	public function row($isArray = true) {
		if (is_bool($isArray)) {
			$isArray = $isArray ? \PDO::FETCH_ASSOC : \PDO::FETCH_CLASS;
		}
		return $this->result->fetch($isArray);
	}

	/**
	 * @param int $index
	 * @return string
	 */
	public function column($index = 0) {
		return $this->result->fetchColumn($index);
	}

	public function next() {
		$this->result->nextRowset();
	}

	public function columnCount() {
		return $this->result->columnCount();
	}

	/**
	 * 获取关联数组
	 * @param string $sql
	 * @param array $parameters
	 * @return array
	 */
	public function getArray($sql = null, $parameters = array()) {
		$this->execute($sql, $parameters);
		return $this->result->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * 事务开始
	 * @return bool
	 */
	public function begin() {
		return $this->driver->beginTransaction();
	}

	/**
	 * 执行事务
	 * @param array $args
	 * @return bool
	 * @throws \Exception
	 */
	public function commit($args = array()) {
		foreach ($args as $item) {
			$this->driver->exec($item);
		}
		return $this->driver->commit();
	}

	/**
	 * 事务回滚
	 * @return bool
	 */
	public function rollBack() {
		return $this->driver->rollBack();
	}
}