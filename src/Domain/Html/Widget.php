<?php
namespace Zodream\Domain\Html;
/**
 * 视图组件基类
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/29
 * Time: 16:04
 */
use Zodream\Infrastructure\Support\Html;
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\ObjectExpand\JsonExpand;
use Zodream\Infrastructure\ObjectExpand\TimeExpand;
use Zodream\Service\Factory;

abstract class Widget extends MagicObject {
    protected $default = array();
    
    abstract protected function run();
    
    public function __construct() {
        $this->set($this->default);
    }

    /**
     * 视图组件总入口
     * @param array $config
     * @return string
     * @throws \Exception
     */
    public static function show($config = array()) {
        ob_start();
        ob_implicit_flush(false);
        try {
            $instance = new static;
            $instance->set($config);
            $out = $instance->run();
        } catch (\Exception $e) {
            // close the output buffer opened above if it has not been closed already
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
        return ob_get_clean() . $out;
    }
    
    protected function json(array $args = array()) {
        return JsonExpand::encode($args);
    }
    
    public function __toString() {
        return $this->show();
    }

    protected function format(array $data, $tag = null) {
        if ($tag instanceof \Closure) {
            return call_user_func_array($tag, $data);
        }
        $result = array();
        foreach ($data as $item) {
            $result[] = $this->formatOne($item, $tag);
        }
        return implode(' ', $result);
    }

    protected function formatOne($data, $tag = null) {
        if (empty($tag)) {
            return $data;
        }
        if (is_array($tag)) {
            return array_key_exists($data, $tag) ? $tag[$data] : null;
        }
        if ($tag === 'html') {
            return htmlspecialchars_decode($data);
        }
        if ($tag === 'img') {
            return Html::img($data);
        }
        if ($tag === 'url') {
            return Html::a($data, $data);
        }
        if ($tag === 'email') {
            return Html::a($data, 'mailto:'.$data);
        }
        if ($tag === 'tel') {
            return Html::a($data, 'tel:'.$data);
        }
        if ($tag === 'int') {
            return intval($data);
        }
        if (empty($data)) {
            return Factory::i18n()->translate('(not set)');
        }
        if ($tag === 'date') {
            return TimeExpand::format($data, 'Y-m-d');
        }
        if ($tag === 'datetime') {
            return TimeExpand::format($data);
        }
        if ($tag === 'ago') {
            return TimeExpand::isTimeAgo($data);
        }
        return $data;
    }
}