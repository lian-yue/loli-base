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
/*	Updated: UTC 2015-03-29 06:59:55
/*
/* ************************************************************************** */
namespace Loli\HTML;
class Format{


	// 所有允许的标签
	protected $tags = [
		'a', 'abbr', 'acronym', 'address', 'applet', 'area', 'article', 'aside', 'audio',
		'b', 'base', 'basefont', 'bdi', 'bdo', 'big', 'blockquote', 'blockcode', 'body', 'br', 'button',
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

	// 所有 单标签 无结束标签的
	protected $singles = [
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

	// 所有 内联元素
	protected $inlines = [
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


	// 所有 行内块元素
	protected $inlinesBlock = [
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


	// 不允许嵌套块元素的 块元素
	protected $blockNotNesteds = [
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'p',
		'dt',
	];


	// 空标签自动添加的
	protected $element = 'p';

	// 快元素和内联元素 并列的时候自动添加的标签
	protected $abreast = 'p';

	// 当前标签允许的父级
	protected $allowParents = [
		'li' => ['ul', 'ol', 'nl'],
		'dt' => ['dl'],
		'dd' => ['dl'],
		'thead' => ['table'],
		'tbody' => ['table'],
		'caption' => ['table'],
		'colgroup' => ['table'],
		'col' => ['table', 'colgroup'],
		'td' => ['tr'],
		'th' => ['tr'],
		'param' => ['object', 'applet'],
	];

	// 当前标签允许的子级
	protected $allowChilds = [
		'ul' => ['li'],
		'ol' => ['li'],
		'nl' => ['li'],
		'dl' => ['dt', 'dd'],
		'table' => ['thead', 'tbody', 'tr', 'caption', 'colgroup', 'col'],
		'thead' => ['tr'],
		'tbody' => ['tr'],
		'tr' => ['td', 'th'],
		'style' => [],
		'script' => [],
		'object' => ['param', 'embed'],
	];


	// 父级 不允许的子级 (多层次)
	protected $multisLevel = [
		'a' => ['a', 'button' , 'input', 'form', 'textarea'],
		'button' => ['textarea' , 'input' , 'button' , 'select' , 'label' , 'form' , 'fieldset' , 'iframe'],
		'frame' => ['frame'],
		'form' => ['form'],
		'label' => ['label'],
		'pre' => ['img', 'object', 'big', 'samll', 'sub', 'sup', 'pre'],
	];

	// 父级 不允许的子级 (单层次)
	protected $singlesLevel = [
		'ul' => ['dl', 'ul', 'ol', 'nl'],
		'ol' => ['dl', 'ul', 'ol', 'nl'],
		'nl' => ['dl', 'ul', 'ol', 'nl'],
		'dl' => ['dl', 'ul', 'ol', 'nl'],
		'li' => ['li'],
		'tr' => ['tr'],
		'dd' => ['dd'],
	];


	// 限制嵌套层次
	protected $limitNesteds = [
		'optgroup' => 0,
		'option' => 0,
	];

	// 限制标签数量
	protected $limitCounts = [
		'h1' => 1,
		'h2' => 6,
		'h3' => 12,
	];


	// 属性回调函数
	protected $attribute;

	// 数据栈
	protected $stacks = [];

	// 栈嵌套层次
	protected $layers = [];

	// 标签数量统计
	protected $counts = [];

	// 上面一个标签
	protected $above = '';

	// 储存返回值
	protected $html = '';

	// 解析属性缓存
	protected $attributeCache = [];

	/**
	 * __construct 自动执行
	 * @param callable|integer $attribute 属性过滤回调
	 * @param Style|null       $style     样式过滤对象
	 */
	public function __construct(callable $attribute = NULL, Style $style = NULL) {
		// 没样式表自动添加
		if (!$style) {
			$this->style = new Style;
		}

		// 回调属性
		if (!$attribute) {
			$this->attribute = new Attribute($this->style);
		}
	}



	/**
	 * __invoke 格式化Html
	 * @return 回调到 get 方法
	 */
	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}



	/**
	 * getTags 取得所有允许的标签
	 * @return array
	 */
	public function getTags() {
		return $this->tags;
	}


	/**
	 * 添加允许的标签
	 * @param array $tags 允许的标签数组
	 * @return this
	 */
	public function addTags(array $tags) {
		$this->tags = array_merge($tags, $this->tags);
		return $this;
	}


	/**
	 * setTags 写入覆盖允许的标签
	 * @param array $tags 标签数组
	 * @return this
	 */
	public function setTags(array $tags) {
		$this->tags = $tags;
		return $this;
	}


	/**
	 * removeTags 删除允许的标签
	 * @param  array  $tags 标签数组
	 * @return this
	 */
	public function removeTags(array $tags) {
		$this->tags = array_diff($this->tags, $tags);
		return $this;
	}



	/**
	 * get 格式化Html
	 * @param  string $html html代码
	 * @return string       格式化后的html代码
	 */
	public function get($html) {
		// 重置变量
		$this->html = $this->above = '';
		$this->counts = $this->stacks = $this->layers = [];

		// 标签排序
		usort($this->tags, function($a, $b) {
			if (($a = strlen($a)) == ($b = strlen($b))) {
				return 0;
			}
			return ($a > $b) ? 1 : -1;
		});


		$tags = [];
		foreach ($this->tags as $tag) {
			$tags[$tag] = in_array($tag, $this->singles);
		}

		// 移除头尾空格
		$html = trim($html);

		// 没有 tag 的
		if (!$tags) {
			return $this->text(strip_tags($html));
		}



		// 没有标签
		if (!$html || !strstr($html, '<') || !($splits = preg_split("/\<(\s*\/\s*)?([a-z0-9]+)((?:\s+(?:\s*(?:[0-9a-z_:-]+)\s*(?:\=\s*(?:\"[^\"]*\"|'[^']*'|[^'\"<> \t\n\r\x0B]*))?)*|))((?(1)|\s*\/?\s*))\>/is", $html, -1, PREG_SPLIT_DELIM_CAPTURE))) {
			$this->element && $this->push($this->element);
			$this->html($this->text($html));
			$this->pop();
			return trim($this->html);
		}
		$type = 3;
		$continue = 0;
		foreach ($splits as $key => $value) {
			if ($continue) {
				--$continue;
				continue;
			}

			switch($type) {
				case 1:
					// 开始标签


					// 丢弃属性和结尾斜杠
					$continue = 2;


					// 跳到字符串
					$type = 3;

					// 标签
					$tag = strtolower($value);


					// 无效标签
					if (!isset($tags[$tag])) {
						break;
					}

					// 属性
					$attribute = isset($splits[$key+1]) ? $splits[$key+1] : '';

					// 单标签
					if ($tags[$tag]) {

						// 限制子级
						$this->child($tag);

						// 限制父级
						if (!$this->parent($tag)) {
							break;
						}

						// 插入单标签
						$this->html($this->single($tag, $attribute));
						break;
					}

					// 限制嵌套数量
					if ($this->layers && isset($this->limitNesteds[$tag]) && in_array($tag, $this->layers)) {
						$count = array_count_values($this->layers)[$tag];
						while ($count > $this->limitNesteds[$tag] && $this->layers) {
							if ($this->pop() == $tag) {
								$count--;
							}
						}
					}

					// 限制子级
					$this->child($tag);


					// 限制父级
					if (!$this->parent($tag)) {
						break;
					}

					$this->push($tag, $attribute);

					switch ($tag) {
						case 'script':
							$type = 4;
							break;
						case 'textarea':
							$type = 5;
							break;
					}
					break;
				case 2:
					// 闭合标签

					// 跳到字符串
					$type = 3;

					// 丢弃属性和结尾斜杠
					$continue = 2;

					$tag = strtolower($value);
					if (!isset($tags[$tag]) || !$tags[$tag]) {
						 $this->pop($tag);
					}
					break;
				case 3:

					// 跳过结尾斜杠
					$continue = 1;

					// 用开始斜杠判断是开始还是结束标签 下一个
					$type = empty($splits[$key+1]) ? 1 : 2;
					if ($value) {
						// 需要插入默认标签的
						if (!$this->layers && $this->element && trim($value)) {
							$this->push($this->element);
						}
						$this->html($this->text($value));
					}
					break;
				case 4:
					// script 字符串

					// 插入内容
					$this->html($value);
					while (isset($splits[$key + $continue + 1])) {
						// 跳过开始斜杠
						++$continue;
						if (strtolower($splits[$key + $continue + 1]) === $tag) {
							$type = 2;
							break;
						}
						// 开始斜杠 +  标签 + 属性 + 结尾斜杠 + 内容
						$this->html('<'. $splits[$key + $continue] . $splits[$key + $continue + 1] . $splits[$key + $continue + 2] . $splits[$key + $continue + 3] . '>' . $splits[$key + $continue + 4]);

						// 跳到下一个标签
						$continue += 4;

					}
					break;
				case 5:
					// textarea 字符串

					// 插入内容
					$this->html($this->text($value));
					while (isset($splits[$key + $continue + 1])) {
						// 跳过开始斜杠
						++$continue;

						if ($splits[$key + $continue] && strtolower($splits[$key + $continue + 1]) === $tag) {
							$type = 2;
							break;
						}

						// 开始斜杠 +  标签 + 属性 + 结尾斜杠 + 内容
						$this->html($this->text('<'. $splits[$key + $continue] . $splits[$key + $continue + 1] . $splits[$key + $continue + 2] . $splits[$key + $continue + 3] . '>' . $splits[$key + $continue + 4]));

						// 跳到下一个标签
						$continue += 4;

					}
			}
		}

		// 关闭未关闭的标签
		while ($this->layers) {
			$this->pop();
		}
		return $this->html;
	}


	/**
	 * parent 判断父级是否允许该标签
	 * @param  string $tag 标签名
	 * @return boolean
	 */
	protected function parent($tag) {

		// 当前标签允许的父级
		if (!empty($this->allowParents[$tag]) && (!$this->layers || !in_array(end($this->layers), $this->allowParents[$tag]))) {
			return false;
		}

		// 父级允许的当前标签
		if ($this->layers && isset($this->allowChilds[$layer = end($this->layers)]) && !in_array($tag, $this->allowChilds[$layer])) {
			return false;
		}

		return true;
	}


	/**
	 * child 闭合不允许的标签
	 * @param  string $tag 标签名
	 */
	protected function child($tag) {
		$i = 0;
		do {

			// 是否再次执行
			$while = false;

			// 内联 特殊快元素 可变 拒绝 允许 标签
			if ($this->layers) {
				// 不允许 快元素 的标签
				while ((in_array($layer = end($this->layers), $this->inlines) || in_array($layer, $this->blockNotNesteds)) && !in_array($tag, $this->inlines) && !in_array($tag, $this->inlinesBlock)) {
					$this->pop();
				}
				// 不允许的子级 多层的
				$count = 0;
				foreach ($this->layers as $layer) {
					if ($count) {
						++$count;
					} elseif (isset($this->multisLevel[$layer]) && in_array($tag, $this->multisLevel[$layer])) {
						++$count;
					}
				}
				while ($count) {
					$this->pop();
					$count--;
				}



				if ($this->layers && isset($this->singlesLevel[$layer = end($this->layers)]) && in_array($tag, $this->singlesLevel[$layer])) {
					$while = true;
					$this->pop();
				}
			}


			// 块级元素 内联元素 出现并列
			if ($this->abreast && $this->above && !in_array($this->above, $this->inlines) && !in_array($this->above, $this->inlinesBlock) && in_array($tag, $this->inlines)) {
				$this->push($this->layers && !empty($this->allowChilds[$layer = end($this->layers)]) ? reset($this->allowChilds[$layer]) : $this->abreast);
				$while = true;
			}

			// 直接是 内联元素 添加 element
			if ($this->element  && !$this->layers && in_array($tag, $this->inlines)) {
				$this->push($this->element);
				$while = true;
			}
			++$i;
		} while($while && $i < 3);
	}




	/**
	 * push 推送一个标签   (入栈)
	 * @param  string  $tag       推送的标签名称
	 * @param  string  $attribute 推送的标签属性
	 * @return boolean
	 */
	protected function push($tag, $attribute = '') {
		if (empty($this->counts[$tag])) {
			$this->counts[$tag] = 0;
		}
		if (!empty($this->limitCounts[$tag]) && $this->counts[$tag] >= $this->limitCounts[$tag]) {
			return false;
		}
		++$this->counts[$tag];
		$this->above = '';
		$this->stacks[] = [$tag, $attribute ? $this->parseAttribute($attribute) : [], false, ''];
		$this->layers[] = $tag;
		return true;
	}




	/**
	 * pop 弹出一个标签  (出栈)
	 * @param  string $tag 如果传入参数 需要判断标签对没再出栈
	 * @return boolean
	 */
	protected function pop($tag = '') {
		if (!$this->layers) {
			return false;
		}
		$params = end($this->stacks);
		if ($tag && $tag !== $params[0]) {
			return false;
		}
		array_pop($this->stacks);
		$this->above = array_pop($this->layers);
		$this->html(call_user_func_array([$this, 'call'], $params));
		return $this->above;
	}






	/**
	 * single 格式化一个单标签
	 * @param  string $tag       推送的标签名称
	 * @param  string $attribute 推送的标签属性
	 * @return html
	 */
	protected function single($tag, $attribute = '') {
		if (empty($this->counts[$tag])) {
			$this->counts[$tag] = 0;
		}
		if (!empty($this->limitCounts[$tag]) && $this->counts[$tag] >= $this->limitCounts[$tag]) {
			return false;
		}
		++$this->counts[$tag];
		return $this->call($tag, $attribute ? $this->parseAttribute($attribute) : [], true, '');
	}

	/**
	 * text 格式化text数据
	 * @param  string $text 字符串
	 * @return string
	 */
	protected function text($text) {
		return strtr($text, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']);
	}





	/**
	 * html 在末尾插入html代码
	 * @param  string $html 代码
	 */
	protected function html($html) {
		if ($this->stacks) {
			end($this->stacks);
			$this->stacks[key($this->stacks)][3] .= $html;
		} else {
			$this->html .= $html;
		}
	}





	/**
	 * call 格式化标签回调
	 * @param  string  $tag        标签名
	 * @param  array   $attributes 属性
	 * @param  boolean $single     是否是单标签
	 * @param  string  $html       标签嵌套的字符串
	 * @return string
	 */
	protected function call($tag, array $attributes, $single = false, $html = '') {
		switch ($tag) {
			case 'param':
				// 变量
				if (empty($attributes['name']) || !isset($attributes['value']) || !($attributes = call_user_func($this->attribute, [$name = $attributes['name'] => $attributes['value']], $tag)) || !isset($attributes[$name]) || $attributes[$name] === false) {
					return '';
				}
				$attributes = [$name => $attributes[$name]];
				break;
			case 'style':
				// style 标签
				if (!$this->style || !$html) {
					return '';
				}
				$attributes = call_user_func($this->attribute, $attributes, $tag);
				$html = call_user_func($this->style, $html);
				break;
			default:
				// 其他的
				$attributes = call_user_func($this->attribute, $attributes, $tag);
		}

		$attributeString = $this->mergeAttribute($attributes);

		return $single ? '<'. $tag . $attributeString . ' />' : '<'.$tag . $attributeString .'>' . $html . '</'.$tag.'>';
	}





	/**
	 * parseAttribute 把属性解析成数值
	 * @param  string $attributeString 属性
	 * @return array
	 */
	protected function parseAttribute($attributeString) {
		if (!$attributeString) {
			return [];
		}
		if (is_array($attributeString)) {
			return $attributeString;
		}
		if (isset($this->attributeCache[$attributeString])) {
			return $this->attributeCache[$attributeString];
		}

		$attributes = [];
		if (preg_match_all("/\s*([0-9a-z_-]+)\s*(?:\=\s*(?:\"([^\"]*)\"|\'([^\']*)\'|(\w*))|)/is", $attributeString, $matchs)) {
			foreach ($matchs[1] as $key => $attribute) {
				if (!$attribute) {
					continue;
				}
				if ($matchs[2][$key] != '') {
					$value = $matchs[2][$key];
				} elseif ($matchs[3][$key] != '') {
					$value = $matchs[3][$key];
				} elseif ($matchs[4][$key] != '') {
					$value = $matchs[4][$key];
				} else {
					$value = '';
				}
				$attributes[strtolower($attribute)] = strtr($value, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']);
			}
		}
		return $this->attributeCache[$attributeString] = $attributes;
	}


	/**
	 * mergeAttribute 吧属性数组合并字符串
	 * @param  array  $attributes 属性数组
	 * @return string
	 */
	protected function mergeAttribute(array $attributes) {
		$attributeString = '';
		foreach ($attributes as $attribute => $value) {
			if ($value === NULL || $value === false) {
				continue;
			}
			$attributeString .= ' ' . $attribute .'="'. $value .'"';
		}
		return $attributeString;
	}
}