<?php
namespace Zodream\Module\Gzo;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/10/25
 * Time: 20:19
 */
use Zodream\Infrastructure\ObjectExpand\StringExpand;

class BlockGenerate implements \IteratorAggregate  {

    protected $prefix = "    ";

    protected $blockTag = [
        '{' => '}',
        '(' => ')',
        '[' => ']',
    ];

    protected $unEndBlock = [];

    protected $lines = [];

    public function addLine($line) {
        $this->lines[] = empty($line) ? '' :
            (str_repeat($this->prefix, count($this->unEndBlock)).$line);
        return $this;
    }

    public function addLineEnd($line) {
        return $this->addLine($line.';');
    }

    public function space($name) {
        if (count($this->lines) == 0) {
            $this->lines = ['<?php'];
        }
        return $this->addLineEnd('namespace '.$name);
    }

    public function useSpace(array $args) {
        foreach ($args as $key => $item) {
            if (!is_integer($key)) {
                $item .= ' as '.$key;
            }
            $this->addLineEnd('use '.$item);
        }
        return $this;
    }

    public function className($name) {
        return $this->startBlock('class '.$name);
    }

    public function readOnly($name) {
        return $this->addLineEnd('const '.$name);
    }

    public function privateValue($name) {
        return $this->addLineEnd('private $'.$name);
    }

    public function publicValue($name) {
        return $this->addLineEnd('public $'.$name);
    }

    public function protectedValue($name) {
        return $this->addLineEnd('protected $'.$name);
    }

    public function privateMethod($name, $args = null) {
        return $this->startBlock('private function '.
            $name.'('.
            $this->getMethodParam($args).')');
    }

    public function publicMethod($name, $args = null) {
        return $this->startBlock('public function '.
            $name.'('.
            $this->getMethodParam($args).')');
    }

    public function protectedMethod($name, $args = null) {
        return $this->startBlock('protected function '.
            $name.'('.
            $this->getMethodParam($args).')');
    }

    protected function getMethodParam($args = null) {
        if (empty($args)) {
            return '';
        }
        if (!is_array($args)) {
            return $args;
        }
        $arg = [];
        foreach ($args as $key => $item) {
            if (!is_integer($key)) {
                $item = $key.' = '.$item;
            }
            $arg[] = '$'.trim($item, '$');
        }
        return implode(', ', $arg);
    }

    public function addBlock($arg) {
        $args = explode("\n", $arg);
        foreach ($args as $item) {
            $item = trim($item);
            foreach ($this->blockTag as $key => $tag) {
                if (StringExpand::endWith($item, $key)) {
                    $this->startBlock(trim($item, $key));
                    continue 2;
                }
                if (strpos($item, $tag) === 0) {
                    $this->endBlock();
                    continue 2;
                }
            }
            $this->addLine($item);
        }
        return $this;
    }

    public function startBlock($name, $tag = '{') {
        $this->unEndBlock[] = $tag;
        return $this->addLine(trim($name).' '.$tag);
    }

    public function endBlock() {
        if (empty($this->unEndBlock)) {
            return $this;
        }
        return $this->addLine($this->getUnEndBlock());
    }

    protected function getEndBlockTag($tag) {
        if (array_key_exists($tag, $this->blockTag)) {
            return $this->blockTag[$tag];
        }
        return $tag;
    }

    protected function getUnEndBlock() {
        if (count($this->unEndBlock) == 0) {
            return false;
        }
        return $this->getEndBlockTag(array_pop($this->unEndBlock));
    }

    public function __toString() {
        while (count($this->unEndBlock) > 0) {
            $this->endBlock();
        }
        return implode("\r\n", $this->lines);
    }

    /**
     * @return array
     */
    public function getLines() {
        return $this->lines;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator() {
        return new \ArrayIterator($this->getLines());
    }
}