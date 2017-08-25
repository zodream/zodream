<?php
namespace Zodream\Domain\Model\Concerns;

use Zodream\Domain\Model\Model;
use Zodream\Domain\Model\Query;

/**
 * Created by PhpStorm.
 * User: ZoDream
 * Date: 2017/5/7
 * Time: 14:21
 */
trait HasRelation {
    /**
     * GET RELATION
     * @var array
     */
    protected $relations = [];

    /**
     * @param string $table
     * @param string $link $table.$link
     * @param string $key $this.$key
     * @return array|Model
     */
    public function hasOne($table, $link, $key = null) {
        if ($table instanceof Model) {
            $table = $table->className();
        }
        if (!array_key_exists($table, $this->relations)) {
            $this->setRelation($table, $this->getRelationQuery($table)
                ->where($this->getRelationWhere($link, $key))
                ->one());
        }
        return $this->getRelation($table);
    }

    /**
     * GET RELATION WHERE SQL
     * @param string|array $links
     * @param string $key
     * @return array
     */
    protected function getRelationWhere($links, $key = null) {
        if (is_null($key) && !is_array($links)) {
            $key = in_array('id', $this->primaryKey) ? 'id' : current($this->primaryKey);
        }
        if (!is_array($links)) {
            $links = [$links => $key];
        }
        foreach ($links as &$item) {
            $item = $this->get($item);
        }
        return $links;
    }

    /**
     * GET RELATION QUERY
     * @param static $table
     * @return Query
     */
    protected function getRelationQuery($table) {
        $query = new Query();
        if (class_exists($table)) {
            return $query->setModelName($table)
                ->from(call_user_func($table.'::tableName'));
        }
        return $query->from($table);
    }

    /**
     * @param string $table
     * @param string $link $table.$link
     * @param string $key $this.$key
     * @return array|Model[]
     */
    public function hasMany($table, $link, $key = 'id') {
        if ($table instanceof Model) {
            $table = $table->className();
        }
        if (!array_key_exists($table, $this->relations)) {
            $this->setRelation($table, $this->getRelationQuery($table)
                ->where($this->getRelationWhere($link, $key))
                ->all());
        }
        return $this->getRelation($table);
    }

    /**
     * Get a specified relationship.
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation) {
        return $this->relations[$relation];
    }

    /**
     * Set the specific relationship in the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value) {
        $this->relations[$relation] = $value;
        return $this;
    }
}