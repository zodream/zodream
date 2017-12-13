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
		if ($this->isBlock($content)) {
		    return $this->parseBlock($content);
        }
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
        if ($content == '/>') {
            return $this->parseEndBlock();
        }
        if ($this->blockTag == '') {
            return '<?php'.PHP_EOL;
        }
        if ($this->blockTag == 'js') {
            return '<script>';
        }
        if ($this->blockTag == 'css') {
            return '<style>';
        }
    }

	protected function isBlock($content) {
	    if ($content == '/>') {
	        return true;
        }
	    $first = substr($content, 0, 1);
	    if ($first != '>') {
	        return false;
        }
        if (substr($content, 1, 1) === ' ') {
	        return false;
        }
        $this->blockTag = substr($content, 1);
	    return true;
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