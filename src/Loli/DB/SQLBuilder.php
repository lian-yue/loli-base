<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
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
/*	Created: UTC 2015-05-21 06:25:27
/*	Updated: UTC 2015-05-23 08:18:19
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\Cache;
class_exists('Loli\DB\Builder') || exit;
class SQLBuilder extends Builder{


	// 逻辑运算符
	private static $_logicals = [
		'AND' => 'AND',
		'&&' => 'AND',

		'OR' => 'OR',
		'||' => 'OR',

		'XOR' => 'XOR',
	];



	// 计算运算符
	private static $_assignments = [
		'INC' => '+',
		'+' => '+',


		'DECR' => '-',
		'-' => '-',
	];


	// 比较运算符
	private static $_compares = [


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


	];

	// 比较运算符 加上not 的
	private static $_notCompares = [


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
	private static $_havings = ['SUM','MIN','MAX','AVG','COUNT'];


	// 全部函数
	private static $_functions = [
		'SUM' => 'MIN',
		'MAX' => 'MAX',
		'AVG' => 'AVG',
		'COUNT' => 'COUNT',

		'FIRST' => 'FIRST',
		'LAST' => 'LAST',
	];



	private static $_typeFunctions = [
		'integer' => 'intval',
		'boolean' => 'boolval',
		'float' => 'floatval',
		'date' => 'strval',
		'string' => 'strval',
		'json' => 'to_array',
	];

	// 全部函数
	private static $_types = [
		// bool
		'boolean' => ['bool' => true],

		//  长度, 有符号, 无符号
		'integer' => [
			'tinyint' => ['length' => 1, 'unsigned' => 3, 'signed' => 4],
			'smallint' => ['length' => 2, 'unsigned' => 5, 'signed' => 6],
			'mediumint' => ['length' => 3, 'unsigned' => 8, 'signed' => 9],
			'int' => ['length' => 4, 'unsigned' => 10, 'signed' => 11],
			'bigint' => ['length' => 8, 'unsigned' => 20, 'signed' => 20],
		],

		// 直接是值
		'float' => [
			'float' => [],
			'real' => [],
			'double' => [],
			'decimal' => [],
		],

		// date 默认值
		'date' => [
			'datetime' => '0000-00-00 00:00:00',
			'year' => '0000',
			'date' => '0000-00-00',
			'time' => '00:00:00',
			'timestamp' => NULL
		],

		// 索引, 二进制数据, 默认, 最大, 最小
		'string' => [
			'varchar' => ['index' => true, 'binary' => false, 'length' => 255, 'max' => 4096, 'min' => 0],
			'text' => ['index' => false, 'binary' => false, 'length' => 0, 'max' => 65535, 'min' => 255],
			'mediumtext' => ['index' => false, 'binary' => false, 'length' => 0, 'max' => 16777215, 'min' => 65535],
			'longtext' => ['index' => false, 'binary' => false, 'length' => 0, 'max' => 0, 'min' => 0],
			'char' => ['index' => true, 'binary' => false, 'length' => 255, 'max' => 4096, 'min' => 0],

			'varbinary' => ['index' => true, 'binary' => true, 'length' => 255, 'max' => 4096, 'min' => 0],
			'blob' => ['index' => false, 'binary' => true, 'length' => 0, 'max' => 65535, 'min' => 255],
			'mediumblob' => ['index' => false, 'binary' => true, 'length' => 0, 'max' => 16777215, 'min' => 65535],
			'logngblob' => ['index' => false, 'binary' => true, 'length' => 0, 'max' => 0, 'min' => 0],
			'binary' => ['index' => true, 'binary' => true, 'length' => 255, 'max' => 4096, 'min' => 0]
		],

		'json' => ['json' => [], 'array' => []],
	];


	// 是否缓存
	private $_isCache = true;


	private $_data = [];

	private function _columnsType() {
		if (!isset($this->_data['columnsType'])) {
			$columnsType = [];
			foreach ($this->columns as $name => $column) {
				if ($column instanceof Param) {
					$name = $column->name;
					$type = $column->type;
				} else {
					if (isset($column['name'])) {
						$name = $column['name'];
					}
					$type = isset($column['type']) ? $column['type'] : '';
				}
				if (!$name) {
					continue;
				}

				if (!$type) {
					$columnsType[$name] = 'string';
					continue;
				}

				if (isset(self::$_types[$type])) {
					$columnsType[$name] = $type;
					continue;
				}

				$continue = false;
				foreach(self::$_types as $key => $value) {
					if (isset($value[$type])) {
						$columnsType[$name] = $key;
						$continue = true;
						break;
					}
				}
				if ($continue) {
					continue;
				}
				$columnsType[$name] = 'string';
			}
			$this->_data['columnsType'] = $columnsType;
		}
		return $this->_data['columnsType'];
	}

	/**
	 * _command 执行命令
	 * @param  string                          $command     query
	 * @param  integer                         $ttl         表过期时间
	 * @return mixed
	 */
	private function _command($command, $ttl = 2) {
		if (!$this->execute) {
			return $command;
		}
		$results = $this->DB->command($command, false);
		$this->setReadonly($ttl);
		return $results;
	}




	/**
	 * _table 取得一个表的对象
	 * @return Param
	 */
	private function _table() {
		if (!isset($this->_data['table'])) {
			$tables = (array) $this->tables;
			// 没有表
			if (!$tables) {
				throw new Exception('', 'Unselected table');
			}

			// 只能处理单个表
			$table = reset($tables);
			if ($table instanceof Param) {
				$table = $table->value;
			} elseif (is_array($table)) {
				$table = empty($table['value']) ? NULL : $table['value'];
			}
			$this->DB->key($table, true);
			$this->_data['table'] = $table;
			$this->_data['tableUseTables'] = [$this->_data['table']];
		}
		return $this->_data['table'];
	}







	/**
	 * _from 取得表的  from
	 * @param  string $type  类型  SELECT or UPDATE  or DELETE
	 * @return string
	 */
	private function _from($type) {
		if (!isset($this->_data['from'])) {
			// 没有表
			if (!$this->tables) {
				throw new Exception('', 'Unselected table');
			}
			$tables = $using = $useTables = $columnUse = $aliasUse = [];

			$this->_where();
			$this->_having();
			foreach (['whereColumns', 'havingColumns'] as $key) {
				foreach ($this->_data[$key] as $value) {
					if (($start = strpos($value, '.')) === false) {
						$columnUse[] = $value;
					} else {
						$aliasUse[] = substr($value, $start);
					}
				}
			}


			foreach ($this->tables as $alias => $table) {
				// 全部转义成 Param
				if ($table instanceof Param) {
				} elseif (is_array($table)) {
					$table = new Param(['value' => empty($table['value']) ? NULL : $table['value'], 'alias' => isset($table['alias']) ? $table['alias'] : ($alias && is_string($alias) ? $alias : NULL), 'join' => empty($table['join']) ? NULL : $table['join'], 'on' => empty($table['on']) ? NULL : $table['on']]);
				} else {
					$table = new Param(['value' => $table]);
				}

				// 没有 value 的
				if (!$table->value) {
					continue;
				}

				// 不需要该表的
				if ($tables && !($table->alias ? in_array($table->alias, $aliasUse, true) : ($table->expression || in_array($table->value, $aliasUse, true))) && (is_array($table->column) && !array_intersect($table->column, $columnUse))) {
					continue;
				}



				if ($table->value instanceof Cursor) {
					// 子查询表
					$execute = $table->value->execute;
					$value = '(' . rtrim($table->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
					$table->value->execute($execute);
					$useTables = array_merge($useTables, $table->value->getUseTables());
				} elseif ($table->expression) {
					// 直接连符
					$value = '('. rtrim($table->value, " \t\n\r\0\x0B;") .')';
				} else {
					// 表名
					$value = $this->DB->key($table->value, true);
					if ($table->using === false) {
						$using[] = $value;
					}
					$useTables[] = $table->value;
				}


				$join = $tables ? (in_array($join = strtoupper($table->join), ['INNER', 'LEFT', 'RIGHT', 'FULL'], true) ? $join : 'INNER') : '';

				$on = '';
				if ($tables) {
					if ($table->on instanceof Param) {
						$on = $table->on->value;
					} elseif ($table->on) {
						$ons = [];
						foreach ((array) $table->on as $column1 => $column2) {
							$ons[] = $this->DB->key($column1, true) . ' = '. $this->DB->key($column2, true);
						}
						$on = implode(' AND ', $ons);
					} else {
						$on = '1 = 2';
					}
				}
				$tables[] = ['value' => $value, 'alias' => $table->alias ? $this->DB->key($table->alias, true) : false, 'join' => $join, 'on' => $on];
			}

			$from = [];
			foreach ($tables as $table) {
				$from[] = $table['value'];
				if ($table['alias']) {
					$from[] = 'AS ' . $table['alias'];
				}
				if ($table['join']) {
					$from[] = $table['join'] . ' JOIN';
				}
				if ($table['on']) {
					$from[] = 'ON '. $table['on'];
				}
			}
			$this->_data['from'] = implode(',', $from);
			$this->_data['fromUsing'] = implode(',', $using);
			$this->_data['fromUseTables'] = $useTables;
		}

		switch ($type) {
			case 'SELECT':
				$command = 'FROM :from';
				break;
			case 'UPDATE':
				$command = ':from';
				break;
			case 'DELETE':
				$command = ':using FROM :from';
				break;
			default:
				throw new Exception($type, 'Unknown table structure type');
		}

		return strtr($command, [':using' => $this->_data['fromUsing'], ':from' => $this->_data['from']]);
	}




	/**
	 * _fields 选择的字段
	 * @return string
	 */
	private function _fields() {
		if (!isset($this->_data['fields'])) {
			$fields = [];
			foreach ($this->fields as $alias => $field) {
				if ($field instanceof Param) {
					$expression = $field->expression;
					$function = $field->function;
					$alias = $field->alias;
					$value = $field->value;
				} else {
					$expression = $function = false;
					if (!$alias || !is_string($alias)) {
						$alias = false;
					}
					$value = $field;
				}

				if (!$value) {
					continue;
				}

				if ($value instanceof Cursor) {
					$execute = $value->execute;
					$field = rtrim($value->execute(false)->select(), " \t\n\r\0\x0B;");
					$value->value->execute($execute);
				} elseif ($expression) {
					$field = $field->value;
				} elseif (!is_string($value) || !($field = $this->DB->key($value))) {
					continue;
				}

				if ($alias) {
					$alias = $this->DB->key($alias);
				}

				if ($function) {
					$function = empty(self::$_functions[$function = strtoupper($function)]) ? false : preg_replace('/[^A-Z]/', '', $function);
					if ($function) {
						$field = $function. '('.$value .')';
					}
				}
				$fields[] = $alias ? $field . ' AS ' . $alias : $field;
			}
			if (!$fields) {
				$fields[] = '*';
			}
			$this->_data['fields'] = implode(', ', $fields);
		}
		return $this->_data['fields'];
	}


	private function _options($optionName = false) {
		if (!isset($this->_data['options'])) {
			$this->_data['options'] = [];
			foreach ($this->options as $name => $option) {
				if (!$option instanceof Param) {
					$option = new Param(['name' => $name, 'value' => $option]);
				}
				$this->_data['options'][$option->name][] = $option;
			}
		}
		return $optionName === false ? $this->_data['options'] : (isset($this->_data['options'][$optionName]) ? $this->_data['options'][$optionName] : false);
	}




	/**
	 * _ignore 忽略参数
	 * @return string
	 */
	private function _ignore() {
		if (!isset($this->_data['ignore'])) {
			$this->_data['ignore'] = ($option = $this->_options('ignore')) && end($option)->value ? 'IGNORE' : '';
		}
		return $this->_data['ignore'];
	}


	/**
	 * _rows 是否记录此次查询的统计  mysql 可用
	 * @return string
	 */
	private function _rows() {
		if (!isset($this->_data['rows'])) {
			switch ($this->DB->protocol()) {
				case 'mysql':
					$rows = ($option = $this->_options('rows')) && end($option)->value ? 'SQL_CALC_FOUND_ROWS' : '';
					break;
				default:
					$rows = '';
			}
			$this->_data['rows'] = $rows;
		}
		return $this->_data['rows'];
	}




	private function _lock() {
		if (!isset($this->_data['lock'])) {
			$lock = ($option = $this->_options('lock')) ? end($option)->value : '';
			if ($lock) {
				switch (strtoupper($lock)) {
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
						throw new Exception($lock, 'Unknown type of lock');
				}
			}
			$this->_data['lock'] = $lock;
		}
		return $this->_data['lock'];
	}


	/**
	 * _limit 限制的数量
	 * @return string
	 */
	private function _limit() {
		if (!isset($this->_data['limit'])) {
			if ($option = $this->_options('limit')) {
				$limit = abs((int) end($option)->value);
				$limit = $limit ? 'LIMIT '. $limit : '';
			} else {
				$limit = '';
			}
			$this->_data['limit'] = $limit;
		}
		return $this->_data['limit'];
	}




	/**
	 * _offset 偏移
	 * @return string
	 */
	private function _offset() {
		if (!isset($this->_data['offset'])) {
			if ($option = $this->_options('offset')) {
				$offset = (int) end($option)->value;
				$offset = $offset ? 'OFFSET '. $offset : '';
			} else {
				$offset = '';
			}
			$this->_data['offset'] = $offset;
		}
		return $this->_data['offset'];
	}





	/**
	 * _order 排序
	 * @return string
	 */
	private function _order() {
		if (!isset($this->_data['order'])) {
			$order = [];
			if ($option = $this->_options('order')) {
				foreach ($option as $value) {
					if (!$value->column && is_array($value->value)) {
						foreach($value->value as $column => $value) {
							if ($column && ($column = $this->DB->key($column))) {
								$order[$column] = $value;
							}
						}
						continue;
					}


					if (!$value->column) {
						continue;
					}
					if ($value->expression) {
						$column = $value->column;
					} elseif (!is_string($value->column) || !($column = $this->DB->key($value->column))) {
						continue;
					}
					$function = $value->function ? (empty(self::$_functions[$function = strtoupper($value->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : false;
					$order[$function ? $function . '('. $column .')' : $column] = $value->value;
				}
				unset($value);
			}
			foreach ($order as $column => &$value) {
				if ($value === false || $value === NULL) {
					unset($order[$column]);
					continue;
				}
				if ((is_string($value) && strtoupper(trim($value)) === 'DESC') || $value < 0) {
					$value = 'DESC';
				} else {
					$value = 'ASC';
				}
				$value = $column . ' ' . $value;
			}
			$this->_data['order'] = $order ? 'ORDER BY ' . implode(', ', $order) : '';
		}
		return $this->_data['order'];
	}


	/**
	 * _group  分组
	 * @param  $type  SELECT or COUNT
	 * @return string
	 */
	private function _group($type) {
		if (!isset($this->_data['group'])) {
			$group = [];
			if ($option = $this->_options('order')) {
				foreach($option as $value) {
					if (!$value->value) {
						continue;
					}
					if ($value->expression) {
						$value = $value->value;
					} elseif (!is_string($value->value) || !($value = $this->DB->key($value->value))) {
						continue;
					}
					$function = $option->function ? (empty(self::$_functions[$function = strtoupper($option->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : false;
					if ($function) {
						$value = $function . '('.$value.')';
					}
					$group[$value] = $value;
				}
			}
			$this->_data['group']  = implode(', ', $group);
		}
		return $type === 'SELECT' ? ($this->_data['group']  ? 'GROUP BY ' . $this->_data['group']  : '') : ($this->_data['group']  ? 'COUNT(DISTINCT ' . $this->_data['group']  . ')' : 'COUNT(*)');
	}






	/**
	 * _where 查询
	 * @return string
	 */
	private function _where() {
		if (!isset($this->_data['where'])) {
			$whereColumns = $whereUseTables = [];
			$this->_data['where'] = $this->_query($this->querys, false, $whereUseTables, $whereColumns);
			if ($this->_data['where']) {
				$this->_data['where'] = 'WHERE ' . $this->_data['where'];
			}
			$this->_data['whereColumns'] = $whereColumns;
			$this->_data['whereUseTables'] = $whereUseTables;
		}
		return $this->_data['where'];
	}

	/**
	 * _having 聚合
	 * @return string
	 */
	private function _having() {
		if (!isset($this->_data['having'])) {
			$havingColumns = $havingUseTables = [];
			$this->_data['having'] = $this->_query($this->querys, true, $havingUseTables, $havingColumns);
			if ($this->_data['having']) {
				$this->_data['having'] = 'HAVING ' . $this->_data['having'];
			}
			$this->_data['havingColumns'] = $havingColumns;
			$this->_data['havingUseTables'] = $havingUseTables;
		}
		return $this->_data['having'];
	}




	/**
	 * _query  查询
	 * @param  array        $querys
	 * @param  boolean|null $having  是否是聚合 null 允许全部
	 * @param  string       $logical 链接运算符
	 * @return array
	 */
	private function _query(array $querys, $having = NULL, array &$useTables, array &$useColumns, $logical = '') {
		// 逻辑 运算符
		if (!$logical) {
			$logical = ($option = $this->_options('logical')) ? strtoupper(end($option)->value) : 'AND';
			if (empty(self::$_logicals[$logical])) {
				throw new Exception($logical, 'Unknown logical');
			}
			$logical = self::$_logicals[$logical];
		}

		$columnsType = $this->_columnsType();

		$commands = [];
		foreach ($querys as $column => &$query) {
			if (!$query instanceof Param) {
				$query = new Param(['column' => $column, 'value' => $query]);
			}

			// 添加索引
			if (!$query->_index && is_string($query->column) && !empty($this->indexs[$query->column])) {
				foreach ((is_array($this->indexs[$query->column]) || is_object($this->indexs[$query->column]) ? $this->indexs[$query->column] : ['compare' => $this->indexs[$query->column]]) as $key => $value) {
					if (!$query->$key) {
						$query->$key = $value;
					}
				}
			}
			$query->_index = true;

			// 跳过 NULL 和空数组
			if ($query->value === NULL || (is_array($query->value) && !($query->value = array_filter($query->value, function($value) { return $value === NULL; })))) {
				continue;
			}

			// 函数
			$function = $query->function ? (empty(self::$_functions[$function = strtoupper($query->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : '';


			// 只允许聚合函数
			if ($having === true && !$query->having && !in_array($function, self::$_havings, true)) {
				continue;
			}

			// 不允许聚合函数
			if ($having === false && ($query->having || ($query->having !== false && in_array($function, self::$_havings, true)))) {
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
			$compare = empty(self::$_compares[$compare]) ? ($compare ? $compare : '=') : self::$_compares[$compare];


			// 二进制
			$binary = empty($query->binary) ? '' : 'BINARY';

			// 绝对 = 二进制
			if ($compare === '===') {
				$compare = '=';
				$binary = 'BINARY';
			} elseif ($compare === '!==') {
				$compare = '!=';
				$binary = 'BINARY';
			}





			// 回调类型
			if ($compare === 'CALL') {
				if ($query->value instanceof Cursor) {
					$execute = $query->value->execute;
					$value = rtrim($query->value->execute(false)->select(), " \t\n\r\0\x0B;");
					$query->value->execute($execute);
					$useTables = array_merge($useTables, $query->value->getUseTables());
				} elseif ($query->expression) {
					$value = $query->value;
				} else {
					$value = $this->_query((array)$query->value, NULL, $useTables, $useColumns, $query->logical ? $query->logical : ($logical === 'OR' ? 'AND' : 'OR'));
				}
				$commands[] = $not . ' ('. $value .')';
				continue;
			}


			// 健名
			if ($query->column instanceof Cursor) {
				$execute = $query->column->execute;
				$column = $function . '(' . rtrim($query->column->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$query->column->execute($execute);
				$useTables = array_merge($useTables, $query->column->getUseTables());
			} elseif (is_string($query->column) && ($column = $this->DB->key($query->column))) {
				$useColumns[] = $query->column;
				$column = $function ? $function . '('. $column .')' : $column;
			} else {
				continue;
			}


			// 直接关联的
			if ($query->value instanceof Cursor) {
				$execute = $query->value->execute;
				$value = '(' . rtrim($query->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$query->value->execute($execute);
				$useTables = array_merge($useTables, $query->value->getUseTables());
				$commands[] = implode(' ', [$binary, $column, $not, $compare, $value]);
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
					$value = array_map([$this->DB, 'value'], !$function && isset($columnsType[$value->column]) ? array_map(self::$_typeFunctions[$columnsType[$value->column]], (array) $query->value) : (array) $query->value);
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
					$value = array_map([$this->DB, 'value'], !$function && isset($columnsType[$value->column]) ? array_map(self::$_typeFunctions[$columnsType[$value->column]], (array) $query->value) : (array) $query->value);
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
					if (preg_match('/^\/(.*)\/(\w*)$/is', (string) $query->value, $matches)) {
						$value = str_replace('\\/', '/', $matches[1]);
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
					$value = $this->DB->value(is_array($query->value) ? implode(' +', $query->value) : (string) $query->value);
					if (!$function || $function !== 'MATCH') {
						$column = 'MATCH('.$column.')';
					}
					$arrays[] = ['compare' => '', 'value' => 'AGAINST('.$value . ' ' . $mode .')'];
					break;
				case 'SEARCH':
					foreach ($this->search($query->value, false) as $key => $values) {
						foreach ($values as $value) {
							$arrays[] = ['not' =>  $key === '-' ? 'NOT' : '', 'compare' => 'LIKE', 'value' => $this->DB->value('%' . addcslashes($value, '_%\\') . '%')];
						}
					}
					break;
				case 'LIKE':
					$arrays[] = ['compare' => $compare, 'value' => $this->DB->value((string)$query->value)];
					break;
				default:
					if (empty(self::$_compares[$compare])) {
						throw new Exception($compare, 'Unknown compare');
					}
					$arrays[] = ['compare' => $compare, 'value' => $this->DB->value(!$function && isset($columnsType[$query->column]) ? call_user_func(self::$_typeFunctions[$columnsType[$query->column]], $query->value) : $query->value)];
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
						if ($array['not'] && !empty(self::$_notCompares[$array['compare']])) {
							$array['not'] = '';
							$array['compare'] = self::$_notCompares[$array['compare']];
						}
						$commands[] =  implode(' ', [$array['binary'], $column, $array['not'], $array['compare'], $array['value']]);
				}
			}
		}

		return implode(' '. $logical .' ', array_filter(array_map('trim', $commands)));
	}




	/**
	 * _union 链接
	 * @return string
	 */
	private function _union() {
		if (!isset($this->_data['union'])) {
			$unions = $useTables = [];
			foreach ($this->unions as $union) {
				if (!$union instanceof Param) {
					$all = $union->all;
					$expression = $union->expression;
					$value = $union->value;
				} else {
					$all = $expression = false;
					$value = $union;
				}


				if ($value instanceof Cursor) {
					$execute = $value->execute;
					$unions[] = 'UNION ' .($all ? '' : 'ALL '). rtrim($value->execute(false)->select(), " \t\n\r\0\x0B;");
					$value->execute($execute);
					$useTables = array_merge($useTables, $value->getUseTables());
					continue;
				}

				if ($expression) {
					$unions[] = 'UNION ' .($all ? '' : 'ALL ') . trim($value, " \t\n\r\0\x0B;");
					continue;
				}
				throw new Exception($union, 'Does not support this type of union');
			}
			$this->_data['union'] = implode(' ', $unions);
			$this->_data['unionsUseTables'] = $useTables;
		}
		return $this->_data['union'];
	}







	public function exists() {
		// 储存语句
		$table = $this->_table();
		switch ($this->DB->protocol()) {
			case 'mysql':
				$table = $this->DB->value(addcslashes($table, '%_'));
				$command = 'SHOW TABLES LIKE :table;';
				break;
			case 'sqlite':
				$table = $this->DB->value($table);
				$command = 'SELECT * FROM sqlite_master WHERE type=\'table\' AND name=:table;';
				break;
			default:
				throw new Exception('this.exists()', 'Does not support this protocol');
		}

		$command = strtr($command, [':table' => $table]);

		// 不执行的
		if (!$this->execute) {
			return $command;
		}

		// 执行
		$result = $this->DB->command($command, true);
		return $result ? true : false;
	}



	public function create() {
		if (empty($this->_data['create'])) {
			$table = $this->DB->key($this->_table(), true);

			$options = [];
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					$options[$option->name] = $option->name;
				} else {
					$options[$name] = $option;
				}
			}
			if (empty($options['engine'])) {
				$options['engine'] = 'InnoDB';
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
					$command = 'CREATE TABLE :exists :table(:value) :engine DEFAULT CHARSET=utf8mb4;';
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
						'null' => 'NOT NULL',
						'value' => 'DEFAULT %s',
						'increment' => 'AUTO_INCREMENT',
						'charset' => 'CHARACTER SET %s',
						'comment' => 'COMMENT %s',
					];
					break;
				default:
					throw new Exception('this.cursor.create()', 'Does not support this protocol');
			}

			foreach ($this->columns as $name => $column) {
				if (!$column instanceof Param) {
					$column = new Param(['name' => empty($column['name']) ? $name : $column['name']] + $column);
				}
				$name = $this->DB->key($column->name ? $column->name : $name, true);


				$value = [];
				foreach (self::$_types as $typeName => $types) {
					if ($typeName === $column->type || isset($types[$column->type])) {
						$value['type'] = $column->type;
						break;
					}
				}
				if (!$value) {
					$value['type'] = $column->type;
					$types = [];
					$typeName = '';
				}

				switch ($typeName) {
					case 'boolean':
						$value['type'] = 'tinyint';
						$value['length'] = 4;
						$value['value'] = $column->value ? 1 : 0;
						break;
					case 'integer':
						foreach ($types as $type => $args) {
							if (($args['length'] === $column->length || (!$column->length && $type === 'int')) || $type === 'bigint') {
								$value['type'] = $type;
								$value['length'] = $args[$column->unsigned ? 'unsigned' : 'signed'];
								break;
							}
						}
						$value['increment'] = (bool) $column->increment;
						if (!$value['increment']) {
							$value['value'] = $column->value ? (int) $column->value : 0;
						}
						$value['unsigned'] = (bool) $column->unsigned;
						break;
					case 'float':
						$value['type'] = $column->type;
						if ($column->length) {
							$value['length'] = implode(',', array_map('intval', is_array($column->length) ? array_slice($column->length , 0, 2) : explode(',', $column->length, 2)));
						}
						$value['value'] = $column->value ? (float) $column->value : 0.0;
						$value['unsigned'] = (bool) $column->unsigned;
						break;
					case 'date':
						if ($types[$value['type']] !== NULL) {
							$value['value'] = $this->DB->value($column->value ? (string) $column->value : $types[$value['type']]);
						}
						break;
					case 'string':
						$index = isset($column->primary) || isset($column->search) || !empty($column->unique) || !empty($column->key);
						if (isset($types[$value['type']]) && ($args = $types[$value['type']]) && $index === !empty($args['index']) && (!isset($args['binary']) || ((bool) $args['binary']) === ((bool) $column->binary)) && (empty($args['min']) || !$column->length || $column->length >= $args['min']) && (empty($args['max']) || !$column->length || $column->length <= $args['max'])) {
							if (!empty($args['index'])) {
								$value['length'] = $column->length ? (int) $column->length : (empty($args['length']) ? 255 : $args['length']);
							}
						} else {
							$length = $column->length ? (int) $column->length : 255;
							$value['type'] = NULL;
							foreach ($types as $type => $args) {
								if (isset($args['binary']) && ((bool) $args['binary']) !== ((bool) $column->binary)) {
									continue;
								}
								if (!empty($args['min']) && $length < $args['min']) {
									continue;
								}
								if (!empty($args['max']) && $length > $args['max']) {
									continue;
								}
								if ($index && empty($args['index'])) {
									continue;
								}
								if (!$index && !empty($args['index']) && $length > 255) {
									continue;
								}

								$value['type'] = $type;
								if (!empty($args['index'])) {
									$value['length'] = $length;
								}
								break;
							}
							if (empty($value['type'])) {
								throw new Exception('this.cursor.create() :' . $value['type'], 'Unknown data type');
							}
						}
						if (empty($commandValues['unique'])) {
							$value['binary'] = (bool) $column->binary;
						}

						if (!empty($args['index'])) {
							$value['value'] = $this->DB->value(isset($column->value) ? (string) $column->value : '');
						}

						// 编码
						if ($column->charset) {
							$value['charset'] = $this->DB->value(preg_replace('/[^0-9a-z_]/i', '', $column->charset));
						}
						break;
					case 'json':
						if (!$column->length || $column->length <= 65535) {
							$value['type'] = 'text';
						} else {
							$value['type'] = 'mediumtext';
						}
						break;
					default:
						if ($column->length) {
							$value['length'] = intval($column->length);
						}
						$value['value'] = $this->DB->value($column->value ? $column->value : '');
						$value['unsigned'] = (bool) $column->unsigned;
				}



				if ($typeName !== 'string' || $types[$value['type']]['index']) {
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

				// 全文
				if ($typeName === 'string' && empty($commandValues['search']) && isset($column->search) && $column->search !== false) {
					$searchs[$name] = $column->search;
				}

				// 是否允许空
				$value['null'] = (bool) $column->null;

				// 注释
				$value['comment'] = $column->comment ? $this->DB->value((string)$column->comment) : NULL;

				// 插入
				$values[$name] = $value;
			}


			foreach ($values as $name => &$value) {
				$array = [];
				$value['name'] = $name;
				foreach ($commandValues as $father => $mom) {
					if (!$mom || !isset($value[$father])) {
						continue;
					}
					if ($father === 'null') {
						if (!$value['null']) {
							$array[] = $mom;
						}
						continue;
					}
					if ($value[$father] || ($value[$father] !== false && in_array($father, ['primary', 'unique', 'key', 'search'], true)) || $father === 'value') {
						$array[] = sprintf($mom, $value[$father]);
					}
				}
				$value = implode(' ', $array);
			}
			unset($value);


			// 主键
			if ($primarys && empty($commandValues['primary']) && !empty($commandOptions['primary'])) {
				asort($primarys, SORT_NUMERIC);
				$values[] = strtr($commandOptions['primary'], [':value' => implode(',',  array_keys($primarys))]);
			}

			// 约束
			if ($uniques && empty($commandValues['unique']) && !empty($commandOptions['unique'])) {
				foreach ($uniques as $name => $unique) {
					ksort($unique, SORT_NUMERIC);
					$array = [];
					foreach ($unique as $columns) {
						foreach ($columns as $column) {
							$array[] = $column;
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
					foreach ($key as $columns) {
						foreach ($columns as $column) {
							$array[] = $column;
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
				if ($value && !empty($options[$name]) && !in_array($name, ['primary', 'unique', 'key', 'search'], true)) {
					$arrays[':'.$name] = strtr($value, [':value' => $this->DB->value($options[$name])]);
				} else {
					$arrays[':'.$name] = '';
				}
			}
			$arrays[':table'] = $table;
			$arrays[':value'] = "\n{$values}\n";
			$this->_data['create'] = strtr($command, $arrays);
		}
		return $this->_command($this->_data['create'], 60);
	}



	public function truncate() {
		$table = $this->DB->key($this->_table(), true);
		switch ($this->DB->protocol()) {
			case 'sqlite':
				$command = 'DELETE FROM :table;';
				break;
			default:
				$command = 'TRUNCATE TABLE :table;';
		}
		return $this->_command(strtr($command, [':table' => $table]), 60);
	}




	public function drop() {
		$table = $this->DB->key($this->_table(), true);
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

		if ($exists && (!($option = $this->_options('exists')) || !$option->value)) {
			$exists = '';
		}
		return $this->_command(strtr($command, [':table' => $table, ':exists' => $exists]), 60);
	}





	public function insert() {
		if (empty($this->_data['insert'])) {
			$table = $this->DB->key($this->_table(), true);

			$options = [];
			foreach ($this->_options() as $name => $option) {
				$options[$name] = end($option)->value;
			}
			$columnsType = $this->_columnsType();


			$defaultDocument = $documents = [];
			foreach ($this->documents as $values) {
				if ($values instanceof Param) {
					throw new Exception($this->documents, 'Documents can not be Param');
				}
				$document = [];
				foreach ($values as $name => &$value) {
					if (!$value instanceof Param) {
						if ($value !== NULL) {
							$document[$name] = $this->DB->value(isset($columnsType[$name]) ? call_user_func(self::$_typeFunctions[$columnsType[$name]], $value) : $value);
						}
						continue;
					}
					if ($value->value === NULL) {
						continue;
					}
					if ($value->value instanceof Param) {
						throw new Exception($this->documents, 'Value can not be Param');
					}

					if ($value->value instanceof Cursor) {
						$execute = $value->value->execute;
						$document[$value->name] = rtrim($value->value->execute(false)->select(), " \t\n\r\0\x0B;");
						$value->value->execute($execute);
					} elseif ($value->expression) {
						$document[$value->name] = $value->value;
					} else {
						$document[$value->name] = $this->DB->value(isset($columnsType[$value->name]) ? call_user_func(self::$_typeFunctions[$columnsType[$value->name]], $value->value) : $value->value);
					}
					$function = $value->function ? (empty(self::$_functions[$function = strtoupper($value->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : '';
					if ($function) {
						$document[$value->name] = $function . '('. $document[$value->name] .')';
					}
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
				$value = $this->DB->value(NULL);
				$column[] = $this->DB->key($key, true);
			}
			$column = implode(',', $column);
			unset($value);



			foreach ($documents as &$document) {
				$document += $defaultDocument;
				ksort($document);
				$document = '('. implode(',', $document) . ')';
			}
			unset($document);
			$documents = implode(',', $documents);

			switch ($this->DB->protocol()) {
				case 'mysql':
				case 'sqlite':
					$command = empty($options['replace']) ? 'INSERT INTO :table (:column) VALUES :document;' : 'REPLACE INTO :table (:column) VALUES :document;';
					break;
				default:
					$command = 'INSERT :ignore INTO :table (:column) VALUES :document;';
			}
			$this->_data['insert'] = strtr($command, [':ignore' => $this->_ignore(), ':table' => $table, ':column' => $column, ':document' => $documents]);
		}
		return $this->_command($this->_data['insert']);
	}





	public function update() {
		if (empty($this->_data['update'])) {
			if (!$this->documents) {
				throw new Exception($this->documents, 'Update can not be empty');
			}
			if (count($this->documents) > 1) {
				throw new Exception($this->documents, 'Can not update multiple');
			}

			$columnsType = $this->_columnsType();
			$document = [];
			foreach($this->documents[0] as $name => $value) {
				if (!$value instanceof Param) {
					if ($value !== NULL) {
						$document[$this->DB->key($name, true)] = $this->DB->value(isset($columnsType[$name]) ? call_user_func(self::$_typeFunctions[$columnsType[$name]], $value) : $value);
					}
					continue;
				}

				if ($value->value === NULL) {
					continue;
				}

				$name = $this->DB->key($value->name, true);

				$assignment = strtoupper($value->assignment);
				$assignment = empty(self::$_assignments[$assignment]) ? $assignment : self::$_assignments[$assignment];

				if ($value->value instanceof Cursor) {
					$execute = $value->value->execute;
					$data = '(' . rtrim($value->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
					$value->value->execute($execute);
				} elseif ($value->expression) {
					$data = '('. $value->value .')';
				} else {
					$data = $this->DB->value(in_array($assignment, ['', '='], true) && isset($columnsType[$value->name]) ? call_user_func(self::$_typeFunctions[$columnsType[$value->name]], $value->value) : $value->value);
				}

				// 字段 + 运算符 + 值
				if (in_array($assignment, ['+', '-', '*', '/'], true)) {
					$column = $value->column ? $this->DB->key($value->column, true) : $name;
					$document[$name] = $value->before ? $data  .' '. $assignment .' '. $column : $column  .' '. $assignment .' '. $data;
					continue;
				}

				// 替换
				if ($assignment === 'REPLACE') {
					$document[$name] =  'REPLACE('. $name .', '. $this->DB->value($value->search) .', '. $data .')';
					continue;
				}

				if (!in_array($assignment, ['', '='], true)) {
					throw new Exception($assignment, 'Unknown assignment');
				}
				$document[$name] = $data;
			}

			if (!$document) {
				throw new Exception($this->documents, 'Update can not be empty');
			}

			foreach ($document as $name => &$value) {
				$value = $name . ' = ' . $value;
			}
			unset($value);
			$value = implode(', ', $document);

			$command = 'UPDATE :form SET :value :where :order :offset :limit';
			$this->_data['update'] = strtr($command, [':ignore' => $this->_ignore(), ':form' => $this->_from('UPDATE'), ':value' => $value, ':where' => $this->_where(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit()]);
		}
		return $this->_command($this->_data['update']);
	}


	public function select() {
		// 读缓存数据
		if ($this->_isCache) {

		} elseif ($this->execute) {
			if (!$this->_lock() && isset($this->_data['select'])) {
				return $this->_data['select'];
			}
		} elseif (isset($this->_data['selectCommand'])) {
			return $this->_data['selectCommand'];
		}



		if (empty($this->_data['selectReplaces']) || empty($this->_data['selectCommand'])) {
			// 替换
			$this->_data['selectReplaces'] = [':field' => $this->_fields(), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':group' => $this->_group('SELECT'), ':having' => $this->_having(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit(), ':union' => $this->_union()];

			// 命令行
			$this->_data['selectCommand'] = strtr('SELECT :rows :field :form :where :group :having :order :offset :limit :lock :union;', $this->_data['selectReplaces'] + [':lock' => $this->_lock(), ':rows' => $this->_rows()]);
		}

		// 不需要执行的
		if (!$this->execute) {
			return $this->_data['selectCommand'];
		}

		if ($this->cache[0] && $this->_isCache && !$this->_lock() && (($this->_data['select'] = Cache::get($cacheKey = json_encode(['cache' => $this->cache] + $this->_data['selectReplaces']), get_class($this->cursor) . $this->DB->database())) !== false && ($this->cache[1] < 1 || $this->cache[0] === -1 || (Cache::ttl($cacheKey, get_class($this)) > $this->cache[1] || !Cache::add(true, 'TTL' . $cacheKey, get_class($this), $this->cache[1] + 1))))) {
			// 用缓存的
		} else {

			// 不用缓存

			// 读取数据
			$this->_data['select'] = $this->DB->command($this->_data['selectCommand'], $this->getReadonly());

			// 写入缓存
			$this->cache[0] && (count($this->_data['select']) || $this->cache[2]) && !$this->_lock() && Cache::set($this->_data['select'], json_encode(['cache' => $this->cache] + $this->_data['selectReplaces']), get_class($this->cursor) . $this->DB->database(), $this->cache[0]);

			// 需要去全部行数的
			if ($this->_isCache && $this->_rows()) {
				$this->_isCache = false;
				$this->count();
				$this->_isCache = true;
			}
		}
		return $this->_data['select'];
	}

	public function selectRow() {
		$this->_limit();
		if (empty($this->_data['limit'])) {
			$this->_data['limit'] = "LIMIT 1";
		}
		$select = $this->select();
		if ($select && !is_string($select)) {
			$select = reset($select);
		}
		unset($this->_data['limit']);
		return $select;
	}



	public function count() {

		// 读缓存数据
		if ($this->_isCache) {

		} elseif ($this->execute) {
			if (!$this->_lock() && isset($this->_data['count'])) {
				return $this->_data['count'];
			}
		} elseif (isset($this->_data['countCommand'])) {
			return $this->_data['countCommand'];
		}


		if ($this->cache[0] || !$this->_rows()) {
			$this->_data['countReplaces'] = [':group' => $this->_group('COUNT'), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':having' => $this->_having(), ':union' => $this->_union()];
		}

		// rows 的
		if ($this->_rows()) {
			$this->_data['countCommand'] = 'SELECT FOUND_ROWS()';
		} else {
			$this->_data['countCommand'] = strtr('SELECT :group :form :where :having :union;', $this->_data['countReplaces']);
		}

		// 不需要执行的
		if (!$this->execute) {
			return $this->_data['countCommand'];
		}

		if ($this->cache[0] && $this->_isCache && (($this->_data['count'] = Cache::get(json_encode(['cache' => $this->cache] + $this->_data['countReplaces']), get_class($this->cursor) . $this->DB->database())) !== false && ($this->cache[1] < 1 || $this->cache[0] === -1 || (Cache::ttl($cacheKey, get_class($this)) > $this->cache[1] || (Cache::ttl($cacheKey, get_class($this)) > $this->cache[1] || !Cache::add(true, 'TTL' . $cacheKey, get_class($this), $this->cache[1] + 1)))))) {
			// 用缓存的
		} else {
			// 需要读取数据的

			// rows 的
			if ($this->_isCache && $this->_rows()) {
				$this->_isCache = false;
				$this->select();
				$this->_isCache = true;
			}

			// 数量
			$this->_data['count'] = 0;
			foreach ((array)$this->DB->command($this->_data['countCommand'], $this->getReadonly()) as $row) {
				$this->_data['count'] += array_sum((array) $row);
			}
			$this->cache[0] ($this->_data['count'] || $this->cache[2]) && Cache::set($this->_data['count'], json_encode(['cache' => $this->cache] + $this->_data['countReplaces']), get_class($this->cursor) . $this->DB->database(), $this->cache[0]);
		}

		return $this->_data['count'];
	}




	public function delete() {
		$command = strtr('DELETE :ignore :form :where :order :offset :limit', [':ignore' => $this->_ignore(), ':form' => $this->_from('DELETE'), ':where' => $this->_where(), ':order' => $this->_order(), ':limit' => $this->_limit()]);
		return $this->_command($command);
	}




	public function deleteCacheSelect($refresh = NULL) {
		$this->cache[0] && Cache::delete(json_encode(['cache' => $this->cache, ':field' => $this->_fields(), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':group' => $this->_group('SELECT'), ':having' => $this->_having(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit(), ':union' => $this->_union(), ':lock' => $this->_lock()]), get_class($this->cursor) . $this->DB->database(), $refresh === NULL ? $this->cache[1] : $refresh);
		unset($this->_data['select']);
		return $this;
	}

	public function deleteCacheSelectRow($refresh = NULL) {
		$this->_limit();
		if (empty($this->_data['limit'])) {
			$this->_data['limit'] = "LIMIT 1";
		}
		$this->deleteCacheSelect();
		unset($this->_data['limit']);
		return $this;
	}

	public function deleteCacheCount($refresh = NULL) {
		$this->cache[0] && Cache::delete(json_encode(['cache' => $this->cache, ':group' => $this->_group('COUNT'), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':having' => $this->_having(), ':union' => $this->_union(), ':lock' => $this->_lock()]), get_class($this->cursor) . $this->DB->database(), $refresh === NULL ? $this->cache[1] : $refresh);
		$this->_data['count'] = false;
		return $this;
	}


	public function flush() {
		$this->_data = [];
	}

	public function getUseTables() {
		$tables = [];
		foreach (['tableUseTables', 'fromUseTables', 'whereUseTables', 'havingUseTables', 'unionsUseTables'] as $key) {
			if (!empty($this->_data[$key])) {
				$tables =  array_merge($tables, $this->_data[$key]);
			}
		}
		return $tables;
	}
}