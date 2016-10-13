<?php
namespace Zodream\Domain\ThirdParty\WeChat;
use Zodream\Infrastructure\Disk\File;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/20
 * Time: 10:39
 */
class Media extends BaseWeChat {
    const IMAGE = 'image';
    const VOICE = 'voice';
    const VIDEO = 'video';
    const THUMB = 'thumb';

    protected $apiMap = [
        'uploadTemp' => [
            [
                'https://api.weixin.qq.com/cgi-bin/media/upload',
                [
                    '#access_token',
                    '#type'
                ]
            ],
            '#media',
            'POST'
        ],
        'downloadTemp' => [
            'https://api.weixin.qq.com/cgi-bin/media/get',
            [
                '#access_token',
                '#media_id'
            ]
        ],
        'addNews' => [
            'https://api.weixin.qq.com/cgi-bin/material/add_news',
            '#access_token'
        ],
        'uploadImg' => [
            [
                'https://api.weixin.qq.com/cgi-bin/media/uploadimg',
                '#access_token'
            ],
            '#media'
        ],
        'addMedia' => [
            [
                'https://api.weixin.qq.com/cgi-bin/material/add_material',
                [
                    '#access_token',
                    '#type'
                ],
                '#media'
            ],
            'POST'
        ],
        'getMedia' => [
            [
                'https://api.weixin.qq.com/cgi-bin/material/get_material',
                '#access_token'
            ],
            '#media_id',
            'POST'
        ],
        'deleteMedia' => [
            [
                'https://api.weixin.qq.com/cgi-bin/material/del_material',
                '#access_token'
            ],
            '#media_id',
            'POST'
        ],
        'updateNews' => [
            'https://api.weixin.qq.com/cgi-bin/material/update_news',
            '#access_token'
        ],
        'count' => [
            'https://api.weixin.qq.com/cgi-bin/material/get_materialcount',
            '#access_token'
        ],
        'mediaList' => [
            [
                'https://api.weixin.qq.com/cgi-bin/material/batchget_material',
                '#access_token'
            ],
            [
                '#type',
                '#offset',
                '#count'
            ],
            'POST'
        ],
    ];

    /**
     * @param string|File $file
     * @param string $type
     * @return array [type, media_id, created_at]
     */
    public function uploadTemp($file, $type) {
        return $this->getJson('uploadTemp', [
            'media' => '@'.$file,
            'type' => $type
        ]);
    }

    public function downloadTemp($mediaId, $file, $type = null) {
        $url = $this->getUrl('downloadTemp', [
            'media_id' => $mediaId
        ]);
        if ($type == self::VIDEO) {
            $url->setScheme('http');
        }
        return $url->get()->download($file);
    }

    /**
     *
     * @param NewsItem $news
     * @return string|bool media_id
     */
    public function addNews(NewsItem $news) {
        $args = $this->jsonPost('addNews', $news->toArray());
        return array_key_exists('media_id', $args) ? $args['media_id'] : false;
    }

    /**
     *
     * @param $file
     * @return string|bool url
     */
    public function uploadImg($file) {
        $args = $this->getJson('uploadImg', [
            'media' => '@'.$file
        ]);
        return array_key_exists('url', $args) ? $args['url'] : false;
    }

    public function addMedia($file, $type, $title = null, $introduction = null) {
        $args = $this->getJson('addMedia', [
            'type' => $type,
            'media' => '@'.$file
        ]);
        if ($type == self::VIDEO) {
            $args = $this->json($this->http
                ->request(false)->post([
                'description' => json_encode([
                    'title' => $title,
                    'introduction' => $introduction
                ])
            ]));
        }
        return $args;
    }

    public function getMedia($mediaId, $file = null) {
        $args = $this->getByApi('getMedia', [
            'media_id' => $mediaId
        ]);
        if (empty($file)) {
            return $this->json($args);
        }
        if (!$file instanceof File) {
            $file = new File($file);
        }
        return $file->write($args);
    }

    public function deleteMedia($mediaId) {
        $args = $this->getJson('deleteMedia', [
            'media_id' => $mediaId
        ]);
        return $args['errcode'] == 0;
    }

    public function updateNews(NewsItem $news) {
        $args = $this->jsonPost('updateNews', $news->toArray());
        return $args['errcode'] == 0;
    }

    public function count() {
        return $this->getJson('count');
    }

    public function mediaList($type, $offset = 0, $count = 20) {
        return $this->getJson('mediaList', [
            'type' => $type,
            'offset' => $offset,
            'count' => $count
        ]);
    }
}