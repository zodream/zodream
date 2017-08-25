<?php
namespace Zodream\Domain\Model;
/**
 * 数据基类
 *
 * @author Jason
 */
use Zodream\Domain\Html\Page;
use Zodream\Domain\Model\Concerns\AutoModel;
use Zodream\Domain\Model\Concerns\HasAttributes;
use Zodream\Domain\Model\Concerns\HasRelation;
use Zodream\Domain\Model\Concerns\ValidateData;
use Zodream\Infrastructure\Database\Query\Record;
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\Traits\ErrorTrait;
use Zodream\Infrastructure\Traits\EventTrait;

abstract class Model extends MagicObject {

    use ErrorTrait, AutoModel, EventTrait, HasRelation, HasAttributes, ValidateData;

    const BEFORE_SAVE = 'before save';
    const AFTER_SAVE = 'after save';
    const BEFORE_INSERT = 'before insert';
    const AFTER_INSERT = 'after insert';
    const BEFORE_UPDATE = 'before update';
    const AFTER_UPDATE = 'after update';

    public $isNewRecord = true;




	/**
	 * 标签
	 * @return array
	 */
	protected function labels() {
		return [];
	}

	/**
	 * 表名
	 * @return string
	 */
	public static function tableName() {
	    return '';
    }

    public static function className() {
	    return static::class;
    }

	/**
	 * 主键
	 * @var array
	 */
	protected $primaryKey = [
		'id'
	];

	public function __construct() {
		$this->init();
	}
	
	public function init() {
		
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getLabel($key) {
		$labels = $this->labels();
		if (array_key_exists($key, $labels)) {
			return $labels[$key];
		}
		return ucwords(str_replace('_', ' ', $key));
	}

	public function save() {
		$this->invoke(self::BEFORE_SAVE, [$this]);
		if ($this->isNewRecord) {
			$row = $this->insert();
		} else {
			$row = $this->update();
		}
		$this->invoke(self::BEFORE_SAVE, [$this]);
		return $row;
	}



	/**
	 * @param bool $all 是否包含主键唯一等字段的值
	 * @return array
	 */
	protected function getValues($all = true) {
		$keys = array_keys($this->rules());
		if ($all) {
			$keys = array_merge($keys, $this->primaryKey);
		}
		$data = [];
		foreach ($this->get() as $k => $item) {
			if (in_array($k, $keys) && !property_exists($this, $k)) {
				$data[$k] = $item;
			}
		}
		return $data;
	}


    /**
     * @return bool|int
     */
	public function insert() {
		if (!$this->validate()) {
			return false;
		}
		$this->invoke(self::BEFORE_INSERT, [$this]);
		$row = static::record()
            ->set($this->getValues())
            ->insert();
		if (!empty($row)) {
			$this->set('id', $row);
		}
		$this->invoke(self::AFTER_INSERT, [$this]);
		return $row;
	}

    /**
     * @return Record
     */
	public static function record() {
		return (new Record())->setTable(static::tableName());
	}

	/**
	 * 自动获取条件
	 * @return array|bool
	 */
	protected function getWhereKey() {
		foreach ($this->primaryKey as $item) {
			if ($this->has($item)) {
				return [$item => $this->get($item)];
			}
		}
		return false;
	}

	/**
	 * 获取需要更新的数据
	 * @return array
	 */
	protected function getUpdateData() {
		if ($this->isNewRecord) {
			return $this->_data;
		}
		$data = [];
		foreach ($this->_data as $key => $item) {
			if (array_key_exists($key, $this->_oldData) 
				&& $item === $this->_oldData[$key]) {
				continue;
			}
			$data[$key] = $item;
		}
		return $data;
	}

	/**
	 * 修改记录
	 *
	 * @param array|string $where 条件 默认使用AND 连接
	 * @param array $args 需要修改的内容
	 * @return int 返回影响的行数,
	 */
	public function update($where = null, $args = null) {
		if (is_null($where)) {
			$where = $this->getWhereKey();
		}
		if (is_array($args)) {
			$this->set($args);
		}
		if (!$this->validate()) {
			return false;
		}
		$this->invoke(self::BEFORE_UPDATE, [$this]);
		$row = $this->record()
            ->set($this->getUpdateData())
            ->whereMany($where)
			->update();
		$this->invoke(self::AFTER_UPDATE, [$this]);
		return $row;
	}

	/**
	 * SELECT ONE BY QUERY
	 * 查询一条数据
	 *
	 * @access public
	 *
	 * @param array|string $param 条件
	 * @param string $field
	 * @param array $parameters
	 * @return static|boolean
     */
	public static function findOne($param, $field = '*', $parameters = array()) {
		$model = new static;
		if (is_numeric($param)) {
			$param = [$model->primaryKey[0] => $param];
		}
		if (!is_array($param) || !array_key_exists('where', $param)) {
			$param = [
				'where' => $param
			];
		}
		return static::find()
			->load($param)
			->select($field)
			->addParam($parameters)
			->one();
	}

	/**
	 * 删除数据
	 * DELETE QUERY
	 *
	 * @param string|array $where 条件
	 * @param array $parameters
	 * @return int 返回影响的行数,
	 */
	public function delete($where = null, $parameters = array()) {
		if (is_null($where)) {
			$where = $this->getWhereKey();
		}
		return $this->record()
			->whereMany($where)
			->addParam($parameters)
			->delete();
	}

	/**
	 * 查询数据
	 *
	 * @access public
	 *
	 * @return Query 返回查询结果,
	 */
	public static function find() {
		return (new Query())
            ->setModelName(static::className())
            ->from(static::tableName());
	}

	/**
	 * @param array $param
	 * @param string $field
	 * @param array $parameters
	 * @return static[]
	 */
	public static function findAll($param = array(), $field = '*', $parameters = array()) {
		if (!is_array($param) ||
			(!array_key_exists('where', $param) &&
				!array_key_exists('group', $param) &&
				!array_key_exists('order', $param) &&
				!array_key_exists('having', $param)) ) {
			$param = array(
				'where' => $param
			);
		}
		return static::find()
			->load($param)
			->select($field)
			->addParam($parameters)
			->all();
	}

    /**
     * @param $args
     * @param int $size
     * @param string $key
     * @param array $parameters
     * @return Page
     */
    public static function findPage($args = null,
                                    $size = 20,
                                    $key = 'page',
                                    $parameters = array()) {
        if (!empty($args) && (!is_array($args) ||
            (!array_key_exists('where', $args) &&
                !array_key_exists('group', $args) &&
                !array_key_exists('order', $args) &&
                !array_key_exists('having', $args))) ) {
            $args = array(
                'where' => $args
            );
        }
        return static::find()
            ->addParam($parameters)
            ->load($args)->page($size, $key);
    }


	/**
	 * 总记录
	 *
	 * @access public
	 *
	 * @param array|null $param 条件
	 * @param string $field 默认为id
	 * @param array $parameters
	 * @return int 返回总数,
	 */
	public static function count(
	    array $param = array(),
        $field = 'id',
        $parameters = array()) {
		if (!array_key_exists('where', $param)) {
			$param = array(
				'where' => $param
			);
		}
		return static::find()->load($param)
			->addParam($parameters)
			->count($field)->limit(1)->scalar();
	}
}