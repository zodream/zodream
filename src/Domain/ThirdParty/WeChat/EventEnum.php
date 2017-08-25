<?php
namespace Zodream\Domain\ThirdParty\WeChat;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/23
 * Time: 18:01
 */
use Zodream\Infrastructure\ObjectExpand\Enum;

class EventEnum extends Enum {
    const Message = 'message';
    /**
     * 普通关注
     * 二维码关注
     */
    const Subscribe = 'subscribe';
    const ScanSubscribe = 'scan_subscribe';
    /**
     * 取消关注
     */
    const UnSubscribe = 'unsubscribe';
    /**
     * 扫描二维码
     */
    const Scan = 'SCAN';
    /**
     * 地理位置
     */
    const Location = 'LOCATION';
    /**
     * 自定义菜单 - 点击菜单拉取消息时的事件推送
     * eventKey
     */
    const Click = 'CLICK';
    /**
     * 自定义菜单 - 点击菜单跳转链接时的事件推送
     * eventKey
     * 
     */
    const View = 'VIEW';
    /**
     * 自定义菜单 - 扫码推事件的事件推送
     */
    const ScanCodePush = 'scancode_push';
    /**
     * 自定义菜单 - 扫码推事件且弹出“消息接收中”提示框的事件推送
     */
    const ScanCodeWaitMsg = 'scancode_waitmsg';
    /**
     * 自定义菜单 - 弹出系统拍照发图的事件推送
     */
    const PicSysPhoto = 'pic_sysphoto';
    /**
     * 自定义菜单 - 弹出拍照或者相册发图的事件推送
     */
    const PicPhotoOrAlbum = 'pic_photo_or_album';
    /**
     * 自定义菜单 - 弹出微信相册发图器的事件推送
     */
    const PicWeChat = 'pic_weixin';
    /**
     * 自定义菜单 - 弹出地理位置选择器的事件推送
     */
    const LOCATION_SELECT = 'location_select';
    /**
     * 群发接口完成后推送的结果
     */
    const MassSendJobFinish = 'MASSSENDJOBFINISH';
    /**
     * 模板消息完成后推送的结果
     */
    const TemplateSendJobFinish = 'TEMPLATESENDJOBFINISH';

    /**
     * 客服接入会话
     */
    const KFCreateSession = 'kf_create_session';
    /**
     * 客服关闭会话
     */
    const KFCloseSession = 'kf_close_session';
    /**
     * 客服接入会话
     */
    const KFSwitchSession = 'kf_switch_session';

}