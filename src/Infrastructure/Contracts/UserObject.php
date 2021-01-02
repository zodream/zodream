<?php
namespace Zodream\Infrastructure\Contracts;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/12
 * Time: 16:08
 */
interface UserObject {

    const BEFORE_LOGIN = 'before login';
    const AFTER_LOGIN = 'after login';
    const BEFORE_LOGOUT = 'before logout';
    const AFTER_LOGOUT = 'after logout';

    /**
     * 根据账号密码登录
     * @param $username
     * @param $password
     * @return UserObject
     */
    public static function findByAccount($username, $password);

    /**
     * 根据 主键获取用户
     * @param $id
     * @return UserObject
     */
    public static function findByIdentity($id);

    /**
     * api 时根据 api token 获取用户
     * @param $token
     * @return UserObject
     */
    public static function findByToken($token);

    /**
     * 根据 记住密码 token 获取用户
     * @param integer $id
     * @param string $token
     * @return UserObject
     */
    public static function findByRememberToken($id, $token);

    /**
     * 登录
     * @param bool $remember
     * @return mixed
     */
    public function login($remember = false);

    /**
     * 注销
     * @return mixed
     */
    public function logout();

    /**
     * 获取用户ID
     * @return int|string
     */
    public function getIdentity();

    /**
     * @return string
     */
    public function getRememberToken();

    /**
     * @param string $token
     * @return static
     */
    public function setRememberToken($token);
}