<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * 模板消息
 * User: zx648
 * Date: 2016/8/23
 * Time: 19:17
 */
class Template extends BaseWeChat {
    protected $apiMap = [
        'setIndustry' => [
            [
                'https://api.weixin.qq.com/cgi-bin/template/api_set_industry',
                '#access_token'
            ],
            [
                '#industry_id1',
                '#industry_id2'
            ],
            'POST'
        ],
        'getIndustry' => [
            'https://api.weixin.qq.com/cgi-bin/template/get_industry',
            '#access_token'
        ],
        'addTemplate' => [
            [
                'https://api.weixin.qq.com/cgi-bin/template/api_add_template',
                '#access_token'
            ],
            '#template_id_short',
            'POST'
        ],
        'all' => [
            'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template',
            '#access_token'
        ],
        'delete' => [
            [
                'https://api,weixin.qq.com/cgi-bin/template/del_private_template',
                '#access_token'
            ],
            '#template_id',
            'POST'
        ],
        'send' => [
            [
                'https://api.weixin.qq.com/cgi-bin/message/template/send',
                '#access_token'
            ],
            [
                '#touser',
                '#template_id',
                '#url',
                '#data'
            ],
            'POST'
        ]
    ];

    public function setIndustry($id1, $id2) {
        return $this->getJson('setIndustry', [
            'industry_id1' => $id1,
            'industry_id2' => $id2
        ]);
    }

    /**
     * @return array [primary_industry, secondary_industry]
     */
    public function getIndustry() {
        return $this->getJson('setIndustry');
    }

    /**
     * @param $id
     * @return bool|string template_id
     */
    public function addTemplate($id) {
        $args = $this->getJson('addTemplate', [
            'template_id_short' => $id
        ]);
        if ($args['errcode'] == 0) {
            return $args['template_id'];
        }
        return false;
    }

    public function allTemplate() {
        return $this->getJson('all');
    }

    public function deleteTemplate($id) {
        $args = $this->getJson('delete', [
            'template_id' => $id
        ]);
        return $args['errcode'] == 0;
    }

    /**
     * 发送模板消息
     * @param string $openId
     * @param string $template
     * @param string $url 链接的网址
     * @param array $data   [key => [value, color]]
     * @return bool
     */
    public function send($openId, $template, $url, array $data) {
        foreach ($data as $key => &$item) {
            if (!is_array($item)) {
                $item = [
                    'value' => $item,
                    'color' => '#777'
                ];
            }
        }
        $arg = $this->getJson('send', [
            'touser' => $openId,
            'template_id' => $template,
            'url' => $url,
            'data' => $data
        ]);
        return $arg['errcode'] == 0;
    }

}