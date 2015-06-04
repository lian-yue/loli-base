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
/*	Updated: UTC 2015-05-23 08:19:01
/*
/* ************************************************************************** */
namespace Loli\DB\Builder;
use Loli\Cache;
class SQL extends Base{

	// 逻辑运算符
	private $_logicals = [
		'AND' => 'AND',
		'&&' => 'AND',

		'OR' => 'OR',
		'||' => 'OR',

		'XOR' => 'XOR',
	];



	// 计算运算符
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

	// 比较运算符 加上not 的
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

	// 聚合函数
	private $_havings = ['SUM','MIN','MAX','AVG','COUNT'];

	// 全部函数
	private $_functions = [
		'SUM' => 'MIN',
		'MAX' => 'MAX',
		'AVG' => 'AVG',
		'COUNT' => 'COUNT',

		'FIRST' => 'FIRST',
		'LAST' => 'LAST',
	];


	// 是否缓存
	private $_isCache = false;


	/**
	 * _command 执行命令
	 * @param  string                          $command     query
	 * @param  integer                         $ttl         表过期时间
	 * @return array|string|integer|boolean
	 */
	private function _command($command, $ttl = 2) {
		if (!$this->execute) {
			return $command;
		}
		$results = $this->DB->command($command, false);
		$this->setSlave($ttl);
		return $results;
	}

	/**
	 * _table 取得一个表的对象
	 * @return Param
	 */
	private function _table() {
		// 没有表
		if (!$this->tables) {
			throw new Exception('', 'Unselected table');
		}

		// 只能处理单个表
		if (count($this->tables) !== 1) {
			throw new Exception($this->tables, 'Can only handle a single table');
		}

		$table = reset($this->tables);
		$table->keyValue = $this->DB->key($table->value, true);

		// 使用的表
		$this->useTables = [$table->value];
		return $table;
	}

	/**
	 * _ignore 忽略参数
	 * @return string
	 */
	private function _ignore() {
		static $ignore;
		if (!isset($ignore)) {
			$ignore = '';
			foreach ($this->options as $option) {
				if ($option->name === 'ignore') {
					$ignore = $option->value;
				}
			}
			$ignore = $ignore ? 'IGNORE' : '';
		}
		return $ignore;
	}

