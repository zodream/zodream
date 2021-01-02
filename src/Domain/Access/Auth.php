<?php
declare(strict_types = 1);

namespace Zodream\Domain\Access;
/**
 * AUTH CONTROL
 *
 * @author Jason
 */
use Zodream\Infrastructure\Contracts\AuthObject;
use Zodream\Infrastructure\Contracts\HttpContext;
use Zodream\Infrastructure\Contracts\UserObject;
use Zodream\Helpers\Str;

class Auth implements AuthObject {

	/**
	 * @var bool|UserObject
	 */
	protected $identity = false;

    /**
     * @var HttpContext
     */
	protected $app;

	public function __construct(HttpContext $context) {
	    $this->app = $context;
    }

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
     * 获取用户记住我的token
     * @param UserObject $user
     * @return string
     */
    protected function getRememberTokenFromUser(UserObject $user) {
        return $user->getRememberToken();
    }

    /**
     * 设置用户的永久token
     * @param UserObject $user
     * @param $token
     */
    protected function setRememberTokenFromUser(UserObject $user, $token) {
        $user->setRememberToken($token);
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
	    $id = session()->get($key);
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
	    $token = $this->app->make('request')
            ->cookie($this->getRememberName());
	    if (empty($token) || !str_contains($token, '|')) {
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
        if (empty($this->getRememberTokenFromUser($user))) {
            $this->setRememberTokenFromUser($user, Str::random(60));
        }
        $this->app->make('response')->cookie($this->getRememberName(), $user->getIdentity().'|'. $user->getRememberToken(), 2628000 * 60);
    }

    /**
     * 取消永久登录
     */
    protected function cancelRememberToken() {
        $this->app->make('response')->cookie($this->getRememberName(), '', -2628000 * 60);
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
        session()->set($this->getName(), $id);
    }

    /**
     * 登出
     * @throws \Exception
     */
    public function logout() {
        if (empty($this->user())) {
            return;
        }
        $this->setRememberTokenFromUser($this->user(), Str::random(60));
        session()->destroy();
        //throw new AuthenticationException();
    }
}