<?php
namespace Zodream\Infrastructure\Database\Query;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/25
 * Time: 9:38
 */
use Zodream\Infrastructure\Database\Schema\BaseSchema;
use Zodream\Helpers\Arr;
use Closure;

abstract class BaseQuery extends BaseSchema  {

    protected $where = array();

    protected $limit;

    protected $offset;

    protected $operators = array(
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'in', 'not in', 'is', 'is not',
        'like', 'like binary', 'not like', 'between', 'not between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to'
    );

    /**
     * INIT WHERE AND SET WHERE
     * @param $condition
     * @param array $params
     * @return static
     */
    public function where($condition, $params = array()) {
        if (empty($condition)) {
            return $this;
        }
        $this->where = [$condition];
        return $this->addParam($params);
    }

    /**
     * 条件语句
     * @param bool $condition
     * @param Closure $trueFunc
     * @param Closure|null $falseFunc
     * @return $this
     */
    public function when($condition, Closure $trueFunc, Closure $falseFunc = null) {
        if ($condition) {
            $trueFunc($this);
            return $this;
        }
        if (!empty($falseFunc)) {
            $falseFunc($this);
        }
        return $this;
    }

    /**
     * ADD WHERE
     * @param $condition
     * @param array $params
     * @return static
     */
    public function whereMany($condition, $params = array()) {
        $this->where = array_merge(
            $this->where,
            $this->addCondition($condition)
        );
        return $this->addParam($params);
    }

