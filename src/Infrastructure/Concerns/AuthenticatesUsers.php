<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Concerns;

use Exception;

trait AuthenticatesUsers {
    use RedirectsUsers;

    protected function sendLoginResponse() {
        return $this->authenticated(auth()::user())
            ?: response()->redirect($this->redirectPath());
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
        return response()->redirect('/');
    }
}