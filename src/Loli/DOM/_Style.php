<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-05-26 08:02:29
/*	Updated: UTC 2015-06-14 08:19:25
/*
/* ************************************************************************** */




/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-28 02:20:01
/*	Updated: UTC 2015-03-29 15:35:51
/*
/* ************************************************************************** */
namespace Loli\HTML;
class Style{
	// 全部 全部允许的属性名
	protected $names = [
		'background' => '', 'background-attachment' => '', 'background-color' => '', 'background-image' => '', 'background-position' => '', 'background-repeat' => '', 'background-clip' => '', 'background-origin' => '', 'background-size' => '',
		'border' => '', 'border-bottom' => '', 'border-bottom-color' => '', 'border-bottom-style' => '', 'border-bottom-width' => '', 'border-color' => '', 'border-left' => '', 'border-left-color' => '', 'border-left-style' => '', 'border-left-width' => '', 'border-right' => '', 'border-right-color' => '', 'border-right-style' => '', 'border-right-width' => '', 'border-style' => '', 'border-top' => '', 'border-top-color' => '', 'border-top-style' => '', 'border-top-width' => '', 'border-width' => '', 'border-collapse' => '', 'border-spacing' => '', 'border-bottom-left-radius' => '', 'border-bottom-right-radius' => '', 'border-image' => '', 'border-image-outset' => '', 'border-image-repeat' => '', 'border-image-slice' => '', 'border-image-source' => '', 'border-image-width' => '', 'border-radius' => '', 'border-top-left-radius' => '', 'border-top-right-radius' => '',
		'box-shadow' => '',
		'outline' => '', 'outline-color' => '', 'outline-style' => '', 'outline-width' => '',
		'overflow' => '', 'overflow-x' => '', 'overflow-y' => '', 'overflow-style' => '',
		'opacity' => '',
		'height' => '', 'width' => '', 'max-height' => '', 'max-width' => '', 'min-height' => '', 'min-width' => '',
		'font' => '', 'font-family' => '', 'font-size' => '', 'font-style' => '', 'font-variant' => '', 'font-weight' => '', 'font-size-adjust' => '',
		'list-style' => '', 'list-style-image' => '', 'list-style-position' => '', 'list-style-type' => '',
		'letter-spacing' => '', 'line-height' => '', 'text-shadow' => '', 'text-overflow' => '', 'white-space' => '',
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

		// media 的属性
		'grid' => '', 'scan' => '', 'resolution' => '', 'monochrome' => '', 'min-color-index' => '', 'max-color-index' => '', 'device-height' => '', 'device-width' => '',
	];

	// 允许使用 url 的属性
	protected $urls = ['background', 'background-attachment', 'background-image'];


	// 允许的前缀
	protected $prefix = '';

	/**
	 * __invoke 回调contents 过滤style内容
	 * @return string 返回 styles
	 */
	public function __invoke() {
		return call_user_func_array([$this, 'contents'], func_get_args());
	}

