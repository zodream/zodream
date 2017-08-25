<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/12
 * Time: 14:37
 */
class Service extends BaseWeChat {
    protected $apiMap = [
        'list' => [
            'https://api.weixin.qq.com/cgi-bin/customservice/getkflist',
            '#access_token'
        ],
        'online' => [
            'https://api.weixin.qq.com/cgi-bin/customservice/getonlinekflist',
            '#access_token'
        ],
        'add' => [
            [
                'https://api.weixin.qq.com/customservice/kfaccount/add',
                '#access_token'
            ],
            [
                '#kf_account',
                '#nickname',
                '#password'
            ],
            'POST'
        ],
        'update' => [
            [
                'https://api.weixin.qq.com/customservice/kfaccount/update',
                '#access_token'
            ],
            [
                '#kf_account',
                '#nickname',
                '#password'
            ],
            'POST'
        ],
        'upload' => [
            [
                'http://api.weixin.qq.com/customservice/kfaccount/uploadheadimg',
                [
                    '#access_token',
                    '#kf_account'
                ],
                '#media',
                'POST'
            ]
        ],
        'delete' => [
            'https://api.weixin.qq.com/customservice/kfaccount/del',
            [
                '#access_token',
                '#kf_account'
            ]
        ],

        //客服会话控制
        'create' => [
            [
                'https://api.weixin.qq.com/customservice/kfsession/create',
                '#access_token'
            ],
            [
                '#openid',
                '#kf_account',
                'text'
            ],
            'POST'
        ],
        'close' => [
            [
                ' https://api.weixin.qq.com/customservice/kfsession/close',
                '#access_token'
            ],
            [
                '#openid',
                '#kf_account',
                'text'
            ],
            'POST'
        ],
        'getSession' => [ //获取客户的会话状态
            'https://api.weixin.qq.com/customservice/kfsession/getsession',
            [
                '#access_token',
                '#openid'
            ]
        ],
        'getKfSession' => [ //获取客服的会话列表
            'https://api.weixin.qq.com/customservice/kfsession/getsessionlist',
            [
                '#access_token',
                '#kf_account'
            ]
        ],
        'getWait' => [ //获取未接入会话列表
            'https://api.weixin.qq.com/customservice/kfsession/getwaitcase',
            '#access_token'
        ],
        'getRecord' => [ //
            [
                'https://api.weixin.qq.com/customservice/msgrecord/getrecord',
                '#access_token'
            ],
            [
                '#access_token',
                '#starttime',
                '#endtime',
                'pagesize' => 50,
                'pageindex' => 1
            ],
            'POST'
        ]
    ];
}