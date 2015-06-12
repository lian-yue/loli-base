<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-06-05 13:41:58
/*	Updated: UTC 2015-06-11 14:36:04
/*
/* ************************************************************************** */

namespace Loli\DOM\CSS;
class Rule{


	const STYLE_RULE = 1;

	const CHARSET_RULE = 2;

	const IMPORT_RULE = 3;

	const MEDIA_RULE = 4;

	const FONT_FACE_RULE = 5;

	const PAGE_RULE = 6;

	const KEYFRAMES_RULE = 7;

	const KEYFRAME_RULE = 8;

	const NAMESPACE_RULE = 9;

	const COUNTER_STYLE_RULE = 10;

	const SUPPORTS_RULE = 11;

	const DOCUMENT_RULE = 12;

	const FONT_FEATURE_VALUES_RULE = 13;

	const FONT_FEATURE_VALUE_RULE = 14;


	const VIEWPORT_RULE = 15;

	const REFERENCE_RULE = 16;

	const COMMENT_RULE = 17;


	// page 的

	const TOPLEFTCORNER_SYM = '@top-left-corner';

	const TOPLEFT_SYM = '@top-left';

	const TOPCENTER_SYM = '@top-center';

	const TOPRIGHT_SYM = '@top-right';

	const TOPRIGHTCORNER_SYM = '@top-right-corner';

	const BOTTOMLEFTCORNER_SYM = '@bottom-left-corner';

	const BOTTOMLEFT_SYM = '@bottom-left';

	const BOTTOMCENTER_SYM = '@bottom-center';

	const BOTTOMRIGHT_SYM = '@bottom-right';

	const BOTTOMRIGHTCORNER_SYM = '@bottom-right-corner';

	const LEFTTOP_SYM = '@left-top';

	const LEFTMIDDLE_SYM = '@left-middle';

	const LEFTBOTTOM_SYM = '@left-bottom';

	const RIGHTTOP_SYM = '@right-top';

	const RIGHTMIDDLE_SYM = '@right-middle';

	const RIGHTBOTTOM_SYM = '@right-bottom';

	// 嵌套规则限制
	const NESTING = 5;



	// 规则类型
	protected $type;

	// 属性值
	protected $value;

	// 属性前缀
	protected $prefix = '';

	// 格式化
	protected $format = false;


	//  父级规则	media  supports document 允许嵌套
	protected $parentRule;


	// 规则子规则
	protected $ruleList = [];




	public function __construct($value, $type = 0, $prefix = '') {
		if ($type) {
			$this->type = $type;
			$this->value = $value;
			$this->prefix = $prefix;
		} else {
			$this->style = trim(mb_convert_encoding((string) $value,'utf-8', 'auto'));
			$this->length = strlen($this->style);
			$this->offset = 0;
			$this->buffer = '';
			$this->prepare($this);
			unset($this->style, $this->length, $this->offset, $this->buffer);
		}
	}



