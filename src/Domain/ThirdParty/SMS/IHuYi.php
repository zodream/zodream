<?php
namespace Zodream\Domain\ThirdParty\SMS;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/12
 * Time: 15:13
 */
use Zodream\Domain\ThirdParty\ThirdParty;

/**
 * Class IHuYi
 * http://www.ihuyi.com/
 * @package Zodream\Domain
 * @property string $template 短信模板 {code} 代替验证码
 * @property string $account 账户
 * @property string $password 密码 可以是32位md5加密过的
 */
class IHuYi extends ThirdParty  {

    protected $configKey = 'sms';

    protected $apiMap = array(
        'url' => array(
            array(
                'http://106.ihuyi.cn/webservice/sms.php',
                array(
                    'format' => 'json',
                    '#method'
                )
            ),
            array(
                '#account',
                '#password',
                'mobile',
                'content'
            ), 
            'post'
        )
    );

    protected $_data = array(
        'template' => '您的验证码是：{code}。请不要把验证码泄露给其他人。'
    );

    /**
     * 发送短信
     * @param $mobile
     * @param $content
     * @return bool
     * @throws \ErrorException
     */
    public function send($mobile, $content) {
        $data = $this->getJson('url', array(
            'mobile' => $mobile,
            'content' => $content,
            'method' => 'Submit'
        ));
        if ($data['code'] == 2) {
            return $data['smsid'];
        }
        //$this->errorNo = $data['code'];
        throw new \ErrorException($data['msg']);
    }
    /**
     * 发送验证短信
     * @param string|integer $mobile
     * @param string|integer $code
     * @return bool|integer false|短信ID
     */
    public function sendCode($mobile, $code) {
        return $this->send($mobile, str_replace('{code}', $code, $this->get('template')));
    }

    /**
     * 余额查询
     * @return bool|int
     * @throws \ErrorException
     */
    public function balance() {
        $data = $this->getJson('url', array(
            'method' => 'GetNum'
        ));
        if ($data['code'] == 2) {
            return $data['num'];
        }
        throw new \ErrorException($data['msg']);
    }
}