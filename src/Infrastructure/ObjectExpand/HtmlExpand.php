<?php
namespace Zodream\Infrastructure\ObjectExpand;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 12:22
 */
class HtmlExpand {

    /**
     * 压缩html，
     * @param string $arg
     * @param bool $all 如果包含js请用false
     * @return string
     */
    public static function compress($arg, $all = true) {
        if (!$all) {
            return preg_replace(
                '/>\s+</',
                '><',
                preg_replace(
                    "/>\s+\r\n/",
                    '>', $arg));
        }
        return ltrim(rtrim(preg_replace(
            array('/> *([^ ]*) *</',
                '//',
                '#/\*[^*]*\*/#',
                "/\r\n/",
                "/\n/",
                "/\t/",
                '/>[ ]+</'
            ),
            array(
                '>\\1<',
                '',
                '',
                '',
                '',
                '',
                '><'
            ), $arg)));
    }

    /**
     * 过滤html元素
     * @param string $content
     * @return string
     */
    public static function filterHtml($content) {
        return preg_replace('/<(.*?)>/', '', htmlspecialchars_decode($content));
    }

    public static function shortString($content, $length = 100) {
        $content = preg_replace('/(\<.+?\>)|(\&nbsp;)+/', '', htmlspecialchars_decode($content));
        return StringExpand::subString($content, 0, $length);
    }
    
    /**
     * 将一个URL转换为完整URL
     * PHP将相对路径URL转换为绝对路径URL
     * @param string $srcUrl
     * @param string $baseUrl
     * @return string
     */
    public static function formatUrl($srcUrl, $baseUrl) {
        $srcInfo = parse_url($srcUrl);
        if(isset($srcInfo['scheme'])) {
            return $srcUrl;
        }
        $baseInfo = parse_url($baseUrl);
        $url = $baseInfo['scheme'].'://'.$baseInfo['host'];
        if(substr($srcInfo['path'], 0, 1) == '/') {
            $path = $srcInfo['path'];
        }else{
            $filename=  basename($baseInfo['path']);
            //兼容基础url是列表
            if(strpos($filename,".")===false){
                $path = dirname($baseInfo['path']).'/'.$filename.'/'.$srcInfo['path'];
            }else{
                $path = dirname($baseInfo['path']).'/'.$srcInfo['path'];
            }

        }
        $rst = array();
        $path_array = explode('/', $path);
        if(!$path_array[0]) {
            $rst[] = '';
        }
        foreach ($path_array AS $key => $dir) {
            if ($dir == '..') {
                if (end($rst) == '..') {
                    $rst[] = '..';
                }elseif(!array_pop($rst)) {
                    $rst[] = '..';
                }
            }elseif($dir && $dir != '.') {
                $rst[] = $dir;
            }
        }
        if(!end($path_array)) {
            $rst[] = '';
        }
        $url .= implode('/', $rst);
        return str_replace('\\', '/', $url);
    }
}