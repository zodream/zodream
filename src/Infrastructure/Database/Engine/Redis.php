<?php
namespace Zodream\Infrastructure\Database\Engine;

/**
 * redis
 *
 * @author Jason
 */
use Zodream\Infrastructure\Error\Exception;

class Redis {
	/**
	 * @var \Redis
	 */
	private $_redis;

	/**
	 * @param string $host
	 * @param int $port
	 */
	public function __construct($host = '127.0.0.1', $port = 6379) {
		$this->_redis = new \Redis();
		$this->_redis->connect($host, $port);
		return $this->_redis;
	}

	/**
	 * 设置值  构建一个字符串
	 * @param string $key KEY名称
	 * @param string $value 设置值
	 * @param int $timeOut 时间  0表示无过期时间
	 * @return bool
	 */
	public function set($key, $value, $timeOut=0) {
		$retRes = $this->_redis->set($key, $value);
		if ($timeOut <= 0) {
			return $retRes;
		}
		$this->_redis->expire($key, $timeOut);
		return true;
	}
	
	/*
	 * 构建一个集合(无序集合)
	 * @param string $key 集合Y名称
	 * @param string|array $value  值
	 */
	public function sadd($key, $value) {
		return $this->_redis->sadd($key, $value);
	}

	/**
	 * 构建一个集合(有序集合)
	 * @param string $key 集合名称
	 * @param $value
	 * @param int $score
	 * @return int
     */
	public function zadd($key, $value, $score = 1) {
		return $this->_redis->zadd($key, $score, $value);
	}

	/**
	 * 取集合对应元素
	 * @param string $setName 集合名字
	 * @return array
	 */
	public function smembers($setName){
		return $this->_redis->smembers($setName);
	}

	/**
	 * 构建一个列表(先进后去，类似栈)
	 * @param string $key KEY名称
	 * @param string $value 值
	 * @return int
	 */
	public function lpush($key, $value){
		return $this->_redis->LPUSH($key, $value);
	}

	/**
	 * 构建一个列表(先进先去，类似队列)
	 * @param string $key KEY名称
	 * @param string $value 值
	 * @return int
	 */
	public function rpush($key, $value){
		return $this->_redis->rpush($key, $value);
	}

	/**
	 * 获取所有列表数据（从头到尾取）
	 * @param string $key KEY名称
	 * @param int $head 开始
	 * @param int $tail 结束
	 * @return array
	 */
	public function lranges($key, $head, $tail){
		return $this->_redis->lrange($key, $head, $tail);
	}

	/**
	 * HASH类型
	 * @param string $tableName 表名字key
	 * @param string $field 字段名字
	 * @param string $value 值
	 * @return int
	 */
	public function hset($tableName, $field, $value){
		return $this->_redis->hset($tableName, $field, $value);
	}
	
	public function hget($tableName, $field){
		return $this->_redis->hget($tableName, $field);
	}


    /**
     * 设置多个值
     * @param array $keyArray KEY名称 获取得到的数据
     * @param int $timeout 时间
     * @return bool|string
     * @throws Exception
     */
	public function sets(array $keyArray, $timeout) {
		if (!is_array($keyArray)) {
			throw new Exception('Call  ' . __FUNCTION__ . ' method  parameter  Error !');
		}
		$retRes = $this->_redis->mset($keyArray);
		if ($timeout <= 0) {
			return $retRes;
		}
		foreach ($keyArray as $key => $value) {
			$this->_redis->expire($key, $timeout);
		}
		return true;
	}

	/**
	 * 通过key获取数据
	 * @param string $key KEY名称
	 * @return bool|string
	 */
	public function get($key) {
		$result = $this->_redis->get($key);
		return $result;
	}

    /**
     * 同时获取多个值
     * @param array $keyArray 获key数值
     * @return array|string
     * @throws \Exception
     */
	public function gets(array $keyArray) {
		if (is_array($keyArray)) {
			return $this->_redis->mget($keyArray);
		}
		throw new Exception('Call  ' . __FUNCTION__ . ' method  parameter  Error !');
	}
	
	/**
	 * 获取所有key名，不是值
	 */
	public function keyAll() {
		return $this->_redis->keys('*');
	}
	
	/**
	 * 删除一条数据key
	 * @param string $key 删除KEY的名称
	 */
	public function del($key) {
		$this->_redis->delete($key);
	}

    /**
     * 同时删除多个key数据
     * @param array $keyArray KEY集合
     * @return int|string
     * @throws \Exception
     */
	public function dels(array $keyArray) {
		if (is_array($keyArray)) {
			return $this->_redis->del($keyArray);
		}
		throw new Exception('Call  ' . __FUNCTION__ . ' method  parameter  Error !');
	}

	/**
	 * 数据自增
	 * @param string $key KEY名称
	 * @return int
	 */
	public function increment($key) {
		return $this->_redis->incr($key);
	}

	/**
	 * 数据自减
	 * @param string $key KEY名称
	 * @return int
	 */
	public function decrement($key) {
		return $this->_redis->decr($key);
	}


	/**
	 * 判断key是否存在
	 * @param string $key KEY名称
	 * @return bool
	 */
	public function isExists($key){
		return $this->_redis->exists($key);
	}

	/**
	 * 重命名- 当且仅当newkey不存在时，将key改为newkey ，当newkey存在时候会报错哦RENAME
	 *  和 rename不一样，它是直接更新（存在的值也会直接更新）
	 * @param string $key KEY名称
	 * @param string $newKey 新key名称
	 * @return bool
	 */
	public function updateName($key,$newKey){
		return $this->_redis->RENAMENX($key,$newKey);
	}

	/**
	 * 获取KEY存储的值类型
	 * none(key不存在) int(0)  string(字符串) int(1)   list(列表) int(3)  set(集合) int(2)   zset(有序集) int(4)    hash(哈希表) int(5)
	 * @param string $key KEY名称
	 * @return int
	 */
	public function dataType($key){
		return $this->_redis->type($key);
	}
	
	
	/**
	 * 清空数据
	 */
	public function flushAll() {
		return $this->_redis->flushAll();
	}
	
	
	
	/**
	 * 返回redis对象
	 * redis有非常多的操作方法，我们只封装了一部分
	 * 拿着这个对象就可以直接调用redis自身方法
	 * eg:$redis->redisOtherMethods()->keys('*a*')   keys方法没封
	 */
	public function redisOtherMethods() {
		return $this->_redis;
	}
}