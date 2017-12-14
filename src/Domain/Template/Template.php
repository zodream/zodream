<?php
namespace Zodream\Domain\Template;

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

    protected $blockTags = [];

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
        if (false !== ($line = $this->parseTag($content))) {
            return $line;
        }
		if (false !== ($line = $this->parseFirstTag($content))) {
            return $line;
        }
        if (strpos($content, ':') > 0 && false !== ($line = $this->parseBlockTag($content))) {
            return $line;
        }

        return $match[0];
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
	    $args = explode(',', $content);
	    $length = count($args);
	    if ($length == 1) {
	        return '<?php while('.$content.'):?>';
        }
        if ($length == 2) {
	        return sprintf('<?php foreach(%s as %s):?>', $args[0], $args[1]);
        }
        $tag = substr(trim($args[2]), 0, 1);

        if (!in_array($tag, ['<', '>', '='])) {
            return sprintf('<?php $i = 0; foreach(%s as %s): $i ++; if ($i > %s): break; endif;?>',
                $args[0],
                $args[1],
                $args[2]);
        }
        list($key, $item) = $this->getForItem($args[1]);
        return sprintf('<?php foreach(%s as %s=>%s): if (!(%s %s)): break; endif;?>',
            $args[0], $key, $item, $key,  $args[2]);
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
	        return '<?php endfor;?>';
        }
        if ($content == '*' || $content == 'switch') {
	        return '<?php endswitch ?>';
        }
        return false;
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