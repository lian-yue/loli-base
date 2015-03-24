<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-03-23 13:58:27
/*
/* ************************************************************************** */
namespace Loli;
class HTML{


	// 储存所有 标签
	public $tag = [
		'a', 'abbr', 'acronym', 'address', 'applet', 'area', 'article', 'aside', 'audio',
		'b', 'basefont', 'bdi', 'bdo', 'big', 'blockquote', 'blockcode', 'body', 'br', 'button',
		'canvas', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'command',
		'datalist', 'dd', 'del', 'details', 'dfn', 'dir', 'div', 'dl', 'dt',
		'em', 'embed',
		'fieldset', 'figcaption', 'figure', 'font', 'footer', 'form', 'frame', 'frameset',
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html',
		'i', 'iframe', 'img', 'input', 'ins',
		'keygen', 'kbd',
		'label', 'legend', 'li', 'link',
		'map', 'mark', 'menu', 'meta', 'meter',
		'nav', 'noframes', 'noscript',
		'object', 'ol', 'optgroup', 'option', 'output',
		'p', 'param', 'pre', 'progress', 'polygon',
		'q',
		'rp', 'rt', 'ruby',
		's', 'samp', 'script', 'select', 'small', 'source', 'span', 'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'svg',
		'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'tt',
		'u', 'ul',
		'var', 'video',
		'wbr',
		'xmp',
	];

	// 下面是特殊的 html 标签
	// 'base', 'html', 'meta', 'link', 'script', 'style', 'head', 'body', 'title', 'noframes', 'noscript', 'frameset', 'frame', 'iframe', 'applet', 'polygon', 'svg'
	// 'object', 'embed'
	// 'form', 'input', 'select', 'option', 'textarea', 'button'

	// 单标签
	public $single = [
		'base', 'basefont', 'br',
		'col',
		'embed',
		'frame',
		'hr',
		'img',
		'input',
		'keygen',
		'link',
		'meta',
		'param',
		'source',
		'track',
	];

	// 内联元素
	public $inline = [
		'a', 'abbr', 'acronym', 'audio',
		'b', 'bdo', 'big', 'br',
		'cite', 'code',
		'dfn',
		'em',
		'font',
		'i', 'img', 'input',
		'kbd',
		'label',
		'q',
		's', 'samp', 'select', 'small', 'span', 'strike', 'strike', 'strong', 'sub', 'sup', 'svg',
		'textarea', 'tt', 'time', 'meter', 'option',
		'u',
		'var', 'video',
	];


	// 行内块元素
	public $inlineBlock = [
		'applet',
		'button',
		'del',
		'iframe',
		'frame',
		'ins',
		'map',
		'object',
		'param',
		'script',
	];


	// 空标签  嵌入text使用标签 和 上下级元素不匹配使用标签
	public $element = 'p';

	// 并列的标签
	public $abreast = 'p';

	// 默认回调函数
	public $call = '';

	// text 回调
	public $text = '';

	// 自定义信息 call = 自定义回调函数, attr = attr 回调函数, nested = 限制嵌套层次,   parents = 允许父级(多层次),  parent = 允许父级(单层次),  refuses = 拒绝子级(多层次),  refuse = 拒绝子级(单层次), allows = 允许子级(多层次),  allow = 允许子级(单层次), count = 限制标签数量
	public $custom = [];

	// attr 类
	public $Attr;

	// cache 缓存
	public $cache = [];

	// 栈
	public $stack = [];

	// 栈嵌套层次
	public $layer = [];

	// 储存返回值
	public $html = '';

	// 上面一个标签
	public $above = '';

	// 数量
	public $count = [];


