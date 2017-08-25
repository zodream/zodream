<?php
namespace Zodream\Infrastructure\Log;


use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Infrastructure\Disk\File;
use Zodream\Service\Factory;

class Logger extends ConfigObject {
    protected $configKey = 'logger';

    protected $configs = [
        'max' => 100000,  //单个文件最大
        'count' => 10     //最多多少个文件
    ];

    public function log($data, $event = 'LOG') {
        $this->getFile()->append(sprintf("\r\n[%s][%s]: %s", date('Y-m-d H:i:s'), $event, $data));
    }

    /**
     * @return File
     */
    protected function getFile() {
        return Factory::root()->childFile(sprintf('data/log/%s.log', date('Ymd')));
    }
}