<?php
namespace Zodream\Domain\Html\Bootstrap;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/30
 * Time: 8:41
 */
use Zodream\Domain\Html\Page;
use Zodream\Domain\Html\Widget;
use Zodream\Infrastructure\ObjectExpand\ArrayExpand;

class TableWidget extends Widget {
    protected $default = array(
        'class' => 'table table-hover'
    );
    
    protected function run() {
        $page = $this->get('page');
        if ($page instanceof Page) {
            $this->set(array(
                'data' => $page->getPage(),
                'foot' => $page->getLink()
            ));
        }
        return Html::tag('table', 
            $this->getHead() . $this->getBody() . $this->getFoot(), 
            $this->get('id,class')
        );
    }
    
    protected function getHead() {
        $content = '';
        foreach ($this->get('columns', array()) as $key => $value) {
            if (is_array($value)) {
                $content .= Html::tag(
                    'th',
                    array_key_exists('label', $value) ? $value['label'] : null
                );
                continue;
            }
            $content .= Html::tag('th', $value);
            
        }
        return Html::tag('thead', Html::tag('tr', $content));
    }
    
    protected function getBody() {
        $data = $this->get('data');
        $columns = $this->get('columns');
        if (empty($data) || empty($columns)) {
            return null;
        }
        $content = '';
        foreach ($data as $item) {
            $content .= $this->getBodyOne($item, $columns);
        }
        return Html::tag('tbody', $content);
    }
    
    protected function getBodyOne(array $item, array $columns) {
        $content = '';
        foreach ($columns as $key => $value) {
            $k = $key;
            // 为避免重复键
            if (is_array($value) && array_key_exists('key', $value)) {
                $k = $value['key'];
            }
            $format = null;
            if (is_array($value) && array_key_exists('format', $value)) {
                $format = $value['format'];
            }
            $content .= Html::tag('td', $this->format((array)ArrayExpand::getValues($k, $item), $format));
        }
        return Html::tag('tr', $content);
    }
    
    protected function getFoot() {
        if (!$this->has('foot')) {
            return null;
        }
        $count = count($this->get('columns'));
        $content = '';
        foreach ((array)$this->get('foot', array()) as $item) {
            $content .= Html::tag('tr', Html::tag('th', $item, array(
                'colspan' => $count
            )));
        }
        return Html::tag('tfoot', $content);
    }
}