	public function __construct() {

		// 默认函数
		$this->Attr = new Attr;
		$this->call = [$this, 'defaultCall'];

		$this->custom['a']['refuses'] = ['button', 'a', 'input', 'form', 'textarea'];


		$inline = array_merge($this->inline, $this->inlineBlock);
		$this->custom['h1']['allows'] = &$inline;
		$this->custom['h2']['allows'] = &$inline;
		$this->custom['h3']['allows'] = &$inline;
		$this->custom['h4']['allows'] = &$inline;
		$this->custom['h5']['allows'] = &$inline;
		$this->custom['h6']['allows'] = &$inline;
		$this->custom['p']['allows'] = &$inline;
		$this->custom['p']['nested'] = 0;
		$this->custom['optgroup']['nested'] = 0;
		$this->custom['option']['nested'] = 0;

		$this->custom['h1']['count'] = 1;
		$this->custom['h2']['count'] = 3;
		$this->custom['h3']['count'] = 6;

		$this->custom['frame']['refuses'] = ['frame'];

		$this->custom['form']['refuses'] = ['form'];

		$this->custom['button']['refuses'] = ['textarea', 'input', 'button', 'select', 'label', 'form', 'fieldset', 'iframe'];
		$this->custom['label']['refuses'] = ['label'];

		$this->custom['textarea']['refuses'] = ['textarea'];

		$this->custom['ul']['refuse'] = ['dl', 'ul', 'ol', 'nl'];
		$this->custom['ul']['allow'] = ['li'];

		$this->custom['ol']['refuse'] = ['dl', 'ul', 'ol', 'nl'];
		$this->custom['ol']['allow'] = ['li'];

		$this->custom['nl']['refuse'] = ['dl', 'ul', 'ol', 'nl'];
		$this->custom['nl']['allow'] = ['li'];

		$this->custom['dl']['refuse'] = ['dl', 'ul', 'ol', 'nl'];
		$this->custom['dl']['allow'] = ['dt', 'dd'];

		$this->custom['li']['parent'] = ['ul', 'ol', 'nl'];
		$this->custom['li']['refuse'] = ['li'];

		$this->custom['dt']['parent'] = ['dl'];
		$this->custom['dt']['allows'] = &$inline;

		$this->custom['dd']['parent'] = ['dl'];
		$this->custom['dd']['refuse'] = ['dd'];

		$this->custom['table']['allow'] = ['thead', 'tbody', 'tr', 'caption', 'colgroup', 'col'];

		$this->custom['thead']['parent'] = ['table'];
		$this->custom['thead']['allow'] = ['tr'];

		$this->custom['tbody']['parent'] = ['table'];
		$this->custom['tbody']['allow'] = ['tr'];

		$this->custom['caption']['parent'] = ['table'];

		$this->custom['colgroup']['parent'] = ['table'];

		$this->custom['col']['parent'] = ['table', 'colgroup'];

		$this->custom['tr']['parent'] = ['tr'];
		$this->custom['tr']['allow'] = ['td', 'th'];

		$this->custom['td']['parent'] = ['tr'];
		$this->custom['th']['parent'] = ['tr'];


		$this->custom['param']['refuse'] = ['object', 'applet'];
		$this->custom['param']['call'] = [$this, 'param'];

		$this->custom['pre']['refuses'] = ['img', 'object', 'big', 'samll', 'sub', 'sup', 'pre'];

		$this->custom['object']['allow'] = ['param','embed'];
		$this->custom['embed']['allow'] = [];
		$this->custom['script']['allow'] = [];
		$this->custom['style']['allow'] = [];
		$this->custom['style']['call'] = [$this, 'style'];

	}


	public function __invoke() {
		return call_user_func_array([$this, 'format'], func_get_args());
	}

	/**
	*	执行 tag 标签
	*
	*	1 参数 代码
	*
	*	返回值经过 处理后的代码
	**/
	public function format($html) {

		// 缓存
		$cache = $html;
		if (!empty($this->cache['format'][$cache])) {
			return $this->cache['format'][$cache];
		}

		// 返回值 变量
		$this->html = $this->above = '';
		$this->count = $this->stack = $this->layer = [];

		// 标签排序
		usort($this->tag, [$this, 'sort']);


		$tag = [];
		foreach ($this->tag as $v) {
			$tag[$v] = in_array($v, $this->single);
		}

		// 移除头尾空格
		$html = trim($html);

		// 没有 tag的
		if (!$tag) {
			return $this->cache['format'][$cache] = $this->text(strip_tags($html));
		}

		// 没有标签
		if (!$html || !strstr($html, '<') || !($arr = preg_split("/\<(\s*\/\s*)?([a-z0-9]+)((?:\s+(?:\s*(?:[0-9a-z_:-]+)\s*(?:\=\s*(?:\"[^\"]*\"|\'[^\']*\'|\w*))?)*|))(?(1)|\s*\/?\s*)\>/is", $html, -1, PREG_SPLIT_DELIM_CAPTURE))) {
			$this->element && $this->_push($this->element);
			$this->html($this->text($html));
			$this->_pop();
			return $this->cache['format'][$cache] = trim($this->html);
		}

		// 遍历
		$type = 3;
		$continue = false;
		foreach ($arr as $k => $v) {
			if ($continue) {
				$continue = false;
				continue;
			}

			switch($type) {

				case 1:
					// 开始标签
					$type = 3;
					$continue = true;
					$v = strtolower($v);
					// 无效标签
					if (!isset($tag[$v])) {
						break;
					}
					$type = $v == 'script' ? 4 : $type;

					// attr
					$attr = $arr[$k+1];

					// 单标签
					if ($tag[$v]) {

						// 限制子级
						$this->_sub($v);

						// 限制父级
						if (!$this->_parent($v)) {
							break;
						}

						$this->html($this->single($v, $attr));
						break;
					}


					// 限制嵌套数量
					if ($this->layer && isset($this->custom[$v]['nested']) && in_array($v, $this->layer)) {
						$count = array_count_values($this->layer);
						$count = $count[$v];
						while ($count > $this->custom[$v]['nested'] && $this->stack) {
							if ($this->_pop() == $v) {
								$count--;
							}
						}
					}

					// 限制子级
					$this->_sub($v);

					// 限制父级
					if (!$this->_parent($v)) {
						break;
					}
					$this->_push($v, $attr);

					break;
				case 2:
					// 闭合标签
					$type = 3;
					$continue = true;
					$v =strtolower($v);
					if (!isset($tag[$v]) || !$tag[$v]) {
						 $this->_pop($v);
					}
					break;
				case 3:
					// text 字符串
					$type = empty($arr[$k+1]) ? 1 : 2;
					$continue = true;
					if ($v) {
						if (!$this->stack && $this->element && trim($v)) {
							$this->_push($this->element);
						}
						$this->html($this->text($v));
					}
					break;
				case 4:
					switch($k % 4) {
						case 1:
							if ($v && !empty($arr[$k+1]) && strtolower($arr[$k+1]) == 'script') {
								$type = 2;
							} else {
								$this->html('<' .$v);
							}
							break;
						case 3:
							$this->html($v. '>');
							break;
						default:
							$this->html($v);
					}
					break;
				default:

			}
		}
		while (!empty($this->stack)) {
			$this->_pop();
		}
		return $this->cache['format'][$cache] = trim($this->html);
	}


