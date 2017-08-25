<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * 自定义菜单
 * User: zx648
 * Date: 2016/8/19
 * Time: 22:31
 */
class Menu extends BaseWeChat {
    protected $apiMap = [
        'create' => [
            [
                'https://api.weixin.qq.com/cgi-bin/menu/create',
                '#access_token'
            ],
            '#button',
            'POST'
        ],
        'get' => [
            'https://api.weixin.qq.com/cgi-bin/menu/get',
            [
                '#access_token'
            ]
        ],
        'delete' => [
            'https://api.weixin.qq.com/cgi-bin/menu/delete',
            '#access_token'
        ],
        'getMenuInfo' => [
            'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info',
            '#access_token'
        ]
    ];


    /**
     * CREATE MENU
     * @param MenuItem $menu
     * @return bool
     * @throws \Exception
     */
    public function create(MenuItem $menu) {
        $args = $this->getJson('create', $menu->toArray());
        if ($args['errcode'] === 0) {
            return true;
        }
        throw new \Exception($args['errmsg']);
    }

    public function getMenu() {
        return $this->getJson('get');
    }

    public function deleteMenu() {
        $arg = $this->getJson('delete');
        return is_array($arg) && $arg['errcode'] == 0;
    }
}