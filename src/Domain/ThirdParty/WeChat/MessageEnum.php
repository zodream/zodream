<?php
namespace Zodream\Domain\ThirdParty\WeChat;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/23
 * Time: 17:57
 */
use Zodream\Infrastructure\ObjectExpand\Enum;

class MessageEnum extends Enum {
    const Text = 'text';
    const Image = 'image';
    const Voice = 'voice';
    const Video = 'video';
    const Music = 'music';
    const News = 'news';

    /**
     * 小视频
     */
    const ShortVideo = 'shortvideo';
    /**
     * 位置
     */
    const Location = 'location';
    /**
     * 链接
     */
    const Link = 'link';

    /**
     * 转发客服
     */
    const Service = 'transfer_customer_service';
}