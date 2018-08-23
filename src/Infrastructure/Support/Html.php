<?php
namespace Zodream\Infrastructure\Support;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/29
 * Time: 16:50
 */


class Html {
    /**
     * @var array 无内容的标签
     */
    public static $voidTags = array(
        'area' => 1,
        'base' => 1,
        'br' => 1,
        'col' => 1,
        'command' => 1,
        'embed' => 1,
        'hr' => 1,
        'img' => 1,
        'input' => 1,
        'keygen' => 1,
        'link' => 1,
        'meta' => 1,
        'param' => 1,
        'source' => 1,
        'track' => 1,
        'wbr' => 1
    );

    /**
     * @var array 属性的顺序
     */
    public static $attributeOrder = array(
        'type',
        'id',
        'class',
        'name',
        'value',

        'href',
        'src',
        'action',
        'method',

        'selected',
        'checked',
        'readonly',
        'disabled',
        'multiple',

        'size',
        'maxlength',
        'width',
        'height',
        'rows',
        'cols',

        'alt',
        'title',
        'rel',
        'media'
    );

    public static $dataAttributes = ['data', 'data-ng', 'ng'];

    /**
     * 标签
     * @param string $name 标签名
     * @param string $content 内容
     * @param array $options 属性值
     * @return string
     */
    public static function tag($name, $content = '', $options = array()) {
        $html = '<'.$name . static::renderTagAttributes($options) . '>';
        return isset(static::$voidTags[strtolower($name)]) ? $html.PHP_EOL : "{$html}{$content}</{$name}>".PHP_EOL;
    }

    /**
     * 链接
     * @param string $text 显示文字
     * @param string $href 链接
     * @param array $option
     * @return string
     */
    public static function a($text, $href = '#', $option = array()) {
        if (array_key_exists('href', $option)) {
            $href = $option['href'];
        }
        $option['href'] = url()->to($href);
        return static::tag('a', $text, $option);
    }

    /**
     * 图片
     * @param string $src
     * @param array $option
     * @return string
     */
    public static function img($src = '#', $option = array()) {
        $option['src'] = url()->to($src);
        return static::tag('img', null, $option);
    }

    /**
     * 表单
     * @param string $type
     * @param array $option
     * @return string
     */
    public static function input($type, $option = array()) {
        $option['type'] = $type;
        return static::tag('input', null, $option);
    }

    /**
     * DIV
     * @param string $content
     * @param array $option
     * @return string
     */
    public static function div($content, $option = array()) {
        return static::tag('div', $content, $option);
    }

    public static function ul($content, $option = array()) {
        return static::tag('ul',
            is_array($content) ? static::getLi($content) : $content,
            $option);
    }

    public static function ol($content, $option = array()) {
        return static::tag('ol', 
            is_array($content) ? static::getLi($content) : $content, 
            $option);
    }
    
    protected static function getLi(array $args) {
        $html = null;
        foreach ($args as $item) {
            $html .= static::li(...(array)$item);
        }
        return $html;
    }

    public static function li($content, $option = array()) {
        return static::tag('li', $content, $option);
    }

    public static function p($content, $option = array()) {
        return static::tag('p', $content, $option);
    }

    public static function span($content, $option = array()) {
        return static::tag('span', $content, $option);
    }

    public static function i($content, $option = array()) {
        return static::tag('i', $content, $option);
    }
    
    public static function form($content, $option = array()) {
        return static::tag('form', $content, $option);
    }

    /**
     * 样式
     * @param $content
     * @param array $options
     * @return string
     */
    public static function style($content, $options = array()) {
        return static::tag('style', $content, $options);
    }

    public static function meta($content, $option = array()) {
        $option['content'] = $content;
        return static::tag('meta', null, $option);
    }


    /**
     * LINK OUTSIDE RESOURCE
     * @param string $url
     * @param array $attributes
     * @return string
     * @internal param array $option
     */
    public static function link($url, $attributes = []) {
        $defaults = ['media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet'];
        $attributes = $attributes + $defaults;
        $attributes['href'] = url()->to($url);
        return static::tag('link', null, $attributes);
    }

    /**
     * 脚本
     * @param $content
     * @param array $options
     * @return string
     */
    public static function script($content, $options = array()) {
        if (is_array($content)) {
            $options = $content;
            $content = null;
        }
        if (array_key_exists('src', $options)) {
            $options['src'] = url()->to($options['src']);
        }
        return static::tag('script', $content, $options);
    }

    public static function renderTagAttributes($attributes) {
        if (count($attributes) > 1) {
            $sorted = array();
            foreach (static::$attributeOrder as $name) {
                if (isset($attributes[$name])) {
                    $sorted[$name] = $attributes[$name];
                }
            }
            $attributes = array_merge($sorted, $attributes);
        }
        $html = '';
        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= " $name";
                }
            } elseif (is_array($value)) {
                if (in_array($name, static::$dataAttributes)) {
                    foreach ($value as $n => $v) {
                        if (is_array($v)) {
                            $html .= " $name-$n='" . json_encode($v) . "'";
                        } else {
                            $html .= " $name-$n=\"" . htmlspecialchars($v) . '"';
                        }
                    }
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . htmlspecialchars(implode(' ', $value)) . '"';
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . static::encode(static::cssStyleFromArray($value)) . '"';
                } else {
                    $html .= " $name='" . json_encode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= " $name=\"" .htmlspecialchars($value) . '"';
            }
        }
        return $html;
    }

    /**
     * 合并css样式
     * @param array $style
     * @return null
     */
    public static function cssStyleFromArray(array $style) {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= "$name: $value; ";
        }
        return $result === '' ? null : rtrim($result);
    }
    
    public static function __callStatic($name, $arguments) {
        if (array_key_exists($name, static::$voidTags)) {
            return static::tag($name, '', count($arguments) > 0 ? $arguments[0] : array());
        }
        return static::tag($name,
            count($arguments) > 0 ? $arguments[0] : '',
            count($arguments) > 1 ? $arguments[1] : array()
        );
    }

    public static function encode($content, $doubleEncode = true) {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    public static function decode($content) {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }
}