	/**
	 * 匹配 style 内容
	 * @param  string $contents 内容
	 * @return string
	 */
	public function contents($contents) {
		// 移除注释
		$contents = preg_replace('/\/\*.*?\*\//is', '', $contents);

		// 移除很特殊的css匹配
		//charset  document  font-face import keyframes media page supports
		$contents = preg_replace('/@\s*((charset|import).*?;|(font-face|page).*?\}|(document|keyframes|)supports[^\{]*?(\{\s*\}|.*?\}\s*\})|media[^\{]*?\{\s*\})/is', '', $contents);

		$contents = trim(preg_replace('/\s+/', ' ', $contents));





		$splits = preg_split('/(@media(?:\s[^{}]*)?\{)?((?(1)|(?<=\})\s*\}))/is', $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
		//print_r($splits);
		//echo "\n\n\n\n";
		$type = 3;
		$mediaCounts = $continue = 0;
		$result = '';
		$subject = '/^' .  ($this->prefix ? '[#.]' . preg_quote($this->prefix, '/') : '') . '[a-z0-9\[\]() #.>+=~|:^$*-]+$/i';
		foreach ($splits as $key => $value) {
			if ($continue) {
				--$continue;
				continue;
			}
			switch ($type) {
				case 1:
					// 开始 media
					$result .= "\n" . str_pad('', $mediaCounts, "\t") . '@media ' . $this->media(substr($value, 7)) .' {';

					$type = 3;
					++$continue;
					++$mediaCounts;
					break;
				case 2:
					// 结束 media
					$type = 3;
					++$continue;
					--$mediaCounts;

					$result .= "\n" . str_pad('', $mediaCounts, "\t") . '}';
					break;
				default:
					// 内容属性
					$type = empty($splits[$key + 1]) ? 2 : 1;
					if (!$value = trim($value)) {
						break;
					}
					$styles = [];
					$t = "\n" . str_pad('', $mediaCounts, "\t");
					foreach(explode('}', $value) as $values) {
						if (count($values = explode('{', $values, 2)) !== 2 || !$values[0] || !$values[1]) {
							continue;
						}
						$selects = [];
						foreach(explode(',', $values[0]) as $select) {
							if (!($select = trim($select)) || !preg_match($subject, $select)) {
								continue;
							}
							$selects[] = $select;
						}

						if ($selects && ($values = $this->values($values[1]))) {
							$result .= $t . implode(', ', $selects) .'{'. $values .'}';
						}

					}

			}
		}

		while ($mediaCounts > 0) {
			--$mediaCounts;
			$result .= "\n" . str_pad('', $mediaCounts, "\t") . '}';
		}
		return trim($result);
	}

	/**
	 * 匹配一行 style
	 * @param  string  $values  一行属性
	 * @param  boolean $isArray 是否返回array
	 * @return string or array
	 */
	public function values($values, $isArray = false) {
		$styles = [];
		foreach (explode(';', preg_replace('/\s+/is', ' ',preg_replace('/\/\*.*?(\*\/|$)|&quot;|&#039;|&lt;|&gt;|&|\\\\|"|\'|\>|<|\{|\}/is', '', $values))) as $value) {
			// 没有 : 的
			if (count($value = explode(':', $value, 2)) !== 2) {
				continue;
			}
			// 没匹配到
			if (!$this->value($name = trim($value[0]), $value = trim($value[1]))) {
				continue;
			}
			$styles[] = $name . ': ' . $value . ';';
		}
		return $isArray ? $styles : implode(' ', $styles);
	}

	/**
	 * 匹配单个 style 属性
	 * @param  string  $name  属性名
	 * @param  string  $value 属性值
	 * @return boolean
	 */
	public function value($name, $value) {
		if (!($name = strtolower(trim($name))) || !($value = trim($value))) {
			return false;
		}
		// 前缀
		if (substr($name, 0, 3) === '-o-') {
			$key = substr($name, 3);
		} elseif (substr($name, 0, 4) === '-ms-') {
			$key = substr($name, 4);
		} elseif (substr($name, 0, 5) === '-moz-') {
			$key = substr($name, 5);
		} elseif (substr($name, 0, 8) === '-webkit-') {
			$key = substr($name, 8);
		} else {
			$key = $name;
		}

		// 不允许的标签
		if (!isset($this->names[$key]) || $this->names[$key] === false) {
			return false;
		}


		// value
		if ($this->names[$key] === true) {
			return $value;
		}

		// 值允许指定value
		if (is_array($this->names[$key])) {
			return in_array(strtolower($$this->names[$key]), $this->names[$key]);
		}

		// 正则匹配
		$pattern = $this->names[$key] ? '/'. str_replace('/', '\\/', $this->names[$key]) .'/i' : '/^(?:[0-9a-z |%#.,-]*(?:(?:rgb|hsl|translate|rotate|scale|skew|matrix|perspective)([axyz]|3d)?\s*\([a-z0-9,.% -]+\)'. (in_array($key, $this->urls) ? '|url\s*\(\s*(?:https?\:)?\/\/\w+\.\w+[0-9a-z.\/_-]+?\s*\)' : '') .')?[0-9a-z !|%#.,-]*)*$/i';
		return preg_match($pattern, $value);
	}

	/**
	 * 匹配 media 属性
	 * @param  string $media attribute Value
	 * @return string
	 */
	public function media($media) {
		if (!preg_match_all('/([0-9a-z,& ]*)(?:\((\s*[0-9a-z_-]+\s*\:[^;\(\)]+\s*)\))*/is', $media, $matchs)) {
			return 'all';
		}

		$result = '';
		foreach ($matchs[1] as $key => $type) {
			if (!$type && !$matchs[2][$key]) {
				continue;
			}
			$rule = $matchs[2][$key];
			if ($rule && !($rule = $this->values($rule))) {
				continue;
			}
			if ($type) {
				$result .= $type;
			}
			if ($rule) {
				$result .= ' ('. trim($rule, ';') .')';
			}
		}
		return trim($result) ? $result : 'all';
	}



	/**
	 * getName 取得一个 属性名
	 * @param  string $name 属性名称
	 * @return boolean|string|array
	 */
	public function getName($name) {
		return isset($this->names[$name]) ? $this->names[$name] : false;
	}

	/**
	 * getNames 取得所有允许的 属性
	 * @return array
	 */
	public function getNames() {
		return $this->names;
	}

	/**
	 * addName 添加一个属性
	 * @param string 				 $name 	属性名
	 * @param boolean|string|array   $value 属性规则
	 * @return this
	 */
	public function addName($name, $value) {
		$this->names += [$name => $value];
		return $this;
	}


	/**
	 * addNames 添加多个属性
	 * @param array $names 属性数组
	 * @return this
	 */
	public function addNames(array $names) {
		$this->names += $names;
		return $this;
	}



	/**
	 * setName 设置一个属性
	 * @param string 				 $name 	属性名
	 * @param boolean|string|array   $value 属性规则
	 * @return this
	 */
	public function setName($name, $value) {
		$this->names[$name] = $value;
		return $this;
	}


	/**
	 * setNames 设置所有允许的属性
	 * @param array $names 属性数组
	 * @return this
	 */
	public function setNames(array $names) {
		$this->names = $names + $this->names;
		return $this;
	}

	/**
	 * removeName 删除一个属性
	 * @param  string $name 属性名
	 * @return this
	 */
	public function removeName($name) {
		unset($this->names[$name]);
		return $this;
	}
	/**
	 * removeName 删除多个属性
	 * @param  string $names 属性名数组
	 * @return this
	 */
	public function removeNames(array $names) {
		foreach ($names as $key => $name) {
			unset($this->names[$key]);
			if (is_string($name)) {
				unset($this->names[$name]);
			}
		}
		return $this;
	}
}


namespace Loli\DOM;
class Style{

