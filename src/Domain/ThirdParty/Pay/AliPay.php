<?php
namespace Zodream\Domain\ThirdParty\Pay;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/17
 * Time: 15:21
 */
use Zodream\Infrastructure\Error\FileException;
use Zodream\Infrastructure\Http\Component\Uri;

class AliPay extends BasePay {

    /**
     * EXAMPLE:
    'alipay' => array(
        'app_id' => '',
        'app_auth_token' => '',
        'privateKeyFile' => '/alipay/rsa_private_key.pem',
        'publicKeyFile' => '/alipay/alipay_rsa_public_key.pem',
        'notify_url' => ''
    )
     * @var string
     */
    protected $configKey = 'alipay';

    /**
     * 支付宝公钥
     * @var string
     */
    protected $publicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDIgHnOn7LLILlKETd6BFRJ0GqgS2Y3mn1wMQmyh9zEyWlz5p1zrahRahbXAfCfSqshSNfqOmAQzSHRVjCqjsAw1jyqrXaPdKBmr90DIpIxmIyKXv4GGAkPyJ/6FTFY99uhpiq0qadD/uSzQsefWo0aTvP/65zi3eof7TcZ32oWpwIDAQAB';

    protected $apiMap = [
        'query' => [
            'https://openapi.alipay.com/gateway.do',
            [
                '#app_id',
                'method' => 'alipay.trade.query',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA',
                'sign',
                '#timestamp', // yyyy-MM-dd HH:mm:ss,
                'version' => '1.0',
                'app_auth_token',
                '#biz_content' => [
                    [
                        'out_trade_no',
                        'trade_no'
                    ]
                ]
            ]
        ],
        'webPay' => [
            'https://mapi.alipay.com/gateway.do', // 即时支付
            [
                'service' => 'create_direct_pay_by_user',
                '#partner',
                '_input_charset' => 'utf-8',
                'sign_type' => 'MD5', //DSA、RSA、MD5
                'sign',
                'notify_url',
                'return_url',
                '#out_trade_no',
                '#subject',
                'payment_type' => 1,
                '#total_fee', // 2位小数
                [
                    'seller_id',
                    'seller_email',
                    'seller_account_name'
                ],
                'buyer_id',
                'buyer_email',
                'buyer_account_name',

                'price',
                'quantity',
                'body',
                'show_url',
                'paymethod',
                'enable_paymethod',
                'anti_phishing_key',
                'exter_invoke_ip',
                'extra_common_param',
                'it_b_pay' => '1h',
                'token',
                'qr_pay_mode',
                'qrcode_width',
                'need_buyer_realnamed',
                'promo_param',
                'hb_fq_param',
                'goods_type'
            ]
        ],
        'pay' => [
            'https://openapi.alipay.com/gateway.do',
            [
                '#app_id',
                'method' => 'alipay.trade.app.pay',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA',
                'sign',
                '#timestamp', // yyyy-MM-dd HH:mm:ss,
                'version' => '1.0',
                '#notify_url',
                'app_auth_token',
                '#biz_content' => [
                    'body',
                    'scene' => 'bar_code',   //bar_code,wave_code
                    '#auth_code',
                    'discountable_amount',
                    'undiscountable_amount',
                    'extend_params',
                    'royalty_info',
                    'sub_merchant',
                    '#subject',
                    '#out_trade_no',
                    '#total_amount',    // 只能有两位小数
                    'seller_id'
                ]
            ]
        ],
        'appPay' => [    //app
            '',
            [
                '#app_id',
                'method' => 'alipay.trade.app.pay',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA',
                'sign',
                '#timestamp', // yyyy-MM-dd HH:mm:ss,
                'version' => '1.0',
                '#notify_url',
                '#biz_content' => [
                    'body',
                    '#subject',
                    '#out_trade_no',
                    'timeout_express',
                    '#total_amount',  // 只能有两位小数
                    'seller_id',
                    'product_code' => 'QUICK_MSECURITY_PAY'
                ]
            ]
        ],
        'mobilePay' => [
            '',
            [
                'service' => 'mobile.securitypay.pay',
                '#partner',
                '_input_charset' => 'UTF-8',
                'sign_type' => 'RSA',
                'sign',
                '#notify_url',
                'app_id',
                'appenv',
                '#out_trade_no',
                '#subject',
                'payment_type' => 1,
                '#seller_id',
                '#total_fee',
                '#body',
                'goods_type' => 1,
                'hb_fq_param',
                'rn_check',
                'it_b_pay' => '90m',
                'extern_token',
                'promo_params'
            ]
        ]
    ];