	protected function prepare(Rule $rule) {
		static $nesting = 0;
		// 限制嵌套层次
		if ($nesting >= self::NESTING) {
			return;
		}
		while (($char = $this->search('@;{}', $rule)) !== false) {
			switch ($char) {
				case ';':
					// 直接结束的无效值
					$this->buffer = '';
					break;
				case '}':
					// 结束括号
					$this->buffer = '';
					// 有父级 跳出
					if ($rule->parentRule) {
						break 2;
					}
					break;
				case '{':
					// 直接元素绑定
					$rule->insert($rule2 = new Rule(/*new Selectors($this->buffer)*/ '', self::STYLE_RULE));

					// 解析属性
					$this->prepareReferences($rule2);
					break;
				case '@':
					// AT 规则

					// 清空缓冲区
					$this->buffer = '';

					// 读下一个
					$char = $this->search(" \t\n\r\0\x0B{}:(;");
					switch ($char) {
						case ';':
						case '}':
							// 直接结束的 无效值
							break;
						default:
							// 读取at 名
							$name = $this->buffer;

							// at 解析前缀
							$prefix = $this->prefix($name);

							// 清空缓冲区
							$this->buffer = '';


							// 匹配at规则
							switch ($name) {
								case 'charset':
									// 编码
									if ($this->search(';', $rule) && !$this->parentRule && ($charset = strtoupper(trim($this->buffer, " \t\n\r\0\x0B\"'"))) && preg_match('/^[A-Z]+[A-Z0-9_-]?$/', $charset)) {
										$rule->insert(new Rule($charset, self::CHARSET_RULE));
									}
									$this->buffer = '';
									break;
								case 'import':
									// 引入文件
									if ($this->search(';', $rule) && ($import = trim($this->buffer, " \t\n\r\0\x0B\"'")) && (preg_match('/\s*url\(("|\')?(.+?)(?(1)\1|)\)(?:\s+(.+))?/i', $import, $matches) || preg_match('/\s*("|\')?(.+?)(?(1)\1|)(?:\s+(.+))?/i', $import, $matches)) && ($matches[2] = preg_replace('/(["\'()*;<>\\\\]|\s)/', '', $matches[2])) && (!($scheme = parse_url($matches[2], PHP_URL_SCHEME)) || strcasecmp($scheme, 'http') === 0 || strcasecmp($scheme, 'https') === 0)) {
										$rule->insert(new Rule([$matches[2], new MediaQuery($matches[3])], self::IMPORT_RULE));
									}
									$this->buffer = '';
								case 'namespace':
									//  命名空间
									if ($this->search(';', $rule) && (preg_match('/(?:([a-z]+[0-9a-z]*)\s+)url\(("|\')?(https?\:\/\/[0-9a-z\/._-])(?(2)\2|)\)/i', $this->buffer, $matches) || preg_match('/(?:([a-z]+[0-9a-z]*)\s+)("|\')(https?\:\/\/[0-9a-z\/._-])\2/i', $this->buffer, $matches))) {
										$rule->insert(new Rule([$matches[3], $matches[1]], self::NAMESPACE_RULE, $prefix));
									}
									$this->buffer = '';
									break;
								case 'font-face':
									// 字体
									$char !== '{' && $this->search('{', $rule);
									$rule->insert($newRule = new Rule('', self::FONT_FACE_RULE, $prefix));
									$this->prepareReferences(['font-family', 'src', 'unicode-range', 'font-variant', 'font-feature-settings', 'font-stretch', 'font-weight', 'font-style']);
									$this->buffer = '';
								case 'viewport':
									// meta的 viewport
									$char !== '{' && $this->search('{', $rule);
									$rule->insert($newRule = new Rule('', self::VIEWPORT_RULE, $prefix));
									$this->prepareReferences($newRule, ['min-width', 'max-width', 'width', 'min-height', 'max-height', 'height', 'zoom', 'min-zoom', 'max-zoom', 'user-zoom', 'orientation']);
									$this->buffer = '';
									break;
								case 'counter-style':
									// 有序规则 计数器定义
									if ($char !== '{' && $this->search('{', $rule) && preg_match('/^[a-z_-]+[a-z0-9_-]?$/i', $value = trim($this->buffer))) {
										$rule->insert($newRule = new Rule($value, self::COUNTER_STYLE_RULE, $prefix));
										$this->prepareReferences($newRule,['system', 'symbols', 'additive-symbols', 'negative', 'prefix', 'suffix', 'range', 'pad', 'speak-as', 'fallback']);
									}
									$this->buffer = '';
									break;
								case 'keyframes':
									// 动画
									if ($char !== '{' && $this->search('{', $rule) && preg_match('/^[a-z_-]+[a-z0-9_-]?$/i', $value = trim($this->buffer))) {
										$rule->insert($newRule = new Rule($value, self::KEYFRAMES_RULE, $prefix));
										$this->buffer = '';

										// 循环遍历
										while ($this->search($newRule, '{}') === '{') {
											if (preg_match('/^(from|to|\d+\%)$/', $value = strtolower(trim($this->buffer)))) {
												$newRule->insert($newRule2 = new Rule($value, self::KEYFRAME_RULE));
												$this->prepareReferences($newRule2);
											}
										}
									}
									break;
								case 'font-feature-values':
									// 字体盒子
									if ($char !== '{' && $this->search('{', $rule) && preg_match('/^[a-z_-]+[a-z0-9_-]\s*[a-z_-]+[a-z0-9_-]?$/i', $value = trim($this->buffer))) {
										$rule->insert($newRule = new Rule($value, self::FONT_FEATURE_VALUES_RULE, $prefix));
										$this->buffer = '';
										while ($this->search($newRule, '{}') === '{') {
											if (in_array($value2 = strtolower(trim($this->buffer)), ['@swash', '@@annotation', '@ornaments', '@stylistic', '@styleset', '@character-variant'], true)) {
												$newRule->insert($newRule2 = new Rule(substr($value2, 1), self::FONT_FEATURE_VALUE_RULE));
												$this->prepareReferences($newRule2);
											}
										}
									}
									break;
								case 'page':
									$char !== '{' && $this->search('{', $rule);
									//$rule->insert($newRule = new Rule($this->buffer, '', self::PAGE_RULE, $prefix));
									break;
								case 'media':
									$char !== '{' && $this->search('{', $rule);
									$rule->insert($newRule = new Rule($newRule new MediaQuery($this->buffer), self::MEDIA_RULE, $prefix));
									$this->buffer = '';
									++$nesting;
									$this->prepare($newRule);
									--$nesting;
								case 'document':
									$char !== '{' && $this->search('{', $rule);

								case 'supports':
									$char !== '{' && $this->search('{', $rule);
									$rule->insert($newRule = new Rule($newRule new SupportsCondition($this->buffer), self::SUPPORTS_RULE, $prefix));
									$this->buffer = '';
									++$nesting;
									$this->prepare($newRule);
									--$nesting;
									break;
								default:
									// 不明属性
									if ($char !== '{') {
										$char = $this->search(NULL, ';{}');
									}
									if ($char !== ';') {
										// 循环同样的 {} 嵌套
										$i = 0;
										do {
											if ($char === '{') {
												++$i;
											} else {
												--$i;
											}
										} while ($i > 0 && ($char = $this->search(NULL, '{}')));
									}
							}
					}
			}
		}
	}






