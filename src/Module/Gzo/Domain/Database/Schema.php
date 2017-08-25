<?php
namespace Zodream\Module\Gzo\Domain\Database;

use Zodream\Infrastructure\Database\Schema\Schema as BaseSchema;
use Zodream\Infrastructure\Support\Collection;

class Schema extends BaseSchema {

    public function map(callable $func) {
        $data = static::getAllTable(true);
        (new Collection($data))->each(function($item) use ($func) {
            $func((new Table($item['Name'], $item))
                ->setComment($item['Comment'])
                ->setEngine($item['Engine'])
                ->setSchema($this));
        });
    }
}