	// 选择器
	const TYPE_RULE = 1;

	//注释
	const TYPE_COMMENT = 2;

	// AT 规则
	const TYPE_AT_RULE = 3;


	// 嵌套层次
	const NESTING = 10;


	// 当前类型
	protected $type;

	// 当前名
	protected $name;

	// 当前值
	protected $value;

	// 嵌套   at规则嵌套的可能是at规则,规则,属性        规则嵌套的就是属性   属性嵌套
	protected $childrens = [];

	public function __construct($value, $type = 0) {
		switch ($type) {
			case self::TYPE_RULE:
				break;
			case self::TYPE_COMMENT:
				$this->value = $value;
				$this->type = self::TYPE_COMMENT;
				break;
			case self::TYPE_AT_RULE:
				$this->value = $value;
				$this->type = self::TYPE_AT_RULE;
				break;
			default:
				$this->style = trim(mb_convert_encoding((string) $value,'utf-8', 'auto'));
				$this->length = strlen($this->style);
				$this->offset = 0;
				$this->buffer = '';
				break;
		}
	}






	public function prepare($style) {
		while ($char = $this->cspn('/@{')) {
			if (!isset($this->style{$this->offset})) {
				$this->buffer .= $char;
				continue;
			}
			switch ($char) {
				case '/':
					// 注释
					$char = $this->offset{$this->offset};
					if ($char === '*') {
						++$this->offset;
						$this->buffer = '';
						$this->pos('*/');
						if ($this->buffer) {
							$style->childrens[] = new Style($this->buffer, self::TYPE_COMMENT);
						}
					}
					break;
				case '@':
					// at 规则
					break;
				case '{':
					break;
			}

		}
	}



	protected function pos($needle, $buffer = true, $ipos = false) {

		$result = $ipos ? stripos($this->style, $needle, $this->offset) : strpos($this->style, $needle, $this->offset);
		$offset = $result === false ? $this->length : $result + strlen($needle);
		if ($buffer) {
			$this->buffer .= substr($this->style, $this->offset, $offset - $this->offset - strlen($needle));
		}


		$this->offset = $offset;
		return $result === false ? false : $needle;
	}



	protected function cspn($mask, $buffer = true) {
		$result = strcspn($this->style, $mask, $this->offset);
		if ($buffer) {
			$this->buffer .= substr($this->style, $this->offset, $result);
		}
		$result += $this->offset;
		if ($result >= $this->length) {
			$this->offset = $result;
			return false;
		}
		$this->offset = $result + 1;
		return $this->style{$result};
	}




