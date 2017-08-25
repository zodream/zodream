<?php
namespace Zodream\Infrastructure\Database\Query;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/12
 * Time: 19:24
 */
use Zodream\Infrastructure\ObjectExpand\StringExpand;

class Record extends BaseQuery  {
    
    protected $parameters = [];

    /**
     * ADD PARAM
     * @param $key
     * @param null $value
     * @return $this
     */
    public function addParam($key, $value = null) {
        if (is_object($key)) {
            $key = (array)$key;
        }
        if (is_array($key)) {
            $this->parameters = array_merge($this->parameters, $key);
            return $this;
        }
        if (empty($key)) {
            return $this;
        }
        $this->parameters[$key] = $value;
        return $this;
    }

    /**
     * HAS PARAMETERS
     * @param $key
     * @return bool
     */
    public function hasParam($key) {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @param array|string $key 需要添加的集合
     * @param string $value
     * @return static
     */
    public function set($key, $value = null) {
        if (is_null($key) && !is_null($value)) {
            $this->_data[] = $value;
            return $this;
        }
        parent::set($key, $value);
        return $this;
    }

    /**
     * SET TABLE
     * @param $table
     * @return $this
     */
    public function setTable($table) {
        $this->command()->setTable($table);
        return $this;
    }


    /**
     * INSERT RECORD
     *
     * @access public
     *
     * @return int 返回最后插入的ID,
     */
    public function insert() {
        if (empty($this->_data)) {
            return $this->command()->insert(null, 'NULL'); // 获取自增值
        }
        $addFields = implode('`,`', array_keys($this->_data));
        return $this->command()
            ->insert("`{$addFields}`", StringExpand::repeat('?', count($this->_data)),
                array_values($this->_data));
    }

    /**
     * INSERT MANY RECORDS
     * @param array|string $columns
     * @param array $data
     * @return int
     */
    public function batchInsert($columns, array $data) {
        $args = [];
        foreach ($data as $item) {
            $arg = [];
            foreach ($item as $value) {
                if (is_null($value)) {
                    $arg[] = 'NULL';
                    continue;
                }
                if (is_bool($value)) {
                    $arg[] = intval($value);
                    continue;
                }
                if (is_string($value)) {
                    $arg[] = "'".addslashes($value)."'";
                    continue;
                }
                if (is_array($value) || is_object($value)) {
                    $arg[] = "'".serialize($value)."'";
                    continue;
                }
                $arg[] = $value;
            }
            $args[] = '(' . implode(', ', $arg) . ')';
        }

        return $this->command()
            ->insert(implode(', ', (array)$columns), implode(', ', $args));
    }

    /**
     * UPDATE
     * @return mixed
     */
    public function update() {
        $data = [];
        $parameters = array();
        foreach ($this->_data as $key => $value) {
            if (is_integer($key)) {
                $data[] = $value;
                continue;
            }
            $data[] = "`{$key}` = ?";
            $parameters[] = $value;
        }
        return $this->command()
            ->update(implode(',', $data), $this->getWhere().$this->getLimit(), $parameters);
    }

    /**
     * 设置bool值
     *
     * @param string $filed
     * @return int
     */
    public function updateBool($filed) {
        $this->_data[] = "{$filed} = CASE WHEN {$filed} = 1 THEN 0 ELSE 1 END";
        return $this->update();
    }

    /**
     * int加减
     *
     * @param string|string $filed
     * @param integer $num
     * @return int
     */
    public function updateOne($filed, $num = 1) {
        $sql = array();
        foreach ((array)$filed as $key => $item) {
            if (is_numeric($key)) {
                $sql[] = "`$item` = `$item` ".$this->_getNumber($num);
            } else {
                $sql[] = "`$key` = `$key` ".$item;
            }
        }
        return $this->record()
            ->set($sql)
            ->update();
    }

    /**
     * 获取加或减
     * @param string|int $num
     * @return string
     */
    private function _getNumber($num) {
        if ($num >= 0) {
            $num = '+'.$num;
        }
        return $num;
    }

    /**
     * INSERT OR REPLACE
     * @return mixed
     */
    public function replace() {
        $addFields = implode('`,`', array_keys($this->_data));
        return $this->command()
            ->insertOrReplace("`{$addFields}`", StringExpand::repeat('?', count($this->_data)),
                array_values($this->_data));
    }

    /**
     * DELETE RECORD
     * @return mixed
     */
    public function delete() {
        return $this->command()
            ->delete($this->getWhere().$this->getLimit(), $this->parameters);
    }

    /**
     * @return string
     */
    public function getSql() {
        return '';
    }
}