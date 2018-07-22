<?php
namespace Zodream\Infrastructure\Security;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/9
 * Time: 14:12
 */
class AuthCode extends BaseSecurity {
    protected $key;
    
    protected $expiry = 0;

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    public function getKey() {
        return $this->key;
    }

    public function setExpiry($key) {
        $this->expiry = $key;
        return $this;
    }

    public function geExpiry() {
        return $this->key;
    }

    public function encrypt($data) {
        return $this->make($data, false);
    }

    public function decrypt($data) {
        return $this->make($data, true);
    }

    //************************加密解密*************************/
    /*
    * $string： 明文 或 密文
    * $operation：DECODE表示解密,其它表示加密
    * $key： 密匙
    * $expiry：密文有效期
    * */
    protected function make($string, $isDecrypt) {
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        $keyLength = 4;

        // 密匙
        $key = md5($this->key);
        // 密匙a会参与加解密
        $keyA = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证
        $keyB = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文
        $keyC = $keyLength ? ($isDecrypt ? substr($string, 0, $keyLength): substr(md5(microtime()), -$keyLength)) : '';
        // 参与运算的密匙
        $cryptKey = $keyA.md5($keyA.$keyC);
        $key_length = strlen($cryptKey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyB(密匙b)，解密时会通过这个密匙验证数据完整性
        // 如果是解码的话，会从第$keyLength位开始，因为密文前$keyLength位保存 动态密匙，以保证解密正确
        $string = $isDecrypt ? base64_decode(substr($string, $keyLength)) : sprintf('%010d', $this->expiry ? $this->expiry + time() : 0).substr(md5($string.$keyB), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndKey = array();
        // 产生密匙簿
        for($i = 0; $i <= 255; $i++) {
            $rndKey[$i] = ord($cryptKey[$i % $key_length]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndKey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($isDecrypt) {
            // substr($result, 0, 10) == 0 验证数据有效性
            // substr($result, 0, 10) - time() > 0 验证数据有效性
            // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyB), 0, 16) 验证数据完整性
            // 验证数据有效性，请看未加密明文的格式
            if((substr($result, 0, 10) == 0 || 
                    substr($result, 0, 10) - time() > 0) && 
                substr($result, 10, 16) == substr(md5(substr($result, 26).$keyB), 0, 16)) {
                return substr($result, 26);
            } 
            return '';
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyC.str_replace('=', '', base64_encode($result));
        }
    }
//************************加密解密结束***********************/
}