<?php
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
use Zodream\Service\Config;
use Zodream\Service\Factory;

class Auth implements AuthObject {

	/**
	 * @var bool|UserObject
	 */
	protected static $identity = false;

	/**
	 * @param bool $refresh
	 * @return bool|UserObject
	 */
	public static function getIdentity($refresh = false) {
		if (static::$identity === false || $refresh) {
			static::$identity = static::getUser();
		}
		return static::$identity;
	}

    /**
     * 获取 session key
     * @return string
     */
    public static function getName() {
        return 'login_'.Config::auth('session_key', 'user').'_'.sha1(static::class);
    }

    /**
     * 获取 记住我 cookie key
     * @return string
     */
    public static function getRememberName() {
        return 'remember_'.Config::auth('session_key', 'user').'_'.sha1(static::class);
    }

    /**
     * @return UserObject
     */
	protected static function getUser() {
	    $userClass = Config::auth('model');
	    if (empty($userClass)) {
	        return null;
        }
        $key = static::getName();
	    $id = Factory::session()->get($key);
	    if (!empty($id)) {
            return call_user_func($userClass.'::findByIdentity', $id);
        }
        $token = static::getRememberToken();
	    if (!empty($token)) {
            return call_user_func_array($userClass.'::findByRememberToken',
                $token);
        }
        return null;
    }

    protected static function getRememberToken() {
	    $token = Cookie::get(static::getRememberName());
	    if (empty($token) && strpos($token, '|') === false) {
	        return null;
        }
        list($id, $token) = explode('|', $token, 2);
	    if (empty($id) || empty($token)) {
	        return null;
        }
	    return [$id, $token];
    }

    /**
     * @param UserObject $user
     */
    protected static function setRememberToken(UserObject $user) {
        if (empty($user->getRememberToken())) {
            $user->setRememberToken(Str::random(60));
        }
        Cookie::forever(static::getRememberName(), $user->getIdentity().'|'. $user->getRememberToken());
    }

    /**
     * 设置用户
     * @param UserObject $user
     */
    public static function setUser(UserObject $user) {
	    static::$identity = $user;
    }

    /**
     * 用户id
     * @return int
     */
	public static function id() {
	    if (empty(static::user())) {
	        return false;
        }
        return static::user()->getIdentity();
    }

	/**
	 * 获取登录
	 * @return bool|UserObject
	 */
	public static function user() {
		if (!empty(static::getIdentity())) {
			return static::$identity;
		}
		return false;
	}

	/**
	 * 判断是否是游客
	 * @return bool
	 */
	public static function guest() {
		return empty(static::getIdentity());
	}

    public static function login(UserObject $user, $remember = false) {
        static::updateSession($user->getIdentity());
        if ($remember) {
            static::setRememberToken($user);
        }
        static::setUser($user);
    }

    /**
     * Update the session with the given ID.
     *
     * @param  string  $id
     * @return void
     */
    protected static function updateSession($id) {
        Factory::session()->set(static::getName(), $id);
    }

    /**
     * 登出
     */
    public static function logout() {
        if (empty(static::user())) {
            return;
        }
        static::user()
            ->setRememberToken(Str::random(60));
        Factory::session()->destroy();
        //throw new AuthenticationException();
    }
}