	public function selectors() {
		static $patterns = [

			// 需要跳出的规则
			'\s*@' => '@',
			'\s*\{' => '{}',


			// or
			'\s*,\s*' => ',',

			// 标签
			'(\*|[a-z][0-9a-z_\-]*)' => '',


			// 表格选择器
			'\s*\|\|\s*' => '||',

			// 多层后代选择器
			'\s*\>\>\s*' => ' ',

			// 单层后代选择器
			'\s*\>\s*' => '>',


			'\s*\+\s*' =>  '+',
			'\s*~\s*' => '~',
			'\s*\|\s*' => '|',

			// 多层后代选择器
			'\s+' => ' ',

			// id名
			'#([a-z_\-][0-0a-z_\-]*)' => '#',

			// class 名
			'\.([a-z_\-][0-0a-z_\-]*)' => '.',

			// 伪类
			'\:?\:([a-z_\-][0-0a-z_\-]*)' => ':',

			// 属性匹配
			'\[((?:[0-0a-z_\-]+|\*)(?:\|(?:[0-0a-z_\-]+|\*))?)(?:((?:|~|\^|\$|\*|\|)?\=)("|\')?(.*?)(?(3)\3|))?\]' => '[]',

			// 注释
			'/\*(.*?)(?:\*/|$)' => '//',



			// 内容规则
//			'\s*\{([^\}]*)\}\s*' => '{}',

			// 内容
//			'\s*@(media)([^;}{}]+)(?=\{)' => '@',
//			'\s*@(charset)\s+("|\')[0-9a-z\-]+(?(2)\2|)\s*;\s*' => '@',
//			'\s*@(font\-face)\s*\{(.+?)\}' => '@',
//			'\s*@(import)\s+url\(("|\')?(.+?)(?(2)\2|)\)(?:\s+(.*?))?\s*\;\s*' => '@',
//			'\s*@(import)\s+("|\')(.+?)\2(?:\s+(.*?))?\s*\;\s*' => '@',
		];
		$selectors = trim($selectors);
		$array = [];
		$continue = true;
		while($selectors && $continue) {
			foreach($patterns as $pattern => $value) {
				if (!$continue = preg_match('/^'.str_replace('/', '\\/', $pattern).'/i', $selectors, $matches)) {
					continue;
				}
				$offset = strlen($matches[0]);
				switch ($value) {
					case '@':
					case '{}':
						// 需要跳出的
						break 3;
					case '//':
						// 注释
						$this->comments[] = $matches[1];
						break;
					case ',':
					case '>':
					case '+':
					case '~':
					case ' ':
						$array[] = [$value];
						break;
					case '#':
					case '.':
						// 类型 字段
						$array[] = [$value, strtolower($matches[1])];
						break;
					case '[]':
						// 类型, 字段, 运算类型, 运算值
						$array[] = ['[]', explode('|', stripcslashes($matches[1])), isset($matches[2]) ? $matches[2] : '', isset($matches[4]) ? $matches[4] : false];
						break;
					case '':
					case '*':
						// 类型 标签名
						$array[] = ['', $matches[1]];
						break;
					case ':':
						$matches[1] = strtolower($matches[1]);
						// 类型 字段 参数
						switch ($matches[1]) {
							case 'first-child':
							case 'last-child':
							case 'only-child':

							case 'first-of-type':
							case 'last-of-type':
							case 'only-of-type':

							case 'read-only':
							case 'read-write':

							case 'disabled':
							case 'enabled':

							case 'required':
							case 'optional':

							case 'empty':
							case 'root':
							case 'target':

							case 'in-range':
							case 'out-of-range':


							case 'active':
							case 'checked':
							case 'focus':
							case 'hover':
							case 'link':
							case 'visited':
							case 'valid':
							case 'invalid':
								$array[] = [':', $matches[1], false];
								break;
							case 'after':
							case 'before':
							case 'first-letter':
							case 'first-line':
							case 'selection':
								$array[] = [':', ':'. $matches[1], false];
								break;
							case 'nth-child':
							case 'nth-last-child':
							case 'nth-of-type':
							case 'nth-last-of-type':
								if (!preg_match('/^\s*\(\s*(even|odd|[0-9+\-]+(?:n[0-9+\-]+)?)\s*\)/i', substr($selectors, $offset), $matches2)) {
									break 4;
								}
								$offset += strlen($matches2[0]);
								$args = strtolower($matches2[1]);
								if ($args === 'even') {
									$args = [2, 'n', 0];
								} elseif ($args === 'odd') {
									$args = [2, 'n', 1];
								} elseif (is_numeric($args)) {
									$args = [intval($args), '', 0];
								} else {
									$args = explode('n', $args, 2) + [1 => 0];
									$args = [$args[0] === '-' ? '-': intval($args[0]), 'n', intval($args[1])];
								}
								$array[] = [':', $matches[1], $args];
								break;
							case 'lang':
								if (!preg_match('/^\s*\(\s*([a-z\-]{2,10})\s*\)/i', substr($selectors, $offset), $matches2)) {
									break 4;
								}
								$offset += strlen($matches2[0]);
								$array[] = [':', $matches[1], strtolower($matches2[1])];
								break;
							case 'has':
							case 'not':
							case 'matches':
								// 没匹配到 开始括号
								if (!preg_match('/^\s*\(/', substr($selectors, $offset), $matches2)) {
									break 4;
								}

								// 字符串移到开始括号
								$selectors = substr($selectors, $offset + strlen($matches2[0]));


								// 设置0偏移
								$offset = 0;

								// 递归匹配
								if (!$args = $this->selectors($selectors)) {
									break 4;
								}

								// 设置偏移
								$offset = strlen(preg_match('/^\s*\)/', $selectors, $matches3) ? $matches3[0] : $selectors);

								// 遍历删除递归的  matches 等
								foreach ($args as $keySelector => &$valueSelector) {
									foreach ($valueSelector as $keyValues => &$valueValues) {
										if (!is_array($valueValues)) {
											continue;
										}
										foreach ($valueValues as $keyValue => &$valueValue) {
											if ($valueValue[0] === ':' && ($valueValue[1] === 'matches' || ($value[1] === 'matches' && in_array($valueValue[1], ['has', 'not'], true)))) {
												unset($valueValues[$keyValue]);
											}
										}
										unset($valueValue);

										// 重置规则下标
										$valueValues = array_values($valueValues);

										// 当前规则为空
										if (!$valueValues) {

											// 删除规则
											unset($valueSelector[$keyValues]);

											// 删除规则前面 的运算符字符串
											if ($valueSelector) {
												unset($valueSelector[$keyValues -1]);
											}
										}
									}
									unset($valueValues);


									// 重置
									$valueSelector = array_values($valueSelector);
									if (!$valueSelector) {
										unset($args[$keySelector]);
									}
								}
								unset($valueSelector);

								// 重置下标
								$args = array_values($args);
								//  过滤后还有内容
								if ($args) {
									$array[] = [':', $matches[1], $args];
								}
								break;
						}
						break;
				}
				$selectors = substr($selectors, $offset);
				break;
			}
		}


		//[['多条规则', '>+~| ', '多条规则']]


		$results = $result = $single = [];


		// 最后一个添加逗号
		$array[] = [','];

		foreach ($array as $value) {
			switch ($value[0]) {
				case ',':
					// 逗号
					if ($single) {
						// 单条规则
						usort($single, [$this, 'selectorsSort']);
						$result[] = $single;
						$single = [];
					} elseif ($result && !is_array(end($result))) {
						// 嵌套规则
						array_pop($result);
					}
					if ($result) {
						//  多条规则
						$results[] = $result;
						$result = [];
					}
					continue;
				case ' ':
				case '>':
				case '+':
				case '~':
					// 嵌套规则

					// 单挑规则
					if ($single) {
						usort($single, [$this, 'selectorsSort']);
						$result[] = $single;
						$single = [];
					}

					// 嵌套规则
					if ($result) {

						// 上一条是空格删除空格
						if (end($result) === ' ') {
							array_pop($result);
						}

						// 上一条不是嵌套规则 写入嵌套规则
						if (is_array(end($result))) {
							$result[] = $value[0];
						}
					}
					break;
				default:
					$single[] = $value;
			}
		}
		return $results;
	}


	/**
	 * selectorsSort 选择器排序回调函数
	 * @param  array $a
	 * @param  array $b
	 * @return integer
	 */
	protected function selectorsSort($a, $b) {
		static $sort = [
			'' => 0,		// 单个
			'#' => 1,		// 单个
			'.' => 2,		// 多个
			'[]' => 3,		// 多个
			':' => 4,		// 多个
		];
		return $sort[$a[0]] ===  $sort[$b[0]] ? : ( $sort[$a[0]] > $sort[$b[0]] ? 1 : -1);
	}

}





















