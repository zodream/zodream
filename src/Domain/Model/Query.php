<?php
namespace Zodream\Domain\Model;

use Zodream\Infrastructure\Database\Query\Query as BaseQuery;
use Zodream\Helpers\Str;

class Query extends BaseQuery {

    protected $relations = [];

    protected $modelName;

    /**
     * @var Model
     */
    protected $model;

    protected $isArray = false;

    public function getModel() {
        if (!$this->model instanceof Model) {
            $this->model = new $this->modelName;
        }
        return $this->model;
    }

    public function with($relations) {
        if (!is_array($relations)) {
            $relations = func_get_args();
        }
        $this->relations = array_merge($this->relations, $relations);
        return $this;
    }

    public function setModelName($model) {
        if ($model instanceof Model) {
            $this->model = $model;
            $model = $model->className();
        }
        $this->modelName = $model;
        return $this;
    }

    public function asArray() {
        $this->isArray = true;
        return $this;
    }

    /**
     * @param bool $isArray
     * @return array|object[]|Model[]
     */
    public function all($isArray = true) {
        $data = parent::all($isArray);
        if (empty($data)
            || $this->isArray
            || !$isArray
            || !class_exists($this->modelName)) {
            return $data;
        }
        $args = [];
        foreach ($data as $item) {
            /** @var $model Model */
            $model = new $this->modelName;
            $model->setOldData($item)->set($item);
            $args[] = $model;
        }
        return $args;
    }

    /**
     * 取一个值
     * @return bool|int|string
     */
    public function scalar() {
        $this->asArray();
        return parent::scalar();
    }

    /**
     * 更新
     * @param array $args
     * @return int
     */
    public function update(array $args) {
        $data = [];
        foreach ($args as $key => $value) {
            if (is_integer($key)) {
                $data[] = $value;
                continue;
            }
            $data[] = "`{$key}` = ?";
            $this->addParam($value);
        }
        return $this->command()
            ->update(implode(',', $data), $this->getWhere().$this->getLimit(), $this->get());
    }

    /**
     * 删除
     * @return int
     */
    public function delete() {
        return $this->command()
            ->delete($this->getWhere().$this->getLimit(), $this->get());
    }

    /***
     * 使用 model 中的方法
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments) {
        $method = 'scope'.Str::studly($name);
        array_unshift($arguments, $this);
        call_user_func_array([$this->getModel(), $method], $arguments);
        return $this;
    }
}