<?php
namespace Zodream\Domain\ThirdParty\Pay;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/17
 * Time: 15:22
 */
use Zodream\Domain\ThirdParty\ThirdParty;
use Zodream\Infrastructure\Disk\File;

abstract class BasePay extends ThirdParty  {
    const MD5 = 'MD5';
    const RSA = 'RSA';
    const RSA2 = 'RSA2';

    protected $signType = self::MD5;

    protected $key;

    /**
     * @var File
     */
    protected $caFile;

    /**
     * 商户私钥文件路径
     * @var File
     */
    protected $privateKeyFile;

    /**
     * @var File
     */
    protected $publicKeyFile;

    public function __construct(array $config = array()) {
        parent::__construct($config);
        if ($this->has('key')) {
            $this->key = $this->get('key');
        }
        if ($this->has('privateKeyFile')) {
            $this->setPrivateKeyFile($this->get('privateKeyFile'));
        }
        if ($this->has('publicKeyFile')) {
            $this->setPublicKeyFile($this->get('publicKeyFile'));
        }
        if ($this->has('caFile')) {
            $this->setCaFile($this->get('caFile'));
        }
        $this->setSignType($this->get('signType'));
    }
    
    public function setSignType($arg = null) {
        if (empty($arg)) {
            $arg = empty($this->privateKeyFile) ? self::MD5 : self::RSA;
        }
        $this->signType = strtoupper($arg);
        return $this;
    }

    public function getKey() {
        return $this->key;
    }

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    public function getPrivateKeyFile() {
        return $this->privateKeyFile;
    }

    public function setPrivateKeyFile($file) {
        $this->privateKeyFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

    public function getPublicKeyFile() {
        return $this->publicKeyFile;
    }

    public function setPublicKeyFile($file) {
        $this->publicKeyFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

    public function getCaFile() {
        return $this->caFile;
    }

    public function setCaFile($file) {
        $this->caFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

    /**
     * 获取带签名的参数
     * @param string $name
     * @param array $args
     * @return array
     */
    protected function getSignData($name, array $args = array()) {
        if (!array_key_exists($name, $this->apiMap)) {
            throw new \InvalidArgumentException('API ERROR');
        }
        $data = $this->getData($this->apiMap[$name][1], array_merge($this->get(), $args));
        $data['sign'] = $this->sign($data);
        return $data;
    }


    /**
     * @param array|string $args
     * @return string
     */
    abstract public function sign($args);

    /**
     * @param $args
     * @param $sign
     * @return bool
     */
    abstract public function verify(array $args, $sign);

    /**
     * @return mixed
     */
    abstract public function callback();
}