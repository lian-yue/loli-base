<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-06-10 15:05:52
/*	Updated: UTC 2015-06-14 08:56:31
/*
/* ************************************************************************** */
namespace Loli\DOM\CSS;
class Media{
	protected $querys = [];
	/*
	$querys = [
		[
			['only', 'screen'],
			['not', [self::FEATURE_PLAIN, xxx,xxx], 'and'...]
		]
	];
	 */
	const NESTING = 5;

	const FEATURE_PLAIN = 1;

	const FEATURE_BOOLEAN = 2;

	const FEATURE_RANGE = 3;


	// 类型
	protected $types = ['all', 'print', 'screen', 'speech', 'tty', 'tv', 'projection', 'handheld', 'braille', 'embossed', 'aural'];

	public function __construct($queryString) {
		$this->offset = 0;
		$this->queryString = trim($queryString);
		$this->length = strlen($this->queryString);
		$this->buffer = '';


		$query = [[false, false], []];
		while (($char = $this->_search(" \t\n\r\0\x0B{};(,")) !== false) {
			switch ($char) {
				case ';':
				case '{':
				case '}':
					// 结束的
					break 2;
				case '(':
					$buffer = strtolower(trim($this->buffer));
					if ((!$query[1] && $buffer === 'not') || ($query[1] && is_array(end($query[1])) && in_array($buffer, ['and', 'or'], true))) {
						$query[1][] = $buffer;
					}

					if (!$query[1] || !is_array(end($query[1]))) {
						$this->buffer = '';
						if ($this->_search('{};(),') !== ')') {
							break 2;
						}
						$buffer = strtolower(trim($this->buffer));
						if (preg_match('/^((?:\-[a-z]+\-)?[a-z][0-9a-z_-]+)\s*\:\s*([0-9a-z%\.-]+)$/i', $buffer, $matches)) {
							$query[1][] = [self::FEATURE_PLAIN, $matches[1], $matches[2]];
						} elseif (preg_match('/^((?:\-[a-z]+\-)?[a-z][0-9a-z_-]+)$/i', $buffer, $matches)) {
							$query[1][] = [self::FEATURE_BOOLEAN, $matches[1]];
						} elseif (preg_match('/^((?:\-[a-z]+\-)?[a-z][0-9a-z_-]+)\s*([<>]\=?|=?)\s*([0-9a-z%\.-]+)$/i', $buffer, $matches)) {
							$query[1][] = [self::FEATURE_RANGE, $matches[1], $matches[2], $matches[3]];
						} elseif (preg_match('/^([0-9a-z%\.-]+)\s*([<>]=?)\s*((?:\-[a-z]+\-)?[a-z][0-9a-z_-]+)(?:\s*([<>]=?)\s*([0-9a-z%\.-]+))?$/i', $buffer, $matches)) {
							$query[1][] = [self::FEATURE_RANGE, $matches[1], $matches[2], $matches[3], empty($matches[4]) ? false: $matches[4], empty($matches[5]) ? $matches[5] : false];
						} elseif ($query[1]) {
							array_pop($query[1]);
						}
					}
					break;
				default:
					$buffer = strtolower(trim($this->buffer));
					if (!$query && in_array($buffer, ['only', 'not'], true)) {
						$query[0][0] = $buffer;
					} elseif (!$query[1] && !$query[0][1] && in_array($buffer, $this->types, true)) {
						$query[0][1] = $buffer;
					}
					if ($char === ',' && $query) {
						$this->querys[] = $query;
						$query = [[false, false], []];
					}
			}
		}
		if ($query) {
			$this->querys[] = $query;
		}

		unset($this->offset, $this->queryString, $this->buffer);
	}


	public function __toString() {
		$querys = [];
		foreach ($this->querys as $query) {
			if ($query[0][1]) {
				$query[0] = implode(' ', array_filter($query[0]));
			} else {
				unset($query[0]);
			}
			if ($query[1]) {
				$parens = empty($query[0]) ? [] : ['and'];
				foreach ($query[1] as $value) {
					if (is_array($value)) {
						switch ($value[0]) {
							case self::FEATURE_PLAIN:
								$parens[] = '('. $value[1] . ':' . $value[2] .')';
								break;
							case self::FEATURE_BOOLEAN:
								$parens[] = '('. $value[1] .')';
								break;
							case self::FEATURE_RANGE:
								unset($value[0]);
								$parens[] = '('. implode(' ', $value) .')';
						}
					} else {
						$parens[] = $value;
					}
				}
				$query[1] = implode(' ', $parens);
			} else {
				unset($query[1]);
			}
			$querys[] = implode(' ', $query);
		}
		return implode(', ', $querys);
	}





	private function _search($search, $buffer = true) {
		$strcspn = strcspn($this->queryString, $search, $this->offset);
		$strcspn += $this->offset;

		if ($strcspn < $this->length) {
			$length = $strcspn;
			$string = $this->queryString{$strcspn};
		} else {
			$length = $this->length;
			$string = false;
		}

		if ($buffer) {
			$this->buffer .= substr($this->queryString, $this->offset, $length - $this->offset);
		}
		$this->offset = $length + 1;
		return $string;
	}


}