	/**
	 * _from 取得表的  from
	 * @param  string $type  类型  SELECT or UPDATE  or DELETE
	 * @return string
	 */
	private function _from($type) {
		static $form, $using, $useTables;

		if (!isset($form)) {
			// 没有表
			if (!$this->tables) {
				throw new Exception('', 'Unselected table');
			}

			$useTables = $usings = $arrays = [];
			foreach ($this->tables as $param) {
				if ($param->value instanceof Cursor) {
					// 子查询表
					$execute = $param->value->execute;
					$name = '(' . rtrim($param->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
					$param->value->execute($execute);
					$useTables = array_merge($useTables, $param->value->getUseTables());
				} elseif ($param->expression) {
					// 直接连符
					$name = '('. $param->value .')';
				} else {
					// 表名
					$name = $this->DB->key($param->value, true);
					if ($param->using === false) {
						$usings[] = $name;
					}
					$useTables[] = $param->value;
				}

				// join
				$join = $arrays ? (in_array($join = strtoupper($param->join), ['INNER', 'LEFT', 'RIGHT', 'FULL']) ? $join : 'INNER') : '';


				$on = '';
				if ($arrays) {
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

				$arrays[] = ['name' => $name, 'alias' => $param->alias ? $this->DB->key($param->alias, true) : false, 'join' => $join, 'on' => $on];
			}

			$forms = [];
			foreach ($arrays as $array) {
				$forms[] = $array['table'];
				if ($array['alias']) {
					$forms[] = 'AS ' . $array['alias'];
				}
				if ($array['join']) {
					$forms[] = $array['join'] . ' JOIN';
				}
				if ($array['on']) {
					$forms[] = 'ON '. $array['on'];
				}
			}
			$form = implode(',', $forms);
			$using = implode(',', $usings);
		}



		switch ($type) {
			case 'SELECT':
				$command = 'FORM :form';
				break;
			case 'UPDATE':
				$command = ':form';
				break;
			case 'DELETE':
				$command = ':using FORM :form';
				break;
			default:
				throw new Exception($type, 'Unknown table structure type');
		}
		$this->useTables = $useTables;

		return strtr($command, [':using' => $using, ':form' => $form]);
	}




	/**
	 * _fields 选择的字段
	 * @return string
	 */
	private function _fields() {
		static $fields;
		if (empty($fields)) {
			$arrays = [];
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
				$arrays[] = $alias ? $value . ' AS ' .$alias : $value;
			}
			if (!$arrays) {
				$arrays[] = '*';
			}
			$fields = implode(', ', $arrays);
		}
		return $fields;
	}

	/**
	 * _limit 限制的数量
	 * @return string
	 */
	private function _limit() {
		static $limit;
		if (!isset($limit)) {
			$limit = 0;
			foreach ($this->options as $option) {
				if ($option->name === 'limit') {
					$limit = $option->value;
				}
			}
			$limit = ($limit = intval($limit)) ? 'LIMIT ' . $limit : '';
		}
		return $limit;
	}

	/**
	 * _rows 是否记录此次查询的统计  mysql 可用
	 * @return string
	 */
	private function _rows() {
		static $rows;
		if (!isset($rows)) {
			$rows = '';
			if ($this->DB->protocol() === 'mysql') {
				foreach ($this->options as $option) {
					if ($option->name === 'rows') {
						$rows = $option->value;
					}
				}
				$rows = $rows ? 'SQL_CALC_FOUND_ROWS' : '';
			}
		}
		return $rows;
	}

	/**
	 * _offset 偏移
	 * @return string
	 */
	private function _offset() {
		static $offset;
		if (!isset($offset)) {
			$offset = 0;
			foreach ($this->options as $option) {
				if ($option->name === 'offset') {
					$offset = $option->value;
				}
			}
			$offset = ($offset = intval($offset)) ? 'OFFSET ' . $offset : '';
		}
		return $offset;
	}


	/**
	 * _order 排序
	 * @return string
	 */
	private function _order() {
		static $order;
		if (!isset($order)) {
			$array = [];
			foreach ($this->options as $option) {
				if ($option->name === 'order') {
					if (!$column = $this->DB->key($option->column)) {
						continue;
					}
					if ($option->value === NULL || $option->value === false) {
						unset($array[$column]);
					} else {
						$array[$column] = $option->order;
					}
				}
			}

			foreach ($array as $column => &$value) {
				if ((is_string($value) && strtoupper($value) === 'DESC') || $value < 0) {
					$value = 'DESC';
				} else {
					$value = 'ASC';
				}
				$value = $column . ' ' . $value;
			}
			$order = $array ? 'ORDER BY ' . implode(', ', $array) : '';
		}
		return $order;
	}

	/**
	 * _group  分组
	 * @param  $type  SELECT or COUNT
	 * @return string
	 */
	private function _group($type) {
		static $group;
		if (!isset($group)) {
			$array = [];
			foreach ($this->options as $option) {
				if ($option->name === 'group' && $option->value) {
					$array[$option->value] = $option->value ? ($option->expression ? $option->value : $this->DB->key($option->value, true)) : NULL;
				}
			}
			$group = implode(', ', array_filter($array));
		}
		return $type === 'SELECT' ? ($group ? 'GROUP BY ' . $group : '') : ($group ? 'COUNT(DISTINCT ' . $group . ')' : 'COUNT(*)');
	}


	/**
	 * _where 查询
	 * @return string
	 */
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

	/**
	 * _having 聚合
	 * @return string
	 */
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

	/**
	 * _query  查询
	 * @param  array        $querys
	 * @param  boolean|null $having  是否是聚合 null 允许全部
	 * @param  string       $logical 链接运算符
	 * @return array
	 */
	private function _query(array $querys, $having = NULL, $logical = '') {
		// 逻辑 运算符
		if (!$logical) {
			$logical = 'AND';
			foreach ($this->options as $option) {
				if ($option->name === 'logical') {
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

			if (substr($compare, 0, 4) === 'NOT ') {
				$compare  = trim(substr($compare, 4));
				$not = 'NOT';
			} else {
				$not = empty($query->not) ? '' : 'NOT';
			}
			$compare = empty($this->_compares[$compare]) ? ($compare ? $compare : '=') : $this->_compares[$compare];


			// 二进制
			$binary = empty($query->binary) ? '' : 'BINARY';


			// 绝对 = 二进制
			if ($compare === '===') {
				$compare = '=';
				$binary = 'BINARY';
			}


			// 回调类型特殊类型
			if ($compare === 'CALL') {
				if ($query->value instanceof SQLCursor) {
					$execute = $query->value->execute;
					$value = rtrim($query->value->execute(false)->select(), " \t\n\r\0\x0B;");
					$query->value->execute($execute);
				} elseif ($query->expression) {
					$value = $query->value;
				} else {
					$value = $this->_query((array)$query->value, NULL, $query->logical ? $query->logical : ($logical === 'OR' ? 'AND' : 'OR'));
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
				$commands[] = implode(' ', [$binary, $column, $not, ($compare === 'CALL' ? '' : $compare), $value]);
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

					if (count($value = array_unique($value)) === 1) {
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
					if (!$function || $function !== 'MATCH') {
						$column = 'MATCH('.$column.')';
					}
					$arrays[] = ['compare' => '', 'value' => 'AGAINST('.$value . ' ' . $mode .')'];
					break;
				case 'SEARCH':
					$search = $this->search($query->value);
					if ($search = $search->get()) {
						foreach ($search as $key => $values) {
							foreach ($values as $value) {
								$arrays[] = ['not' =>  $key === '-' ? 'NOT' : '', 'compare' => 'LIKE', 'value' => $this->DB->value('%' . addcslashes($value, '_%\\') . '%')];
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




	/**
	 * _union 链接
	 * @return string
	 */
	private function _union() {
		static $union, $useTables;
		if (!isset($union)) {
			$array = $useTables = [];
			foreach ($this->unions as $param) {
				if (!$param->value) {
					continue;
				}
				if ($param->value instanceof Cursor) {
					$execute = $param->value->execute;
					$array[] = 'UNION ' .($param->all ? '' : 'ALL '). rtrim($param->value->execute(false)->select(), " \t\n\r\0\x0B;");
					$param->value->execute($execute);
					$useTables = array_merge($useTables, $param->value->getUseTables());
					continue;
				}

				if ($param->expression) {
					$array[] = 'UNION ' .($param->all ? '' : 'ALL ') . trim($param->value, " \t\n\r\0\x0B;");
					continue;
				}
				throw new Exception($param, 'Does not support this type of union');
			}
			$union = implode(' ', $array);
		}

		$this->useTables = array_merge($this->useTables, $useTables);
		return $union;
	}









	public function exists() {
		// 储存语句
		$param = $this->_table()->keyValue;
		switch ($this->DB->protocol()) {
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
		switch ($this->DB->protocol()) {
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

			switch ($this->DB->protocol()) {
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
					} elseif ($column->type === 'bool') {
						// bool 类型
						$value['type'] = 'tinyint';
						$value['length'] = 4;
					} elseif ($isIntegerType) {
						// 整数类型
						$length = $column->length ? intval($column->length) : $integerType[$column->type][0];
						foreach ($integerType as $type => $args) {
							if ($args[0] == $length || $type === 'bigint') {
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
					} elseif ($isIndexStringType || ($column->type === key($stringType) && ($column->length && $column->length <= 255) || isset($column->primary) || $column->unique || $column->key)) {
						// 能索引的字符串
						$isStringType = false;
						$isIndexStringType = true;
						$value['type'] = $column->type === key($stringType) ? reset($indexStringType) : $column->type;
						$value['length'] = $column->length ? intval($column->length) : 255;
					} elseif ($isindexBinaryType || ($column->type === reset($indexBinaryType) && ($column->length && $column->length <= 255) || isset($column->primary) || $column->unique || $column->key)) {
						// 能索引的二进制
						$isStringType = false;
						$isindexBinaryType = true;
						$value['type'] = $column->type === reset($indexBinaryType) ? reset($indexStringType) : $column->type;
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

			} elseif ($value['type'] === 'bool') {
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
				if ($k === 'null') {
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




	public function truncate() {
		$table = $this->_table()->keyValue;
		switch ($this->DB->protocol()) {
			case 'sqlite':
				$command = 'DELETE FROM :table;';
				break;
			default:
				$command = 'TRUNCATE TABLE :table;';
		}
		$command = strtr($command, [':table' => $table]);
		return $this->_command($command, 60);
	}




	public function drop() {
		$table = $this->_table()->keyValue;
		switch ($this->DB->protocol()) {
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
			if ($option->name === 'exists') {
				$ifExists = $option->value;
			}
		}

		$command = strtr($command, [':table' => $table, ':exists' => $ifExists ? $exists : '']);
		return $this->_command($command, 60);
	}



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


		switch ($this->DB->protocol()) {
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
			if ($assignment === 'REPLACE') {
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


	public function delete() {
		$command = 'DELETE :ignore :form :where :order :offset :limit';
		$command = strtr($command, [':ignore' => $this->_ignore(), ':using' => $this->_using(), ':form' => $this->_from('DELETE'), ':where' => $this->_where(), ':order' => $this->_order(), ':limit' => $this->_limit()]);
		return $this->_command($command);
	}


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

		$replaces = [':field' => $this->_fields(), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':group' => $this->_group('SELECT'), ':having' => $this->_having(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit(), ':union' => $this->_union()];
		$command = 'SELECT :rows :field :form :where :group :having :order :offset :limit :lock :union;';
		$command = strtr($command, $replaces + [':lock' => $lock, ':rows' => $rows = $this->_rows()]);

		// 不需要执行的
		if (!$this->execute) {
			return $command;
		}

		if (!$lock && $this->cache[0] && $this->_isCache && (is_array($results = Cache::get($cacheKey = json_decode(['database' => $this->DB->database(), 'protocol' => $this->DB->protocol(), 'cache' => $this->cache] + $replaces), __CLASS__)) && ($this->cache[1] < 1 || $this->cache[0] === -1 || (Cache::ttl($cacheKey, __CLASS__) > $this->cache[1] || !Cache::add(true, 'TTL' . $cacheKey, __CLASS__, $this->cache[1] + 1))))) {
			// 用缓存的
		} else {
			// 不用缓存
			$results = $this->DB->command($command, $this->slave);
			$this->cache[0] && Cache::set($results, json_decode(['database' => $this->DB->database(), 'protocol' => $this->DB->protocol(), 'cache' => $this->cache] + $replaces), __CLASS__, $this->cache[0]);
			if ($this->_isCache && $rows) {
				$this->_isCache = false;
				$this->count();
				$this->_isCache = true;
			}
		}
		if ($this->callback) {
			$rows = [];
			foreach ($results as $result) {
				$row = clone $result;
				$rows[] = call_user_func($this->callback, $row);
			}
			$results = $rows;
		}
		return $this->data[__FUNCTION__] = $results;
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

		if ($this->cache[0] && $this->_isCache && (($this->data[__FUNCTION__] = Cache::get(json_decode(['database' => $this->DB->database(), 'protocol' => $this->DB->protocol(), 'cache' => $this->cache] + $replaces), __CLASS__)) !== false && ($this->cache[1] < 1 || $this->cache[0] === -1 || (Cache::ttl($cacheKey, __CLASS__) > $this->cache[1] || (Cache::ttl($cacheKey, __CLASS__) > $this->cache[1] || !Cache::add(true, 'TTL' . $cacheKey, __CLASS__, $this->cache[1] + 1)))))) {
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
			$this->cache[0] && Cache::set($this->data[__FUNCTION__], json_decode(['database' => $this->DB->database(), 'protocol' => $this->DB->protocol(), 'cache' => $this->cache] + $replaces), __CLASS__, $this->cache[0]);
		}

		return $this->data[__FUNCTION__];
	}


	public function deleteCacheSelect($refresh = NULL) {
		$this->cache[0] && Cache::delete(json_decode(['database' => $this->DB->database(), 'protocol' => $this->DB->protocol(), 'cache' => $this->cache, ':field' => $this->_fields(), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':group' => $this->_group('SELECT'), ':having' => $this->_having(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit(), ':union' => $this->_union()]), __CLASS__, $refresh === NULL ? $this->cache[1] : $refresh);
		unset($this->data['select']);
		return $this;
	}

	public function deleteCacheCount($refresh = NULL) {
		$this->cache[0] && Cache::delete(json_decode(['database' => $this->DB->database(), 'protocol' => $this->DB->protocol(), 'cache' => $this->cache, ':group' => $this->_group('COUNT'), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':having' => $this->_having(), ':union' => $this->_union()]), __CLASS__, $refresh === NULL ? $this->cache[1] : $refresh);
		unset($this->data['count']);
		return $this;
	}

}