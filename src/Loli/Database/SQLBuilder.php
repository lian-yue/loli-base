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
namespace Loli\Database;
use Loli\Cache;

class SqlBuilder extends AbstractBuilder{


	// 逻辑运算符
	private static $logicals = [
		'AND' => 'AND',
		'&&' => 'AND',

		'OR' => 'OR',
		'||' => 'OR',

		'XOR' => 'XOR',
	];



	// 计算运算符
	private static $assignments = [
		'INC' => '+',
		'+' => '+',


		'DECR' => '-',
		'-' => '-',
	];


	// 比较运算符
	private static $compares = [


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
	private static $notCompares = [


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
	private static $havings = ['SUM','MIN','MAX','AVG','COUNT'];


	// 全部函数
	private static $functions = [
		'SUM' => 'MIN',
		'MAX' => 'MAX',
		'AVG' => 'AVG',
		'COUNT' => 'COUNT',

		'FIRST' => 'FIRST',
		'LAST' => 'LAST',
	];



	private static $typeFunctions = [
		'integer' => 'intval',
		'boolean' => 'boolval',
		'float' => 'floatval',
		'date' => 'to_string',
		'string' => 'to_string',
		'json' => 'to_array',
	];

	// 全部函数
	private static $types = [
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
			'datetime' => ['value' => null],
			'year' => ['value' => '0000'],
			'date' => ['value' => '0000-00-00'],
			'time' => ['value' => '00:00:00'],
			'timestamp' => ['value' => null],
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
	private $isCache = true;


	private $data = [];





	private function getColumns() {
		if (empty($this->data['columns'])) {
			foreach ($this->columns as $name => &$column) {
				if ($column instanceof Param) {
				} elseif (is_array($column)) {
					$column = new Param($column);
				} else {
					$column = new Param(['name' => $name, 'type' => $value]);
				}
				if (!$column->name) {
					$column->name = $name;
				}
			}
			$this->data['columns'] = true;
		}
		return $this->columns;
	}




	private function getColumnsType() {
		if (!isset($this->data['columnsType'])) {
			$columnsType = [];
			foreach ($this->getColumns() as $column) {
				$name = $column->name;
				$type = $column->type;

				if (!$name) {
					continue;
				}

				if (!$type) {
					$columnsType[$name] = 'string';
					continue;
				}

				if (isset(self::$types[$type])) {
					$columnsType[$name] = $type;
					continue;
				}

				$continue = false;
				foreach(self::$types as $key => $value) {
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
			$this->data['columnsType'] = $columnsType;
		}
		return $this->data['columnsType'];
	}

	/**
	 * command 执行命令
	 * @param  string                          $command     query
	 * @param  integer                         $ttl         表过期时间
	 * @return mixed
	 */
	private function command($command, $ttl = 2) {
		if (!$this->execute) {
			return $command;
		}
		$results = $this->database->command($command, false);
		$this->setReadonly($ttl);
		return $results;
	}




	/**
	 * getTable 取得一个表的对象
	 * @return Param
	 */
	private function getTable() {
		if (!isset($this->data['table'])) {
			// 没有表
			if (!$tables = $this->tables) {
				throw new QueryException('', 'Unselected table');
			}

			// 只能处理单个表
			$table = reset($tables);
			if ($table instanceof Param) {
				$table = $table->value;
			} elseif (is_array($table)) {
				$table = empty($table['value']) ? null : $table['value'];
			}
			$this->database->key($table, true);
			$this->data['table'] = $table;
			$this->data['tableUseTables'] = [$this->data['table']];
		}
		return $this->data['table'];
	}


	/**
	 * getFrom 取得表的  from
	 * @param  string $type  类型  SELECT or UPDATE  or DELETE
	 * @return string
	 */
	private function getFrom($type) {
		if (!isset($this->data['from'])) {
			// 没有表
			if (!$this->tables) {
				throw new QueryException('', 'Unselected table');
			}
			$tables = $using = $useTables = $columnUse = $aliasUse = [];

			$this->getWhere();
			$this->getHaving();
			foreach (['whereColumns', 'havingColumns'] as $key) {
				foreach ($this->data[$key] as $value) {
					if (($start = strpos($value, '.')) === false) {
						$columnUse[] = $value;
					} else {
						$aliasUse[] = substr($value, $start);
					}
				}
			}

			$this->tables = (array) $this->tables;

			foreach ($this->tables as $alias => &$table) {
				if ($table instanceof Param) {
				} elseif (is_array($table)) {
					$table = new Param(['value' => empty($table['value']) ? null : $table['value'], 'alias' => isset($table['alias']) ? $table['alias'] : ($alias && !is_numeric($alias) ? $alias : null), 'join' => empty($table['join']) ? null : $table['join'], 'on' => empty($table['on']) ? null : $table['on']]);
				} else {
					$table = new Param(['value' => $table]);
				}

				// 没有 value 的
				if (!$table->value) {
					throw new QueryException('', 'Table name is empty');
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
					$value = $this->database->key($table->value, true);
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
							$ons[] = $this->database->key($column1, true) . ' = '. $this->database->key($column2, true);
						}
						$on = implode(' AND ', $ons);
					} else {
						$on = '1 = 2';
					}
				}
				$tables[] = ['value' => $value, 'alias' => $table->alias ? $this->database->key($table->alias, true) : false, 'join' => $join, 'on' => $on];
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
			$this->data['from'] = implode(',', $from);
			$this->data['fromUsing'] = implode(',', $using);
			$this->data['fromUseTables'] = $useTables;
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
				throw new QueryException($type, 'Unknown table structure type');
		}

		return strtr($command, [':using' => $this->data['fromUsing'], ':from' => $this->data['from']]);
	}




	/**
	 * getFields 选择的字段
	 * @return string
	 */
	private function getFields() {
		if (!isset($this->data['fields'])) {
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
				} elseif (!is_string($value) || !($field = $this->database->key($value))) {
					continue;
				}

				if ($alias) {
					$alias = $this->database->key($alias);
				}

				if ($function) {
					$function = empty(self::$functions[$function = strtoupper($function)]) ? false : preg_replace('/[^A-Z]/', '', $function);
					if ($function) {
						$field = $function. '('.$value .')';
					}
				}
				$fields[] = $alias ? $field . ' AS ' . $alias : $field;
			}
			if (!$fields) {
				$fields[] = '*';
			}
			$this->data['fields'] = implode(', ', $fields);
		}
		return $this->data['fields'];
	}


	private function getOptions($optionName = false) {
		if (!isset($this->data['options'])) {
			$this->data['options'] = [];
			foreach ($this->options as $name => &$option) {
				if (!$option instanceof Param) {
					$option = new Param(['name' => $name, 'value' => $option]);
				}
				$this->data['options'][$option->name][] = $option;
			}
		}
		return $optionName === false ? $this->data['options'] : (isset($this->data['options'][$optionName]) ? $this->data['options'][$optionName] : false);
	}




	/**
	 * getIgnore 忽略参数
	 * @return string
	 */
	private function getIgnore() {
		if (!isset($this->data['ignore'])) {
			$this->data['ignore'] = ($option = $this->getOptions('ignore')) && end($option)->value ? 'IGNORE' : '';
		}
		return $this->data['ignore'];
	}


	/**
	 * getRows 是否记录此次查询的统计  mysql 可用
	 * @return string
	 */
	private function getRows() {
		if (!isset($this->data['rows'])) {
			switch ($this->database->protocol()) {
				case 'mysql':
					$rows = ($option = $this->getOptions('rows')) && end($option)->value ? 'SQL_CALC_FOUND_ROWS' : '';
					break;
				default:
					$rows = '';
			}
			$this->data['rows'] = $rows;
		}
		return $this->data['rows'];
	}




	private function getLock() {
		if (!isset($this->data['lock'])) {
			$lock = ($option = $this->getOptions('lock')) ? end($option)->value : '';
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
						throw new QueryException($lock, 'Unknown type of lock');
				}
			}
			$this->data['lock'] = $lock;
		}
		return $this->data['lock'];
	}


