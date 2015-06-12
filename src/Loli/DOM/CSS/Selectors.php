<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-06-05 03:10:27
/*	Updated: UTC 2015-06-08 10:22:26
/*
/* ************************************************************************** */
namespace Loli\DOM\CSS;
use IteratorAggregate, ArrayAccess, Countable, ArrayIterator;
class Selectors implements IteratorAggregate, ArrayAccess, Countable{

	/**
	 * $selectors
	 * @var array
	 */
	protected $selectors = [];

	/**
	 * $comments 解析到的注释
	 * @var array
	 */
	protected $comments = [];

	/**
	 * $recursive 递归
	 * @var array
	 */
	protected $recursive;

	// 解析的数据长度
	protected $length = 0;


	// 匹配规则
	protected $patterns = [

		// 需要跳出的规则
		'\s*@' => '@',
		'\s*\{' => '{}',


		// or
		'\s*,\s*' => ',',

		// 标签
		'(\*|[a-z][0-9a-z_\-]*)' => '',


		// 多层后代选择器
		'\s*\>\>\s*' => ' ',

		// 单层后代选择器
		'\s*\>\s*' => '>',


		// 当前层次 向上一个选择
		'\s*\+\s*' =>  '+',

		// 当前层次 之上选择
		'\s*~\s*' => '~',

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
	];





	public function __construct($selectors, $recursive = false) {
		$this->recursive = $recursive;
		$this->parse($selectors);
	}



