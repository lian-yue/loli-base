<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-11 16:13:26
/*	Updated: UTC 2015-03-25 03:33:10
/*
/* ************************************************************************** */
namespace Loli\DB;
use Traversable, Loli\Cache;
class SQLCursor extends Cursor{

	// 逻辑运算符
	private $_logicals = [
		'AND' => 'AND',
		'&&' => 'AND',

		'OR' => 'OR',
		'||' => 'OR',

		'XOR' => 'XOR',
	];



	// assignment
	private $_assignments = [
		'INC' => '+',
		'+' => '+',


		'DECR' => '-',
		'-' => '-',
	];


	// 比较运算符
	private $_compares = [


		'EQ' => '=',
		'=' => '=',
		'==' => '=',
		'===' => '===',

		'!=' => '!=',
		'<>' => '!=',
		'NE' => '!=',


		'GT' => '>',
		'>' => '>',


		'GTE' => '>=',
		'>=' => '>=',
		'=>' => '>=',


		'LT' => '<',
		'<' => '<',


		'LTE' => '<=',
		'<=' => '<=',
		'=<' => '<=',


		'LIKE' => 'LIKE',
	];


	private $_notCompares = [


		'EQ' => '!=',
		'=' => '!=',
		'==' => '!=',
		'===' => '!=',

		'!=' => '=',
		'<>' => '=',
		'NE' => '=',


		'GT' => '<=',
		'>' => '<=',


		'GTE' => '<',
		'>=' => '<',
		'=>' => '<',


		'LT' => '>=',
		'<' => '>=',


		'LTE' => '>',
		'<=' => '>',
		'=<' => '>',

	];

	private $_havings = ['SUM','MIN','MAX','AVG','COUNT'];


	private $_functions = [
		'SUM' => 'MIN',
		'MAX' => 'MAX',
		'AVG' => 'AVG',
		'COUNT' => 'COUNT',

		'FIRST' => 'FIRST',
		'LAST' => 'LAST',
	];

	private $_isCache = false;

	private $_useTables = [];

	private function _command($command, $ttl = 2) {
		if (!$this->execute) {
			return $command;
		}
		$results = $this->DB->command($command, false);
		$this->setSlave($ttl);
		return $results;
	}

	private function _table() {
		// 没有表
		if (!$this->tables) {
			throw new Exception('', 'Unselected table');
		}

		// 只能处理单个表
		if (count($this->tables) != 1) {
			throw new Exception($this->tables, 'Can only handle a single table');
		}
		$table = reset($this->tables);
		$table->keyValue = $this->DB->key($table->value, true);
		$this->data['users'] = [$table->value];
		return $table;
	}

	private function _ignore() {
		$ignore = '';
		foreach ($this->options as $option) {
			if ($option->name == 'ignore') {
				$ignore = $option->value;
			}
		}
		return $ignore ? 'IGNORE' : '';
	}

