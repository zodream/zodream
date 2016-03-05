<?php 
namespace Zodream\Infrastructure\Database;
/**
 * 
 * @author zx648
 *
 */

abstract class Database {
	//用于存放实例化的对象
	protected static $instance = null;
	/**
	 * 公共静态方法获取实例化的对象
	 */
	public static function getInstance(array $config) {
		if (is_null(static::$instance)) {
			static::$instance = new static($config);
		}
		return static::$instance;
	}
	
	protected $driver             = null;

	//存放当前操作的错误信息
	protected $error           = null;
	
	protected $result;
	
	protected $configs = array(
			'host'     => 'localhost',                //服务器
			'port'     => '3306',						//端口
			'database' => 'test',				//数据库
			'user'     => 'root',						//账号
			'password' => '',					//密码
			'prefix'   => '',					//前缀
			'encoding' => 'utf8'					//编码
	);
	 
	//私有克隆
	protected function __clone() {}
	
	protected function __construct(array $config) {
		$this->configs = $config;
		$this->connect();
	}
	
	protected abstract function connect();
	
	public function getDriver() {
		return $this->driver;
	}
	

	/**
	 * 查询
	 * @param string $sql
	 * @return array
	 */
	public function select($sql) {
		return $this->getArray($sql);
	}
	
	/**
	 * 插入
	 * @param string $sql
	 * @return integer id
	 */
	public function insert($sql) {
		$this->execute($sql);
		return $this->lastInsertId();
	}
	
	/**
	 * 修改
	 * @param string $sql
	 * @return integer 改变的行数
	 */
	public function update($sql){
		$this->execute($sql);
		return $this->rowCount();
	}
	
	/**
	 * 删除
	 * @param string $sql
	 * @return integer 删除的行数
	 */
	public function delete($sql) {
		$this->execute($sql);
		return $this->rowCount();
	}
	
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
	 * @return mixed
	 */
	abstract public function getObject($sql);
	
	/**
	 * 获取关联数组
	 * @param string $sql
	 */
	abstract public function getArray($sql);
	
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
	
}