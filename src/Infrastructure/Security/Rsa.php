<?php
namespace Zodream\Infrastructure\Security;



use Zodream\Disk\File;

class Rsa extends BaseSecurity {
    protected $privateKey;

    protected $publicKey;

    public function setPublicKey($key) {
        if ($key instanceof File) {
            $key = $key->read();
        }
        if (strpos($key, 'PUBLIC KEY') === false) {
            $key = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($key, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }
        $this->publicKey = openssl_pkey_get_public($key);
        return $this;
    }

    public function setPrivateKey($key, $password = '') {
        if ($key instanceof File) {
            $key = $key->read();
        }
        if (strpos($key, 'PRIVATE KEY') === false) {
            $key = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($key, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }
        $this->privateKey = openssl_pkey_get_private($key, $password);
        return $this;
    }

    public function getPublicKey() {
        return $this->publicKey;
    }

    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * 私钥加密
     * @param string $data 要加密的数据
     * @return string 加密后的字符串
     */
    public function privateKeyEncrypt($data) {
        if (empty($this->privateKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_private_encrypt($data, $encrypted, $this->privateKey)) {
            return false;
        }
        return base64_encode($encrypted);
    }

    /**
     * 公钥加密
     * @param string $data 要加密的数据
     * @return string 加密后的字符串
     */
    public function publicKeyEncrypt($data) {
        if (empty($this->publicKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_public_encrypt($data, $encrypted, $this->publicKey)) {
            return false;
        }
        return base64_encode($encrypted);
    }

    /**
     * 用公钥解密私钥加密内容
     * @param string $data 要解密的数据
     * @return string 解密后的字符串
     */
    public function decryptPrivateEncrypt($data) {
        if (empty($this->privateKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_public_decrypt(base64_decode($data), $encrypted, $this->publicKey)) {
            return false;
        }
        return $encrypted;
    }
    /**
     * 用私钥解密公钥加密内容
     * @param string $data  要解密的数据
     * @return string 解密后的字符串
     */
    public function decryptPublicEncrypt($data) {
        if (empty($this->privateKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_private_decrypt(base64_decode($data), $encrypted, $this->privateKey)) {
            return false;
        }
        return $encrypted;
    }

    /**
     * ENCRYPT STRING
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        return $this->publicKeyEncrypt($data);
    }

    /**
     * DECRYPT STRING
     * @param string $data
     * @return string
     */
    public function decrypt($data) {
        return $this->decryptPublicEncrypt($data);
    }
}