    protected function addCondition($condition) {
        if (!is_array($condition)) {
            return array(
                array(
                    $condition,
                    'AND'
                )
            );
        }
        $result = array();
        foreach ($condition as $key => $item) {
            if (!is_integer($key)) {
                $item = (array)$item;
                array_unshift($item, $key);
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * AND WHERE
     * @param $condition
     * @param array $params
     * @return static
     */
    public function andWhere($condition, $params = array()) {
        $this->where[] = array(
            $condition,
            'AND'
        );
        return $this->addParam($params);
    }

    /**
     * OR WHERE
     * @param $condition
     * @param array $params
     * @return static
     */
    public function orWhere($condition, $params = array()) {
        $this->where[] = array(
            $condition,
            'OR'
        );
        return $this->addParam($params);
    }

    public function limit($limit, $length = null) {
        if (!empty($length)) {
            $this->offset($limit);
            $limit = $length;
        }
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param $params
     * @return static
     */
    abstract public function addParam($params);

    /**
     * HAS BUILD VALUE
     * @param string $key
     * @return bool
     */
    public function hasParam($key) {
        return $this->has($key);
    }

    protected function getWhere() {
        if (empty($this->where)) {
            return null;
        }
        return ' WHERE'.$this->getCondition($this->where);
    }



    /**
     * 合并where 或 having 的条件
     * @param array|string $param
     * @return string
     */
    protected function getCondition($param) {
        if (is_string($param)) {
            return $param;
        }
        $sql = '';
        foreach ($param as $key => $value) {
            $val = $value;
            if (!is_numeric($key)) {
                $val = (array)$val;
                array_unshift($val, $key);
            }
            $sql .= $this->getConditionOne($val);
        }
        if (empty($sql)) {
            return null;
        }
        if (strpos($sql, 'OR') === 1) {
            return substr($sql, 3);
        }
        if (strpos($sql, 'AND') === 1) {
            return substr($sql, 4);
        }
        return $sql;
    }

    /**
     * 合成一条条件语句
     * @param string|array $arg
     * @return null|string
     */
    protected function getConditionOne($arg) {
        if (is_string($arg)) {
            return $this->getConditionLink($arg);
        }
        if (!is_array($arg)) {
            return null;
        }
        if (Arr::isAssoc($arg)) {
            return $this->getCondition($arg);
        }
        // [[], 'or']
        if (is_array($arg[0])) {
            /** 修改允许通过数组添加条件 by 2017/05/13 */
            $arg[0] = !is_integer(key($arg[0]))
                ? $this->getCondition($arg[0])
                :  ('('.$this->getCondition($arg[0]).')');
        }
        $length = count($arg);
        if ($length == 1) {
            // 'a = b'
            return $this->getConditionLink($arg[0]);
        }
        if ($length == 2) {
            if ($this->isOrOrAnd($arg[1])) {
                // ['a = b', 'or']
                return $this->getConditionLink($arg[0], $arg[1]);
            }
            // ['id', []]
            if (is_array($arg[1])) {
                $sql = [];
                foreach ($arg[1] as $item) {
                    $sql[] = "{$arg[0]} = ". $this->getValueByOperator($item);
                }
                return ' AND ('.implode(' AND ', $sql).')';
            }
            // ['a', 'b']
            return $this->getConditionLink(
                "{$arg[0]} = ". $this->getValueByOperator($arg[1]));
        }
        if ($length == 3) {
            // ['id', [], 'or']
            if (is_array($arg[1])) {
                // ['id', ['1', 'int'], '@'] 需要安全检查的
                if ($arg[2] == '@') {
                    // ['a', 'b']
                    return $this->getConditionLink(
                        "{$arg[0]} = ". $this->getValueByOperator($arg[1]));
                }
                $sql = [];
                foreach ($arg[1] as $item) {
                    $sql[] = "{$arg[0]} = ". $this->getValueByOperator($item);
                }
                return ' '.strtoupper($arg[2]) .' ('.implode(' AND ', $sql).')';
            }

            if (in_array($arg[1], $this->operators)) {
                // ['a', '=', 'b']
                return $this->getConditionLink(
                    "{$arg[0]} {$arg[1]} ". $this->getValueByOperator($arg[2], $arg[1]));
            }
            // ['a', 'b', 'or']
            return $this->getConditionLink(
                "{$arg[0]} = ". $this->getValueByOperator($arg[1]), $arg[2]);
        }
        if ($length == 4) {
            // ['id', [], 'or']
            if (is_array($arg[1])) {
                // ['id', ['1', 'int'], '@', 'or'] 需要安全检查的
                if ($arg[2] == '@') {
                    // ['a', 'b']
                    return $this->getConditionLink(
                        "{$arg[0]} = ". $this->getValueByOperator($arg[1]), $arg[3]);
                }
                // ['id', [1, 3], 'or', 'or']
                $sql = [];
                foreach ($arg[1] as $item) {
                    $sql[] = "{$arg[0]} = ". $this->getValueByOperator($item);
                }
                return ' '.strtoupper($arg[3]) .' ('.
                implode(' '. strtoupper($arg[2]).' ', $sql).')';
            }

            if ($this->isOrOrAnd($arg[3])) {
                // ['a', '=', 'b', 'or']
                return $this->getConditionLink(
                    $arg[0].' '.$arg[1]. ' '. $this->getValueByOperator($arg[2], $arg[1]),
                    $arg[3]);
            }
            // ['a', 'between', 'b', 'c']
            return $this->getConditionLink(
                $this->getBetween($arg[0], $arg[2], $arg[3]));
        }

        if ($length == 5) {
            if ($this->isOrOrAnd($arg[4])) {
                //['a', 'between', 'b', 'c', 'or']
                return $this->getConditionLink(
                    $this->getBetween($arg[0], $arg[2], $arg[3]),
                    $arg[4]);
            }
            //['a', 'between', 'b', 'and', 'c']
            return $this->getConditionLink(
                $this->getBetween($arg[0], $arg[2], $arg[4]));
        }
        //['a', 'between', 'b', 'and', 'c', 'or']
        return $this->getConditionLink(
            $this->getBetween($arg[0], $arg[2], $arg[4]),
            $arg[5]);
    }

    protected function getBetween($key, $start, $end) {
        $tag = is_numeric($start) && is_numeric($end) ? 'int' : 'string';
        $start = $this->getValueWithParser($start, $tag);
        $end = $this->getValueWithParser($end, $tag);
        return "{$key} BETWEEN {$start} AND {$end}";
    }

    /**
     * 把值进行转化
     * @param $arg
     * @param string $tag
     * @return float|int|string
     */
    protected function getValueWithParser($arg, $tag = 'string') {
        if (is_array($arg)
            && count($arg) == 2
            && is_string($arg[1])
            && in_array(strtolower($arg['1']), ['int', 'integer', 'numeric', 'bool', 'boolean', 'string', 'float', 'double'])) {
            $tag = $arg[1];
            $arg = $tag;
        }
        if (is_object($arg) || is_array($arg)) {
            return "'".serialize($arg)."'";
        }
        switch (strtolower($tag)) {
            case 'int':
            case 'integer':
            case 'numeric':
                return intval($arg);
            case 'bool':
            case 'boolean':
                return boolval($arg) ? 1 : 0;
            case 'float':
                return floatval($arg);
            case 'double':
                return doubleval($arg);
            case 'string':
            default:
                return "'". addslashes($arg). "'";
        }
    }

    protected function getValueByOperator($value, $operator = null) {
        if ($value instanceof Query) {
            return '('.$value->getSql().')';
        }
        if (('is' == $operator || 'is not' == $operator) && is_null($value)) {
            return 'null';
        }
        if (('in' == $operator || 'not in' == $operator)) {
            if (is_array($value)) {
                $value = "'".implode("', '", $value)."'";
            }
            return '('.$value. ')';
        }
        // [a, int]
        if (is_array($value)) {
            return $this->getValueWithParser($value);
        }
        // 连接查询 排除邮箱 排除网址
//        if (strpos($value, '.') !== false
//            && substr_count($value, '.') === 1
//            && strpos($value, '@') === false) {
//            return $value;
//        }
//        // 表内字段关联
//        if (strpos($value, '@') === 0
//            && preg_match('/^[\w\.]+$/', $value, $match)) {
//            return substr($value, 1);
//        }

        if (is_numeric($value) ||
            $value === '?' ||
            (strpos($value, ':') === 0
                && $this->hasParam($value))) {

            return $value;
        }
        return sprintf("'%s'", addslashes($value));
    }

    /**
     * 判断是否是or 或 and 连接符
     * @param string $arg
     * @return bool
     */
    protected function isOrOrAnd($arg) {
        return is_string($arg) &&
        in_array(strtolower($arg), array('and', 'or'));
    }

    /**
     * 把连接符换成标准格式
     * @param string $arg
     * @param string $tag
     * @return null|string
     */
    protected function getConditionLink($arg, $tag = 'and') {
        if (empty($arg)) {
            return null;
        }
        if (is_array($arg)) {
            $arg = '('. $this->getCondition($arg).')';
        }
        if (strtolower($tag) === 'or') {
            return ' OR '.$arg;
        }
        return ' AND '.$arg;
    }

    protected function getLimit() {
        if (empty($this->limit)) {
            return null;
        }
        $param = (array)$this->limit;
        if (count($param) == 1) {
            return " LIMIT {$param[0]}";
        }
        $param[0] = intval($param[0]);
        $param[1] = intval($param[1]);
        if ($param[0] < 0) {
            $param[0] = 0;
        }
        return " LIMIT {$param[0]},{$param[1]}";
    }

    protected function getOffset() {
        if (empty($this->offset)) {
            return null;
        }
        return ' OFFSET '.intval($this->offset);
    }
}