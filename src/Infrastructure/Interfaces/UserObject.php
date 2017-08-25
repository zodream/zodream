<?php
namespace Zodream\Infrastructure\Interfaces;
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
     * 登录
     * @param $user
     * @return mixed
     */
    public function login($user);

    /**
     * 注销
     * @return mixed
     */
    public function logout();

    /**
     * 获取用户ID
     * @return int|string
     */
    public function getId();
}