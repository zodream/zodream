<?php
namespace Zodream\Domain\ThirdParty\WeChat;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/6
 * Time: 19:37
 */
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Infrastructure\Security\BaseSecurity;

class Aes extends BaseSecurity {

    protected $key;

    protected $appId;

    protected $blockSize = 32;

    public function __construct($key, $appId = null) {
        $this->key = base64_decode($key . '=');
        $this->appId = $appId;
    }

    public function getAppId() {
        return $this->appId;
    }

    /**
     * ENCRYPT STRING
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        //获得16位随机字符串，填充到明文之前
        $random = StringExpand::random(16);
        $data = $random . pack("N", strlen($data)) . $data . $this->appId;
        // 网络字节序
        //$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $iv = substr($this->key, 0, 16);
        //使用自定义的填充方式对明文进行补位填充
        $data = $this->pkcs7Pad($data, $this->blockSize);
        mcrypt_generic_init($module, $this->key, $iv);
        //加密
        $data = mcrypt_generic($module, $data);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);

        //			print(base64_encode($data));
        //使用BASE64对加密后的字符串进行编码
        return base64_encode($data);
    }

    /**
     * DECRYPT STRING
     * @param string $data
     * @return string
     */
    public function decrypt($data) {
        //使用BASE64对需要解密的字符串进行解码
        $cipherTextDec = base64_decode($data);
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $iv = substr($this->key, 0, 16);
        mcrypt_generic_init($module, $this->key, $iv);
        //解密
        $decrypted = mdecrypt_generic($module, $cipherTextDec);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        //去除补位字符
        $result = $this->pkcs7UnPad($decrypted, $this->blockSize);
        //去除16位随机字符串,网络字节序和AppId
        if (strlen($result) < 16) {
            throw new \InvalidArgumentException('LENGTH < 16');
        }
        $content = substr($result, 16, strlen($result));
        $len_list = unpack('N', substr($content, 0, 4));
        $xml_len = $len_list[1];
        $xml_content = substr($content, 4, $xml_len);
        $fromAppId = substr($content, $xml_len + 4);
        if (!$this->appId)
            $this->appId = $fromAppId;
        return $xml_content;
    }
}