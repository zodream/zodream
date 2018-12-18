<?php
declare(strict_types = 1);

namespace Zodream\Service;

class Web extends Application {

    protected function formatUri(string $path): string {
        if ($path === '') {
            $path = $this['url']->getVirtualUri();
        }
        return $this->getRealPath($path);
    }

    protected function getRealPath(string $path): string {
        list($path, $args) = $this['url']->deRewrite($path);
        if (!empty($args) && is_array($args)) {
            $this['request']->append($args);
        }
        return $path;
    }
}