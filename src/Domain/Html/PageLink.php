<?php
namespace Zodream\Domain\Html;
/**
 * 分页类
 * 使用方式:
 * $page = new Page();
 */
use Zodream\Service\Routing\Url;

class PageLink extends Widget {
	
	protected $default = array(
		'total' => 0, //总条数
		'pageSize' => 20,
        'key' => 'page',
		'index' => 1,
		'length' => 8, //数字分页显示
		/**
		 * 分页显示模板
		 * 可用变量参数
		 * {total}      总数据条数
		 * {pageSize}   每页显示条数
		 * {start}      本页开始条数
		 * {end}        本页结束条数
		 * {pageNum}    共有多少页
		 * {pre}        上一页
		 * {next}       下一页
		 * {list}       数字分页
		 * {goto}       跳转按钮
		 */
		'template' => '<nav><ul class="pagination pagination-lg">{pre}{list}{next}</ul></nav>', //'<div><span>共有{total}条数据</span><span>每页显示{pagesize}条数据</span>,<span>本页{start}-{end}条数据</span><span>共有{pagenum}页</span><ul>{pre}{list}{next}{goto}</ul></div>'
		'active' => '<li class="active"><span>{text}</span></li>',//'<li class="active"><a href="javascript:;">{text}</a></li>';
		'common' => '<li><a href="{url}">{text}</a></li>',//'<li><a href="{url}">{text}</a></li>';
		'pre' => '《',
		'next' => '》'
	);
	/**
	 * 总页数
	 */
	private $_pageNum;

	/**
	 * 返回分页
	 * @return string
	 */
	protected function replace() {
		return str_ireplace(array(
				'{total}',
				'{pageSize}',
				'{start}',
				'{end}',
				'{pageNum}',
				'{pre}',
				'{next}', 
				'{list}',
				'{goto}',
		), array(
				$this->get('total'),
				$this->_setPageSize(),
				$this->_star(),
				$this->_end(),
				$this->_pageNum,
				$this->_prev(),
				$this->_next(),
				$this->_pageList(),
				$this->_gopage(),
		), $this->get('template'));
	}

	/**
	 * 本页开始条数
	 * @return int
	 */
	private function _star() {
		if ($this->get('total') == 0) {
			return 0;
		}
		return ($this->get('index') - 1) * $this->get('pageSize') + 1;
	}

	/**
	 * 本页结束条数
	 * @return int
	 */
	private function _end() {
		return min($this->get('index')* $this->get('pageSize'), $this->get('total'));
	}

	/**
	 * 设置当前页大小
	 * @return int
	 */
	private function _setPageSize() {
		return $this->_end() - $this->_star() + 1;
	}

	/**
	 * 上一页
	 * @return string
	 */
	private function _prev() {
		if ($this->get('index')> 1) {
			return $this->_replaceLine($this->get('index')- 1, $this->get('pre'));
		}
		return null;
	}

	/**
	 * 分页数字列表
	 * @return string
	 */
	private function _pageList() {
		if ($this->_pageNum < 2) {
			return null;
		}
		$linkPage = '';
		$linkPage .= $this->_replaceLine(1);
		$lastList= floor($this->get('length') / 2);
		$i = 0;
		$length = 0;
		if ($this->_pageNum < $this->get('length') || $this->get('index')- $lastList< 2 || $this->_pageNum - $this->get('length') < 2) {
			$i = 2;
			if ($this->_pageNum <= $this->get('length')) {
				$length = $this->_pageNum - 1;
			} else {
				$length = $this->get('length');
			}
		} elseif ($this->get('index')- $lastList>= 2 && $this->get('index')+ $lastList<= $this->_pageNum) {
			$i = $this->get('index')- $lastList;
			$length = $this->get('index')+ $lastList- 1;
		} elseif ($this->get('index')+ $lastList> $this->_pageNum) {
			$i = $this->_pageNum - $this->get('length') + 1;
			$length = $this->_pageNum - 1;
		}
		if ($this->get('index')> $lastList+ 1 && $i > 2) {
			$linkPage .= $this->_replace(null, '...');
		}
		for (; $i <= $length; $i ++) {
			$linkPage .= $this->_replaceLine($i);
		}
		if ($this->get('index')< $this->_pageNum - $lastList&& $length < $this->_pageNum - 1) {
			$linkPage .= $this->_replace(null, '...');
		}
		$linkPage .= $this->_replaceLine($this->_pageNum);
		return $linkPage;
	}

	/**
	 * 下一页
	 * @return string
	 */
	private function _next() {
		if ($this->get('index')< $this->_pageNum) {
			return $this->_replaceLine($this->get('index')+ 1, $this->get('next'));
		}
		return null;
	}

	/**
	 * 跳转按钮
	 * @return string
	 */
	private function _goPage() {
		return '&nbsp;<input type="text" value="' . $this->get('index'). '" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>' . $this->_pageNum . ')?' . $this->_pageNum . ':this.value;location=\'' . Url::to(null) . '&page=\'+page+\'\'}" style="width:25px;"/><input type="button" onclick="javascript:var page=(this.previousSibling.value>' . $this->_pageNum . ')?' . $this->_pageNum . ':this.previousSibling.value;location=\'' . Url::to() . '&page=\'+page+\'\'" value="GO"/>';
	}
	
	private function _replaceLine($page, $text = null) {
		return $this->_replace(
				Url::to(null, array(
					$this->get('key') => $page
				)),
				$text == null ? $page : $text, 
				$page == $this->get('index')
		);
	}

	/**
	 * 模板替换
	 * @param string $url 替换内容
	 * @param string $text
	 * @param bool|string $result 条件
	 * @return string
	 */
	private function _replace($url, $text, $result = TRUE) {
		$template = ($result ? $this->get('active') : $this->get('common'));
		$html = str_replace('{url}', $url, $template);
		return str_replace('{text}', $text, $html);
	}

	/**
	 * 执行
	 * @return string
	 */
	protected function run() {
		$this->_pageNum = ceil($this->get('total') / $this->get('pageSize'));
		return $this->replace();
	}
}