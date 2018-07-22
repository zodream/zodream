<?php
namespace Zodream\Infrastructure\Security;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/9
 * Time: 12:14
 */
class Aes extends BaseSecurity {
    protected $key;

    public function setKey($key) {
        $this->key = md5($key);
        return $this;
    }

    public function getKey() {
        return $this->key;
    }

    public function encrypt($input) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = $this->pkcs5Pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $this->key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return base64_encode($data);
    }

    public function decrypt($data) {
        $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $this->key,
            base64_decode($data),
            MCRYPT_MODE_ECB
        );
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        return substr($decrypted, 0, -$padding);
    }
}