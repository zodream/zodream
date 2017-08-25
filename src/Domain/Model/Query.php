<?php
namespace Zodream\Domain\Model;

use Zodream\Infrastructure\Database\Query\Query as BaseQuery;

class Query extends BaseQuery {

    protected $modelName;
    protected $isArray = false;

    public function setModelName($model) {
        if ($model instanceof Model) {
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
            $model->set($item);
            $model->isNewRecord = false;
            $args[] = $model;
        }
        return $args;
    }

    public function scalar() {
        $this->asArray();
        return parent::scalar();
    }
}