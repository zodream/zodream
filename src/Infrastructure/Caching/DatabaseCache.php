<?php 
namespace Zodream\Infrastructure\Caching;

/**
* 数据库缓存类
* 
* @author Jason
*/
use Zodream\Database\Command;
use Zodream\Database\Query\Query;

class DatabaseCache extends Cache {
	
	protected $configs = [
	    'table' => 'cache',
        'gc' => 10
    ];

    /**
     *  ```php
     * CREATE TABLE cache (
     *     id char(128) NOT NULL PRIMARY KEY,
     *     expire int(11),
     *     data BLOB
     * );
     * ```
     * @var Command
     */
	public function __construct() {
	    $this->loadConfigs();
	}

    /**
     * @return Command
     */
	public function command() {
	    return Command::getInstance()
            ->setTable($this->configs['table']);
    }

	protected function query() {
		$query = new Query();
		$query->from($this->configs['table']);
		return $query;
	}
	
	protected function getValue($key) {
		return $this->query()->select('data')->where(array(
			'id' => $key,
			'(expire = 0 OR expire > '.time().')',
		))->scalar();
	}
	
	protected function setValue($key, $value, $duration) {
		 $result = $this->command()
             ->update('expire = :expire, data = :data', 'id = :id', array(
			':expire' => $duration > 0 ? $duration + time() : 0,
			':data' => [$value, \PDO::PARAM_LOB],
			':id' => $key
		));
		if (empty($result)) {
			$this->gc();
			return true;
		}
		return $this->addValue($key, $value, $duration);
	}
	
	protected function addValue($key, $value, $duration) {
		$this->gc();
		try {
			$this->command()
                ->insert('id, expire, data', ':id, :expire, :data', array(
					':id' => $key,
					':expire' => $duration > 0 ? $duration + time() : 0,
					':data' => [$value, \PDO::PARAM_LOB],
				));
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function gc($force = false) {
		if ($force || mt_rand(0, 1000000) < $this->getGC()) {
			$this->command()
                ->delete('expire > 0 AND expire < ' . time());
		}
	}
	
	protected function hasValue($key) {
		$count = $this->query()->count()->where(array(
			'id' => $key,
			'(expire = 0 OR expire > '.time().')',
		))->scalar();
		return !empty($count);
	}
	
	protected function deleteValue($key) {
		$this->command()
            ->delete('id = :id', array(
			':id' => $key
		));
	}
	
	protected function clearValue() {
		$this->command()
            ->delete();
	}
}