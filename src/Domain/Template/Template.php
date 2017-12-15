<?php
namespace Zodream\Domain\Template;
use function GuzzleHttp\Psr7\str;
use Zodream\Helpers\Str;

/**
{>}         <?php
{>css}
{>js}
{/>}        ?>
{> a=b}     <?php a = b?>
{| a==b}    <?php if (a==b):?>
{+ a > c}   <?php elseif (a==b):?>
{+}         <?php else:?>
{-}         <?php endif;?>
{~}         <?php for():?>
{/~}        <?php endfor;?>

{name}      <?php echo name;?>
{name.a}    <?php echo name[a];?>
{name,hh}   <?php echo isset(name) ? name : hh;?>

{for:name}                      <?php while(name):?>
{for:name,value}                <?php foreach(name as value):?>
{for:name,key=>value}           <?php foreach(name as key=>value):?>
{for:name,key=>value,length}     <?php $i = 0; foreach(name as key=>value): $i ++; if ($i > length): break; endif;?>
{for:name,key=>value,>=h}        <?php foreach(name as key=>value): if (key >=h):?>
{/for}                           <?php endforeach;?>

{name=qq?v}                     <?php name = qq ? qq : v;?>
{name=qq?v:b}                   <?php name = qq ? v : b;?>

{if:name=qq}                    <?php if (name = qq):?>
{if:name=qq,hh}                 <?php if (name = qq){ echo hh; }?>
{if:name>qq,hh,gg}              <?php if (name = qq){ echo hh; } else { echo gg;}?>
{/if}                           <?php endif;?>
{else}                          <?php else:?>
{elseif}                        <?php elseif:?>

{switch:name}
{switch:name,value}
{case:hhhh>0}
{/switch}

{extend:file,hhh}

{name=value}                <?php name = value;?>
{arg,...=value,...}         <?php arg = value;. = .;?>

' string                    ''
t f bool                    true false
0-9 int                     0-9
[] array                    array()
 **/

class Template extends BaseTemplate {

	protected $beginTag = '{';

	protected $endTag = '}';

    protected $salePattern = '/\<\?(.|\r\n|\s)*\?\>/U';

    protected $blockTag = false; // 代码块开始符

    protected $forTags = [];

    protected $allowFilters = true;

	public function setTag($begin, $end) {
		$this->beginTag = $begin;
		$this->endTag = $end;
	}

	public function render($content) {
        if (empty($content)) {
            return $content;
        }
        $content = preg_replace($this->salePattern, '', $content);
        return $this->renderContent($this->parse($content));
	}

    public function parse($content) {
        $pattern = sprintf('/%s\s*(.+?)\s*%s(\r?\n)?/s', $this->beginTag, $this->endTag);
        return preg_replace_callback($pattern, [$this, 'replaceCallback'], $content);
    }

	protected function replaceCallback($match) {
		$content = $match[1];
        if ($content == '/>' && $this->blockTag !== false) {
            return $this->parseEndBlock();
        }
		if (empty($content) || $this->blockTag !== false) {
		    return $match[0];
        }
        if (false !== ($line = $this->parseNote($content))) {
            return $line;
        }
        if (false !== ($line = $this->parseTag($content))) {
            return $line;
        }
		if (false !== ($line = $this->parseFirstTag($content))) {
            return $line;
        }
        if (strpos($content, ':') > 0 && false !== ($line = $this->parseBlockTag($content))) {
            return $line;
        }
        if (false !== ($line = $this->parseLambda($content))) {
            return $line;
        }
        if (false !== ($line = $this->parseAssign($content))) {
            return $line;
        }
        if ($this->hasOrderTag($content, ['$', '='])) {
            return '<?php '.$content.';?>';
        }
        if ($this->hasOrderTag($content, ['$', ',', '$']) > 0) {
            $args = explode(',', $content, 2);
            return sprintf('<?php echo isset(%s) ? %s : %s; ?>', $args[0], $args[1]);
        }
        if (preg_match('/^\$[_\w\.\[\]\|\$]+$/i', $content)) {
            return sprintf('<?php echo %s;?>', $this->parseVal($content));
        }
        return $match[0];
	}


