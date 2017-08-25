<?php
namespace Zodream\Module\Gzo\Domain\Database;

use Zodream\Infrastructure\Database\Schema\Column as BaseColumn;

class Column extends BaseColumn {
    private $_data = [];

    public function setData(array $data) {
        $this->_data = $data;
        return $this;
    }

    public function type() {
        return $this->_data['DATA_TYPE'];
    }

    public function length() {
        return $this->_data['NUMERIC_PRECISION'];
    }

    public function maxLength() {
        return $this->_data['CHARACTER_MAXIMUM_LENGTH'];
    }

    public function getDefault() {
        return $this->_data['COLUMN_DEFAULT'];
    }

    public function isPK() {
        return $this->_data['COLUMN_KEY'] == 'PRI';
    }

    public function canNull() {
        return $this->_data['IS_NULLABLE'] != 'NO';
    }

    public function isAuto() {
        return $this->_data['EXTRA'] == 'auto_increment';
    }

    public function getComment() {
        return $this->_data['COLUMN_COMMENT'];
    }
}