<?php
declare(strict_types = 1);

namespace Zodream\Service;

use Zodream\Infrastructure\Http\URL;

class Api extends Web {
    protected function formatUri(string $path): string {
        if (is_null($path)) {
            $path = URL::getVirtualUri();
        }
        return preg_replace('#^/?'.$this->version().'#i', '', $path);
    }
}