	protected function parseVal($val) {
        if (strrpos($val, '|') !== false) {
            $filters = explode('|', $val);
            $val = array_shift($filters);
        }
        if (empty($val)) {
            return '';
        }
        if (strpos($val, '.$') !== false) {
            $all = explode('.$', $val);
            foreach ($all AS $key => $val) {
                $all[$key] = $key == 0 ? $this->makeVar($val)
                    : '[$'. $this->makeVar($val) . ']';
            }
            $p = implode('', $all);
        } else {
            $p = $this->makeVar($val);
        }
        if (empty($filters)) {
            return $p;
        }
        foreach ($filters as $filter) {
            list($tag, $vals) = Str::explode($filter, ':', 2);
            if ($this->allowFilters !== true &&
                !in_array($tag, (array)$this->allowFilters)) {
                continue;
            }
            $p = sprintf('%s(%s%s)', $tag, $p, empty($vals) ? '' : (','.$vals));
        }
        return $p;
    }

    protected function makeVar($val) {
	    if (strrpos($val, '.') === false) {
	        return $val;
        }
        $t = explode('.', $val);
        $p = array_shift($t);
        foreach ($t AS $val) {
            $p .= '[\'' . $val . '\']';
        }
        return $p;
    }

    /**
     * 是否包含指定顺序的字符
     * @param $content
     * @param $search
     * @return bool
     */
	protected function hasOrderTag($content, $search) {
	    $last = -1;
	    foreach ((array)$search as $tag) {
	        $index = strpos($content, $tag,
                $last < 0 ? 0 : $last);
	        if ($index === false) {
	            return false;
            }
            if ($index <= $last) {
	            return false;
            }
            $last = $index;
        }
        return true;
    }

    /**
     * 注释
     * @param $content
     * @return bool|string
     */
	protected function parseNote($content) {
	    if (($content{0} == '*'
            && substr($content, -1) == '*') ||
            (substr($content, 0, 2) == '//'
                && substr($content, -2, 2) == '//')) {
	        return '';
        }
        return false;
    }

	protected function parseAssign($content) {
        $eqI = strpos($content, '=');
        $dI = strpos($content, ',');
	    if ($eqI === false || $dI === false || $dI >= $eqI) {
	        return false;
        }
        $args = explode('=', $content, 2);
        return sprintf('<?php list(%s) = %s; ?>', $args[0], $args[1]);
    }

	protected function parseLambda($content) {
	    if (preg_match('/(.+)=(.+)\?((.*):)?(.+)/', $content, $match)) {
	        return sprintf('<?php %s = %s ? %s : %s; ?>',
                $match[1], $match[2], $match[4] ?: $match[2] , $match[5]);
        }
        return false;
    }

	protected function parseBlockTag($content) {
	    list($tag, $content) = explode(':', $content, 2);
	    if ($tag == 'for') {
	        return $this->parseFor($content);
        }
        if ($tag == 'switch') {
	        return $this->parseSwitch($content);
        }
        if ($tag == 'case') {
            sprintf('<?php case %s:?>', $content);
        }
        if ($tag == 'extend') {
	        return '';
        }
        if ($tag == 'if') {
            return $this->parseIf($content);
        }
        if ($tag == 'elseif' || $tag == 'else if') {
	        return sprintf('<?php elseif(%s):?>', $content);
        }
        return false;
    }

    protected function parseIf($content) {
        $args = explode(',', $content);
        $length = count($args);
        if ($length == 1) {
            return '<?php if('.$content.'):?>';
        }
        if ($length == 2) {
            return sprintf('<?php if (%s){ echo %s; }?>', $args[0], $args[1]);
        }
        return sprintf('<?php if (%s){ echo %s; } else { echo %s;}?>',
            $args[0], $args[1], $args[2]);
    }

    protected function parseSwitch($content) {
        $args = explode(',', $content);
        if (count($args) == 1) {
            return sprintf('<?php switch(%s):?>', $content);
        }
        return sprintf('<?php switch(%s): case %s:?>', $args[0], $args[1]);
    }

    protected function parseFor($content) {
	    $args = strpos($content, ';') !== false ?
            explode(';', $content) :
            explode(',', $content);
	    $length = count($args);
	    if ($length == 1) {
	        $this->forTags[] = 'while';
	        return '<?php while('.$content.'):?>';
        }
        if ($length == 2) {
            $this->forTags[] = 'foreach';
	        return sprintf('<?php if (!empty(%s) && is_array(%s)): foreach(%s as %s):?>',
                $args[0],
                $args[0],
                $args[0], $args[1] ?: '$item');
        }
        $tag = substr(trim($args[2]), 0, 1);
        if (in_array($tag, ['<', '>', '='])) {
            list($key, $item) = $this->getForItem($args[1]);
            $this->forTags[] = 'foreach';
            return sprintf('<?php if (!empty(%s) && is_array(%s)): foreach(%s as %s=>%s): if (!(%s %s)): break; endif;?>',
                $args[0],
                $args[0],
                $args[0], $key, $item, $key,  $args[2]);
        }
        if ($this->isForTag($args)) {
            $this->forTags[] = 'for';
            return sprintf('<?php for(%s; %s; %s): ?>',
                $args[0],
                $args[1],
                $args[2]);
        }

        $this->forTags[] = 'foreach';
        return sprintf('<?php if (!empty(%s) && is_array(%s)):  $i = 0; foreach(%s as %s): $i ++; if ($i > %s): break; endif;?>',
            $args[0],
            $args[0],
            $args[0],
            $args[1]  ?: '$item',
            $args[2]);
    }