	protected function prepareReferences(Rule $rule, $inArray = []) {
		// 直接元素绑定

		// 清空缓冲区
		$this->buffer = '';

		// 读元素长度
		$this->search($rule, '}');

		// 发布
		foreach (explode(';', $this->buffer) as $value) {
			if ($value && count($value = explode(':', $value, 2)) === 2) {
				$prefix = $this->prefix($value[0]);
				$value[1] = trim($value[1]);
				$value[2] = strcasecmp(substr($value[1], -10, 10), '!important') === 0;

				if ($value[2]) {
					$value[1] = trim(substr($value[1], 0, -10));
				}

				if (!$inArray || in_array($value[0], $inArray, true)) {
					$rule->insert(new Rule($value, self::REFERENCE_RULE, $prefix));
				}
			}
		}
		$this->buffer = '';
	}





	protected function search($char, Rule $rule = NULL) {
		while (($search = $this->_search(['/*' => true, '\\\'"' . $char => false])) !== false) {
			switch ($search) {
				case '/*':
					//  注释
					$buffer = $this->buffer;
					$this->buffer = '';
					$this->_search(['*/' => true]);
					$rule && $rule->insert(new Rule($this->buffer, self::COMMENT_RULE));
					$this->buffer = $buffer;
					break;
				case '\\':
					$this->buffer .= $search . $this->style{$this->offset};
					++$this->offset;
					break;
				case '"':
				case '\'':
					$quote = $search;
					$this->buffer .= $quote;
					while (($search = $this->_search(['\\' . $quote => false])) === '\\') {
						$this->buffer .= '\\';
						if (isset($this->style{$this->offset})) {
							$this->buffer .= $this->style{$this->offset};
						}
						++$this->offset;
					}
					if ($search !== false) {
						$this->buffer .= $search;
					}
				default:
					return $search;
			}
		}
		return false;
	}




