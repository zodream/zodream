<?php
declare(strict_types = 1);

namespace Zodream\Domain\Access;
/**
 * AUTH CONTROL
 *
 * @author Jason
 */
use Zodream\Infrastructure\Cookie;
use Zodream\Infrastructure\Interfaces\AuthObject;
use Zodream\Infrastructure\Interfaces\UserObject;
use Zodream\Helpers\Str;
use Zodream\Service\Factory;

class Auth implements AuthObject {

	/**
	 * @var bool|UserObject
	 */
	protected $identity = false;

    /**
     * @param bool $refresh
     * @return bool|UserObject
     * @throws \Exception
     */
	public function getIdentity(bool $refresh = false) {
		if ($this->identity === false || $refresh) {
			$this->identity = $this->getUser();
		}
		return $this->identity;
	}

    /**
     * 获取 session key
     * @return string
     */
    public function getName(): string {
        return 'login_'.config('auth.session_key', 'user').'_'.sha1(static::class);
    }

    /**
     * 获取 记住我 cookie key
     * @return string
     */
    public function getRememberName(): string {
        return 'remember_'.config('auth.session_key', 'user').'_'.sha1(static::class);
    }

    /**
     * @return UserObject
     * @throws \Exception
     */
	protected function getUser() {
	    $userClass = config('auth.model');
	    if (empty($userClass)) {
	        return null;
        }
        $key = $this->getName();
	    $id = Factory::session()->get($key);
	    if (!empty($id)) {
            return call_user_func($userClass.'::findByIdentity', $id);
        }
        list($id, $token) = $this->getRememberToken();
	    if (!empty($token)) {
            return call_user_func($userClass.'::findByRememberToken',
                $id, $token);
        }
        return null;
    }

    protected function getRememberToken(): array {
	    $token = Cookie::get($this->getRememberName());
	    if (empty($token) || strpos($token, '|') === false) {
	        return [0, null];
        }
        list($id, $token) = explode('|', $token, 2);
	    if (empty($id) || empty($token)) {
	        return [0, null];
        }
	    return [$id, $token];
    }

    /**
     * @param UserObject $user
     */
    protected function setRememberToken(UserObject $user) {
        if (empty($user->getRememberToken())) {
            $user->setRememberToken(Str::random(60));
        }
        Cookie::forever($this->getRememberName(), $user->getIdentity().'|'. $user->getRememberToken());
    }

    /**
     * 取消永久登录
     */
    protected function cancelRememberToken() {
        Cookie::forget($this->getRememberName());
    }

    /**
     * 设置用户
     * @param UserObject $user
     */
    public function setUser(UserObject $user) {
	    $this->identity = $user;
    }

    /**
     * 用户id
     * @return int
     * @throws \Exception
     */
	public function id(): int {
	    if (empty($this->user())) {
	        return 0;
        }
        return intval($this->user()->getIdentity());
    }

    /**
     * 获取登录
     * @return UserObject
     * @throws \Exception
     */
	public function user() {
		if (!empty($this->getIdentity())) {
			return $this->identity;
		}
		return null;
	}

    /**
     * 判断是否是游客
     * @return bool
     * @throws \Exception
     */
	public function guest(): bool {
		return empty($this->getIdentity());
	}

    /**
     * 登录
     * @param UserObject $user
     * @param bool $remember
     * @throws \Exception
     */
    public function login(UserObject $user, $remember = false) {
        $this->updateSession($user->getIdentity());
        if ($remember) {
            $this->setRememberToken($user);
        }
        $this->setUser($user);
    }

    /**
     * Update the session with the given ID.
     *
     * @param  string $id
     * @return void
     * @throws \Exception
     */
    protected function updateSession($id) {
        Factory::session()->set($this->getName(), $id);
    }

    /**
     * 登出
     * @throws \Exception
     */
    public function logout() {
        if (empty($this->user())) {
            return;
        }
        $this->user()
            ->setRememberToken(Str::random(60));
        Factory::session()->destroy();
        //throw new AuthenticationException();
    }
}