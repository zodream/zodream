<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * 用户管理
 * User: zx648
 * Date: 2016/8/20
 * Time: 12:55
 */
class User extends BaseWeChat {
    protected $apiMap = [
        'createGroup' => [
            [
                'https://api.weixin.qq.com/cgi-bin/groups/create',
                '#access_token'
            ],
            '#group',
            'POST'
        ],
        'getGroup' => [
            'https://api.weixin.qq.com/cgi-bin/groups/get',
            '#access_token'
        ],
        'getGroupId' => [
            [
                'https://api.weixin.qq.com/cgi-bin/groups/getid',
                '#access_token'
            ],
            'openid',
            'POST'
        ],
        'updateGroup' => [
            [
                'https://api.weixin.qq.com/cgi-bin/groups/update',
                '#access_token'
            ],
            '#group',
            'POST'
        ],
        'moveUser' => [
            [
                'https://api.weixin.qq.com/cgi-bin/groups/members/update',
                '#access_token'
            ],
            [
                '#openid',
                '#to_groupid'
            ],
            'POST'
        ],
        'moveUsers' => [
            [
                'https://api.weixin.qq.com/cgi-bin/groups/members/batchupdate',
                '#access_token'
            ],
            [
                '#openid_list',
                '#to_groupid'
            ],
            'POST'
        ],
        'deleteGroup' => [
            [
                'https://api.weixin.qq.com/cgi-bin/groups/delete',
                '#access_token'
            ],
            '#group',
            'POST'
        ],
        'mark' => [
            [
                'https://api.weixin.qq.com/cgi-bin/user/info/updateremark',
                '#access_token'
            ],
            [
                '#openid',
                '#remark'
            ],
            'POST'
        ],
        'info' => [
            'https://api.weixin.qq.com/cgi-bin/user/info',
            [
                '#access_token',
                '#openid',
                'lang' => 'zh_CN'
            ]
        ],
        'usersInfo' => [
            [
                'https://api.weixin.qq.com/cgi-bin/user/info/batchget',
                '#access_token'
            ],
            '#user_list',
            'POST'
        ],
        'userList' => [
            'https://api.weixin.qq.com/cgi-bin/user/get',
            [
                '#access_token',
                'next_openid'
            ]
        ]
    ];

    /**
     * @param string $name
     * @return bool|array ['id', 'name']
     */
    public function createGroup($name) {
        $args = $this->getJson('createGroup', [
                'group' => ['name' => $name]
            ]);
        if (array_key_exists('group', $args)) {
            return $args['group'];
        }
        return false;
    }

    /**
     * @return array
     */
    public function getGroup() {
        return $this->getJson('getGroup');
    }

    /**
     * @param string $openId
     * @return bool|string groupid
     */
    public function getUserGroup($openId) {
        $args = $this->getJson('getGroupId', [
                'openid' => $openId
            ]);
        if (array_key_exists('groupid', $args)) {
            return $args['groupid'];
        }
        return false;
    }

    /**
     * @param string|integer $id
     * @param string $name
     * @return bool
     */
    public function updateGroup($id, $name) {
        $args = $this->getJson('updateGroup', [
                'group' => [
                    'id' => $id,
                    'name' => $name
                ]
            ]);
        return $args['errcode'] == 0;
    }

    public function moveUserGroup($openId, $group) {
        $args = $this->getJson('moveUser',[
                'openid' => $openId,
                'to_groupid' => $group
            ]);
        return $args['errcode'] == 0;
    }

    public function moveUsers(array $openId, $group) {
        $args = $this->getJson('moveUsers', [
                'openid_list' => $openId,
                'to_groupid' => $group
            ]);
        return $args['errcode'] == 0;
    }

    public function deleteGroup($group) {
        $args = $this->getJson('deleteGroup', [
                'group' => [
                    'id' => $group
                ]
            ]);
        return $args['errcode'] == 0;
    }

    public function markUser($openId, $remark) {
        $args = $this->getJson('mark', [
                'openid' => $openId,
                'remark' => $remark
            ]);
        return $args['errcode'] == 0;
    }

    /**
     * UnionID 在不同平台是一致
     * @param $openId
     * @return mixed
     */
    public function userInfo($openId) {
        $user = $this->getJson('info', [
            'openid' => $openId
        ]);
        if (!array_key_exists('nickname', $user)) {
            return false;
        }
        $user['username'] = $user['nickname'];
        $user['avatar'] = $user['headimgurl'];
        return $user;
    }

    public function usersInfo(array $openId) {
        $data = [];
        foreach ($openId as $item) {
            if (is_array($item)) {
                $data = $openId;
                break;
            }
            $data[] = [
                'openid' => $item,
                'lang' => 'zh-CN'
            ];
        }
        return $this->getJson('usersInfo', [
                'user_list' => $data
            ]);
    }

    public function getUserList($nextOpenId = null) {
        return $this->getJson('userList', [
            'next_openid' => $nextOpenId
        ]);
    }
}