	private function _search(array $array, $buffer = true) {
		$length = $this->length;
		$string = false;
		foreach ($array as $key => $value) {
			if ($value) {
				if (($strpos = strpos($this->style, $key, $this->offset)) !== false && $strpos < $length) {
					$length = $strpos;
					$string = $key;
				}
			} else {
				$strcspn = strcspn($this->style, $key, $this->offset);
				$strcspn += $this->offset;
				if ($strcspn < $length) {
					$length = $strcspn;
					$string = $this->style{$strcspn};
				}
			}
		}

		if ($buffer) {
			$this->buffer .= substr($this->style, $this->offset, $length - $this->offset);
		}
		$this->offset = $length + strlen($string);
		return $string;
	}









	public function format($format) {
		$this->format = $format ? 0 : false;
		return $this;
	}


	// to string
	public function __toString() {
		switch ($this->type) {
			case self::COMMENT_RULE:
				$result = '/*'. trim(strtr($this->value, ['*/' => '', '/*' => ''])) . '*/';
				break;
			case self::REFERENCE_RULE:
				$result = $this->parentRule ? $this->value[0] .':' . $this->value[1] . ($this->value[2] ? '!important': '') . ';' : '';
				break;
			case self::STYLE_RULE:
				$result = $this->value . '{';
				$result = '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::CHARSET_RULE;
				$result = '@charset "'. strtr($this->value, ['"' => '&quot;']) .'";';
				break;
			case self::IMPORT_RULE;
				$result = '@import url("'. strtr($this->value[0], ['"' => '&quot;']) .'") '. $this->value[1] .';';
				break;
			case self::NAMESPACE_RULE:
				$result = '@namespace '. $this->value[1] .' url("'. strtr($this->value[0], ['"' => '&quot;']) .'");';
				break;
			case self::FONT_FACE_RULE;
				$result = '@font-face';
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::VIEWPORT_RULE;
				$result = '@viewport';
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::COUNTER_STYLE_RULE:
				$result = '@counter-style '. $this->value;
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::KEYFRAMES_RULE:
				$result = '@keyframes '. $this->value;
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::KEYFRAME_RULE:
				$result = $this->value;
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::FONT_FEATURE_VALUES_RULE:
				$result = '@font-feature-values '. $this->value;
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::FONT_FEATURE_VALUE_RULE:
				$result = '@'. $this->value;
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::MEDIA_RULE:
				$result = '@media '. $this->value;
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			case self::DOCUMENT_RULE:
				$result = '@document ';
				$array = [];
				foreach ($this->value as $value) {
					$array[] = $value[0] . '("'. strtr($value[1], ['"' => '&quot;']) .'")';
				}
				$result = implode(',', $array);
				$result .= '{';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				$result .= '}';
				break;
			default:
				$result = '';
				foreach($this->ruleList as $value) {
					$result .= $value;
				}
				break;
		}
		return $result;
	}





	/**
	 * prefix 过滤前缀
	 * @param  string $name 注意要小写
	 * @return string
	 */
	protected function prefix(&$name) {
		$name = strtolower(trim($name));
		if (!$name || $name{0} !== '-') {
			$prefix = '';
		} else {
			if (($length = strpos(substr($name, 1), '-')) === false) {
				$length = strlen($name);
			}
			$prefix = substr($name, 1, $length - 1);
			$name = substr($name, $length);
		}
		return $prefix;
	}


	public function insert($rule, $index = NULL) {
		if ($rule instanceof Rule) {
			$rule->parentRule && $rule->remove();
		}
		$rule->parentRule = $this;
		if ($index === NULL) {
			$this->ruleList[] = $rule;
		} else {
			array_splice($this->ruleList, $index, 0,[$rule]);
		}
	}