	/**
	*	允许父级
	*
	*	1 参数
	*
	*	返回值 true false
	**/
	private function _parent($tag) {
		// 允许嵌套的父级 (多层次)
		if (!empty($this->custom[$tag]['parents']) && !in_array($a['tag'], $this->custom[$tag]['parents'])) {
			return false;
		}

		// 允许嵌套的父级 (单层次)
		if (!empty($this->custom[$tag]['parent']) && (!$this->layer || $tag == end($this->layer))) {
			return false;
		}
		return true;
	}


	/**
	*	允许子级
	*
	*	1 参数 当前 tag 标签
	*
	*	返回值 无
	**/
	private function _sub($tag) {
		$i = 0;
		do {

			// 是否再次执行
			$while = false;


			// 内联 可变 拒绝 允许 标签
			if ($this->stack) {
				// 内联 标签 限制
				while (in_array(end($this->layer), $this->inline) && !in_array($tag, $this->inline) && !in_array($tag, $this->inlineBlock)) {
					$this->_pop();
				}

				// 拒绝 允许 子 标签 多级
				$count = 0;
				foreach ($this->layer as $kk => $vv) {
					if ($count) {
						$count++;
					} elseif (isset($this->custom[$vv]['allows']) && !in_array($tag, $this->custom[$vv]['allows'])) {
						$count++;
					} elseif (isset($this->custom[$vv]['refuses']) && in_array($tag, $this->custom[$vv]['refuses'])) {
						$count++;
					}
				}
				while ($count) {
					$this->_pop();
					$count--;
				}


				// 拒绝 允许 子 标签 单级
				$vv = end($this->layer);
				if (isset($this->custom[$vv]['allow']) && !in_array($tag, $this->custom[$vv]['allow'])) {
					$while = true;
					$this->_pop();
				} elseif (isset($this->custom[$vv]['refuse']) && in_array($tag, $this->custom[$vv]['refuse'])) {
					$while = true;
					$this->_pop();
				}
			}

			// 块级元素 内联元素 出现并列
			if ($this->abreast && $this->above && !in_array($this->above, $this->inline) && !in_array($this->above, $this->inlineBlock) && in_array($tag, $this->inline)) {
				$this->_push($this->abreast);
				$while = true;
			}

			// 直接是 内联元素
			if ($this->element && !$this->stack && !$this->layer && in_array($tag, $this->inline)) {
				$this->_push($this->abreast);
				$while = true;
			}

			$i++;
		} while($while && $i < 3);
	}



	/**
	*	打开 一个 标签
	*
	*	1 参数
	*
	*	返回值 str 字符串
	**/
	private function _push($tag, $attr = '') {
		if (empty($this->count[$tag])) {
			$this->count[$tag] = 0;
		}
		if (!empty($this->custom[$tag]['count']) && $this->count[$tag] >= $this->custom[$tag]['count']) {
			return false;
		}
		$this->count[$tag]++;
		$a['tag'] = $tag;
		$a['attr'] = empty($attr) ? [] : $this->attr($attr, $tag);
		$a['single'] = false;
		$a['html'] = '';
		$this->above = '';
		$this->stack[] = $a;
		$this->layer[] = $a['tag'];
		return true;
	}

	/**
	*	关闭 最后个 标签
	*
	*	1 参数 如果制定了 tag 就要先判断是否对
	*
	*	返回值 true false
	**/
	private function _pop($tag = '') {
		if (!$a = array_pop($this->stack)) {
			return false;
		}
		if ($tag && $tag  != $a['tag']) {
			$this->stack[] = $a;
			return false;
		}

		array_pop($this->layer);
		$this->above = $a['tag'];

		$this->html($this->call($a));
		return $a['tag'];
	}




	/**
	*	单标签写入
	*
	*	1 参数 tag 名
	*	2 参数 attr
	*
	*	返回值 true false;
	**/
	public function single($tag, $attr = '') {
		if (empty($this->count[$tag])) {
			$this->count[$tag] = 0;
		}
		if (!empty($this->custom[$tag]['count']) && $this->count[$tag] >= $this->custom[$tag]['count']) {
			return;
		}
		$this->count[$tag]++;

		$a['tag'] = $tag;
		$a['attr'] = $attr ? $this->attr($attr, $tag) : [];
		$a['single'] = true;
		$a['html'] = '';
		return $this->call($a);
	}



