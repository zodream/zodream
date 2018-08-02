<?php
namespace Zodream\Infrastructure\Traits;

use Zodream\Domain\Access\Auth;
use Zodream\Service\Factory;
use Exception;

trait AuthenticatesUsers {
    use RedirectsUsers;

    protected function sendLoginResponse() {
        return $this->authenticated(Factory::user())
            ?: Factory::response()->redirect($this->redirectPath());
    }

    protected function authenticated($user) {
        //
    }


    protected function sendFailedLoginResponse() {
        throw new Exception(
            __('auth failed')
        );
    }

    public function logout() {
        if (!auth()->guest()) {
            auth()->user()->logout();
        }
        return Factory::response()->redirect('/');
    }
}