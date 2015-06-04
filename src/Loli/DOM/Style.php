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
/*	Updated: UTC 2015-06-04 13:43:26
/*
/* ************************************************************************** */
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



	/*

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
				if (!$continue = preg_match('/^'.strtr($pattern, ['/' => '\\/']).'/i', $selectors, $matches)) {
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





















