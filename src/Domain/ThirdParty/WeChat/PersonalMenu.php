<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * 自定义个性化菜单
 * User: zx648
 * Date: 2016/8/20
 * Time: 0:01
 */
class PersonalMenu extends BaseWeChat {
    protected $apiMap = [
        'create' => [
            [
                'https://api.weixin.qq.com/cgi-bin/menu/addconditional',
                '#access_token'
            ],
            '#button',
            'POST'
        ],
        'delete' => [
            [
                'https://api.weixin.qq.com/cgi-bin/menu/delconditional',
                '#access_token'
            ],
            '#menuid',
            'POST'
        ],
        'test' => [
            [
                'https://api.weixin.qq.com/cgi-bin/menu/trymatch',
                '#access_token'
            ],
            '#user_id',
            'POST'
        ]
    ];

    public function create(MenuItem $menu) {
        $args = $this->getJson('create', $menu->toArray());
        if (array_key_exists('menuid', $args)) {
            return $args['menuid'];
        }
        return false;
    }

    public function deleteMenu() {
        $arg = $this->getJson('delete');
        return is_array($arg) && $arg['errcode'] == 0;
    }
}