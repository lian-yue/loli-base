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
/*	Updated: UTC 2015-06-14 14:59:52
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
			if ($prefix && preg_match('/^\-[a-z]+\-$/i', $prefix)) {
				$this->prefix = $prefix;
			}
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
					$rule->insert($rule2 = new Rule(new Selectors($this->buffer), self::STYLE_RULE));

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
							unset($name, $prefix);

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
									if ($this->search(';', $rule) && !$this->parentRule && ($charset = strtoupper(trim($this->buffer, " \t\n\r\0\x0B\"'"))) && preg_match('/^[A-Z]+[A-Z0-9_-]*$/', $charset)) {
										$rule->insert(new Rule($charset, self::CHARSET_RULE, $prefix));
									}
									$this->buffer = '';
									break;
								case 'import':
									// 引入文件
									if ($this->search(';', $rule) && ($import = trim($this->buffer, " \t\n\r\0\x0B\"'")) && (preg_match('/\s*url\(("|\')?(.+?)(?(1)\1|)\)(?:\s+(.+))?/i', $import, $matches) || preg_match('/\s*("|\')?(.+?)(?(1)\1|)(?:\s+(.+))?/i', $import, $matches)) && ($matches[2] = preg_replace('/(["\'()*;<>\\\\]|\s)/', '', $matches[2])) && (!($scheme = parse_url($matches[2], PHP_URL_SCHEME)) || strcasecmp($scheme, 'http') === 0 || strcasecmp($scheme, 'https') === 0)) {
										$rule->insert(new Rule([$matches[2], new Media(empty($matches[3]) ? '' : $matches[3])], self::IMPORT_RULE, $prefix));
									}
									$this->buffer = '';
									break;
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
									$this->prepareReferences($newRule, ['font-family', 'src', 'unicode-range', 'font-variant', 'font-feature-settings', 'font-stretch', 'font-weight', 'font-style']);
									$this->buffer = '';
									break;
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
										while ($this->search('{}', $newRule) === '{') {
											$value = [];
											foreach (array_map('trim', explode(',', $this->buffer)) as $buffer) {
												if (!($buffer = trim($buffer)) || !preg_match('/^(from|to|\d+\%)$/', $buffer = strtolower($buffer))) {
													continue;
												}
												$value[] = $buffer;
											}
											if ($value) {
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
									// page 暂时不支持
									if ($char !== '{') {
										$char = $this->search(';{}');
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
										} while ($i > 0 && ($char = $this->search('{}')));
									}
									break;
								case 'media':
									// meta 规则
									$char !== '{' && $this->search('{', $rule);
									$rule->insert($newRule = new Rule(new Media($this->buffer), self::MEDIA_RULE, $prefix));
									$this->buffer = '';
									++$nesting;
									$this->prepare($newRule);
									--$nesting;
									break;
								case 'document':
									// 文档域 匹配规则
									if ($char !== '{') {
										$array =[];
										while (($w = $this->search(',{', $rule)) === ',') {
											$array[] = $this->buffer;
											$this->buffer = '';
										}
										$array[] = $this->buffer;
										$this->buffer = '';
										foreach ($array as $key => $value) {
											if (!preg_match('/^\s*(url|url\-prefix|domain|regexp)\s*\(("|\')?(.*?)(?(2)\2|)\)\s*$/i', $value, $matches)) {
												unset($array[$key]);
												continue;
											}
											$array[$key] = [strtolower($matches[1]), $matches[3]];
										}
										$rule->insert($newRule = new Rule(array_values($array), self::DOCUMENT_RULE, $prefix));
										++$nesting;
										$this->prepare($newRule);
										--$nesting;
									}
									break;
								case 'supports':
									$char !== '{' && $this->search('{', $rule);
									$rule->insert($newRule = new Rule(new Supports($this->buffer), self::SUPPORTS_RULE, $prefix));
									$this->buffer = '';
									++$nesting;
									$this->prepare($newRule);
									--$nesting;
									break;
								default:
									// 不明属性
									if ($char !== '{') {
										$char = $this->search(';{}');
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
										} while ($i > 0 && ($char = $this->search('{}')));
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
		$this->search('}', $rule);

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
					break;
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
		$t = $this->format === false ? '' : "\n" . str_repeat("\t", $this->format);
		switch ($this->type) {
			case self::COMMENT_RULE:
				// 注释
				$result = '/*'. str_replace('*/', '', $this->value) . '*/';
				break;
			case self::REFERENCE_RULE:
				// 属性
				$result = $this->parentRule ? $this->prefix . $this->value[0] .':' . $this->value[1] . ($this->value[2] ? '!important': '') . ';' : '';
				break;
			case self::STYLE_RULE:
				// style 样式表
				$result = $this->value;
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::CHARSET_RULE;
				// 编码
				$result = '@'. $this->prefix .'charset "'. str_replace('"', '&quot;', $this->value) .'";';
				break;
			case self::IMPORT_RULE;
				// 引入文件
				$result = '@'. $this->prefix .'import url("'. str_replace('"', '&quot;', $this->value[0]) .'")'. (($media = (string) $this->value[1]) ? ' ' . $media : '') .';';
				break;
			case self::NAMESPACE_RULE:
				// 命名空间
				$result = '@'. $this->prefix .'namespace '. $this->value[1] .' url("'. str_replace('"', '&quot;', $this->value[0]) .'");';
				break;
			case self::FONT_FACE_RULE;
				// 字体文件
				$result = '@'. $this->prefix .'font-face';
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::VIEWPORT_RULE;
				// 缩放
				$result = '@'. $this->prefix .'viewport';
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::COUNTER_STYLE_RULE:
				// 计数器 li 什么的
				$result = '@'. $this->prefix .'counter-style '. $this->value;
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::KEYFRAMES_RULE:
				// 动画
				$result = '@'. $this->prefix .'keyframes '. $this->value;
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::KEYFRAME_RULE:
				// 动画 单个规则
				$result = implode(', ', $this->value);
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::FONT_FEATURE_VALUES_RULE:
				// 字体属性  大小什么的
				$result = '@'. $this->prefix .'font-feature-values '. $this->value;
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::FONT_FEATURE_VALUE_RULE:
				// 字体属性单条规则
				$result = '@'. $this->value;
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::MEDIA_RULE:
				// media 分辨率控制
				$result = '@'. $this->prefix .'media '. $this->value;
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::DOCUMENT_RULE:
				// 文档
				$result = '@'. $this->prefix .'document ';
				$array = [];
				foreach ($this->value as $value) {
					$array[] = $value[0] . '("'. str_replace('"', '&quot;', $value[1]) .'")';
				}
				$result .= implode(',', $array);
				$result .= ' {';

				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			case self::SUPPORTS_RULE:
				// 属性支持判断
				$result = '@'. $this->prefix .'supports ' . $this->value;
				$result .= ' {';
				foreach($this->ruleList as $value) {
					if ($t) {
						$value->format = $this->format + 1;
					}
					$result .= $value;
				}
				$result .= $t . '}';
				break;
			default:
				$result = '';
				foreach($this->ruleList as $value) {
					$value->format = $this->format;
					$result .= $value;
				}
				break;
		}
		return (!$this->parentRule || (!$this->format &&  reset($this->parentRule->ruleList) === $this) ? '' : $t) . $result;
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
			} else {
				$length +=2;
			}
			$prefix = substr($name, 0, $length);
			if (!preg_match('/-[a-z][0-9a-z]*-/', $prefix)) {
				$prefix = '';
			}
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