    public function __construct(array $config = array()) {
        parent::__construct($config);
        if ($this->has('publicKey')) {
            $this->publicKey = $this->get('publicKey');
        }
        $this->set('sign_type', $this->signType);
    }

    protected function getSignData($name, array $args = array()) {
        if (!array_key_exists($name, $this->apiMap)) {
            throw new \InvalidArgumentException('API ERROR');
        }
        $data = $this->getData($this->apiMap[$name][1], array_merge($this->get(), $args));
        if (array_key_exists('sign_type', $data)) {
            $this->signType = $data['sign_type'] =
                ($data['sign_type'] == static::RSA
                && !empty($this->privateKeyFile)) ? static::RSA : static::MD5;
        }
        if (array_key_exists('biz_content', $data)) {
            $data['biz_content'] = $this->encodeContent($data['biz_content']);
        }
        $data['sign'] = $this->sign($data);
        return $data;
    }

    /**
     * 加密方法
     * @param string $str
     * @return string
     */
    protected function encrypt($str){
        //AES, 128 模式加密数据 CBC
        $screct_key = base64_decode($this->key);
        $str = trim($str);
        $str = addPKCS7Padding($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), 1);
        $encrypt_str =  mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_CBC);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     * @param string $str
     * @return string
     */
    function decrypt($str){
        //AES, 128 模式加密数据 CBC
        $str = base64_decode($str);
        $screct_key = base64_decode($this->key);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), 1);
        $encrypt_str =  mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_CBC);
        $encrypt_str = trim($encrypt_str);

        $encrypt_str = stripPKSC7Padding($encrypt_str);
        return $encrypt_str;

    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    protected function addPKCS7Padding($source){
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }
    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    protected function stripPKSC7Padding($source){
        $source = trim($source);
        $char = substr($source, -1);
        $num = ord($char);
        if($num==62)return $source;
        $source = substr($source,0,-$num);
        return $source;
    }

    public function rsaEncrypt($data) {
        //转换为openssl格式密钥
        $res = openssl_get_publickey($this->publicKeyFile->read());
        $blocks = $this->splitCN($data, 0, 30, 'utf-8');
        $chrtext  = null;
        $encodes  = array();
        foreach ($blocks as $n => $block) {
            if (!openssl_public_encrypt($block, $chrtext , $res)) {
                throw new \InvalidArgumentException(openssl_error_string());
            }
            $encodes[] = $chrtext ;
        }
        $chrtext = implode(',', $encodes);

        return $chrtext;
    }

    public function rsaDecrypt($data, $rsaPrivateKeyPem, $charset) {
        //读取私钥文件
        $priKey = file_get_contents($rsaPrivateKeyPem);
        //转换为openssl格式密钥
        $res = openssl_get_privatekey($priKey);
        $decodes = explode(',', $data);
        $strnull = '';
        $dcyCont = '';
        foreach ($decodes as $n => $decode) {
            if (!openssl_private_decrypt($decode, $dcyCont, $res)) {
                throw new \InvalidArgumentException(openssl_error_string());
                return '';
            }
            $strnull .= $dcyCont;
        }
        return $strnull;
    }

    /**
     * 生成请求参数的签名
     *
     * @param string|array $content
     * @return string
     * @throws FileException
     */
    public function sign($content) {
        if (is_array($content)) {
            $content = $this->getSignContent($content);
        }
        if ($this->signType == self::MD5
            || empty($this->privateKeyFile)) {
            return md5($content.$this->key);
        }
        if ($this->signType != self::RSA
            && $this->signType != self::RSA2) {
            return null;
        }
        if (!$this->privateKeyFile->exist()) {
            throw new FileException('私钥文件不存在');
            return '';
        }
        $res = openssl_get_privatekey($this->privateKeyFile->read());
        openssl_sign($content, $sign, $res,
            $this->signType == self::RSA ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        //base64编码
        return base64_encode($sign);
    }

    public function verify(array $params, $sign) {
        if (array_key_exists('sign_type', $params)) {
            $this->signType = strtoupper($params['sign_type']);
        }
        $content = $this->getSignContent($params);
        $result = $this->verifyContent($content, $sign);
        if (!$result && strpos($content, '\\/') > 0) {
            $content = str_replace('\\/', '/', $content);
            return $this->verifyContent($content, $sign);
        }
        return $result;
    }

    public function verifyContent($content, $sign) {
        if ($this->signType == self::MD5) {
            return md5($content. $this->key) == $sign;
        }
        return $this->checkRsa($content, $sign);
    }

    /**
     * 根据公钥地址验签
     * @param string $content
     * @param string $sign
     * @return bool
     * @throws FileException
     */
    protected function checkRsaByFile($content, $sign) {
        if (!$this->publicKeyFile->exist()) {
            throw new FileException('公钥文件不存在');
            return false;
        }
        //转换为openssl格式密钥
        $res = openssl_get_publickey($this->publicKeyFile->read());
        if(!$res){
            throw new \InvalidArgumentException('公钥格式错误');
            return false;
        }
        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($content, base64_decode($sign), $res,
            $this->signType == self::RSA ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256);
        //释放资源
        openssl_free_key($res);
        return $result;
    }

    /**
     * 验签
     * @param string $content
     * @param string $sign
     * @return bool
     */
    protected function checkRsa($content, $sign) {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($this->publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        if (!$res) {
            throw new \InvalidArgumentException('支付宝RSA公钥错误。请检查公钥文件格式是否正确');
            return false;
        }
        return (bool)openssl_verify($content, base64_decode($sign), $res,
            $this->signType == self::RSA ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256);
    }

    protected function getSignContent(array $params) {
        ksort($params);
        $args = [];
        foreach ($params as $key => $item) {
            if ($this->checkEmpty($item)
                || $key == 'sign'
                || $key == 'sign_type'
                || strpos($item, '@') === 0
            ) {
                continue;
            }
            $args[] = $key.'='.$item;
        }
        return implode('&', $args);
    }

    /**
     *
     * @return mixed
     */
    public function callback() {
        Factory::log()
            ->info('ALIPAY CALLBACK: '.var_export($_POST, true));
        $data = $_POST;//Requests::isPost() ? $_POST : $_GET;
        if (!array_key_exists('sign', $data) || 
            !$this->verify($data, $data['sign'])) {
            throw new \InvalidArgumentException('验签失败！');
            return false;
        }
        return $data;
    }

    /**
     * 查询接口
     * EXMAPLE:
     * [
     * 'timestamp' => date('Y-m-d H:i:s'),
     * 'out_trade_no' => ''
     * ]
     * @param array $args
     * @return array|bool|mixed
     * @throws \ErrorException
     */
    public function queryOrder(array $args = array()) {
        $url = new Uri($this->apiMap['query'][0]);
        $args = $this->json($this->httpGet($url->setData($this->getSignData('query', $args))));
        if (!array_key_exists('alipay_trade_query_response', $args)) {
            throw new \ErrorException('未知错误！');
        }
        $args = $args['alipay_trade_query_response'];
        if ($args['code'] != 10000) {
            throw new \ErrorException($args['msg']);
        }
        if (!$this->verify($args, $args['sign'])) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        return $args;
    }

    /**
     * 获取APP支付
     * @param array $arg
     * @return string
     */
    public function getAppPayOrder($arg = array()) {
        return http_build_query($this->getSignData('appPay'));
    }

    /**
     * 合并biz_content
     * @param array $args
     * @return string
     */
    protected function encodeContent(array $args) {
        $data = [];
        foreach ($args as $key => $item) {
            $data[] = sprintf('"%s"="%s"', $key, $item);
        }
        return '{'.implode(',', $data).'}';
    }

    /**
     * APP 支付 异步回调必须输出 success
     * EXAMPLE:
    [
        'timestamp' => date('Y-m-d H:i:s'),
        'subject' => '',
        'out_trade_no' => '',
        'total_amount' => 0.01,
        'body' => ''
    ]
     * @param array $arg
     * @return string
     */
    public function getMobilePayOrder($arg = array()) {
        $data = $this->getData($this->getMap('mobilePay')[1], array_merge($this->get(), $arg));
        ksort($data);
        reset($data);
        $args = [];
        foreach ($data as $key => $item) {
            if ($this->checkEmpty($item) || $key == 'sign' || $key == 'sign_type') {
                continue;
            }
            $args[] = $key.'="'.$item.'"';
        }
        $content = implode('&', $args);
        $data['sign'] = urlencode($this->sign($content));
        return $content.'&sign='.'"'.$data['sign'].'"'.'&sign_type='.'"'.$data['sign_type'].'"';;
    }

    /**
     *  获取支付的网址
     * @param array $arg
     * @return $this
     */
    public function getPayUrl($arg = array()) {
        $uri = new Uri($this->getMap('pay')[0]);
        return $uri->setData($this->getSignData('pay', $arg));
    }

    /**
     * 及时支付
     * @param array $arg
     * @return $this
     */
    public function getWebPayUrl($arg = array()) {
        $uri = new Uri();
        return $uri->decode($this->getMap('pay')[0])
            ->setData($this->getSignData('webPay', $arg));
    }
}