	// 移除自己
	public function remove() {
		if ($this->parentRule && ($index = array_search($this, $this->parentRule->ruleList, true)) !== false) {
			unset($this->parentRule->ruleList[$index]);
			$this->parentRule->ruleList = array_values($this->parentRule->ruleList);
		}
	}
}





	/*
	public function __construct($value, $type = false, $prefix = '') {
		$this->type = self::STYLE_RULE;
		$this->prefix = $prefix;

		$this->style = trim(mb_convert_encoding((string) $value,'utf-8', 'auto'));
		$this->length = strlen($this->style);
		$this->offset = 0;
		$this->buffer = '';
		$this->prepare($this);
		unset($this->style, $this->length, $this->offset, $this->buffer);
	}

	/*
	protected function prepare(Rule $rule) {
		static $nesting = 0;
		// 限制嵌套层次
		if ($nesting >= self::NESTING) {
			return;
		}
		while (($search = $this->_search(['/*' => true, '@;{}"\'\\' => false])) !== false) {
			if (!isset($this->style{$this->offset})) {
				break;
			}
			switch ($search) {
				case ';':
					// 直接结束的无效值
					$this->buffer = '';
					break;
				case '}':
					// 结束括号
					$this->buffer = '';
					// 有父级 跳出
					if ($rule->parentRule) {
						break 2;
					}
					break;
				case '/*':
					//  注释
					$this->prepareComment($rule);
					break;
				case '{':
					// 直接元素绑定
					$this->prepareReferences($rule);
					break;
				case '@':
					$this->buffer = '';
					$char = $this->_search([" \t\n\r\0\x0B{}:(;" => false]);
					switch ($char) {
						case ';':
						case '}':
							// 直接结束的 无效值
							break;
						default:
							$name = $this->buffer;
							$prefix = $this->prefix($name);
							$this->buffer = '';
							switch ($name) {
								case 'charset':
								case 'import':
								case 'namespace':
									//  编码 载入 命名空间 是无 {} 的
									$this->pos(';');
									if ($this->buffer && ($name !== 'charset' || $this->parentRule)) {
										$rule->insert(new Rule($this->buffer, self::$atIndexs[$name], $prefix));
									}
									$this->buffer = '';
									break;
								case 'font-face':
								case 'viewport':
									// 字体 和 viewport 无嵌套的
									$char !== '{' && $this->_search(['{' => false]);
									$this->insert($newRule = new Rule('', self::$atIndexs[$name], $prefix));
									$this->prepareReferences($newRule);
									break;
								case 'counter-style':
									// 有序规则 计数器定义
									if ($char !== '{' && $this->_search(['{' => true]) && $this->buffer) {
										$this->insert($newRule = new Rule($this->buffer, self::$atIndexs[$name], $prefix));
										$this->prepareReferences($newRule);
									}
									break;
								case 'keyframes':
									// 动画
									if ($char !== '{' && $this->_search(['{' => true]) && $this->buffer) {
										$this->insert($newRule = new Rule($this->buffer, self::$atIndexs[$name], $prefix));
										$this->prepareKeyFrames($newRule);
									}
									break;
								case 'page':
									break;
								case 'font-feature-values':
								case 'media':
								case 'supports':
								case 'document':
									break;
								case '':
							}
					break;
				default:
					$this->prepareEscape($search);
			}
		}
	}






	protected function prepareKeyFrames(Rule $rule) {


	}


			/*switch ($search) {
				case '@':
					$this->buffer = '';
					$char = $this->cspn(" \t\n\r\0\x0B{}:();");
					switch ($char) {
						case ';':
						case '}':
							// 直接结束的 无效值
							break;
						case ')':
							// 结束 的跳到  ; 或 ; 去 并且清除无效缓冲区
							$this->cspn('};', false);
							break;
						default:
							// at 名
							$name = $this->buffer;
							$prefix = $this->prefix($name);
							$this->buffer = '';
							switch ($name) {
								case 'charset':
								case 'import':
								case 'namespace':
									//  编码 载入 命名空间 是无{}的
									$this->pos(';');
									if (($value = $this->trim($this->buffer)) && ($name !== 'charset' || $this->parentRule)) {
										$this->insert(new Rule($value, self::CHARSET_RULE, $prefix));
									}
									$this->buffer = '';
									break;
								case 'font-face':
									// font-face 类型
									// 无嵌套 {} 的
									$char !== '{' && $this->pos('{', false);
									$this->pos('\}');
									if ($this->buffer) {
										$this->insert(new Rule($this->buffer, self::FONT_FACE_RULE, $prefix));
									}
									break;
								case 'font-face':
								case 'viewport':
								case 'page':
								case 'media':
								case 'keyframes':
								case 'counter-style':
								case 'supports':
								case 'document':
									break;
								default:
									$char = $this->cspn('{;', false);
									if ($char === '')
									$this->buffer = '';
							}
							break;
					}
					break;
				case '{':
					// 样式类型
					break;
				case '/':
					// 注释
					//if ($this->style{$this->offset} === '*') {
					//	$this->pos('* /', false);
					//	$this->insert(new Rule(, self::COMMENT_RULE));
					//}
					break;
			}
		}
	}

	protected function trim($a) {
		return trim($a, " \t\n\r\0\x0B\"'");
	}*/





