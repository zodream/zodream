<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;

/**
* 数据库缓存类
* 
* @author Jason
*/
use Zodream\Infrastructure\Contracts\Database;

class DatabaseCache extends Cache {
	
	protected array $configs = [
	    'table' => 'cache',
        'gc' => 10,
        'serializer' => null,
        'keyPrefix' => ''
    ];

    /**
     * @var Database
     */
	protected Database $db;

    /**
     *  ```php
     * CREATE TABLE cache (
     *     id char(128) NOT NULL PRIMARY KEY,
     *     expire int(11),
     *     data BLOB
     * );
     * ```
     * @param Database $db
     */
	public function __construct(Database $db) {
	    $this->loadConfigs();
	    $this->db = $db;
	}
	
	protected function getValue($key) {
        $sql = sprintf('SELECT data AS count FROM %s WHERE id=:id AND (expire=0 OR expire>%d)', $this->tableName(), time());
        return $this->db->executeScalar($sql, [
            ':id' => $key
        ]);
	}
	
	protected function setValue($key, $value, $duration) {
        $sql = sprintf('UPDATE %s SET expire=:expire, data=:data WHERE id=:id', $this->tableName());
        $result = $this->db->update($sql, [
            ':expire' => $duration > 0 ? $duration + time() : 0,
            ':data' => [$value, \PDO::PARAM_LOB],
            ':id' => $key
        ]);
		if (empty($result)) {
			$this->gc();
			return true;
		}
		return $this->addValue($key, $value, $duration);
	}
	
	protected function addValue($key, $value, $duration) {
		$this->gc();
		try {
            $sql = sprintf('INSERT INTO %s (id, expire, data) VALUES (:id, :expire, :data)', $this->tableName());
            $this->db->insert($sql, [
                ':id' => $key,
                ':expire' => $duration > 0 ? $duration + time() : 0,
                ':data' => [$value, \PDO::PARAM_LOB],
            ]);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function gc($force = false) {
		if ($force || mt_rand(0, 1000000) < $this->getGC()) {
            $sql = sprintf('DELETE FROM %s WHERE expire>0 AND expire<%d', $this->tableName(), time());
            $this->db->delete($sql);
		}
	}
	
	protected function hasValue($key) {
        $sql = sprintf('SELECT COUNT(*) AS count FROM %s WHERE id=:id AND (expire=0 OR expire>%d)', $this->tableName(), time());
		$count = $this->db->executeScalar($sql, [
		    ':id' => $key
        ]);
        return intval($count) > 0;
	}
	
	protected function deleteValue($key) {
        $sql = sprintf('DELETE FROM %s WHERE id=:id', $this->tableName());
        $this->db->delete($sql, [
            ':id' => $key
        ]);
	}
	
	protected function clearValue() {
	    $sql = sprintf('DELETE FROM %s', $this->tableName());
		$this->db->delete($sql);
	}

	protected function tableName() {
	    return $this->db->addPrefix($this->configs['table']);
    }
}