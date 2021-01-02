<?php
namespace Zodream\Infrastructure\Concerns;

trait RedirectsUsers {

    /**
     * 跳转
     * @return string
     */
    public function redirectPath() {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }
        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/';
    }
}