/*
	protected function prepareReferences(Rule $rule) {
		// 直接元素绑定

		// 选择器
		$selectors = $this->buffer;

		// 清空缓冲区
		$this->buffer = '';

		// 读元素长度
		while (($search = $this->_search(['/*' => true, '\\\'"}' => false])) !== false) {
			switch ($search) {
				case '/*':
					//  注释
					$this->prepareComment($rule);
					break;
				case '}':
					//  结束
					break 2;
				default
					$this->prepareEscape($search);
			}
		}

		// 读取
		$references = $this->buffer;

		// 设置规则
		$rule->insert($newRule = new Rule($selectors, self::STYLE_RULE));

		// 发布
		foreach (explode(';', preg_replace('/\/\*.*\*\//s', '', $references))  as $value) {
			if ($value && count($value = explode(':', $value, 2)) === 2) {
				$prefix = $this->prefix($value[0]);
				$newRule->insert(new Rule($value[0], $value[1], self::REFERENCE_RULE, $prefix));
			}
		}
	}








	protected function prepareComment(Rule $rule) {
		$buffer = $this->buffer;
		$this->_search(['* /' => true]);
		$rule->insert(new Rule($this->buffer, self::COMMENT_RULE));
		$this->buffer = $buffer;
	}


	protected function prepareEscape($quote) {
		if ($search === '\\') {
			$this->buffer .= $search . $this->style{$this->offset};
			++$this->offset;
		} else {
			$this->buffer .= $quote;
			while (($search = $this->_search(['\\' . $quote => false])) === '\\') {
				$this->buffer .= '\\';
				if (isset($this->style{$this->offset})) {
					$this->buffer .= $this->style{$this->offset};
				}
				++$this->offset;
			}
			if ($search !== false) {
				$this->buffer .= $search;
			}
		}
	}




	protected function search(array $array, $buffer = true) {
		$length = $this->length;
		$string = false;
		foreach ($array as $key => $value) {
			if ($value) {
				if (($strpos = strpos($this->style, $key, $this->offset)) !== false && $strpos < $length) {
					$length = $strpos;
					$string = $key;
				}
			} else {
				$strcspn = strcspn($this->style, $key, $this->offset);
				$strcspn += $this->offset;
				if ($strcspn < $length) {
					$length = $strcspn;
					$string = $this->style{$strcspn};
				}
			}
		}

		if ($buffer) {
			$this->buffer .= substr($this->style, $this->offset, $length - $this->offset);
		}
		$this->offset = $length + strlen($string);
		return $string;
	}


	//abstract public function __toString();



	/**
	 * prefix 过滤前缀
	 * @param  string $name 注意要小写
	 * @return string
	 *//*
	protected function prefix(&$name) {
		$name = strtolower($name);
		if (!$name || $name{0} !== '-') {
			$prefix = '';
		} else {
			if (($length = strpos(substr($name, 1), '-')) === false) {
				$length = strlen($name);
			}
			$prefix = substr($name, 1, $length - 1);
			$name = substr($name, $length);
		}
		return $prefix;
	}

	protected function mediaQuery($query) {
		$query = preg_replace('/\s+/', ' ', $query);
		$query = preg_replace('/[^0-0a-z.%:()_-]/i', '', $query);
		return $query;
	}


	public function insert($rule, $index = NULL) {
		if ($rule instanceof Rule) {
			$rule->parentRule && $rule->remove();
			$this->ruleList[] = $rule;
		} else {

		}
	}

	// 移除自己
	public function remove() {
		if ($this->parentRule && ($index = array_search($this, $this->parentRule->ruleList, true)) !== false) {
			unset($this->parentRule->ruleList[$index]);
			$this->parentRule->ruleList = array_values($this->parentRule->ruleList);
		}
	}

}

*/