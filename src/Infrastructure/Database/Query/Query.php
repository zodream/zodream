<?php
namespace Zodream\Infrastructure\Database\Query;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/19
 * Time: 10:30
 */
use Zodream\Domain\Html\Page;
use Zodream\Helpers\Arr;

class Query extends BaseQuery {

    protected $select = array();

    protected $from = array();

    protected $join = array();

    protected $group = array();

    protected $having = array();

    protected $order = array();

    protected $union = array();

    protected $sequence =  array(
        'select',
        'from',
        'join',
        'left',
        'inner',
        'right',
        'where',
        'group',
        'having',
        'order',
        'limit',
        'offset'
    );

    public function __construct($args = array()) {
        $this->load($args);
    }

    /**
     * MAKE LIKE 'SELECT' TO EMPTY ARRAY!
     * @param $tag
     * @return static
     */
    public function flush($tag) {
        $args = func_get_args();
        foreach ($args as $item) {
            if (in_array($item, $this->sequence)) {
                $this->$item = [];
            }
        }
        return $this;
    }

    public function load($args = []) {
        if (empty($args)) {
            return $this;
        }
        foreach ($args as $key => $item) {
            $tag = strtolower(is_integer($key) ? array_shift($item) : $key);
            if (!in_array($tag, $this->sequence) || empty($item)) {
                continue;
            }
            $this->$tag($item);
        }
        return $this;
    }

    /**
     * @param string|array $field
     * @return static
     */
    public function select($field = '*') {
        $this->select = [];
        if (!is_array($field)) {
            $field = func_get_args();
        }
        return $this->andSelect($field);
    }
    
    public function andSelect($field = '*') {
        if (!is_array($field)) {
            $field = func_get_args();
        }
        foreach ($field as $key => $value) {
            if (!is_int($key)) {
                $this->select[] = $value. ' AS '.$key;
                continue;
            }
            if (!is_null($value)) {
                $this->select[] = $value;
            }
        }
        return $this;
    }

    /**
     * 统计
     * @param string $column
     * @return integer
     */
    public function count($column = '*') {
        return $this->_selectFunction(__FUNCTION__, $column)->scalar();
    }

    /**
     * 最大值
     * @param $column
     * @return bool|string
     */
    public function max($column)  {
        return $this->_selectFunction(__FUNCTION__, $column)->scalar();
    }

    /**
     * 最小值
     * @param $column
     * @return bool|int|string
     */
    public function min($column)  {
        return $this->_selectFunction(__FUNCTION__, $column)->scalar();
    }

    /**
     * 平均值
     * @param $column
     * @return bool|int|string
     */
    public function avg($column)  {
        return $this->_selectFunction(__FUNCTION__, $column)->scalar();
    }

    /**
     * 总和
     * @param $column
     * @return bool|int|string
     */
    public function sum($column)  {
        return $this->_selectFunction(__FUNCTION__, $column)->scalar();
    }

    /**
     * @param string $name
     * @param string $column
     * @return $this
     */
    private function _selectFunction($name, $column) {
        $this->select[] = "{$name}({$column}) AS {$name}";
        return $this;
    }

    /**
     * @param string|array $tables
     * @return static
     */
    public function from($tables) {
        if (!is_array($tables)) {
            $tables = func_get_args();
        }
        $this->from = array_merge($this->from, $tables);
        return $this;
    }



    public function join($type, $table, $on = '', $params = array()) {
        $this->join[] = array($type, $table, $on);
        return $this->addParam($params);
    }

    public function inner($table, $on = '', $params = array()) {
        $this->addJoin($table, $on, 'INNER');
        return $this->addParam($params);
    }

    public function addJoin($args, $on = '', $tag = 'left') {
        if (is_array($on)) {
            if (count($on) == 2) {
                $on = $on[0].' = '.$on[1];
            } else {
                list($key, $value) = Arr::split($on);
                $on = $key.' = '.$value;
            }
        }
        $tag = strtoupper($tag);
        if (!is_array($args)) {
            $this->join[] = array( $tag.' JOIN', $this->addPrefix($args), $on);
            return;
        }
        if ($args[0] instanceof Query) {
            $this->join[] = array( $tag.' JOIN', '('.$args[0]->getSql().') '.$args[1], $on);
            return;
        }
        for ($i = 1, $length = count($args); $i < $length; $i += 2) {
            $this->join[] = array($tag.' JOIN ', $this->addPrefix($args[$i - 1]), $args[$i]);
        }
    }

    public function left($table, $on = '', $params = array()) {
        $this->addJoin($table, $on, 'LEFT');
        return $this->addParam($params);
    }

    public function right($table, $on = '', $params = array()) {
        $this->addJoin($table, $on, 'RIGHT');
        return $this->addParam($params);
    }