	private function _from($type) {
		if (isset($this->data[__FUNCTION__][$type])) {
			$this->data['uses'] = $this->data[__FUNCTION__][$type][1];
			return $this->data[__FUNCTION__][$type][0];
		}
		$this->data['uses'] = [];

		// 没有表
		if (!$this->tables) {
			throw new Exception('', 'Unselected table');
		}

		switch ($type) {
			case 'SELECT':
				$command = 'FORM :table';
				break;
			case 'UPDATE':
				$command = ':table';
				break;
			case 'DELETE':
				$command = ':using FORM :table';
				break;
			default:
				throw new Exception($type, 'Unknown table structure type');
		}



		$usings = $tables = [];
		foreach ($this->tables as $param) {
			if ($param->value instanceof SQLCursor) {
				$execute = $param->value->execute;
				$table = '(' . rtrim($param->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$param->value->execute($execute);
				$this->data['uses'] = array_merge($this->data['uses'], $param->value->data['uses']);
			} elseif ($param->expression) {
				$table = '('. $param->value .')';
			} else {
				$table = $this->DB->key($param->value, true);
				if ($param->using === false) {
					$usings[] = $table;
				}
				$this->data['uses'][] = $param->value;
			}
			$join = $tables ? (in_array($join = strtoupper($param->join), ['INNER', 'LEFT', 'RIGHT', 'FULL']) ? $join : 'INNER') : '';
			$on = '';
			if ($tables) {
				if ($param->on instanceof Param) {
					$on = $param->on->value;
				} elseif ($param->on) {
					$ons = [];
					foreach ((array) $param->on as $column1 => $column2) {
						$ons[] = $this->DB->key($column1, true) . ' = '. $this->DB->key($column2);
					}
					$on = implode(' AND ', $ons);
				} else {
					$on = '1 = 2';
				}
			}
			$tables[] = ['table' => $table, 'alias' => $param->alias ? $this->DB->key($param->alias, true) : false, 'join' => $join, 'on' => $on];
		}

		$arrays = [];
		foreach ($tables as $value) {
			$arrays[] = $value['table'];
			if ($value['alias']) {
				$arrays[] = 'AS ' . $value['alias'];
			}
			if ($value['join']) {
				$arrays[] = $value['join'] . ' JOIN';
			}
			if ($value['on']) {
				$arrays[] = 'ON '. $value['on'];
			}
		}
		$this->data[__FUNCTION__][$type][1] = $this->data['uses'];
		return $this->data[__FUNCTION__][$type][0] = strtr($command, [':using' => implode(',', $usings), ':table' => implode(' ', $arrays)]);
	}

	private function _field() {
		if (isset($this->data[__FUNCTION__])) {
			return $this->data[__FUNCTION__];
		}
		// 字段
		$fields = [];
		foreach ($this->fields as $field) {
			if (!$field->value) {
				continue;
			}
			$value = $field->expression ? $field->value : $this->DB->key($field->value);
			if (!$value) {
				continue;
			}
			$function = empty($field->function) ? '' : (empty($this->_functions[$function = strtoupper($field->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function);
			$alias = $field->alias ? $this->DB->key($field->alias) : '';
			if ($function) {
				$value = $function. '('.$value .')';
			}
			$fields[] = $alias ? $value . ' AS ' .$alias : $value;
		}
		if (!$fields) {
			$fields[] = '*';
		}
		return $this->data[__FUNCTION__] = implode(', ', $fields);
	}


	private function _limit() {
		if (isset($this->data[__FUNCTION__])) {
			return $this->data[__FUNCTION__];
		}
		$limit = 0;
		foreach ($this->options as $option) {
			if ($option->name == 'limit') {
				$limit = $option->value;
			}
		}
		return $this->data[__FUNCTION__] = ($limit = intval($limit)) ? 'LIMIT ' . $limit : '';
	}


	private function _rows() {
		if (isset($this->data[__FUNCTION__])) {
			return $this->data[__FUNCTION__];
		}
		$rows = '';
		if ($this->protocol == 'mysql') {
			foreach ($this->options as $option) {
				if ($option->name == 'rows') {
					$rows = $option->value;
				}
			}
			$rows = $rows ? 'SQL_CALC_FOUND_ROWS' : '';
		}
		return $this->data[__FUNCTION__] = $rows;
	}


	private function _offset() {
		if (isset($this->data[__FUNCTION__])) {
			return $this->data[__FUNCTION__];
		}
		$offset = 0;
		foreach ($this->options as $option) {
			if ($option->name == 'offset') {
				$offset = $option->value;
			}
		}
		return $this->data[__FUNCTION__] = ($offset = intval($offset)) ? 'LIMIT ' . $offset : '';
	}



	private function _order() {
		if (isset($this->data[__FUNCTION__])) {
			return $this->data[__FUNCTION__];
		}
		$orderby = [];
		foreach ($this->options as $option) {
			if ($option->name == 'order') {
				if (!$column = $this->DB->key($option->column)) {
					continue;
				}
				if ($option->value === NULL || $option->value === false) {
					unset($orderby[$column]);
				} else {
					$orderby[$column] = $option->order;
				}
			}
		}

		$arrays = [];
		foreach ($orderby as $column => $value) {
			if (!$value || $value < 0) {
				$value = 'ASC';
			} else {
				$value = 'DESC';
			}
			$arrays[] = $column . ' ' . $value;
		}
		return $this->data[__FUNCTION__] = $arrays ? 'ORDER BY ' . implode(', ', $arrays) : '';
	}

	private function _group($type) {
		if (isset($this->data[__FUNCTION__][$type])) {
			return $this->data[__FUNCTION__][$type];
		}
		// 分组
		$group = [];
		foreach ($this->options as $option) {
			if ($option->name == 'group' && $option->value) {
				$group[$option->name] = $option->value ? ($option->expression ? $option->value : $this->DB->key($option->value, true)) : NULL;
			}
		}
		$group = array_filter($group);
		if ($type == 'SELECT') {
			$group = $group ? 'GROUP BY ' . implode(', ', $group) : '';
		} else {
			$group = $group ? 'COUNT(DISTINCT ' . implode(',', $group) . ')' : 'COUNT(*)';
		}
		return $this->data[__FUNCTION__][$type] = $group;

	}


	private function _where() {
		if (isset($this->data[__FUNCTION__])) {
			$this->data['uses'] = array_merge($this->data['uses'], $this->data[__FUNCTION__][1]);
			return $this->data[__FUNCTION__][0];
		}
		$this->_useTables = [];
		$query = $this->_query($this->querys, false);
		$this->data['uses'] = array_merge($this->data['uses'], $this->data[__FUNCTION__][1] = $this->_useTables);
		return $this->data[__FUNCTION__][0] = $query ? 'WHERE ' . $query : '';
	}

	private function _having() {
		if (isset($this->data[__FUNCTION__])) {
			$this->data['uses'] = array_merge($this->data['uses'], $this->data[__FUNCTION__][1]);
			return $this->data[__FUNCTION__][0];
		}
		$this->_useTables = [];
		$query = $this->_query($this->querys, true);
		$this->data['uses'] = array_merge($this->data['uses'], $this->data[__FUNCTION__][1] = $this->_useTables);
		return $this->data[__FUNCTION__][0] = $query ? 'HAVING ' . $query : '';
	}


	private function _query(array $querys, $having = NULL, $logical = '') {
		// 逻辑 运算符
		if (!$logical) {
			$logical = 'AND';
			foreach ($this->options as $option) {
				if ($option->name == 'logical') {
					$logical = $option->value;
				}
			}
			if (empty($this->_logicals[$logical])) {
				throw new Exception($logical, 'Unknown logical');
			}
			$logical = $this->_logicals[$logical];
		}


		$commands = [];
		foreach ($querys as $column => $query) {
			if (!$query instanceof Param) {
				$query = new Param(['column' => $column, 'value' => $query]);
			}

			// 添加索引
			if (!empty($this->indexs[$query->column])) {
				foreach ($this->indexs[$query->column] as $key => $value) {
					$query->$key || ($query->$key = $value);
				}
			}

			// 跳过 NULL 和空数组
			if ($query->value === NULL || (is_array($query->value) && !($query->value = array_unnull($query->value)))) {
				continue;
			}

			// 函数
			$function = empty($query->function) ? '' : (empty($this->_functions[$function = strtoupper($query->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function);

			// 只允许聚合函数
			if ($having && !$query->having && !in_array($function, $this->_havings)) {
				continue;
			}

			// 不允许聚合函数
			if ($having === false && ($query->having || ($query->having !== false && in_array($function, $this->_havings)))) {
				continue;
			}

			// 运算符 和 not
			$compare = strtoupper(trim($query->compare));

			if (substr($compare, 0, 4) == 'NOT ') {
				$compare  = trim(substr($compare, 4));
				$not = 'NOT';
			} else {
				$not = empty($query->not) ? '' : 'NOT';
			}
			$compare = empty($this->_compares[$compare]) ? ($compare ? $compare : '=') : $this->_compares[$compare];


			// 二进制
			$binary = empty($query->binary) ? '' : 'BINARY';


			// 绝对 = 二进制
			if ($compare == '===') {
				$compare = '=';
				$binary = 'BINARY';
			}


			// 回调类型特殊类型
			if ($compare == 'CALL') {
				if ($query->value instanceof SQLCursor) {
					$execute = $query->value->execute;
					$value = rtrim($query->value->execute(false)->select(), " \t\n\r\0\x0B;");
					$query->value->execute($execute);
				} elseif ($query->expression) {
					$value = $query->value;
				} else {
					$value = $this->_query((array)$query->value, NULL, $query->logical ? $query->logical : ($logical == 'OR' ? 'AND' : 'OR'));
				}
				$commands[] = $not . ' ('. $value .')';
				continue;
			}



			// 健名和使用函数
			if ($column instanceof SQLCursor) {
				$execute = $query->column->execute;
				$column = $function . '(' . rtrim($query->column->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$query->column->execute($execute);
				$this->_useTables = array_merge($this->_useTables, $query->column->data['uses']);
			} elseif ($column = $this->DB->key($query->column)) {
				$column = $function ? $function . '('. $column .')' : $column;
			} else {
				continue;
			}

			// 直接关联的
			if ($query->value instanceof SQLCursor) {
				$execute = $query->value->execute;
				$value = '(' . rtrim($query->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$query->value->execute($execute);
				$this->_useTables = array_merge($this->_useTables, $query->value->data['uses']);
				$commands[] = implode(' ', [$binary, $column, $not, ($compare == 'CALL' ? '' : $compare), $value]);
				continue;
			}


			// 直接执行的
			if ($query->expression) {
				$commands[] = implode(' ', [$binary, $column, $not, $compare, $value]);
				continue;
			}

			$arrays = [];
			switch ($compare) {
				case 'IN':
					// IN 运算符
					$value = array_map([$this->DB, 'value'], (array)$query->value);

					// 空 的返回 1 = 2
					if (!$value) {
						$commands = ['1 = 2'];
						break 2;
					}

					if (count($value = array_unique($value)) == 1) {
						$arrays[] = ['compare' => '=', 'value' => end($value)];
					} else {
						$arrays[] = ['compare' => 'IN', 'value' => $value];
					}
					break;
				case 'NULL':
					// NULL 查询
					$is = $not ? !$query->value : $query->value;
					$arrays[] = ['compare' => 'IS', 'value' => $is ? 'NULL' : 'NOT NULL', 'not' => ''];
					break;
				case 'BETWEEN':
					// BETWEEN
					$value = array_map([$this->DB, 'value'], (array) $query->value);
					// 都存在的
					if (isset($value[0]) && isset($value[1])) {
						if ($value[1] === $value[0]) {
							$arrays[] = ['compare' => '=', 'value' => $value[0]];
						} else {
							$arrays[] = ['compare' => $compare, 'value' => $value];
						}
					} elseif (isset($value[0])) {
						$arrays[] = ['compare' => '>=', 'value' => $value];
					} elseif (isset($value[1])) {
						$arrays[] = ['compare' => '<=', 'value' => $value];
					}
					break;
				case '~':
				case '~*':
				case 'REGEX':
				case 'REGEXP':
					if (preg_match('/^\/(.*)\/(\w*)$/is', $query->value, $matches)) {
						$value = strtr($matches[1], ['\/' => '/']);
						$binary = strpos($matches[2], 'i') === false ? 'BINARY' : $binary;
					} else {
						$value = $query->value;
					}
					$arrays[] = ['compare' => 'REGEXP', 'value' => $this->DB->value($value)];
					break;
				case 'TEXT':
				case 'MATCH':
				case 'AGAINST':
					$mode = empty($query->mode) ? '' : strtoupper($query->mode);
					$value = $this->DB->value(is_array($query->value) ? implode(' +', $query->value) : $query->value);
					if (!$function || $function != 'MATCH') {
						$column = 'MATCH('.$column.')';
					}
					$arrays[] = ['compare' => '', 'value' => 'AGAINST('.$value . ' ' . $mode .')'];
					break;
				case 'SEARCH':
					$search = $this->search($query->value);
					if ($search = $search->get()) {
						foreach ($search as $key => $values) {
							foreach ($values as $value) {
								$arrays[] = ['not' =>  $key == '-' ? 'NOT' : '', 'compare' => 'LIKE', 'value' => $this->DB->value('%' . addcslashes($value, '_%\\') . '%')];
							}
						}
					}
					break;
				default:
					if (empty($this->_compares[$compare])) {
						throw new Exception($compare, 'Unknown compare');
					}
					$arrays[] = ['compare' => $compare, 'value' => $this->DB->value($query->value)];
			}


			foreach ($arrays as $array) {
				$array += ['not'=> $not, 'binary'=> $binary, 'compare'=> $compare];
				switch ($array['compare']) {
					case 'IN':
						$commands[] =  implode(' ', [$array['binary'], $column, $array['not'], 'IN(' . implode(',', $array['value']). ')']);
						break;
					case 'BETWEEN':
						$commands[] =  implode(' ', [$array['binary'], $column, $array['not'], $array['compare'], $array['value'][0] . ' AND ' . $array['value'][1] ]);
						break;
					default;
						if ($array['not'] && !empty($this->_notCompares[$array['compare']])) {
							$array['not'] = '';
							$array['compare'] = $this->_notCompares[$array['compare']];
						}
						$commands[] =  implode(' ', [$array['binary'], $column, $array['not'], $array['compare'], $array['value']]);
				}
			}
		}

		$commands = array_filter(array_map('trim', $commands));
		return implode(' '. $logical .' ', $commands);
	}

	private function _union() {
		if (isset($this->data[__FUNCTION__])) {
			$this->data['uses'] = array_merge($this->data['uses'], $this->data[__FUNCTION__][1]);
			return $this->data[__FUNCTION__][0];
		}
		$useTables = [];
		// 联合表
		$unions = [];
		foreach ($this->unions as $union) {
			if (!$union->value) {
				continue;
			}
			if ($union->value instanceof Cursor) {
				$execute = $union->value->execute;
				$unions[] = 'UNION ' .($union->all ? '' : 'ALL '). rtrim($union->value->execute(false)->select(), " \t\n\r\0\x0B;");
				$union->value->execute($execute);
				$useTables = array_merge($useTables, $union->data['uses']);
				continue;
			}

			if ($union->expression) {
				$unions[] = 'UNION ' .($union->all ? '' : 'ALL ') . trim($union->value, " \t\n\r\0\x0B;");
				continue;
			}
			throw new Exception($union, 'Does not support this type of union');
		}
		$this->data[__FUNCTION__][1] = $useTables;
		return $this->data[__FUNCTION__][0] = implode(' ', $unions);
	}

	// 判断表是否存在
	public function exists() {
		// 储存语句
		$param = $this->_table()->keyValue;
		switch ($this->protocol) {
			case 'mysql':
				$table = $this->DB->value(addcslashes($param->value, '%_'));
				$command = 'SHOW TABLES LIKE :table;';;
				break;
			case 'sqlite':
				$table = $this->DB->value($param->value);
				$command = 'SELECT * FROM sqlite_master WHERE type=\'table\' AND name=:table;';
				break;
			default:
				throw new Exception('this.cursor.exists()', 'Does not support this protocol');
		}

		$command = strtr($command, [':table' => $table]);

		// 不执行的
		if (!$this->execute) {
			return $command;
		}

		// 执行
		$result = $this->DB->command($command, false);
		return $result ? true : false;
	}


	// 创建表
	public function create() {
		$table = $this->_table()->keyValue;
		$options = [];
		foreach ($this->options as $option) {
			$options[$option->name] = $option->name;
		}
		$command = '';
		$commandOptions = [
			'exists' => '',
			'value' => '',
			'engine' => '',
			'primary' => '',
			'unique' => '',
			'key' => '',
			'search' => '',
		];
		$commandValues = [
			'unsigned' => '',
			'value' => '',
			'null' => '',
			'increment' => '',
		];
		$searchs = $keys = $uniques = $primarys = $values = [];
		switch ($this->protocol) {
			case 'mysql':
				$command = 'CREATE TABLE :exists :table(:value) :engine DEFAULT CHARSET=utf8;';
				$commandOptions = [
					'exists' => 'IF NOT EXISTS',
					'primary' => 'PRIMARY KEY(:value)',
					'unique' => 'UNIQUE KEY :name(:value)',
					'key' => 'KEY :name(:value)',
					'search' => 'FULLTEXT KEY :value(:value)',
					'engine' => 'ENGINE=:value',
				];
				$commandValues = [
					'name' => '%s',
					'type' => '%s',
					'length' => '(%s)',
					'unsigned' => 'unsigned',
					'binary' => 'BINARY',
					'null' => 'NOT NULL',
					'value' => 'DEFAULT %s',
					'increment' => 'AUTO_INCREMENT',
					'charset' => 'CHARACTER SET %s',
				];
				break;
			default:
				throw new Exception('this.cursor.create()', 'Does not support this protocol');
		}




		foreach ($this->columns as $column) {
			$value = [];
			$name = $this->DB->key($column->name, true);

			switch ($this->protocol) {
				case 'mysql':
					$integerType = ['tinyint' => [1, 3, 4], 'smallint' => [2, 5, 6], 'mediumint' => [3, 8, 9], 'int' => [4, 10, 11], 'bigint' => [8, 20, 20]];
					$floatType = ['real', 'double', 'float', 'decimal'];
					$indexStringType = ['varchar', 'char'];
					$indexBinaryType = ['varbinary', 'binary'];
					$stringType = ['text' => 65535, 'mediumtext' => 16777215, 'longtext' => 0];
					$binaryType = ['blob' => 65535, 'mediumblob' => 16777215, 'logngblob' => 0];

					$isIntegerType = array_key_exists($column->type, $integerType);
					$isFloatType = in_array($column->type, $floatType);
					$isIndexStringType = in_array($column->type, $indexStringType);
					$isindexBinaryType = in_array($column->type, $indexBinaryType);
					$isStringType = array_key_exists($column->type, $stringType);
					$isBinaryType = array_key_exists($column->type, $binaryType);
					if (in_array($column->type, ['bit', 'date', 'time', 'year', 'datetime', 'timestamp'])) {
						// 其他类型
						$value['type'] = $column->type;
						if ($column->length && !in_array($column->type, ['date', 'year'])) {
							$value['length'] = intval($column->length);
						}
					} elseif ($column->type == 'bool') {
						// bool 类型
						$value['type'] = 'tinyint';
						$value['length'] = 4;
					} elseif ($isIntegerType) {
						// 整数类型
						$length = $column->length ? intval($column->length) : $integerType[$column->type][0];
						foreach ($integerType as $type => $args) {
							if ($args[0] == $length || $type == 'bigint') {
								$value['type'] = $type;
								$value['length'] = $column->unsigned ? $args[1] : $args[2];
								break;
							}
						}
					} elseif ($isFloatType) {
						// 浮点类型
						$value['type'] = $column->type;
						if ($column->length) {
							$length = is_array($column->length) ? array_slice($column->length , 0, 2): explode(',', $column->length, 2);
							$length = array_map('intval', $length);
							$value['length'] = implode(',', $length);
						}
					} elseif ($isIndexStringType || ($column->type == key($stringType) && ($column->length && $column->length <= 255) || isset($column->primary) || $column->unique || $column->key)) {
						// 能索引的字符串
						$isStringType = false;
						$isIndexStringType = true;
						$value['type'] = $column->type == key($stringType) ? reset($indexStringType) : $column->type;
						$value['length'] = $column->length ? intval($column->length) : 255;
					} elseif ($isindexBinaryType || ($column->type == reset($indexBinaryType) && ($column->length && $column->length <= 255) || isset($column->primary) || $column->unique || $column->key)) {
						// 能索引的二进制
						$isStringType = false;
						$isindexBinaryType = true;
						$value['type'] = $column->type == reset($indexBinaryType) ? reset($indexStringType) : $column->type;
						$value['length'] = $column->length ? intval($column->length) : 255;
					} elseif ($isStringType) {
						// 不能索引的字符串
						$value['type'] = $column->type;
						if ($column->length) {
							foreach ($stringType as $type => $length) {
								if ($column->length <= $length || !$length) {
									$value['type'] = $type;
									break;
								}
							}
						}
					} elseif ($isBinaryType) {
						// 不能索引的二进制
						$value['type'] = $column->type;
						if ($column->length) {
							foreach ($strType as $type => $length) {
								if ($column->length <= $length || !$length) {
									$value['type'] = $type;
									break;
								}
							}
						}
					} else {
						throw new Exception('this.cursor.create() :' . $column->type, 'Unknown data type');
					}
					if (empty($options['engine'])) {
						$options['engine'] = 'InnoDB';
					}
					break;
				default:
					throw new Exception('this.cursor.create()', 'Does not support this protocol');
			}









			// 无符号
			if ($isIntegerType || $isFloatType) {
				$value['unsigned'] = (bool) $column->unsigned;
			}

			// 编码
			if ($column->charset && ($isIndexStringType || $isStringType)) {
				$value['charset'] = $this->DB->value(preg_replace('/[^0-9a-z_]/i', '', $column->charset));
			}

			if ($isStringType) {
				// 全文
				if (empty($commandValues['search']) && isset($column->search) && $column->search !== false) {
					$searchs[$name] = $column->search;
				}
			} elseif (!$isBinaryType) {
				// 主键
				if (empty($commandValues['primary']) && isset($column->primary) && $column->primary !== false) {
					$primarys[$name] = $column->primary;
				}

				// 约束
				if (empty($commandValues['unique']) && $column->unique && is_array($column->unique)) {
					foreach ($column->unique as $k => $v) {
						$uniques[$k][$v][] = $name;
					}
				}

				// 索引
				if (empty($commandValues['key']) && $column->key && is_array($column->key)) {
					foreach ($column->key as $k => $v) {
						$keys[$k][$v][] = $name;
					}
				}
			}


			// 自动递增
			$value['increment'] = $column->increment && $isIntegerType;


			$defaultValues = [
				'year' => '0000',
				'date' => '0000-00-00',
				'datetime' => '0000-00-00 00:00:00',
				'timestamp' => NULL,
			];

			// 默认 value
			if ($value['increment']) {

			} elseif ($value['type'] == 'bool') {
				$value['value'] = (bool) $column->value;
			} elseif ($isIntegerType || $isFloatType) {
				$value['value'] = (int) $column->value;
			} elseif ($isStringType) {

			} elseif (!$column->value && array_key_exists($value['type'], $defaultValues)) {
				if ($defaultValues[$value['type']] !== NULL) {
					$value['value'] = $this->DB->value($defaultValues[$value['type']]);
				}
			} else {
				$value['value'] = $this->DB->value($column->value);
			}


			// 是否允许空
			$value['null'] = (bool) $column->null;

			// 插入
			$values[$name] = $value;
		}



		foreach ($values as $name => &$value) {
			$array = [];
			$value['name'] = $name;
			foreach ($commandValues as $k => $v) {
				if (!$v || !isset($value[$k])) {
					continue;
				}
				if ($k == 'null') {
					if (!$value['null']) {
						$array[] = $v;
					}
					continue;
				}
				if ($value[$k] || ($value[$k] !== false && in_array($k, ['primary', 'unique', 'key', 'search'])) || in_array($k, ['value'])) {
					$array[] = sprintf($v, $value[$k]);
				}
			}
			$value = implode(' ', $array);
		}
		unset($value);


		if ($primarys && empty($commandValues['primary']) && !empty($commandOptions['primary'])) {
			asort($primarys, SORT_NUMERIC);
			$values[] = strtr($commandOptions['primary'], [':value' => implode(',',  array_keys($primarys))]);
		}

		// 约束
		if ($uniques && empty($commandValues['unique']) && !empty($commandOptions['unique'])) {
			foreach ($uniques as $name => $unique) {
				ksort($unique, SORT_NUMERIC);
				$array = [];
				foreach ($unique as $v) {
					foreach ($v as $vv) {
						$array[] = $vv;
					}
				}
				$values[] = strtr($commandOptions['unique'], [':name' => $this->DB->key($name, true), ':value' => implode(',',  $array)]);
			}
		}

		// 索引
		if ($keys && empty($commandValues['key']) && !empty($commandOptions['key'])) {
			foreach ($keys as $name => $key) {
				ksort($key, SORT_NUMERIC);
				$array = [];
				foreach ($key as $v) {
					foreach ($v as $vv) {
						$array[] = $vv;
					}
				}
				$values[] = strtr($commandOptions['key'], [':name' => $this->DB->key($name, true), ':value' => implode(',',  $array)]);
			}
		}


		// 搜索
		if ($searchs && empty($commandValues['search']) && !empty($commandOptions['search'])) {
			asort($searchs, SORT_NUMERIC);
			foreach ($searchs as $search => $value) {
				$values[] = strtr($commandOptions['search'], [':value' => $search]);
			}
		}

		$values = implode(",\n", $values);


		$arrays = [];
		foreach ($commandOptions as $name => $value) {
			if ($value && !empty($options[$name]) && !in_array($name, ['primary', 'unique', 'key', 'search'])) {
				$arrays[':'.$name] = strtr($value, [':value' => $this->DB->value($options[$name])]);
			} else {
				$arrays[':'.$name] = '';
			}
		}
		$arrays[':table'] = $table;
		$arrays[':value'] = "\n{$values}\n";
		$command = strtr($command, $arrays);
		return $this->_command($command, 60);
	}

	// 清空表
	public function truncate() {
		$table = $this->_table()->keyValue;
		switch ($this->protocol) {
			case 'sqlite':
				$command = 'DELETE FROM :table;';
				break;
			default:
				$command = 'TRUNCATE TABLE :table;';
		}
		$command = strtr($command, [':table' => $table]);
		return $this->_command($command, 60);
	}


	// 删除表
	public function drop() {
		$table = $this->_table()->keyValue;
		switch ($this->protocol) {
			case 'mysql':
			case 'pgsql':
			case 'sqlite':
				$exists = 'IF EXISTS';
				$command = 'DROP TABLE :exists :table;';
				break;
			default:
				$exists = '';
				$command = 'DROP TABLE :table;';
		}

		$ifExists = false;
		foreach ($this->options as $option) {
			if ($option->name == 'exists') {
				$ifExists = $option->value;
			}
		}

		$command = strtr($command, [':table' => $table, ':exists' => $ifExists ? $exists : '']);
		return $this->_command($command, 60);
	}

	// 插入字段
	public function insert() {
		$table = $this->_table()->keyValue;

		$options = [];
		foreach ($this->options as $option) {
			$options[$option->name] = $option->name;
		}


		if ($this->values) {
			$this->documents[] = $this->values;
			$this->values = [];
		}


		$defaultDocument = [];
		$documents = [];
		foreach ($this->documents as $value) {
			$document = [];
			foreach ($value as $param) {
				$document[$param->name] = $param->value;
			}
			if (!$document) {
				throw new Exception($this->documents, 'Inserted rows can not be empty');
			}
			$defaultDocument += $document;
			$documents[] = $document;
		}
		if (!$documents) {
			throw new Exception($this->documents, 'Inserted rows can not be empty');
		}

		ksort($defaultDocument);
		$column = [];
		foreach ($defaultDocument as $key => &$value) {
			$value = NULL;
			$column[] = $this->DB->key($key, true);
		}
		$column = implode(',', $column);
		unset($value);

		foreach ($documents as &$document) {
			$document = array_map([$this->DB, 'value'], $document + $defaultDocument);
			ksort($document);
			print_r($document);
			$document = '('. implode(',', $document) . ')';
		}
		unset($document);
		$documents = implode(',', $documents);

		$commandOptions = [
		];


		switch ($this->protocol) {
			case 'mysql':
			case 'sqlite':
				$command = empty($options['replace']) ? 'INSERT INTO :table (:column) VALUES :document;' : 'REPLACE INTO :table (:column) VALUES :document;';
				break;
			default:
				$command = 'INSERT :ignore INTO :table (:column) VALUES :document;';
		}


		$command = strtr($command, [':ignore' => $this->_ignore(), ':table' => $table, ':column' => $column, ':document' => $documents]);
		return $this->_command($command);
	}

	// 更新字段
	public function update() {
		if ($this->values) {
			$this->documents[] = $this->values;
			$this->values = [];
		}

		if (!$this->documents) {
			throw new Exception($this->documents, 'Update can not be empty');
		}
		if (count($this->documents) > 1) {
			throw new Exception($this->documents, 'Can not update multiple');
		}

		$document = [];
		foreach (end($this->documents) as $param) {
			$name = $this->DB->key($param->name);
			if ($param->value instanceof SQLCursor) {
				$execute = $param->value->execute;
				$value = '(' . rtrim($param->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$param->value->execute($execute);
			} elseif ($param->expression) {
				$value = '('. $param->value .')';
			} else {
				$value = $this->DB->value($param->value, true);
			}

			$assignment = strtoupper($param->assignment);
			$assignment = empty($this->_assignments[$assignment]) ? $assignment : $this->_assignments[$assignment];

			// 字段 + 运算符 + 值
			if (in_array($assignment, ['+', '-', '*', '/'])) {
				$column = $param->column ? $this->DB->key($param->column) : $name;
				$document[$name] = $param->before ? $value  .' '. $assignment .' '. $column : $column  .' '. $assignment .' '. $value;
				continue;
			}

			// 替换
			if ($assignment == 'REPLACE') {
				$document[$name] =  'REPLACE('. $name .', '. $this->DB->value($param->search) .', '. $value .')';
				continue;
			}
			if (!in_array($assignment, ['', '='])) {
				throw new Exception($assignment, 'Unknown assignment');
			}

			$document[$name] = $value;
		}




		if (!$document) {
			throw new Exception($this->documents, 'Update can not be empty');
		}

		$values = [];
		foreach ($document as $name => $value) {
			$values[] = $name . ' = ' . $value;
		}
		$value = implode(', ', $values);


		$command = 'UPDATE :using :form SET :value :where :order :offset :limit';
		$command = strtr($command, [':ignore' => $this->_ignore(), ':using' => $this->_using(), ':form' => $this->_from('UPDATE'), ':value' => $value, ':where' => $this->_where(), ':order' => $this->_order(), ':limit' => $this->_limit()]);
		return $this->_command($command);
	}

	// 删除字段
	public function delete() {
		$command = 'DELETE :ignore :form :where :order :offset :limit';
		$command = strtr($command, [':ignore' => $this->_ignore(), ':using' => $this->_using(), ':form' => $this->_from('DELETE'), ':where' => $this->_where(), ':order' => $this->_order(), ':limit' => $this->_limit()]);
		return $this->_command($command);
	}

	// 读取表
	public function select() {
		//  缓存数据
		if ($this->execute && $this->_isCache && isset($this->data[__FUNCTION__])) {
			return $this->data[__FUNCTION__];
		}

		$options = [];
		foreach ($this->options as $option) {
			$options[$option->name] = $option->name;
		}

		// 文件锁
		$lock = '';
		if (!empty($options['lock'])) {
			switch (strtoupper($options['lock'])) {
				case 'UPDATE':
				case 'FOR UPDATE':
					$lock = 'FOR UPDATE';
					break;
				case 'SHARE':
				case 'SHARED':
				case 'LOCK IN SHARE MODE':
					$lock = 'LOCK IN SHARE MODE';
					break;
				default:
					throw new Exception($options['lock'], 'Unknown type of lock');
			}
		}

		$replaces = [':field' => $this->_field(), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':group' => $this->_group('SELECT'), ':having' => $this->_having(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit(), ':union' => $this->_union()];
		$command = 'SELECT :rows :field :form :where :group :having :order :offset :limit :lock :union;';
		$command = strtr($command, $replaces + [':lock' => $lock, ':rows' => $rows = $this->_rows()]);

		// 不需要执行的
		if (!$this->execute) {
			return $command;
		}

		if (!$lock && $this->cache[0] && $this->_isCache && (is_array($this->data[__FUNCTION__] = Cache::get($cacheKey = json_decode(['database' => $this->database, 'protocol' => $this->protocol] + $replaces), __CLASS__)) && ($this->cache[1] < 1 || $this->cache[0] == -1 || (Cache::ttl($cacheKey, __CLASS__) > $this->cache[1] || !Cache::add(true, 'TTL' . $cacheKey, __CLASS__, $this->cache[1] + 1))))) {
			// 用缓存的
		} else {
			// 不用缓存
			$this->data[__FUNCTION__] = $this->DB->command($command, $this->slave);
			$this->cache[0] && Cache::set($this->data[__FUNCTION__], json_decode(['database' => $this->database, 'protocol' => $this->protocol] + $replaces), __CLASS__, $this->cache[0]);
			if ($this->_isCache && $rows) {
				$this->_isCache = false;
				$this->count();
				$this->_isCache = true;
			}
		}
		return $this->data[__FUNCTION__];
	}


	public function count() {
		//  缓存数据
		if ($this->execute && $this->_isCache && isset($this->data[__FUNCTION__])) {
			return $this->data[__FUNCTION__];
		}

		if ($this->cache[0] || !$this->_rows()) {
			$replaces = [':group' => $this->_group('COUNT'), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':having' => $this->_having(), ':union' => $this->_union()];
		}
		// rows 的
		if ($this->_rows()) {
			$command = 'SELECT FOUND_ROWS()';
		} else {
			$command = 'SELECT :group :form :where :having :union;';
			$command = strtr($command, $replaces);
		}
		// 不需要执行的
		if (!$this->execute) {
			return $command;
		}

		if ($this->cache[0] && $this->_isCache && (($this->data[__FUNCTION__] = Cache::get(json_decode(['database' => $this->database, 'protocol' => $this->protocol] + $replaces), __CLASS__)) !== false && ($this->cache[1] < 1 || $this->cache[0] == -1 || (Cache::ttl($cacheKey, __CLASS__) > $this->cache[1] || (Cache::ttl($cacheKey, __CLASS__) > $this->cache[1] || !Cache::add(true, 'TTL' . $cacheKey, __CLASS__, $this->cache[1] + 1)))))) {
			// 用缓存的
		} else {
			if ($this->_isCache && $this->_rows()) {
				$this->_isCache = false;
				$this->select();
				$this->_isCache = true;
			}
			$this->data[__FUNCTION__] = 0;
			foreach ((array)$this->DB->command($command, $this->slave) as $row) {
				$this->data[__FUNCTION__] += array_sum((array) $row);
			}
			$this->cache[0] && Cache::set($this->data[__FUNCTION__], json_decode(['database' => $this->database, 'protocol' => $this->protocol] + $replaces), __CLASS__, $this->cache[0]);
		}

		return $this->data[__FUNCTION__];
	}


	public function deleteCacheSelect() {
		$this->cache[0] && Cache::delete(json_decode(['database' => $this->database, 'protocol' => $this->protocol, ':field' => $this->_field(), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':group' => $this->_group('SELECT'), ':having' => $this->_having(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit(), ':union' => $this->_union()]), __CLASS__);
		unset($this->data['select']);
		return $this;
	}

	public function deleteCacheCount() {
		$this->cache[0] && Cache::delete(json_decode(['database' => $this->database, 'protocol' => $this->protocol, ':group' => $this->_group('COUNT'), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':having' => $this->_having(), ':union' => $this->_union()]), __CLASS__);
		unset($this->data['count']);
		return $this;
	}
}