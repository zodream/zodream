<?php 
namespace Zodream\Infrastructure\Database\Engine;

/**
 *
 * @author zx648
 *
 */
use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Service\Factory;
abstract class BaseEngine extends ConfigObject {
	
	protected $driver             = null;

	//存放当前操作的错误信息
	protected $error           = null;
	
	protected $result;

	protected $version;
	
	
	protected $configs = array(
	    'type'     => 'mysql',                //数据库类型
		'host'     => 'localhost',                //服务器
		'port'     => '3306',						//端口
		'database' => 'test',				//数据库
		'user'     => 'root',						//账号
		'password' => '',					//密码
		'prefix'   => '',					//前缀
		'encoding' => 'utf8',					//编码
		'persistent' => false                   //使用持久化连接
	);
	 
	//私有克隆
	protected function __clone() {}

    /**
     * BaseEngine constructor.
     * @param array|resource|\mysqli|\PDO $config
     */
	public function __construct($config) {
        if (is_array($config)) {
            Factory::timer()->record('dbEngineInit');
            $this->setConfigs($config)->connect();
            Factory::timer()->record('dbEngineEnd');
            return;
        }
        $this->setDriver($config);
	}

	public function getConfig($key, $default = null) {
	    return array_key_exists($key, $this->configs) ? $this->configs[$key] : $default;
    }
	
	protected abstract function connect();
	
	public function getDriver() {
		return $this->driver;
	}

    /**
     * @param mixed $driver
     */
	public function setDriver($driver) {
	    $this->driver = $driver;
    }

	public function getVersion() {
		if (empty($this->version)) {
			$args = $this->select('SELECT VERSION();');
			if (count($args) > 0 && count($args[0]) > 0) {
				$this->version = current($args[0]);
			}
		}
		return $this->version;
	}

	/**
	 * 查询
	 * @param string $sql
	 * @param array $parameters
	 * @return array
	 */
	public function select($sql, $parameters = array()) {
		return $this->getArray($sql, $parameters);
	}

	/**
	 * 插入
	 * @param string $sql
	 * @param array $parameters
	 * @return int id
	 */
	public function insert($sql, $parameters = array()) {
		$this->execute($sql, $parameters);
		return $this->lastInsertId();
	}

	/**
	 * 修改
	 * @param string $sql
	 * @param array $parameters
	 * @return int 改变的行数
	 */
	public function update($sql, $parameters = array()){
		$this->execute($sql, $parameters);
		return $this->rowCount();
	}

	/**
	 * 删除
	 * @param string $sql
	 * @param array $parameters
	 * @return int 删除的行数
	 */
	public function delete($sql, $parameters = array()) {
		$this->execute($sql, $parameters);
		return $this->rowCount();
	}

	/**
	 * 事务开始
	 * @return bool
	 */
	abstract public function begin();

	/**
	 * 执行事务
	 * @param array $args
	 * @return bool
	 */
	public function transaction($args) {
		$this->begin();
		try {
			$this->commit($args);
			return true;
		} catch (\Exception $ex) {
			$this->rollBack();
			$this->error = $ex->getMessage();
			return false;
		}
	}

	/**
	 * 执行事务
	 * @param array $args
	 * @return bool
	 * @throws \Exception
	 */
	abstract public function commit($args = array());

	/**
	 * 事务回滚
	 * @return bool
	 */
	abstract public function rollBack();
	
	/**
	 * 获取最后修改的id
	 * @return string
	 */
	abstract public function lastInsertId();
	
	/**
	 * 改变的行数
	 */
	abstract public function rowCount();

	/**
	 * 获取Object结果集
	 * @param string $sql
	 * @param array $parameters
	 * @return mixed
	 */
	abstract public function getObject($sql, $parameters = array());

	/**
	 * 获取关联数组
	 * @param string $sql
	 * @param array $parameters
	 * @return
	 */
	abstract public function getArray($sql, $parameters = array());

	
	abstract public function execute($sql = null, $parameters = array());
	
	
	
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
	
	public function close() {
		$this->driver = null;
	}
	
}