    protected function isForTag($args) {
	    return $this->isJudge($args[1]) && $this->hasTag($args[2], ['+', '-', '*', '/', '%']);
    }

    /**
     * 是否是判断语句
     * @param $str
     * @return bool
     */
    protected function isJudge($str) {
	    return $this->hasTag($str, ['<', '>', '==']);
    }

    /**
     * 是否包含字符
     * @param $str
     * @param $search
     * @return bool
     */
    protected function hasTag($str, $search) {
	    foreach ((array)$search as $tag) {
	        if (strpos($str, $tag) !== false) {
	            return true;
            }
        }
        return false;
    }

    protected function getForItem($content) {
	    $key = '$key';
	    $item = $content;
	    if (strpos($content, '=>') !== false) {
	        list($key, $item) = explode('=>', $content);
        } elseif (strpos($content, ' ') !== false) {
	        list($key, $item) = explode(' ', $content);
        }
        if (empty($key)) {
	        $key = '$key';
        }
        if (empty($item)) {
	        $item = '$item';
        }
        return [$key, $item];
    }

	protected function parseTag($content) {
	    if ($content == 'else' || $content == '+') {
	        return '<?php else: ?>';
        }
        if ($content == 'forelse' && end($this->forTags) == 'foreach') {
	        $this->forTags[count($this->forTags) - 1] = 'if';
	        return '<?php endforeach; else: ?>';
        }
        if ($content == '-') {
	        return '<?php endif; ?>';
        }
        return false;
    }

	protected function parseFirstTag($content) {
	    $first = substr($content, 0, 1);
	    if ($first == '>') {
	        return $this->parseBlock(substr($content, 1));
        }
        if ($first == '/') {
	        return $this->parseEndTag(substr($content, 1));
        }
        if ($first == '|') {
	        return '<?php if ('.substr($content, 1).'):?>';
        }
        if ($first == '+') {
	        return '<?php elseif ('.substr($content, 1).'):?>';
        }
        if ($first == '~') {
	        return '<?php for('.substr($content, 1).'):?>';
        }
        return false;
    }

	protected function parseEndTag($content) {
	    if ($content == '|' || $content == 'if') {
	        return '<?php endif;?>';
        }
        if ($content == '~' || $content == 'for') {
	        return $this->parseEndForTag();
        }
        if ($content == '*' || $content == 'switch') {
	        return '<?php endswitch ?>';
        }
        return false;
    }

    protected function parseEndForTag() {
	    if (count($this->forTags) == 0) {
	        return false;
        }
        $tag = array_pop($this->forTags);
        return '<?php end'.$tag.';?>';
    }

	protected function parseEndBlock() {
	    list($tag, $this->blockTag) = [$this->blockTag, false];
	    if ($tag == 'php' || $tag === '') {
	        return '?>';
        }
        if ($tag == 'js') {
            return '</script>';
        }
        if ($tag == 'css') {
            return '</style>';
        }
    }

	protected function parseBlock($content) {
        if ($content == '' || $content == 'php') {
            $this->blockTag = 'php';
            return '<?php ';
        }
        if ($content == 'js') {
            $this->blockTag = 'js';
            return '<script>';
        }
        if ($content == 'css') {
            $this->blockTag = 'css';
            return '<style>';
        }
        return sprintf('<?php %s; ?>', $content);
    }

	public function renderFile($file) {
		if (!is_file($file)) {
			return false;
		}
		return $this->render(file_get_contents($file));
	}

    protected function renderContent($content) {
        $obLevel = ob_get_level();
        ob_start();
        extract($this->get(), EXTR_SKIP);
        try {
            eval('?>'.$content);
        } catch (\Exception $e) {
            $this->handleViewException($e, $obLevel);
        } catch (\Throwable $e) {
            $this->handleViewException(new \Exception($e), $obLevel);
        }
        return ltrim(ob_get_clean());
    }
}