	/**
	*	其他代码转换
	*
	*	1 参数 val
	*
	*	返回值 转换后的
	**/
	public function text($a) {
		$a = strtr($a, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']);
		$this->text && ($a = call_user_func($this->text, $a, $this));
		return $a;
	}



	/**
	*	写入 html 字符串
	*
	*	1 参数 字符串
	*
	*	返回值 true
	**/
	public function html($html) {
		if ($this->stack) {
			$a = array_pop($this->stack);
			$a['html'] .= $html;
			$this->stack[] = $a;
		} else {
			$this->html .= $html;
		}
		return true;
	}

	/**
	*	回调函数应用
	*
	*	1 参数
	*
	*	返回值 str 字符串
	**/
	public function call($a) {
		return call_user_func(empty($this->custom[$a['tag']]['call']) ? $this->call : $this->custom[$a['tag']]['call'], $a['tag'], $a['attr'], $a['single'], $a['html'], $this);
	}



	/**
	* 	默认回调函数
	*
	*	1 参数 标签名字
	*	2 参数 标签参数
	*	3 参数 是否单标签
	*	4 参数 如果不是单标签就有标签内容
	*
	*	返回值 字符串
	**/
	public function defaultCall($tag, array $attr, $single = false, $html = '') {
		// attr
		foreach ($attr as $k => $v) {
			if (!isset($attr[$k]) || ($v = $this->Attr->run($attr[$k], $k, $tag)) === NULL) {
				unset($attr[$k]);
				continue;
			}
			$attr[$k] = $v;
		}

 		// a 链接强制新窗口
		if ($tag == 'a' && !empty($attr['href'])) {
			$attr['target'] = '_blank';
		}

		// 默认 flash
		if ($tag == 'embed' && (empty($attr['type']) || strpos($attr['type'], '/'))) {
			$attr['type'] = 'application/x-shockwave-flash';
		}
		if ($tag == 'object') {
			if (!empty($attr['type'])) {
				$attr['type'] = strpos($attr['type'], '/') ? $attr['type'] : 'application/x-shockwave-flash';
			} elseif (empty($attr['classid'])) {
				$attr['classid'] = 'clsid:d27cdb6e-ae6d-11cf-96b8-444553540000';
			}
		}
		$a = '';
		foreach ($attr as $k => $v) {
			$a .= ' '. $k . '="' . $v .'"';
		}
		return $single ? "<{$tag}{$a} />" : "<{$tag}{$a}>{$html}</{$tag}>";
	}

	/**
	* 	style 回调函数
	*
	*	1 参数 标签名字
	*	2 参数 标签参数
	*	3 参数 是否单标签
	*	4 参数 如果不是单标签就有标签内容
	*
	*	返回值 字符串
	**/
	public function style($tag, array $attr, $single = false, $html = '') {
		$media = empty($attr['media']) ? 'all' : $this->Attr->run($attr['media'], 'media', 'style');

		// 移除注释
		$html = preg_replace('/\/\*.*?\*\//is', '', $html);

		// 移除很特殊的css匹配
		//charset  document  font-face import keyframes media page supports
		$html = preg_replace('/@((charset|import).*?;|(font-face|page).*?\}|(document|keyframes|supports)[^\{]*?(\{\s*\}|.*?\}\s*\})|media[^\{]*?\{\s*\})/is', '', $html);

		$r = '';
		if (preg_match_all('/(?:(?:(@media)(.*?)\{(.*?\})\s*\})|((?:(?!@media).)*))/is', $html, $matchs)) {
			foreach ($matchs[1] as  $k => $v) {
				$t = $v == '@media' ? "\t" : '' ;
				if ($t) {
					$r .= '@media ' . $this->Attr->run($matchs[2][$k], 'media', 'style') . '{' ."\n";
				}
				$style = [];
				foreach(explode('}', $matchs[$v == '@media' ? 3: 4][$k]) as $vv){
					if (count($vv = explode('{', $vv)) != 2 || !$vv[1] || !($vv[1] = trim($vv[1]))) {
						continue;
					}
					$vv[0] = explode(',', $vv[0]);
					foreach ($vv[0] as $kkk => &$vvv) {
						if (!$vvv || !($vvv = trim($vvv)) || !preg_match('/^([.#]?)([0-9a-z.#*:\(\) _-]+)$/i', $vvv, $prefix) || ($this->Attr->prefix && (!$prefix[1] || substr($prefix[2], 0, strlen($this->Attr->prefix)) != $this->Attr->prefix))) {
							unset($vv[0][$kkk]);
							continue;
						}
					}
					if (!$vv[0] || !($vv[1] = $this->Attr->run($vv[1], 'style', 'style'))) {
						continue;
					}

					$style[] = $vv;
				}
				foreach ($style as $vv) {
					$r .= $t. implode(', ', $vv[0]);
					$r .= '{'. $vv[1] .'}' ."\n";
				}

				if ($t) {
					$r .=  '}' ."\n";
				}
			}
		}
		return '<style type="text/css" media="'. $media .'">' . "\n". $r .'</style>';
	}


	/**
	* 	param 回调函数
	*
	*	1 参数 标签名字
	*	2 参数 标签参数
	*	3 参数 是否单标签
	*	4 参数 如果不是单标签就有标签内容
	*
	*	返回值 字符串
	**/
	public function param($tag, array $attr, $single = false, $html = '') {
		$tag = end($this->stack);
		if (empty($attr['name']) || !isset($attr['value']) || ($attr['name'] == 'type' && !strpos($attr['value'], '/')) || ($attr['value'] = $this->Attr->run($attr['value'],  $attr['name'], $tag)) === NULL) {
			return '';
		}
		$attr['value'] = $this->Attr->run($attr['value'],  $attr['name'], $tag);
		foreach($attr as $k => &$v) {
			if (!in_array($k, ['name', 'value']) && ($v = $this->Attr->run($v, $k, $tag)) === NULL) {
				unset($attr[$k]);
			}
		}
		unset($v);
		$a = '';
		foreach ($attr as $k => $v) {
			$a .= ' '. $k . '="' . $v .'"';
		}
		return "<param{$a} />";
	}


	/**
	*	解析 html 参数
	*
	*	1 参数 attr 引用
	*
	*	返回值解析后的数组
	**/
	public function attr($attr, $tag) {
		if (!$attr) {
			return [];
		}
		$cache = $attr;
		if (isset($this->cache['attr'][$cache])) {
			$r = $this->cache['attr'][$cache];
		} else {
			$r = [];
			if (preg_match_all("/\s*([0-9a-z_-]+)\s*(?:\=\s*(?:\"([^\"]*)\"|\'([^\']*)\'|(\w*))|)/is", $attr, $matchs)) {
				foreach ($matchs[1] as $k => $v) {
					if ($v) {
						if ($matchs[2][$k] != '') {
							$vv = $matchs[2][$k];
						} elseif ($matchs[3][$k] != '') {
							$vv = $matchs[3][$k];
						} elseif ($matchs[4][$k] != '') {
							$vv = $matchs[4][$k];
						} else {
							$vv = '';
						}
						$r[strtolower($v)] = strtr($vv, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']);
					}
				}
			}
			$this->cache['attr'][$cache] = $r;
		}
		if (!empty($this->custom[$tag]['attr'])) {
			$r = call_user_func($this->custom[$tag]['attr'], $r, $tag, $this);
		}
		return $r;
	}


	 public function sort($a, $b) {
		if (($a = strlen($a)) == ($b = strlen($b))) {
			return 0;
		}
		return ($a > $b) ? 1 : -1;
	}
}















class Attr{

	// 回调函数
	public $call = [];

	// 全部 style 属性
	public $style = [
		'background' => '', 'background-attachment' => '', 'background-color' => '', 'background-image' => '', 'background-position' => '', 'background-repeat' => '', 'background-clip' => '', 'background-origin' => '', 'background-size' => '',
		'border' => '', 'border-bottom' => '', 'border-bottom-color' => '', 'border-bottom-style' => '', 'border-bottom-width' => '', 'border-color' => '', 'border-left' => '', 'border-left-color' => '', 'border-left-style' => '', 'border-left-width' => '', 'border-right' => '', 'border-right-color' => '', 'border-right-style' => '', 'border-right-width' => '', 'border-style' => '', 'border-top' => '', 'border-top-color' => '', 'border-top-style' => '', 'border-top-width' => '', 'border-width' => '', 'border-collapse' => '', 'border-spacing' => '', 'border-bottom-left-radius' => '', 'border-bottom-right-radius' => '', 'border-image' => '', 'border-image-outset' => '', 'border-image-repeat' => '', 'border-image-slice' => '', 'border-image-source' => '', 'border-image-width' => '', 'border-radius' => '', 'border-top-left-radius' => '', 'border-top-right-radius' => '',
		'box-shadow' => '',
		'outline' => '', 'outline-color' => '', 'outline-style' => '', 'outline-width' => '',
		'overflow' => '', 'overflow-x' => '', 'overflow-y' => '', 'overflow-style' => '',
		'opacity' => '',
		'height' => '', 'width' => '', 'max-height' => '', 'max-width' => '', 'min-height' => '', 'min-width' => '',
		'font' => '', 'font-family' => '', 'font-size' => '', 'font-style' => '', 'font-variant' => '', 'font-weight' => '', 'font-size-adjust' => '',
		'list-style' => '', 'list-style-image' => '', 'list-style-position' => '', 'list-style-type' => '',
		'line-height' => '', 'text-shadow' => '', 'text-overflow' => '', 'white-space' => '',
		'text-align' => '', 'text-indent' => '', 'text-transform' => '', 'text-decoration' => '',
		'margin' => '', 'margin-bottom' => '', 'margin-left' => '', 'margin-right' => '', 'margin-top' => '',
		'padding' => '', 'padding-bottom' => '', 'padding-left' => '', 'padding-right' => '', 'padding-top' => '',
		'position' => '', 'left' => '', 'top' => '', 'bottom' => '',
		'display' => '',
		'visibility' => '',
		'z-index' => '',
		'clear' => '',
		'cursor' => '',
		'float' => '',
		'color' => '',
		'vertical-align' => '',
		'white-profile' => '',
		'word-spacing' => '', 'word-wrap' => '',
		'caption-side' => '',
		'empty-cells' => '',
		'table-layout' => '',
		'counter-reset' => '',
		'scrollbar-face-color' => '',
		'scrollbar-track-color' => '',
		'scrollbar-arrow-color' => '',


		// css 3
		'animation' => '', 'animation-name' => '', 'animation-duration' => '', 'animation-timing-function' => '', 'animation-delay' => '', 'animation-iteration-count' => '', 'animation-direction' => '', 'animation-play-state' => '',
		'box-align' => '', 'box-direction' => '', 'box-flex' => '', 'box-flex-group' => '', 'box-lines' => '', 'box-ordinal-group' => '', 'box-orient' => '', 'box-pack' => '',
		'column-count' => '', 'column-fill' => '', 'column-gap' => '', 'column-rule' => '', 'column-rule-color' => '', 'column-rule-style' => '', 'column-rule-width' => '', 'column-span' => '', 'column-width' => '', 'columns' => '',
		'transform' => '', 'transform-origin' => '', 'transform-style' => '', 'perspective' => '', 'perspective-origin' => '', 'backface-visibility' => '',
		'transition' => '', 'transition-property' => '', 'transition-duration' => '', 'transition-timing-function' => '', 'transition-delay' => '',

		// media 的
		'grid' => '', 'scan' => '', 'resolution' => '', 'monochrome' => '', 'min-color-index' => '', 'max-color-index' => '', 'device-height' => '', 'device-width' => '',
	];

	// css 图片
	public $styleUrl = ['background', 'background-attachment'];

	// 允许 name  class id 的前缀
	public $prefix = '';

	// 允许的class 名
	public $class = [];

	// 允许的 id 名
	public $id = [];

	// 允许的 name
	public $name = [];

	// 允许 的 type
	public $type = ['application/x-shockwave-flash' => ['embed', 'object'], 'text/javascript' => ['script'], 'hidden'=> ['input'], 'text'=> ['input'], 'password'=> ['input'], 'reset'=> ['input'], 'radio'=> ['input'], 'checkbox'=> ['input'], 'image' => ['input'], 'submit'=> ['input', 'button'], 'email'=> ['input'], 'url'=> ['input'], 'number'=> ['input'], 'range'=> ['input'], 'search'=> ['input'], 'color'=> ['input'], 'date'=> ['input'], 'month'=> ['input'], 'week'=> ['input'], 'time' => ['input'], 'datetime'=> ['input'], 'datetime-local'=> ['input']];

	// classid ie 的
	public $classid = ['clsid:d27cdb6e-ae6d-11cf-96b8-444553540000'];

	// 缓存
	public $cache = [];

	// 允许的  target
	public $target = ['_blank'];

	public $rel = ['stylesheet' => ['link']];
	/**
	*	默认添加
	*
	*	无参数
	*
	*	无返回值
	**/
	public function __construct() {
		$this->call = [
			'src' => [$this, 'url'],
			'data' => [$this, 'url'],
			'cite' => [$this, 'url'],
			'action' => [$this, 'url'],
			'movie' => [$this, 'url'],
			'base' => [$this, 'url'],
			'href' => [$this, 'url'],

			'span' => [$this, 'intval'],
			'rows' => [$this, 'intval'],
			'cols' => [$this, 'intval'],
			'size' => [$this, 'intval'],
			'border' => [$this, 'intval'],
			'colspan' => [$this, 'intval'],
			'rowspan' => [$this, 'intval'],
			'maxlength' => [$this, 'intval'],
			'cellpadding' => [$this, 'intval'],
			'cellspacing' => [$this, 'intval'],
			'frameborder' => [$this, 'intval'],
			'tabIndex' => [$this, 'intval'],


			'width' => [$this, 'widthHeight'],
			'height' => [$this, 'widthHeight'],

			'value' => [$this, 'value'],
			'title' => [$this, 'value'],
			'alt' => [$this, 'value'],
			'vars' => [$this, 'value'],
			'flashvars' => [$this, 'value'],
			'themenu' => [$this, 'value'],
			'salign' => [$this, 'value'],
			'allowfullscreen' => [$this, 'value'],
			'contenteditable' => [$this, 'value'],
			'spellcheck' => [$this, 'value'],
			'defaultvalue' => [$this, 'value'],
			'pattern' => [$this, 'value'],
			'placeholder' => [$this, 'value'],

			'min' => [$this, 'minMaxStep'],
			'max' => [$this, 'minMaxStep'],
			'step' => [$this, 'minMaxStep'],

			'color' => [$this, 'color'],
			'bgcolor' => [$this, 'color'],

			'controls' => [$this, 'one'],
			'autoplay' => [$this, 'one'],
			'checked' => [$this, 'one'],
			'disabled' => [$this, 'one'],
			'readonly' => [$this, 'one'],
			'required' => [$this, 'one'],
			'autofocus' => [$this, 'one'],
			'async' => [$this, 'one'],
			'defer' => [$this, 'one'],


			'id' => [$this, 'id'],
			'for' => [$this, 'id'],
			'list' => [$this, 'id'],
			'form' => [$this, 'id'],


			'rel' => [$this, 'rel'],
			'name' => [$this, 'name'],
			'type' => [$this, 'type'],
			'style' => [$this, 'style'],
			'class' => [$this, 'class_'],
			'target' => [$this, 'target'],
			'datetime' => [$this, 'datetime'],
			'lang' => [$this, 'lang'],
			'dir' => [$this, 'dir'],
			'usemap' => [$this, 'usemap'],
			'shape' => [$this, 'shape'],
			'coords' => [$this, 'coords'],
			'method' => [$this, 'method'],
			'align' => [$this, 'align'],
			'rules' => [$this, 'rules'],
			'frame' => [$this, 'frame'],
			'valign' => [$this, 'valign'],
			'media' => [$this, 'media'],
			'wmode' => [$this, 'wmode'],
			'quality' => [$this, 'quality'],
			'scale' => [$this, 'scale'],
			'autocomplete' => [$this, 'autocomplete'],
			'classid' => [$this, 'classid'],

			//onabort
			//onblur
			//onchange
			//onclick
			//ondblclick
			//onerror
			//onfocus
			//onkeydown
			//onkeypress
			//onkeyup
			//onload
			//onmousedown
			//onmousemove
			//onmouseout
			//onmouseover
			//onmouseup
			//onreset
			//onresize
			//onselect
			//onsubmit
			//onunload
		];

	}

	/**
	*	运行返回
	*
	*	1 参数 值
	*	2 参数 名称
	*	3 参数 标签名
	*
	**/
	public function run($value, $attr, $tag = '') {
		$attr = strtolower($attr);
		return empty($this->call[$attr]) ? NULL : call_user_func($this->call[$attr], $value, $attr, $tag);
	}



	/**
	*	controls autoplay
	**/
	public function  one() {
		return 1;
	}

	/**
	*	text
	**/
	public function  value($value) {
		return (string) $value;
	}


	/**
	*	size height width border
	**/
	public function intval($value) {
		return intval($value);
	}


	/**
	*	src data cite
	**/
	public function url($value) {
		if (empty($value)) {
			return NULL;
		}
		if (isset($this->cache['url'][$value]) || (isset($this->data['url']) && array_key_exists($value, $this->data['url']))) {
			return $this->cache['url'][$value];
		}
		$parse = parse_url($value);
		if (!empty($parse['scheme']) && !in_array(strtolower($parse['scheme']), ['http', 'https', 'ftp', 'gopher', 'news', 'telnet', 'rtsp', 'mms', 'callto', 'bctp', 'synacast', 'thunder', 'flashget', 'qqid', 'magnet', 'ed2k'])) {
			return $this->cache['url'][$value] = NULL;
		}
		return $this->cache['url'][$value] = $value;
	}




	/**
	*	style
	**/
	public function style($value) {
		if (empty($value)) {
			return NULL;
		}
		if (isset($this->cache['style'][$value]) || (isset($this->data['style']) && array_key_exists($value, $this->data['style']))) {
			return $this->cache['style'][$value];
		}
		$style = [];
		foreach (explode(';', preg_replace('/\s+/is', ' ', preg_replace('/\/\*.*?(\*\/|$)&quot;|&#039;|&lt;|&gt;|&|\\\\|"|\'|\>|</is', $value))) as $v) {
			$v = explode(':', $v, 2);
			if (count($v) != 2) {
				continue;
			}

			$name = strtolower(trim($v[0]));
			if ((substr($name, 0, 3) == '-o-' && !isset($this->style[$key = substr($name, 3)])) || (substr($name, 0, 4) == '-ms-' && !isset($this->style[$key = substr($name, 3)])) || (substr($name, 0, 5) == '-moz-' && !isset($this->style[$key = substr($name, 5)])) || (substr($name, 0, 8) == '-webkit-' && !isset($this->style[$key = substr($name, 8)])) || !isset($this->style[$key = $name])) {
				continue;
			}
			$val = trim($v[1]);
			$arg = $this->style[$key];
			if ($val === '' || !($arg ? (is_array($arg) ? in_array($val, $arg) : preg_match($arg, $val)) : preg_match('/^[0-9a-z |%#.,-]*\/*(|(?:\s*rgba?\s*\([0-9,. ]+\))+' . (in_array($key, $this->styleUrl) ? '|url\s*\(\s*(https?\:)?\/\/\w+\.\w+[0-9a-z.\/_-]+?\s*\)' : '') . '|hsla?\([0-9%,]+\))[0-9a-z !|%#.,-]*$/i', $val))) {
				continue;
			}

			if (in_array($name, ['font-family', 'font'])) {
				$val = explode(',', $val);
				$font = '';
				if ($name == 'font') {
					$font = explode(' ', $val[0], 2);
					$val[0] = empty($font[1]) ? '' : $font[1];
					$font = $font[0] . ' ';
				}
				foreach ($val as $k => $v) {
					$val[$k] = trim($v);
				}
				$val = $font . '&#039;'. implode('&#039;, &#039;', $val) .'&#039;';
			}
			$style[$name] = $val;
		}

		if (empty($style)) {
			return $this->cache['style'][$value] = NULL;
		}

		$r = '';
		foreach ($style as $k => $v) {
			$r .=   $k . ':'. $v .';';
		}
		return $this->cache['style'][$value] = $r;
	}



	/**
	*	class
	**/
	public function class_($value) {
		if (!$value = trim($value)) {
			return NULL;
		}
		if (isset($this->cache['class'][$value]) || (isset($this->data['class']) && array_key_exists($value, $this->data['class']))) {
			return $this->cache['class'][$value];
		}

		$r = [];
		foreach (explode(' ', trim($value)) as $k => $v) {
			if (!($v = trim($v)) || (!in_array($v, $this->class) && $this->prefix && substr($v, 0, strlen($this->prefix)) != $this->prefix)) {
				continue;
			}
			$r[] = preg_replace('/[^0-9a-z_-]/i', '', $v);
		}
		if (!$r = implode(' ', $r)) {
			return $this->cache['class'][$value] = NULL;
		}
		return $this->cache['class'][$value] = $r;
	}





	/**
	*	id
	**/
	public function id($value) {
		if (!$value = trim($value)) {
			return NULL;
		}
		if (isset($this->cache['id'][$value]) || (isset($this->data['id']) && array_key_exists($value, $this->data['id']))) {
			return $this->cache['id'][$value];
		}
		return $this->cache['id'][$value] = !in_array($value, $this->id) && $this->prefix && substr($value, 0, strlen($this->prefix)) != $this->prefix ? NULL : preg_replace('/[^0-9a-z_-]/i', '', $value);
	}




	/**
	*	name
	**/
	public function name($value) {
		if (!$value = trim($value)) {
			return NULL;
		}
		if (isset($this->cache['name'][$value]) || (isset($this->data['name']) && array_key_exists($value, $this->data['name']))) {
			return $this->cache['name'][$value];
		}
		return $this->cache['name'][$value] = !in_array($value, $this->name) && $this->prefix && substr($value, 0, strlen($this->prefix)) != $this->prefix ? NULL : preg_replace('/[^0-9a-z_-]/i', '', $value);
	}




	/**
	*	align
	**/
	public function align($value) {
		if (!in_array($value, ['left', 'right', 'top', 'bottom', 'center', 'middle'])) {
			return NULL;
		}
		return $value;
	}


	/**
	*	color
	**/
	public function color($value) {
		if (!preg_match('/^\s*([0-9a-z#]+|rgba?\([0-9,. ]\))\s*$/i', $value)) {
			return NULL;
		}
		return $value;
	}

	/**
	*	type
	**/
	public function type($value, $attr, $tag) {
		if (!empty($this->type[$value]) && ($this->type[$value] === true || in_array($tag, $this->type[$value]))) {
			return $value;
		}
		if (in_array($tag, ['video', 'audio']) && preg_match('/^(video|audio|image)\/[0-9a-z_-]+$/i', $value)) {
			return $value;
		}
		return NULL;
	}


	/**
	*	target
	**/
	public function target($value) {
		return in_array($value, $this->target) ? $this->target : NULL;
	}


	/**
	*	datetime
	**/
	public function datetime($value) {
		return preg_replace('/[^0-9a-z_: -]/i', '', $value);
	}

	/**
	*	lang
	**/
	public function lang($value) {
		if (!preg_match('/^[a-z_-]{2,10}$/i', $value)) {
			return NULL;
		}
		return $value;
	}

	/**
	*	dir
	**/
	public function dir($value) {
		if (!in_array($value, ['rtl', 'rtl'])) {
			return NULL;
		}
		return $value;
	}

	/**
	*	shape
	**/
	public function usemap($value) {
		if (!preg_match('/^[#.]'. $this->prefix .'[0-9a-z_-]+$/i', $value)) {
			return NULL;
		}
		return $value;
	}

	/**
	*	shape
	**/
	public function shape($value) {
		if (!in_array($value, ['default', 'rect', 'circ', 'poly'])) {
			return NULL;
		}
		return $value;
	}

	/**
	*	coords
	**/
	public function coords($value) {
		return preg_replace('/[^0-9a-z,]/i', '', $value);
	}
	/**
	*	method
	**/
	public function method($value) {
		return $value ? (strtoupper($value) == 'POST' ? 'POST' : 'GET') : NULL;
	}

	/**
	*	rules
	**/
	public function rules($value){
		if (!in_array($value, ['none', 'groups', 'rows', 'cols', 'all'])) {
			return NULL;
		}
		return $value;
	}
	/**
	*	frame
	**/
	public function frame($value) {
		if (!in_array($value, ['void', 'above', 'below', 'hsides', 'vsides', 'lhs', 'rhs', 'box', 'border'])) {
			return NULL;
		}
		return $value;
	}
	/**
	*	valign
	**/
	public function valign($value) {
		if (!in_array($value, ['top', 'middle', 'bottom', 'baseline'])) {
			return NULL;
		}
		return $value;
	}

	/**
	*	media
	**/
	public function media($value, $attr, $tag) {
		if ($tag != 'style') {
			return NULL;
		}
		if (!empty($this->cache['media'][$value])) {
			return $this->cache['media'][$value];
		}
		$r = '';
		if (preg_match_all('/([0-9a-z,& ]*)(?:\((\s*[0-9a-z_-]+\s*\:[^;\(\)]+\s*)\))*/is', $value, $matchs)) {
			foreach ($matchs[1] as $k => $v) {
				if ((!$v && !$matchs[2][$k]) || ($matchs[2][$k] && !($matchs[2][$k] = $this->style($matchs[2][$k])))) {
					continue;
				}
				if ($v	) {
					$r .= $v;
				}
				if ($matchs[2][$k]) {
					$r .= ' ('. trim($matchs[2][$k], ';') .')';
				}
			}
		}
		return $this->cache['media'][$value] = $r ? $r : 'all';
	}
	/**
	*	wmode
	**/
	public function wmode($value) {
		return in_array($value = strtolower($value), ['transparent', 'window', 'opaque']) ? $value : 'window';
	}
	/**
	*	quality
	**/
	public function quality($value) {
		return in_array($value = strtolower($value), ['low', 'medium', 'high', 'autolow', 'autohigh', 'best']) ? $value : 'high';
	}
	/**
	*	scale
	**/
	public function scale($value) {
		return in_array($value = strtolower($value), ['default', 'showall', 'noborder', 'exactfit', 'noscale']) ? $value : 'default';
	}

	/**
	*	width height
	**/
	public function widthHeight($value) {
		return preg_replace('/[^0-9.%]/i', '', $value);
	}

	/**
	*	autocomplete
	**/
	public function autocomplete($value) {
		return in_array(strtolower($value), ['on', 'off']) ? $value : NULL;
	}
	/**
	*	classid
	**/
	public function classid($value, $attr, $tag) {
		return $tag == 'object' && in_array(strtolower($value), $this->classid) ? $value : NULL;
	}

	public function rel($value, $attr, $tag){
		if (!empty($this->rel[$value]) && ($this->rel[$value] === true || in_array($tag, $this->rel[$value]))) {
			return $value;
		}
		return NULL;
	}

	public function minMaxStep($value){
		return preg_replace('/^[^0-9a-z_:% -]$/i', '', $value);
	}
}