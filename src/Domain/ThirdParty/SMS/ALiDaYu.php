<?php
namespace Zodream\Domain\ThirdParty\SMS;

use Zodream\Domain\ThirdParty\ThirdParty;

class ALiDaYu extends ThirdParty {

    protected $baseMap = [
        'http://gw.api.taobao.com/router/rest',
        [
            '#app_key',
            'target_app_key',
            'sign_method' => 'md5',
            'sign',
            'session',
            '#timestamp',
            'format' => 'json',
            'v' => '2.0',
            'partner_id',
            'simplify',
        ],
        'POST'
    ];

    protected $apiMap = [
        'send' => [
            'method' => 'alibaba.aliqin.fc.sms.num.send',
            'extend',
            'sms_type' => 'normal',
            '#sms_free_sign_name',
            'sms_param',   //值必须为字符串
            '#rec_num',
            '#sms_template_code'
        ],
        'query' => [
            'method' => 'alibaba.aliqin.fc.sms.num.query',
            'extend',
            'sms_type' => 'normal',
            '#sms_free_sign_name',
            'sms_param',   //值必须为字符串
            '#rec_num',
            '#sms_template_code'
        ],
        'voice' => [
            'method' => 'alibaba.aliqin.fc.voice.num.doublecall',
            'session_time_out',
            'extend',
            '#caller_num',
            '#caller_show_num',
            '#called_num',
            '#called_show_num'
        ],
        'tts' => [
            'method' => 'alibaba.aliqin.fc.tts.num.singlecall',
            'extend',
            'tts_param',
            '#called_num',
            '#called_show_num',
            '#tts_code'
        ],
        'singleCall' => [
            'method' => 'alibaba.aliqin.fc.voice.num.singlecall',
            'extend',
            '#called_num',
            '#called_show_num',
            '#voice_code'
        ],
    ];

    public function getMap($name) {
        $data =$this->baseMap;
        $data[1] = array_merge($data, parent::getMap($name));
        return $data;
    }

    protected function getPostData($name, array $args) {
        $data = parent::getPostData($name, $args);
        $data['sign'] = $this->sign($data);
        return $data;
    }

    public function send($templateId, $data, $signName = '阿里大于') {
        $args = $this->getJson('send', [
            'sms_template_code' => $templateId,
            'sms_free_sign_name' => $signName,
            'sms_param' => is_array($data) ? json_encode($data) : $data
        ]);
        if (array_key_exists('error_response', $args)) {
            throw new \Exception($args['error_response']['msg']);
        }
        return array_key_exists('alibaba_aliqin_fc_sms_num_send_response', $args);
    }

    public function sign(array $data) {
        $secret = $this->get('secret');
        if (empty($secret)) {
            throw  new \ErrorException('SECRET ERROR!');
        }
        ksort($data);
        reset($data);
        $arg = '';
        foreach ($data as $key => $item) {
            if ($this->checkEmpty($item) || $key == 'sign') {
                continue;
            }
            $arg .= $key.$item;
        }
        return strtoupper(md5($secret.implode('', $arg).$secret));
    }
}