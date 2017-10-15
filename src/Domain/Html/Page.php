<?php
namespace Zodream\Domain\Html;

use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Database\Query\Query;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Interfaces\ArrayAble;
use Zodream\Infrastructure\Interfaces\JsonAble;
use Zodream\Helpers\JsonExpand;
use  JsonSerializable;
use ArrayIterator;

class Page extends MagicObject implements JsonAble, ArrayAble {
	private $_total = 0;

	private $_index = 1;
	
	private $_pageSize = 20;

	private $_key = 'page';

	public function __construct($total, $pageSize = 20, $key = 'page') {
		$this->setTotal($total);
		$this->_key = $key;
		$this->_index = max(1, Request::get($key, 1));
		$this->_pageSize = $pageSize;
	}

	/**
	 * 获取总共的数据
	 * @return int
	 */
	public function getTotal() {
		return $this->_total;
	}

	/**
	 * 设置总共的数据
	 * @param $total
	 * @return $this
	 */
	public function setTotal($total) {
		if ($total instanceof Query) {
			$this->_total = intval($total->count());
			return $this;
		}
		$this->_total = intval($total);
		return $this;
	}

	/**
	 * 获取一页的数据
	 * @return array
	 */
	public function getPage() {
		return $this->getAttribute();
	}

	/**
	 * 设置一页的数据
	 * @param array|Query $data
	 * @return $this
	 */
	public function setPage($data) {
		if ($data instanceof Query) {
			$data = $data->limit($this->getLimit())->all();
		}
		$this->setAttribute($data);
		return $this;
	}

	/**
	 * 获取一页数据的长度
	 * @return int
	 */
	public function getPageCount() {
		return $this->count();
	}

	/**
	 * 获取查询分页的值
	 * @return mixed
	 */
	public function getLimit() {
		return max(($this->_index- 1) * $this->_pageSize, 0) . ','.$this->_pageSize;
	}

    /**
     * 是否还有更多
     * @return bool
     */
	public function hasMore() {
	    return $this->_index * $this->_pageSize >= $this->_total;
    }

	/**
	 * 获取分页链接
	 * @param array $option
	 * @return string
	 * @throws \Exception
	 */
	public function getLink($option = array()) {
		$option['total'] = $this->_total;
		$option['pageSize'] = $this->_pageSize;
		$option['page'] = $this->_index;
		$option['key'] = $this->_key;
		return PageLink::show($option);
	}
	
	public function __toString() {
		return $this->getLink();
	}

    public function jsonSerialize() {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof JsonAble) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof ArrayAble) {
                return $value->toArray();
            } else {
                return $value;
            }
        }, $this->toArray());
    }

    public function getIterator() {
        return new ArrayIterator($this->getAttribute());
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray() {
        return [
            'total' => $this->getTotal(),
            'page' => $this->_index,
            'pageSize' => $this->_pageSize,
            'key' => $this->_key,
            'pagelist' => $this->getAttribute()
        ];
    }
}