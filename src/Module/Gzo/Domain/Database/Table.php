<?php
namespace Zodream\Module\Gzo\Domain\Database;

use Zodream\Infrastructure\Database\Schema\Table as BaseTable;
use Zodream\Infrastructure\Support\Collection;
use Zodream\Module\Gzo\Domain\InformationSchemaModel;

class Table extends BaseTable {

    public function length() {
        return $this->_data['Data_length'];
    }

    public function maxLength() {
        return $this->_data['Max_data_length'];
    }

    public function rows() {
        return $this->_data['Rows'];
    }

    public function version() {
        return $this->_data['Version'];
    }

    public function collation() {
        return $this->_data['Collation'];
    }

    public function map(callable $func) {
        $data = InformationSchemaModel::column()->where(['TABLE_SCHEMA' => $this->schema->getSchema()])
        ->andWhere(['TABLE_NAME' => $this->getTableName()])->all();
        (new Collection($data))->each(function($item) use ($func) {
            $func((new Column($this, $item['COLUMN_NAME']))
                ->setData($item));
        });
    }
}