	protected function parse($selectors) {

		// 字符串长度
		$length = strlen($selectors);


		// 解析到的数组
		$array = [];

		// 判断是否循环
		$continue = true;

		// 循环
		while($selectors && $continue) {
			foreach($this->patterns as $pattern => $type) {
				// 匹配失败 跳过
				if (!$continue = preg_match('/^'.strtr($pattern, ['/' => '\\/']).'/i', $selectors, $matches)) {
					continue;
				}
				// 匹配到的长度
				$offset = strlen($matches[0]);


				// 匹配到的类型
				switch ($type) {
					case '@':
					case '{}':
						// 需要跳出的 跳出 while
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
						// 类型 链接符号
						$array[] = [$type];
						break;
					case '':
					case '*':
						// 类型 标签名
						$array[] = ['', strtolower($matches[1])];
						break;
					case '#':
					case '.':
						// 类型 字段
						$array[] = [$type, $matches[1]];
						break;
					case '[]':
						// 类型, 字段, 运算类型, 运算值
						$array[] = ['[]', explode('|', strtolower($matches[1])), isset($matches[2]) ? $matches[2] : '', isset($matches[4]) ? $matches[4] : false];
						break;
					case ':':
						// 类型 字段 参数
						$matches[1] = strtolower($matches[1]);
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
									// 跳出
									break 4;
								}

								// 字符串移到开始括号
								$selectors = substr($selectors, $offset + strlen($matches2[0]));

								// 递归匹配
								$selectorsArg = new Selectors($selectors, $matches[1]);

								// 继续偏移
								$selectors = substr($selectors, $selectorsArg->length());

								// 设置偏移
								$offset = strlen(preg_match('/^\s*\)/', $selectors, $matches3) ? $matches3[0] : $selectors);

								//  储存 不是递归 or 递归 不是 matches 并且  当前不是matches
								if ((!$this->recursive || ($this->recursive !== 'matches' && $matches[1] !== 'matches')) && $selectorsArg->count()) {
									$array[] = [':', $matches[1], $selectorsArg];
								}
								break;
						}
						break;
				}
				$selectors = substr($selectors, $offset);
				break;
			}
		}

		// 解析的长度
		$this->length = $length - strlen($selectors);










		//[['多条规则', '>+~| ', '多条规则']]


		$results = $result = $single = [];


		// 最后一个添加逗号
		$array[] = [','];

		foreach ($array as $value) {
			switch ($value[0]) {
				case ',':
					// 逗号
					if ($single) {
						$result[] = $this->single($single);
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
						$result[] = $this->single($single);
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
		$this->selectors = $results;
	}


	/**
	 * __toString 对象字符串输出
	 * @return string
	 */
	public function __toString() {
		$selectors = [];
		foreach ($this as $selector) {
			$selector = '';
			foreach ($selector as $values) {
				if (is_array($values)) {
					foreach ($values as $value) {
						switch ($value[0]) {
							case '*':
								$selector .= $value[0];
								break;
							case '':
							case '.':
							case '#':
								if (preg_match('/^[a-z_-][a-z0-9_-]*$/i', $value[1])) {
									$selector .= $value[0] . $value[1];
								}
								break;
							case '[]':
								$attributeNames = [];
								foreach ($value[1] as $attributeName) {
									if ($attributeName === '*' || ($attributeName = preg_replace('/^[0-0a-z_-]/i', '', $attributeName))) {
										$attributeNames[] = $attributeName;
									}
								}
								if ($attributeNames) {
									$selector .= '['. implode('|', $attributeNames) . ($value[2] ? $value[2] . '"'. strtr($value[3], ['"' => '&quot;']) .'"' : '') .']';
								}
							case ':':
								switch ($value[1]) {
									case 'lang':
										if (preg_match('/^[a-z0-9_-]+$/i', $value[2])) {
											$selector .= $value[0] . $value[1] . '('. $value[2]. ')';
										}
										break;
									case 'nth-child':
									case 'nth-last-child':
									case 'nth-of-type':
									case 'nth-last-of-type':
										$value[2][0] = $value[2][0] === '-' ? '-' : (int) $value[2][0];
										$args = $value[2][1] ? $value[2][0] . 'n' . ($value[2][2] < 0 ? intval($value[2][2]) : '+' . intval($value[2][2])) : $value[2][0];
										$selector .= $value[0] . $value[1] . '('. $args. ')';
										break;
									case 'has':
									case 'hot':
									case 'matches':
										$selector .= $value[0] . $value[1] . '('. $value[2] . ')';
									default:
										$selector .= $value[0] . $value[1];
								}
						}
					}
				} else {
					$selector .= $values;
				}
			}
			$selectors[] = $selector;
		}
		return implode(', ', $selectors);
	}




	protected function single(&$single) {
		$types = [];
		$result = [];
		foreach ($single as $value) {
			// 标签 和 id 不能多个 多个就删除
			if (in_array($value[0], ['', '#'], true)) {
				if (isset($types[$value[0]])) {
					continue;
				}
				$types[$value[0]] = true;
			}
			$result[] = $value;
		}


		// 清空 single
		$single = [];


		// 规则排序
		usort($result, [$this, 'sort']);
		return $result;
	}

	/**
	 * sort 选择器排序回调函数
	 * @param  array $a
	 * @param  array $b
	 * @return integer
	 */
	protected function sort($a, $b) {
		static $sort = [
			'' => 0,		// 单个
			'#' => 1,		// 单个
			'.' => 2,		// 多个
			'[]' => 3,		// 多个
			':' => 4,		// 多个
		];
		return $sort[$a[0]] ===  $sort[$b[0]] ? : ( $sort[$a[0]] > $sort[$b[0]] ? 1 : -1);
	}







	public function comments() {
		return $this->comments;
	}

	/**
	 * length 解析的字符串长度
	 * @return
	 */
	public function length() {
		return $this->length;
	}

	/**
	 * count 选择器数量
	 * @return
	 */
	public function count() {
		return count($this->selectors);
	}



	public function offsetSet($name, $value) {
		if ($value && is_array($value)) {
			$this->selectors[$name] = $value;
		}
	}
	public function offsetExists($name) {
		return isset($this->selectors[$name]);
	}
	public function offsetUnset($name) {
		unset($this->selectors[$name]);
	}

	public function offsetGet($name) {
		return isset($this->selectors[$name]) ? $this->selectors[$name] : NULL;
	}

	public function getIterator() {
		return new ArrayIterator($this->selectors);
	}

	public function toArray() {
		return $this->selectors;
	}
}