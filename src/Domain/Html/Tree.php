<?php 
namespace Zodream\Domain\Html;

use Zodream\Infrastructure\Interfaces\JsonAble;
use Zodream\Infrastructure\ObjectExpand\JsonExpand;

/**
 * 无限树生成
 *
 * @author Jason
 * @time 2015-12-1
 */
class Tree implements JsonAble {
    protected $config = array(
        /* 主键 */
        'primary_key' 	=> 'id',
        /* 父键 */
        'parent_key'  	=> 'parent_id',
        /* 展开属性 */
        'expanded_key'  => 'expanded',
        /* 叶子节点属性 */
        'leaf_key'      => 'leaf',
        /* 孩子节点属性 */
        'children_key'  => 'children',
        /* 是否展开子节点 */
        'expanded'    	=> false
    );

    const NORMAL = 'normal';
    const LINEAR = 'linear';
    const ID_AS_KEY = 'id as key';

    /* 结果集 */
    protected $result = [];

    /* 层次暂存 */
    protected $level = [];

    protected $data = [];

    public function __construct(array $data, array $option = []) {
        $this->config = array_merge($this->config, $option);
        $this->set($data);
    }

    public function set(array $data) {
        $this->data = $this->_format($data);
        return $this;
    }

    /**
     * @name 生成树形结构
     * @return mixed 多维数组
     */
    public function makeTree(){
        return $this->_makeTreeCore(0, $this->data, self::NORMAL);
    }

    /**
     * 生成以ID 作为键的数组
     * @return array
     */
    public function makeIdTree(){
        return $this->_makeTreeCore(0, $this->data, self::ID_AS_KEY);
    }

    /**
     * 生成线性结构, 便于HTML输出, 参数同上
     * @return array
     */
    public function makeTreeForHtml(){
        return $this->_makeTreeCore(0, $this->data, self::LINEAR);
    }

    /**
     * 格式化数据, 私有方法
     * @param array $args
     * @return array
     */
    private function _format(array $args){
        $data = [];
        foreach($args as $item){
            $id = $item[$this->config['primary_key']];
            $parent_id = $item[$this->config['parent_key']];
            $data[$parent_id][$id] = (array)$item;
        }
        return $data;
    }

    /**
     * 生成树核心, 私有方法
     * @param $index
     * @param $data
     * @param string $type
     * @return array
     */
    private function _makeTreeCore($index, $data, $type = self::LINEAR) {
        $args = [];
        foreach($data[$index] as $id => $item) {
            if ($type == self::NORMAL) {
                if (isset($data[$id])) {
                    $item[$this->config['expanded_key']] = $this->config['expanded'];
                    $item[$this->config['children_key']] = $this->_makeTreeCore($id, $data, $type);
                } else {
                    $item[$this->config['leaf_key']] = true;
                }
                $args[] = $item;
            } elseif ($type == self::ID_AS_KEY) {
                if (isset($data[$id])) {
                    $item[$this->config['expanded_key']] = $this->config['expanded'];
                    $item[$this->config['children_key']] = $this->_makeTreeCore($id, $data, $type);
                } else {
                    $item[$this->config['leaf_key']] = true;
                }
                $args[$id] = $item;
            } elseif ($type == self::LINEAR) {
                $parent_id = $item[$this->config['parent_key']];
                $this->level[$id] = $index == 0 ? 0 : $this->level[$parent_id]+1;
                $item['level'] = $this->level[$id];
                $this->result[] = $item;
                if (isset($data[$id])) {
                    $this->_makeTreeCore($id, $data, $type);
                }
                $args = $this->result;
            }
        }
        return $args;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE) {
        return JsonExpand::encode($this->makeTree(), $options);
    }
}