	/**
	 * getLimit 限制的数量
	 * @return string
	 */
	private function getLimit() {
		if (!isset($this->data['limit'])) {
			if ($option = $this->getOptions('limit')) {
				$limit = abs((int) end($option)->value);
				$limit = $limit ? 'LIMIT '. $limit : '';
			} else {
				$limit = '';
			}
			$this->data['limit'] = $limit;
		}
		return $this->data['limit'];
	}




	/**
	 * getOffset 偏移
	 * @return string
	 */
	private function getOffset() {
		if (!isset($this->data['offset'])) {
			if ($option = $this->getOptions('offset')) {
				$offset = (int) end($option)->value;
				$offset = $offset ? 'OFFSET '. $offset : '';
			} else {
				$offset = '';
			}
			$this->data['offset'] = $offset;
		}
		return $this->data['offset'];
	}





	/**
	 * getOrder 排序
	 * @return string
	 */
	private function getOrder() {
		if (!isset($this->data['order'])) {
			$order = [];
			if ($option = $this->getOptions('order')) {
				foreach ($option as $value) {
					if (!$value->column && is_array($value->value)) {
						foreach($value->value as $column => $value) {
							if ($column && ($column = $this->database->key($column))) {
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
					} elseif (!is_string($value->column) || !($column = $this->database->key($value->column))) {
						continue;
					}
					$function = $value->function ? (empty(self::$functions[$function = strtoupper($value->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : false;
					$order[$function ? $function . '('. $column .')' : $column] = $value->value;
				}
				unset($value);
			}
			foreach ($order as $column => &$value) {
				if ($value === false || $value === null) {
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
			$this->data['order'] = $order ? 'ORDER BY ' . implode(', ', $order) : '';
		}
		return $this->data['order'];
	}


	/**
	 * getGroup  分组
	 * @param  $type  SELECT or COUNT
	 * @return string
	 */
	private function getGroup($type) {
		if (!isset($this->data['group'])) {
			$group = [];
			if ($option = $this->getOptions('order')) {
				foreach($option as $value) {
					if (!$value->value) {
						continue;
					}
					if ($value->expression) {
						$value = $value->value;
					} elseif (!is_string($value->value) || !($value = $this->database->key($value->value))) {
						continue;
					}
					$function = $option->function ? (empty(self::$functions[$function = strtoupper($option->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : false;
					if ($function) {
						$value = $function . '('.$value.')';
					}
					$group[$value] = $value;
				}
			}
			$this->data['group']  = implode(', ', $group);
		}
		return $type === 'SELECT' ? ($this->data['group']  ? 'GROUP BY ' . $this->data['group']  : '') : ($this->data['group']  ? 'COUNT(DISTINCT ' . $this->data['group']  . ')' : 'COUNT(*)');
	}






	/**
	 * getWhere 查询
	 * @return string
	 */
	private function getWhere() {
		if (!isset($this->data['where'])) {
			$whereColumns = $whereUseTables = [];
			$this->data['where'] = $this->getQuery($this->querys, false, $whereUseTables, $whereColumns);
			if ($this->data['where']) {
				$this->data['where'] = 'WHERE ' . $this->data['where'];
			}
			$this->data['whereColumns'] = $whereColumns;
			$this->data['whereUseTables'] = $whereUseTables;
		}
		return $this->data['where'];
	}

	/**
	 * getHaving 聚合
	 * @return string
	 */
	private function getHaving() {
		if (!isset($this->data['having'])) {
			$havingColumns = $havingUseTables = [];
			$this->data['having'] = $this->getQuery($this->querys, true, $havingUseTables, $havingColumns);
			if ($this->data['having']) {
				$this->data['having'] = 'HAVING ' . $this->data['having'];
			}
			$this->data['havingColumns'] = $havingColumns;
			$this->data['havingUseTables'] = $havingUseTables;
		}
		return $this->data['having'];
	}




	/**
	 * getQuery  查询
	 * @param  array        $querys
	 * @param  boolean|null $having  是否是聚合 null 允许全部
	 * @param  string       $logical 链接运算符
	 * @return array
	 */
	private function getQuery(array $querys, $having = null, array &$useTables, array &$useColumns, $logical = '') {

		// 逻辑 运算符
		if (!$logical) {
			$logical = ($option = $this->getOptions('logical')) ? strtoupper(end($option)->value) : 'AND';
			if (empty(self::$logicals[$logical])) {
				throw new QueryException($logical, 'Unknown logical');
			}
			$logical = self::$logicals[$logical];
		}

		$columnsType = $this->getColumnsType();

		$commands = [];
		foreach ($querys as $column => &$query) {
			if (!$query instanceof Param) {
				$query = new Param(['column' => $column, 'value' => $query]);
			}
			if (!$query->column && !is_numeric($column)) {
				$query->column = $column;
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

			// 跳过 null 和空数组
			if (is_array($query->value) && !($query->value = array_filter($query->value, function($value) { return $value === null; }))) {
				continue;
			}

			// 函数
			$function = $query->function ? (empty(self::$functions[$function = strtoupper($query->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : '';


			// 只允许聚合函数
			if ($having === true && !$query->having && !in_array($function, self::$havings, true)) {
				continue;
			}

			// 不允许聚合函数
			if ($having === false && ($query->having || ($query->having !== false && in_array($function, self::$havings, true)))) {
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
			$compare = empty(self::$compares[$compare]) ? ($compare ? $compare : '=') : self::$compares[$compare];


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


			// value 是 null
			if ($query->value === null) {
				if ($binary || ($compare !== '=' && $compare !== '!=')) {
					continue;
				}
				$query->value = $compare === '=';
				$compare = 'null';
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
					$value = $this->getQuery((array)$query->value, null, $useTables, $useColumns, $query->logical ? $query->logical : ($logical === 'OR' ? 'AND' : 'OR'));
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
			} elseif (is_string($query->column) && ($column = $this->database->key($query->column))) {
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
					$value = array_map([$this->database, 'value'], !$function && isset($columnsType[$value->column]) ? array_map(self::$typeFunctions[$columnsType[$value->column]], (array) $query->value) : (array) $query->value);
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
				case 'null':
					// null 查询
					$arrays[] = ['compare' => 'IS', 'value' => ($not ? !$query->value : $query->value) ? 'null' : 'NOT null', 'not' => ''];
					break;
				case 'BETWEEN':
					// BETWEEN
					$value = array_map([$this->database, 'value'], !$function && isset($columnsType[$value->column]) ? array_map(self::$typeFunctions[$columnsType[$value->column]], (array) $query->value) : (array) $query->value);
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
					$arrays[] = ['compare' => 'REGEXP', 'value' => $this->database->value($value)];
					break;
				case 'TEXT':
				case 'MATCH':
				case 'AGAINST':
					$mode = empty($query->mode) ? '' : strtoupper($query->mode);
					$value = $this->database->value(is_array($query->value) ? implode(' +', $query->value) : (string) $query->value);
					if (!$function || $function !== 'MATCH') {
						$column = 'MATCH('.$column.')';
					}
					$arrays[] = ['compare' => '', 'value' => 'AGAINST('.$value . ' ' . $mode .')'];
					break;
				case 'SEARCH':
					foreach ($this->search($query->value, false) as $key => $values) {
						foreach ($values as $value) {
							$arrays[] = ['not' =>  $key === '-' ? 'NOT' : '', 'compare' => 'LIKE', 'value' => $this->database->value('%' . addcslashes($value, '_%\\') . '%')];
						}
					}
					break;
				case 'LIKE':
					$arrays[] = ['compare' => $compare, 'value' => $this->database->value((string)$query->value)];
					break;
				default:
					if (empty(self::$compares[$compare])) {
						throw new QueryException($compare, 'Unknown compare');
					}
					$arrays[] = ['compare' => $compare, 'value' => $this->database->value(!$function && isset($columnsType[$query->column]) ? call_user_func(self::$typeFunctions[$columnsType[$query->column]], $query->value) : $query->value)];
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
						if ($array['not'] && !empty(self::$notCompares[$array['compare']])) {
							$array['not'] = '';
							$array['compare'] = self::$notCompares[$array['compare']];
						}
						$commands[] =  implode(' ', [$array['binary'], $column, $array['not'], $array['compare'], $array['value']]);
				}
			}
		}

		return implode(' '. $logical .' ', array_filter(array_map('trim', $commands)));
	}




	/**
	 * getUnion 链接
	 * @return string
	 */
	private function getUnion() {
		if (!isset($this->data['union'])) {
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
				throw new QueryException($union, 'Does not support this type of union');
			}
			$this->data['union'] = implode(' ', $unions);
			$this->data['unionsUseTables'] = $useTables;
		}
		return $this->data['union'];
	}







	public function exists() {
		// 储存语句
		$table = $this->getTable();
		switch ($this->database->protocol()) {
			case 'mysql':
				$table = $this->database->value(addcslashes($table, '%_'));
				$command = 'SHOW TABLES LIKE :table;';
				break;
			case 'sqlite':
				$table = $this->database->value($table);
				$command = 'SELECT * FROM sqlite_master WHERE type=\'table\' AND name=:table;';
				break;
			default:
				throw new QueryException(__METHOD__.'()', 'Does not support this protocol');
		}

		$command = strtr($command, [':table' => $table]);

		// 不执行的
		if (!$this->execute) {
			return $command;
		}

		// 执行
		$result = $this->database->command($command, true);
		return $result ? true : false;
	}



	public function create() {
		if (empty($this->data['create'])) {
			$table = $this->database->key($this->getTable(), true);

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

			switch ($this->database->protocol()) {
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
						'null' => 'NOT null',
						'value' => 'DEFAULT %s',
						'increment' => 'AUTO_INCREMENT',
						'charset' => 'CHARACTER SET %s',
						'comment' => 'COMMENT %s',
					];
					break;
				default:
					throw new QueryException(__METHOD__.'()', 'Does not support this protocol');
			}

			foreach ($this->getColumns() as $column) {
				$name = $this->database->key($column->name, true);


				$value = [];
				foreach (self::$types as $typeName => $types) {
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
						if ($column->value) {
							$value['value'] = $this->database->value($column->value);
						} elseif ($column->value !== null) {

						} elseif ($types[$value['type']]['value'] !== null) {
							$value['value'] = $this->database->value($types[$value['type']]['value']);
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
							$value['type'] = null;
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
								throw new QueryException(__METHOD__.'() :' . $value['type'], 'Unknown data type');
							}
						}
						if (empty($commandValues['unique'])) {
							$value['binary'] = (bool) $column->binary;
						}

						if (!empty($args['index'])) {
							$value['value'] = $this->database->value(isset($column->value) ? (string) $column->value : '');
						}

						// 编码
						if ($column->charset) {
							$value['charset'] = $this->database->value(preg_replace('/[^0-9a-z_]/i', '', $column->charset));
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
						$value['value'] = $this->database->value($column->value ? $column->value : '');
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
				$value['null'] = $column->null;

				// 注释
				$value['comment'] = $column->comment ? $this->database->value((string)$column->comment) : null;

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
					$values[] = strtr($commandOptions['unique'], [':name' => $this->database->key($name, true), ':value' => implode(',',  $array)]);
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
					$values[] = strtr($commandOptions['key'], [':name' => $this->database->key($name, true), ':value' => implode(',',  $array)]);
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
					$arrays[':'.$name] = strtr($value, [':value' => $this->database->value($options[$name])]);
				} else {
					$arrays[':'.$name] = '';
				}
			}
			$arrays[':table'] = $table;
			$arrays[':value'] = "\n{$values}\n";
			$this->data['create'] = strtr($command, $arrays);
		}
		return $this->command($this->data['create'], 60);
	}



	public function truncate() {
		$table = $this->database->key($this->getTable(), true);
		switch ($this->database->protocol()) {
			case 'sqlite':
				$command = 'DELETE FROM :table;';
				break;
			default:
				$command = 'TRUNCATE TABLE :table;';
		}
		return $this->command(strtr($command, [':table' => $table]), 60);
	}




	public function drop() {
		$table = $this->database->key($this->getTable(), true);
		switch ($this->database->protocol()) {
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

		if ($exists && (!($option = $this->getOptions('exists')) || !$option->value)) {
			$exists = '';
		}
		return $this->command(strtr($command, [':table' => $table, ':exists' => $exists]), 60);
	}





	public function insert() {
		if (empty($this->data['insert'])) {
			$table = $this->database->key($this->getTable(), true);

			$options = [];
			foreach ($this->getOptions() as $name => $option) {
				$options[$name] = end($option)->value;
			}
			$columnsType = $this->getColumnsType();
			$default = $inserts = [];

			foreach ($this->documents as $document) {
				$insert = [];
				foreach ($document as $name => $value) {
					if ($value === null) {
						$insert[$name] = $this->database->value(null);
						continue;
					}
					if (!$value instanceof Param) {
						$insert[$name] = $this->database->value(isset($columnsType[$name]) ? call_user_func(self::$typeFunctions[$columnsType[$name]], $value) : $value);
						continue;
					}

					if ($value->value instanceof Param) {
						throw new QueryException($this->documents, 'Value can not be Param');
					}
					if ($value->value instanceof Cursor) {
						$execute = $value->value->execute;
						$insert[$name] = rtrim($value->value->execute(false)->select(), " \t\n\r\0\x0B;");
						$value->value->execute($execute);
					} elseif ($value->expression) {
						$insert[$name] = $value->value;
					} elseif ($value->value === null) {
						$insert[$name] = $this->database->value(null);
					} else {
						$insert[$name] = $this->database->value(isset($columnsType[$name]) ? call_user_func(self::$typeFunctions[$columnsType[$name]], $value->value) : $value->value);
					}
					$function = $value->function ? (empty(self::$functions[$function = strtoupper($value->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : '';
					if ($function) {
						$insert[$name] = $function . '('. $insert[$name] .')';
					}
				}
				if (!$insert) {
					throw new QueryException($this->documents, 'Inserted rows can not be empty');
				}
				$default += $insert;
				$inserts[] = $insert;
			}
			if (!$inserts) {
				throw new QueryException($this->documents, 'Inserted rows can not be empty');
			}


			ksort($default);
			$column = [];
			foreach ($default as $key => &$value) {
				$value = $this->database->value(null);
				$column[] = $this->database->key($key, true);
			}
			$column = implode(',', $column);
			unset($value);

			unset($insert);
			foreach ($inserts as &$insert) {
				$insert += $default;
				ksort($insert);
				$insert = '('. implode(',', $insert) . ')';
			}
			unset($insert);
			$document = implode(',', $inserts);


			switch ($this->database->protocol()) {
				case 'mysql':
				case 'sqlite':
					$command = empty($options['replace']) ? 'INSERT INTO :table (:column) VALUES :document;' : 'REPLACE INTO :table (:column) VALUES :document;';
					break;
				default:
					$command = 'INSERT :ignore INTO :table (:column) VALUES :document;';
			}
			$this->data['insert'] = strtr($command, [':ignore' => $this->getIgnore(), ':table' => $table, ':column' => $column, ':document' => $document]);

		}

		return $this->command($this->data['insert']);
	}





	public function update() {
		if (empty($this->data['update'])) {
			if (!$this->documents) {
				throw new QueryException($this->documents, 'Update can not be empty');
			}
			if (count($this->documents) > 1) {
				throw new QueryException($this->documents, 'Can not update multiple');
			}
			$columnsType = $this->getColumnsType();
			$document = [];
			foreach($this->documents[0] as $name => $value) {
				$name = $this->database->key($name, true);
				if ($value === null) {
					$document[$name] = $this->database->value(null);
					continue;
				}

				if (!$value instanceof Param) {
					$document[$name] = $this->database->value(isset($columnsType[$name]) ? call_user_func(self::$typeFunctions[$columnsType[$name]], $value) : $value);
					continue;
				}

				$assignment = strtoupper($value->assignment);
				$assignment = empty(self::$assignments[$assignment]) ? $assignment : self::$assignments[$assignment];

				if ($value->value instanceof Cursor) {
					$execute = $value->value->execute;
					$data = '(' . rtrim($value->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
					$value->value->execute($execute);
				} elseif ($value->expression) {
					$data = '('. $value->value .')';
				} elseif ($value->value === null) {
					$data = $this->database->value(null);
				} else {
					$data = $this->database->value(in_array($assignment, ['', '='], true) && isset($columnsType[$value->name]) ? call_user_func(self::$typeFunctions[$columnsType[$value->name]], $value->value) : $value->value);
				}


				// 字段 + 运算符 + 值
				if (in_array($assignment, ['+', '-', '*', '/'], true)) {
					$column = $value->column ? $this->database->key($value->column, true) : $name;
					$document[$name] = $value->before ? $data  .' '. $assignment .' '. $column : $column  .' '. $assignment .' '. $data;
					continue;
				}

				// 替换
				if ($assignment === 'REPLACE') {
					$document[$name] =  'REPLACE('. $name .', '. $this->database->value($value->search) .', '. $data .')';
					continue;
				}

				if (!in_array($assignment, ['', '='], true)) {
					throw new QueryException($assignment, 'Unknown assignment');
				}
				$document[$name] = $data;
			}

			if (!$document) {
				throw new QueryException($this->documents, 'Update can not be empty');
			}

			unset($value);
			foreach ($document as $name => &$value) {
				$value = $name . ' = ' . $value;
			}
			unset($value);
			$document = implode(', ', $document);

			$command = 'UPDATE :form SET :document :where :order :offset :limit';
			$this->data['update'] = strtr($command, [':ignore' => $this->getIgnore(), ':form' => $this->getFrom('UPDATE'), ':document' => $document, ':where' => $this->getWhere(), ':order' => $this->getOrder(), ':offset' => $this->getOffset(), ':limit' => $this->getLimit()]);

		}
		return $this->command($this->data['update']);
	}


	public function select() {
		// 读缓存数据
		if (!$this->isCache) {

		} elseif ($this->execute) {
			if (!$this->getLock() && isset($this->data['select'])) {
				return $this->data['select'];
			}
		} elseif (isset($this->data['selectCommand'])) {
			return $this->data['selectCommand'];
		}



		if (empty($this->data['selectReplaces']) || empty($this->data['selectCommand'])) {
			// 替换
			$this->data['selectReplaces'] = [':field' => $this->getFields(), ':form' => $this->getFrom('SELECT'), ':where' => $this->getWhere(), ':group' => $this->getGroup('SELECT'), ':having' => $this->getHaving(), ':order' => $this->getOrder(), ':offset' => $this->getOffset(), ':limit' => $this->getLimit(), ':union' => $this->getUnion(), ':lock' => $this->getLock()];

			// 命令行
			$this->data['selectCommand'] = strtr('SELECT :rows :field :form :where :group :having :order :offset :limit :lock :union;', $this->data['selectReplaces'] + [':rows' => $this->getRows()]);
		}

		// 不需要执行的
		if (!$this->execute) {
			return $this->data['selectCommand'];
		}


		// 读取缓存
		if ($this->isCache && $this->cache[0]) {
			$cachePool = Cache::database();
			$cacheKey = md5(json_encode(['cache' => $this->cache, 'class' => $this->class] + $this->data['selectReplaces']));
			$item = $cachePool->getItem($cacheKey);
			if (($results = $item->get()) instanceof Results) {
				if (!$this->cache[1] || !method_exists($item, 'getExpiresAfter') || ($expiresAfter = $item->getExpiresAfter()) === null || $expiresAfter > $this->cache[1] || $cachePool->getItem('expires'. $cacheKey)->isHit()) {
					$this->data['select'] = $results;
				} else {
					$cachePool->save($cachePool->getItem('expires'. $cacheKey)->set(true)->expiresAfter(2));
				}
			}
		}


		if (!isset($this->data['select']) || (!$this->cache[2] && !$this->data['select']->count())) {

			// 读取数据
			$this->data['select'] = $this->database->command($this->data['selectCommand'], $this->getReadonly(), $this->class);

			if (isset($item) && ($this->cache[2] || $this->data['select']->count())) {
				$cachePool->save($item->set($this->data['select'])->expiresAfter($this->cache[0]));
			}
			// 需要去全部行数的
			if ($this->isCache && $this->getRows()) {
				$this->isCache = false;
				$this->count();
				$this->isCache = true;
			}

		}

		return $this->data['select'];
	}

	public function selectRow() {
		$this->getLimit();
		if (empty($this->data['limit'])) {
			$this->data['limit'] = "LIMIT 1";
		}
		$select = $this->select();
		if ($select && !is_string($select)) {
			$select = $select[0];
		}
		unset($this->data['limit']);
		return $select;
	}



	public function count() {
		// 读缓存数据
		if (!$this->isCache) {

		} elseif ($this->execute) {
			if (!$this->getLock() && isset($this->data['count'])) {
				return $this->data['count'];
			}
		} elseif (isset($this->data['countCommand'])) {
			return $this->data['countCommand'];
		}


		if ($this->cache[0] || !$this->getRows()) {
			$this->data['countReplaces'] = [':group' => $this->getGroup('COUNT'), ':form' => $this->getFrom('SELECT'), ':where' => $this->getWhere(), ':having' => $this->getHaving(), ':union' => $this->getUnion()];
		}

		// rows 的
		if ($this->getRows()) {
			$this->data['countCommand'] = 'SELECT FOUND_ROWS()';
		} else {
			$this->data['countCommand'] = strtr('SELECT :group :form :where :having :union;', $this->data['countReplaces']);
		}

		// 不需要执行的
		if (!$this->execute) {
			return $this->data['countCommand'];
		}


		// 读取缓存
		if ($this->isCache && $this->cache[0]) {
			$cachePool = Cache::database();
			$cacheKey = md5(json_encode(['cache' => $this->cache, 'class' => $this->class] + $this->data['countReplaces']));
			$item = $cachePool->getItem($cacheKey);
			if (is_int($count = $item->get())) {
				if (!$this->cache[1] || !method_exists($item, 'getExpiresAfter') || ($expiresAfter = $item->getExpiresAfter()) === null || $expiresAfter > $this->cache[1] || $cachePool->getItem('expires'. $cacheKey)->isHit()) {
					$this->data['count'] = $count;
				} else {
					$cachePool->save($cachePool->getItem('expires'. $cacheKey)->set(true)->expiresAfter(2));
				}
			}
		}

		if (!isset($this->data['count']) || (!$this->cache[2] && !$this->data['count'])) {
			// 需要读取数据的

			// rows 的
			if ($this->isCache && $this->getRows()) {
				$this->isCache = false;
				$this->select();
				$this->isCache = true;
			}

			// 数量
			$result = $this->database->command($this->data['countCommand'], $this->getReadonly());
			if (is_scalar($result)) {
				$this->data['count'] = (int) $result;
			} else {
				$this->data['count'] = 0;
				foreach ($result as $row) {
					if (is_scalar($row)) {
						$this->data['count'] += $row;
					} else {
						$this->data['count'] += array_sum(to_array($row));
					}
				}
			}
			if (isset($item) && ($this->cache[2] || $this->data['count'])) {
				$cachePool->save($item->set($this->data['count'])->expiresAfter($this->cache[0]));
			}
		}
		return $this->data['count'];
	}




	public function delete() {
		$command = strtr('DELETE :ignore :form :where :order :offset :limit;', [':ignore' => $this->getIgnore(), ':form' => $this->getFrom('DELETE'), ':where' => $this->getWhere(), ':order' => $this->getOrder(), ':offset' => $this->getOffset(), ':limit' => $this->getLimit()]);
		return $this->command($command);
	}



	public function deleteCache($refresh = null) {
		if (!$this->cache[0]) {
			return $this->cursor;
		}
		$key = [
			'cache' => $this->cache,
			'class' => $this->class,
			':field' => $this->getFields(),
			':form' => $this->getFrom('SELECT'),
			':where' => $this->getWhere(),
			':group' => $this->getGroup('SELECT'),
			':having' => $this->getHaving(),
			':order' => $this->getOrder(),
			':offset' => $this->getOffset(),
			':limit' => $this->getLimit(),
			':union' => $this->getUnion(),
			':lock' => $this->getLock()
		];

		$keys[] = md5(json_encode($key));

		$key2 = $key;
		$key2[':limit'] = '';
		$keys[] = md5(json_encode($key2));
		$keys[] = md5(json_encode([
			'cache' => $this->cache,
			'class' => $this->class,
			':group' => $this->getGroup('COUNT'),
			':form' => $this->getFrom('SELECT'),
			':where' => $this->getWhere(),
			':having' => $this->getHaving(),
			':union' => $this->getUnion(),
			':lock' => $this->getLock()
		]));


		$cachePool = Cache::database()->deleteItems($keys);


		unset($this->data['count']);
		unset($this->data['select']);
		return $this->cursor;
	}

	public function clear() {
		$this->data = [];
	}

	public function getUseTables() {
		$tables = [];
		foreach (['tableUseTables', 'fromUseTables', 'whereUseTables', 'havingUseTables', 'unionsUseTables'] as $key) {
			if (!empty($this->data[$key])) {
				$tables =  array_merge($tables, $this->data[$key]);
			}
		}
		return $tables;
	}
}
