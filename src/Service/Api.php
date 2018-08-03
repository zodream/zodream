<?php
declare(strict_types = 1);

namespace Zodream\Service;

class Api extends Web {
    protected function formatUri(string $path): string {
        if (is_null($path)) {
            $path = $this['url']->getVirtualUri();
        }
        return preg_replace('#^/?'.$this->version().'#i', '', $path);
    }
}