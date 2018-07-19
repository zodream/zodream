<?php
declare(strict_types = 1);

namespace Zodream\Service;

class Api extends Web {
    public function setPath($path) {
        if (is_null($path)) {
            $path = Url::getVirtualUri();
        }
        return parent::setPath(preg_replace('#^/?'.$this->version().'#i', '', $path));
    }
}