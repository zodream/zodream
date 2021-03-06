<?php
namespace Zodream\Infrastructure\Session;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/6
 * Time: 9:56
 */

class CacheSession extends Session {

    public function useCustomStorage() {
        return true;
    }

    public function readSession($id) {
        $data = cache()->get($this->calculateKey($id));
        return $data === false ? '' : $data;
    }


    public function writeSession($id, $data) {
        cache()->set($this->calculateKey($id), $data, $this->getTimeout());
    }

    public function destroySession($id) {
        return cache()->delete($this->calculateKey($id));
    }

    /**
     * 0 表示永久
     * @return int
     */
    public function getTimeout() {
        return 0;
    }

    protected function calculateKey($id) {
        return json_encode([__CLASS__, $id]);
    }
}