    public function group($columns) {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }
        $this->group = array_merge($this->group, $columns);
        return $this;
    }

    /**
     * 起别名
     * @param string $key
     * @return static
     */
    public function alias($key) {
        if (count($this->from) == 1) {
            $this->from = array($key => current($this->from));
        }
        return $this;
    }

    public function having($condition, $params = array()) {
        $this->having = [$condition];
        return $this->addParam($params);
    }

    public function havingMany($condition, $params = array()) {
        $this->having = array_merge($this->having, $this->addCondition($condition));
        return $this->addParam($params);
    }

    /**
     * @param $condition
     * @param array $params
     * @return Query
     */
    public function andHaving($condition, $params = array()) {
        $this->having[] = array(
            $condition,
            'AND'
        );
        return $this->addParam($params);
    }

    /**
     * @param $condition
     * @param array $params
     * @return Query
     */
    public function orHaving($condition, $params = array()) {
        $this->having[] = array(
            $condition,
            'OR'
        );
        return $this->addParam($params);
    }

    /**
     * ORDER SQL
     * @param array|string $args
     * @return Query
     */
    public function order($args) {
        if (!is_array($args)) {
            $args = func_get_args();
        }
        // 把关联数组变成 1，asc
        foreach ($args as $key => $item) {
            if (!is_integer($key)) {
                if (is_array($item)) {
                    //'asc' => ['a', 'b']
                    foreach ($item as $value) {
                        $this->order[] = $value;
                        $this->order[] = $key;
                    }
                    continue;
                }
                // 'a' => 'b'
                $this->order[] = $key;
                $this->order[] = $item;
                continue;
            }
            if (is_array($item)) {
                // ['a', 'asc']
                $this->order[] = $item[0];
                $this->order[] = $item[1];
                continue;
            }
            $this->order[] = $item;
        }
        return $this;
    }

    public function union($sql, $all = false) {
        $this->union[] = ['query' => $sql, 'all' => $all];
        return $this;
    }



    /**
     * add build value
     * @param string|array $key
     * @param string $value
     * @return Query
     */
    public function addParam($key, $value = null) {
        $this->set($key, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getSql() {
        return $this->getSelect().
        $this->getFrom().
        $this->getJoin().
        $this->getWhere().
        $this->getGroup().
        $this->getHaving().
        $this->getOrder().
        $this->getLimit().
        $this->getOffset();
    }

    /**
     * @param bool $isArray
     * @return array|object[]
     */
    public function all($isArray = true) {
        if ($isArray) {
            return $this->command()->getArray($this->getSql(), $this->get());
        }
        return $this->command()->getObject($this->getSql(), $this->get());
    }

    /**
     *
     * @param int $size
     * @param string $key
     * @return Page
     */
    public function page($size = 20, $key = 'page') {
        $select = $this->select;
        $this->select = [];
        $page = new Page($this, $size, $key);
        $this->select = $select;
        return $page->setPage($this->limit($page->getLimit())->all());
    }

    /**
     * @return array|bool
     */
    public function one() {
        $this->limit(1);
        $result = $this->all();
        if (empty($result)) {
            return false;
        }
        return current($result);
    }

    /**
     *
     * @return bool|string|int
     */
    public function scalar() {
        $result = $this->one();
        if (empty($result)) {
            return false;
        }
        return current($result);
    }

    protected function getSelect() {
        return 'SELECT '.$this->getField();
    }

    protected function getFrom() {
        if (empty($this->from)) {
            return null;
        }
        $result = array();
        foreach ($this->from as $key => $item) {
            if (is_integer($key)) {
                $result[] = $this->addPrefix($item);
                continue;
            }
            if ($item instanceof Query) {
                $result[] = '('.$item->getSql().') ' .$key;
                continue;
            }
            $result[] = $this->addPrefix($item).' ' .$key;
        }
        return ' FROM '.implode($result, ',');
    }

    /**
     * @return string
     */
    protected function getUnion() {
        if (empty($this->union)) {
            return null;
        }
        $sql = ' ';
        foreach ($this->union as $item) {
            $sql .= 'UNION ';
            if ($item['all']) {
                $sql .= 'ALL ';
            }
            if ($item['query'] instanceof Query) {
                $sql .= $item['query']->getSql();
                continue;
            }
            if (is_array($item['query'])) {
                $sql .= (new Query())->load($item['query'])->getSql();
            }
            $sql .= $item['query'];
        }
        return $sql;
    }

    protected function getHaving() {
        if (empty($this->having)) {
            return null;
        }
        return ' Having'.$this->getCondition($this->having);
    }



    /**
     * 支持多个相同的left [$table, $where, ...]
     * @return string
     */
    protected function getJoin() {
        if (empty($this->join)) {
            return null;
        }
        $sql = '';
        foreach ($this->join as $item) {
            $sql .= " {$item[0]} {$item[1]}";
            if (!empty($item[2])) {
                $sql .= " ON {$item[2] }";
            }
        }
        return $sql;
    }

    /**
     *
     * 关键字 DISTINCT 唯一 AVG() COUNT() FIRST() LAST() MAX()  MIN() SUM() UCASE() 大写  LCASE()
     * MID(column_name,start[,length]) 提取字符串 LEN() ROUND() 舍入 NOW() FORMAT() 格式化
     * @return string
     */
    protected function getField() {
        if (empty($this->select)) {
            return '*';
        }
        $result = array();
        foreach ((array)$this->select as $key => $item) {
            if (is_integer($key)) {
                $result[] = $item;
            } else {
                $result[] = "{$item} AS {$key}";
            }
        }
        return implode($result, ',');
    }

    protected function getGroup() {
        if (empty($this->group)) {
            return null;
        }
        return ' GROUP BY '.implode(',', (array)$this->group);
    }

    protected function getOrder() {
        if (empty($this->order)) {
            return null;
        }
        $result = array();
        for ($i = 0, $length = count($this->order); $i < $length; $i ++) {
            $sql = $this->order[$i];
            if ($i < $length - 1 && in_array(strtolower($this->order[$i + 1]), array('asc', 'desc')) ) {
                $sql .= ' '.strtoupper($this->order[$i + 1]);
                $i ++;
            }
            $result[] = $sql;
        }
        return ' ORDER BY '.implode($result, ',');
    }


}