<?php
declare(strict_types=1);
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
    public static array $voidTags = array(
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
    public static array $attributeOrder = array(
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

    public static array $dataAttributes = ['data', 'data-ng', 'ng'];

    /**
     * 标签
     * @param string $name 标签名
     * @param string|null $content 内容
     * @param array $options 属性值
     * @return string
     */
    public static function tag(string $name, string|null $content = '', array $options = array()): string {
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
    public static function a(string $text, string|array $href = '#', array $option = array()): string {
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
    public static function img(string $src = '#', array $option = array()): string {
        $option['src'] = is_string($src) && str_starts_with($src, 'data:') ? $src :  url()->to($src);
        return static::tag('img', null, $option);
    }

    /**
     * 表单
     * @param string $type
     * @param array $option
     * @return string
     */
    public static function input(string $type, array $option = array()): string {
        $option['type'] = $type;
        return static::tag('input', null, $option);
    }

    /**
     * select 生成
     * @param array $items
     * @param null|array|string|int $value
     * @param array $options
     * @return string
     */
    public static function select(array $items, mixed $value = null, array $options = []): string {
        $html =  '';
        foreach ($items as $key => $item) {
            $html .= Html::tag('option', $item, array(
                'value' => $key,
                'selected' => (is_array($value) && in_array($key, $value))
                    || (!is_array($value) && $key == $value)
            ));
        }
        return Html::tag(
            'select', $html, $options);
    }

    /**
     * DIV
     * @param string $content
     * @param array $option
     * @return string
     */
    public static function div(string $content, array $option = array()): string {
        return static::tag('div', $content, $option);
    }

    public static function ul(string|array $content, array $option = array()): string {
        return static::tag('ul',
            is_array($content) ? static::getLi($content) : $content,
            $option);
    }

    public static function ol(string|array $content, array $option = array()): string {
        return static::tag('ol', 
            is_array($content) ? static::getLi($content) : $content, 
            $option);
    }
    
    protected static function getLi(array $args): string {
        $html = null;
        foreach ($args as $item) {
            $html .= static::li(...(array)$item);
        }
        return $html;
    }

    public static function li(string $content, array $option = array()): string {
        return static::tag('li', $content, $option);
    }

    public static function p(string $content, array $option = array()): string {
        return static::tag('p', $content, $option);
    }

    public static function span(string $content, array $option = array()): string {
        return static::tag('span', $content, $option);
    }

    public static function i(string $content, array $option = array()): string {
        return static::tag('i', $content, $option);
    }
    
    public static function form(string $content, array $option = array()): string {
        return static::tag('form', $content, $option);
    }

    /**
     * 样式
     * @param $content
     * @param array $options
     * @return string
     */
    public static function style(string $content, array $options = array()): string {
        return static::tag('style', $content, $options);
    }

    public static function meta(string $content, array $option = array()): string {
        $option['content'] = $content;
        return static::tag('meta', null, $option);
    }

    public function nbsp(int $num = 1): string {
        return str_repeat('&nbsp;', $num);
    }


    /**
     * LINK OUTSIDE RESOURCE
     * @param string|array $url
     * @param array $attributes
     * @return string
     * @throws \Exception
     * @internal param array $option
     */
    public static function link(string|array $url, array $attributes = []): string {
        $defaults = ['media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet'];
        $attributes = $attributes + $defaults;
        $attributes['href'] = is_string($url) && str_starts_with($url, 'data:') ? $url : url()->to($url);
        return static::tag('link', null, $attributes);
    }

    /**
     * 脚本
     * @param $content
     * @param array $options
     * @return string
     */
    public static function script(string|null $content, array $options = array()): string {
        if (is_array($content)) {
            $options = $content;
            $content = null;
        }
        if (array_key_exists('src', $options)) {
            $options['src'] = url()->asset($options['src']);
        }
        return static::tag('script', $content, $options);
    }

    public static function renderTagAttributes(array $attributes): string {
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
                            $html .= " $name-$n=\"" . static::encode($v) . '"';
                        }
                    }
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . static::encode(implode(' ', $value)) . '"';
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . static::encode(static::cssStyleFromArray($value)) . '"';
                } else {
                    $html .= " $name='" . json_encode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= " $name=\"" .static::encode($value) . '"';
            }
        }
        return $html;
    }

    /**
     * 合并css样式
     * @param array $style
     * @return string|null
     */
    public static function cssStyleFromArray(array $style): string|null {
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

    public static function encode(mixed $content, bool $doubleEncode = true): string
    {
        if (!is_string($content)) {
            $content = (string)$content;
        }
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    public static function decode(string $content): string
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }
}