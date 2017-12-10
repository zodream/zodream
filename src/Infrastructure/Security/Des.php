<?php
namespace Zodream\Infrastructure\Security;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/9
 * Time: 11:55
 */
class Des extends BaseSecurity {
    protected $key;

    /**
     * @var string MCRYPT_RIJNDAEL_128、MCRYPT_RIJNDAEL_192、MCRYPT_RIJNDAEL_256
     */
    protected $size = MCRYPT_RIJNDAEL_256;

    public function setKey($key) {
        $this->key = md5($key);
        return $this;
    }

    public function getKey() {
        return $this->key;
    }

    public function setSize($size) {
        $this->size = $size;
        return $this;
    }

    public function getSize() {
        return $this->size;
    }

    /**
     * @param $data
     * @return string
     */
    public function encrypt($data) {
        return base64_encode(
            mcrypt_encrypt(
                $this->size,
                $this->key,
                $data,
                MCRYPT_MODE_ECB,
                $this->createIv()
            )
        );
    }

    public function decrypt($data) {
        $arg = mcrypt_decrypt(
            $this->size,
            $this->key,
            base64_decode($data),
            MCRYPT_MODE_ECB,
            $this->createIv()
        );
        for ($i = strlen($arg)- 1; $i >= 0; $i --) {
            if (ord($arg[$i]) > 0) {
                $arg = substr($arg, 0, $i + 1);
                break;
            }
        }
        return $arg;
    }

    public function createIv() {
        $iv_size = mcrypt_get_iv_size($this->size, MCRYPT_MODE_ECB);
        return mcrypt_create_iv($iv_size, MCRYPT_RAND);
    }
}