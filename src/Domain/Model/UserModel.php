<?php
namespace Zodream\Domain\Model;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/12
 * Time: 16:13
 */
use Zodream\Infrastructure\Interfaces\UserObject;
use Zodream\Service\Factory;

abstract class UserModel extends Model implements UserObject {
    
    public function getId() {
        $id = 'id';
        if (in_array($id, $this->primaryKey)) {
            $id = current($this->primaryKey);
        }
        return $this->get($id);
    }

    public function login($user) {
        $this->invoke(static::BEFORE_LOGIN, [$this]);
        Factory::session()->set('user', $user);
        $this->invoke(static::AFTER_LOGIN, [$this]);
        return true;
    }
    
    public function logout() {
        $this->invoke(static::BEFORE_LOGOUT, [$this]);
        Factory::session()->destroy();
        $this->invoke(static::AFTER_LOGOUT, [$this]);
        unset($this);
        return true;
    }

}