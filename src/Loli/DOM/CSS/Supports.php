<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-06-14 05:39:16
/*	Updated: UTC 2015-06-14 13:46:38
/*
/* ************************************************************************** */
namespace Loli\DOM\CSS;
class Supports{

	const TYPE_ROOT = 1;

	const TYPE_GROUP = 2;

	const TYPE_VALUE = 3;

	protected $conditions = [];

	protected $parent;

	protected $type;

	protected $name;

	protected $value;

	protected $logical = 'and';

	const NESTING = 10;

	public function __construct($value = false, $type = 0) {
		switch ($type) {
			case self::TYPE_VALUE:
				$this->type = self::TYPE_VALUE;
				break;
			case self::TYPE_GROUP:
				$this->type = self::TYPE_GROUP;
				if (is_array($value)) {
					foreach($value as $support) {
						$this->insert($support);
					}
				}
				break;
			default:
				$this->type = self::TYPE_ROOT;
				if ($value) {
					$this->value = trim(mb_convert_encoding((string) $value,'utf-8', 'auto'));
					$this->length = strlen($this->value);
					$this->offset = 0;
					$this->buffer = '';
					$this->prepare($this);
					unset($this->value, $this->length, $this->offset, $this->buffer);
				}
		}
	}

	protected function prepare($supports) {
		static $nesting = 0;
		// 限制嵌套层次
		if ($nesting >= self::NESTING) {
			return;
		}

		while (($char = $this->_search('{};()')) !== false) {
			switch ($char) {
				case ';':
				case '{':
				case '}':
					// 结束的
					$this->buffer = '';
					break 2;
				case ')':
					// 结束
					if (!$supports->conditions) {
						$buffer = strtolower(trim($this->buffer));
						$array = explode(':', $buffer, 2);
						$array = array_map('trim', $array);
						if (preg_match('/^((?:\-[a-z]+\-)?[a-z][0-9a-z_-]+)$/i', $array[0])) {
							$supports->type = self::TYPE_VALUE;
							$supports->name = $array[0];
							$supports->value = empty($array[1]) ? '' : preg_replace('/[^0-9a-z !|\/%#.,-]/i', '', $array[1]);
						}
					}
					$this->buffer = '';
					break 2;
				case '(':
					// 开始
					$buffer = strtolower(trim($this->buffer));
					if ($supports->conditions) {
						// and or 运算符
						if (in_array($buffer, ['and', 'or'], true)) {
							$supports->logical = $buffer;
						}
					} elseif (!$supports->parent && $buffer === 'not') {
						// not 运算符
						$supports->logical = 'not';
					}

					// 清空缓冲区
					$this->buffer = '';

					// 递归创建对象
					$supports->insert($newSupports = new Supports);
					++$nesting;
					$this->prepare($newSupports);
					--$nesting;


					// 组类型
					if ($this->conditions) {
						$supports->type = self::TYPE_GROUP;
					}
					break;
			}
		}

		if ($supports->parent && !$supports->type === self::TYPE_ROOT) {
			// 无效的对象移除
			$supports->remove();
		} elseif ($supports->logical === 'not' && $supports->conditions) {
			// 如果是not 运算符只允许一个 属性
			$supports->conditions = [reset($supports->conditions)];
		}
	}




	private function _search($search, $buffer = true) {
		$strcspn = strcspn($this->value, $search, $this->offset);
		$strcspn += $this->offset;

		if ($strcspn < $this->length) {
			$length = $strcspn;
			$string = $this->value{$strcspn};
		} else {
			$length = $this->length;
			$string = false;
		}

		if ($buffer) {
			$this->buffer .= substr($this->value, $this->offset, $length - $this->offset);
		}
		$this->offset = $length + 1;
		return $string;
	}




	public function insert(Supports $supports, $index = NULL) {
		$supports->parent && $supports->remove();
		$supports->parent = $this;
		if ($index === NULL) {
			$this->conditions[] = $supports;
		} else {
			array_splice($this->conditions, $index, 0,[$supports]);
		}
	}



	// 移除自己
	public function remove() {
		if ($this->parent && ($index = array_search($this, $this->parent->conditions, true)) !== false) {
			unset($this->parent->conditions[$index]);
			$this->parent->conditions = array_values($this->parent->conditions);
		}
	}



	public function __toString() {
		switch ($this->type) {
			case self::TYPE_ROOT:
			case self::TYPE_GROUP:
				$conditions = [];
				foreach ($this->conditions as $condition) {
					$conditions[] = '('. $condition .')';
				}
				return ($this->logical === 'not' ?  'not ' : '') . implode(' ' . $this->logical . ' ', $conditions);
				break;
			case self::TYPE_VALUE:
				return $this->name . ': ' . $this->value;
		}
	}
}