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


		'LIKE' => 'LIKE',
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





	// 全部函数
	private static $_types = [
		'bool' => ['bit' => true, 'bool' => true],
		'json' => ['array' => true, 'json' => true],

		//  长度, 有符号, 无符号
		'integer' => ['tinyint' => [1, 3, 4], 'smallint' => [2, 5, 6], 'mediumint' => [3, 8, 9], 'int' => [4, 10, 11], 'bigint' => [8, 20, 20]],

		// 直接是值
		'float' => ['real' => true, 'double' => true, 'float' => true, 'decimal' => true],

		// 索引, 默认, 最大, 最小
		'string' => ['varchar' => [true, 255, 4096, 0], 'text' => [false, false, 65535, 255], 'mediumtext' => [false, false, 16777215, 65535], 'longtext' => [false, false, 0, 0], 'char' => [true, 255, 4096, 0]],
		'binary' => ['binary' => [true, 255, 4096, 0], 'varbinary' => [true, 255, 4096, 0], 'blob' => [false, false, 65535, 255], 'mediumblob' => [false, false, 16777215, 65535], 'logngblob' => [false, false, 0, 0]],

		// date 默认值
		'date' => ['datetime' => '0000-00-00 00:00:00', 'year' => '0000', 'date' => '0000-00-00', 'time' => '00:00:00', 'timestamp' => NULL],
	];


	// 是否缓存
	private $_isCache = true;


	private $_data = [];

	private function _columnsType() {
		if (!isset($this->_data['columnsType'])) {
			$columnsType = [];
			foreach ($this->columns as $name => $column) {
				$type = false;
				if ($column instanceof Param) {
					if ($column->name && is_scalar($column->name)) {
						$name = $column->name;
					}
					if ($column->type) {
						$type = $column->type;
					}
				} else {
					if (!empty($column['name'])) {
						$name = $column['name'];
					}
					if (!empty($column['type'])) {
						$type = $column['type'];
					}
				}
				if ($type) {
					if (!isset(self::$_types[$type])) {
						$break = false;
						foreach(self::$_types as $key => $value) {
							if (isset($value[$type])) {
								$type = $key;
								$break = true;
								break;
							}
						}
						if (!$break) {
							$type = 'string';
						}
					}
				} else {
					$type = 'string';
				}
				$columnsType[$name] = $type;
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
			if (count($tables) !== 1) {
				throw new Exception($tables, 'Can only handle a single table');
			}
			$table = reset($tables);
			if ($table instanceof Param) {
				$table = $table->value;
			} elseif (is_array($table)) {
				$table = empty($table['value']) ? NULL : $table['value'];
			}
			$this->DB->key($table, true);
			$this->_data['table'] = $table;
		}
		$this->useTables = [$this->_data['table']];
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

			$useTables = $using = $tables = [];
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



				if ($table->value instanceof Cursor) {
					// 子查询表
					$execute = $table->value->arg('execute');
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



			$form = [];
			foreach ($tables as $table) {
				$form[] = $table['value'];
				if ($table['alias']) {
					$form[] = 'AS ' . $table['alias'];
				}
				if ($table['join']) {
					$form[] = $table['join'] . ' JOIN';
				}
				if ($table['on']) {
					$form[] = 'ON '. $table['on'];
				}
			}
			$this->_data['form'] = implode(',', $form);
			$this->_data['formUsing'] = implode(',', $using);
			$this->_data['formUseTables'] = $useTables;
		}



		switch ($type) {
			case 'SELECT':
				$command = 'FROM :form';
				break;
			case 'UPDATE':
				$command = ':form';
				break;
			case 'DELETE':
				$command = ':using FROM :form';
				break;
			default:
				throw new Exception($type, 'Unknown table structure type');
		}

		$this->useTables = $this->_data['formUseTables'];
		return strtr($command, [':using' => $this->_data['formUsing'], ':form' => $this->_data['form']]);
	}




	/**
	 * _fields 选择的字段
	 * @return string
	 */
	private function _fields() {
		if (!isset($this->_data['fields'])) {
			$fields = [];
			foreach ($this->fields as $alias => $field) {
				if (!$field instanceof Param) {
					$field = new Param(['value' => $field, 'alias' => is_string($alias) && $alias ? $alias : NUll]);
				}
				if (!$field->value) {
					continue;
				}
				$value = $field->expression ? $field->value : $this->DB->key($field->value);
				if (!$value) {
					continue;
				}
				$alias = $field->alias ? $this->DB->key($field->alias) : false;
				$function = $field->function ? (empty(self::$_functions[$function = strtoupper($field->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : false;
				if ($function) {
					$value = $function. '('.$value .')';
				}
				$fields[] = $alias ? $value . ' AS ' . $alias : $value;
			}
			if (!$fields) {
				$fields[] = '*';
			}
			$this->_data['fields'] = implode(', ', $fields);
		}
		return $this->_data['fields'];
	}





	/**
	 * _ignore 忽略参数
	 * @return string
	 */
	private function _ignore() {
		if (!isset($this->_data['ignore'])) {
			$ignore = '';
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'ignore') {
						$ignore = $option->value;
					}
				} elseif ($name === 'ignore') {
					$ignore = $option;
				}
			}
			$this->_data['ignore'] = $ignore ? 'IGNORE' : '';
		}
		return $this->_data['ignore'];
	}


	/**
	 * _rows 是否记录此次查询的统计  mysql 可用
	 * @return string
	 */
	private function _rows() {
		if (!isset($this->_data['rows'])) {
			$rows = '';
			if ($this->DB->protocol() === 'mysql') {
				foreach ($this->options as $name => $option) {
					if ($option instanceof Param) {
						if ($option->name === 'rows') {
							$rows = $option->value;
						}
					} elseif ($name === 'rows') {
						$rows = $option;
					}
				}
				$this->_data['rows'] = $rows ? 'SQL_CALC_FOUND_ROWS' : '';
			}
		}
		return $this->_data['rows'];
	}

	private function _lock() {
		if (!isset($this->_data['lock'])) {
			$lock = '';
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'lock') {
						$lock = $option->value;
					}
				} elseif ($name === 'lock') {
					$lock = $option;
				}
			}
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
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'limit') {
						$limit = $option->value;
					}
				} elseif ($name === 'limit') {
					$limit = $option;
				}
			}
			$this->_data['limit'] = isset($limit) && ($limit = intval($limit)) ? 'LIMIT ' . $limit : '';
		}
		return $this->_data['limit'];
	}




	/**
	 * _offset 偏移
	 * @return string
	 */
	private function _offset() {
		if (!isset($this->_data['offset'])) {
			$offset = 0;
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'offset') {
						$offset = $option->value;
					}
				} elseif ($name === 'offset') {
					$offset = $option;
				}
			}
			$this->_data['offset'] = ($offset = intval($offset)) ? 'OFFSET ' . $offset : '';
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
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'order' && $option->column) {
						$column = $option->expression ? $option->column : $this->DB->key($option->column);
						if ($column) {
							$function = $option->function ? (empty(self::$_functions[$function = strtoupper($option->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : false;
							$order[$function? $function . '('. $column .')' : $column] = $option->value;
						}
					}
				} elseif ($name === 'order' && is_array($option)) {
					foreach((array)$option as $column => $value) {
						if ($column && ($column = $this->DB->key($column))) {
							$order[$column] = $value;
						}
					}
				}
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
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'group' && $option->value) {
						$value = $option->expression ? $option->value : $this->DB->key($option->value);
						if ($value) {
							$function = $option->function ? (empty(self::$_functions[$function = strtoupper($option->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : false;
							if ($function) {
								$value = $function . '('.$value.')';
							}
							$group[$value] = $value;
						}
					}
				} elseif ($name === 'group' && $option) {
					foreach((array)$option as $value) {
						if ($value && ($value = $this->DB->key($value))) {
							$group[$value] = $value;
						}
					}
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
			$this->_data['whereUseTables'] = [];
			$this->_data['where'] = $this->_query($this->querys, false, $this->_data['whereUseTables']);
			if ($this->_data['where']) {
				$this->_data['where'] = 'WHERE ' . $this->_data['where'];
			}
		}
		$this->useTables = array_merge($this->_data['whereUseTables'], $this->useTables);
		return $this->_data['where'];
	}

	/**
	 * _having 聚合
	 * @return string
	 */
	private function _having() {
		if (!isset($this->_data['having'])) {
			$this->_data['havingUseTables'] = [];
			$this->_data['having'] = $this->_query($this->querys, true, $this->_data['havingUseTables']);
			if ($this->_data['having']) {
				$this->_data['having'] = 'HAVING ' . $this->_data['having'];
			}
		}
		$this->useTables = array_merge($this->_data['havingUseTables'], $this->useTables);
		return $this->_data['having'];
	}




	/**
	 * _query  查询
	 * @param  array        $querys
	 * @param  boolean|null $having  是否是聚合 null 允许全部
	 * @param  string       $logical 链接运算符
	 * @return array
	 */
	private function _query(array $querys, $having = NULL, array &$useTables, $logical = '') {
		// 逻辑 运算符
		if (!$logical) {
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'logical') {
						$logical = $option->value;
					}
				} elseif ($name === 'logical') {
					$logical = $value;
				}
			}

			if (!$logical) {
				$logical = 'AND';
			}
			if (empty(self::$_logicals[$logical])) {
				throw new Exception($logical, 'Unknown logical');
			}
			$logical = self::$_logicals[$logical];
		}


		$columnsType = $this->_columnsType();

		$commands = [];
		foreach ($querys as $column => $query) {
			if (!$query instanceof Param) {
				$query = new Param(['column' => $column, 'value' => $query]);
			}

			// 添加索引
			if (is_string($query->column) && !empty($this->indexs[$query->column])) {
				foreach ((is_array($this->indexs[$query->column]) || is_object($this->indexs[$query->column]) ? $this->indexs[$query->column] : ['compare' => $this->indexs[$query->column]]) as $key => $value) {
					if (!$query->$key) {
						$query->$key = $value;
					}
				}
			}

			// 跳过 NULL 和空数组
			if ($query->value === NULL || (is_array($query->value) && !($query->value = array_filter($query->value, function($value) { return $value === NULL; })))) {
				continue;
			}

			// 函数
			$function = $query->function ? (empty(self::$_functions[$function = strtoupper($query->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : '';

			// 只允许聚合函数
			if ($having && !$query->having && !in_array($function, self::$_havings, true)) {
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


			// 回调类型特殊类型
			if ($compare === 'CALL') {
				if ($query->value instanceof Cursor) {
					$execute = $query->value->arg('execute');
					$value = rtrim($query->value->execute(false)->select(), " \t\n\r\0\x0B;");
					$query->value->execute($execute);
					$useTables = array_merge($useTables, $query->value->getUseTables());
				} elseif ($query->expression) {
					$value = $query->value;
				} else {
					$value = $this->_query((array)$query->value, NULL, $useTables, $query->logical ? $query->logical : ($logical === 'OR' ? 'AND' : 'OR'));
				}
				$commands[] = $not . ' ('. $value .')';
				continue;
			}


			// 健名和使用函数
			if ($query->column instanceof Cursor) {
				$execute = $query->column->arg('execute');
				$column = $function . '(' . rtrim($query->column->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$query->column->execute($execute);
				$useTables = array_merge($useTables, $query->column->getUseTables());
			} elseif ($column = $this->DB->key($query->column)) {
				$column = $function ? $function . '('. $column .')' : $column;
			} else {
				continue;
			}

			// 直接关联的
			if ($query->value instanceof Cursor) {
				$execute = $query->value->arg('execute');
				$value = '(' . rtrim($query->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
				$query->value->execute($execute);
				$useTables = array_merge($useTables, $query->value->getUseTables());
				$commands[] = implode(' ', [$binary, $column, $not, ($compare === 'CALL' ? '' : $compare), $value]);
				continue;
			}


			// 直接执行的
			if ($query->expression) {
				$commands[] = implode(' ', [$binary, $column, $not, $compare, $value]);
				continue;
			}


			if (is_string($query->column) && isset($columnsType[$query->column]) && (!$function || !in_array($function, self::$_havings, true)) && (!$compare || !in_array($compare, ['~', '~*', 'REGEX', 'REGEXP', 'TEXT', 'MATCH', 'AGAINST', 'SEARCH', 'NULL'], true))) {
				switch ($columnsType[$query->column]) {
					case 'bool':
						if (in_array($compare, ['IN', 'BETWEEN'], true)) {
							$query->value = array_map('boolval', (array) $query->value);
						} else {
							$query->value = (bool) $query->value;
						}
					case 'json':
						$query->value = (array) $query->value;
						break;
					case 'integer':
						if (in_array($compare, ['IN', 'BETWEEN'], true)) {
							$query->value = array_map('intval', (array) $query->value);
						} else {
							$query->value = (int) $query->value;
						}
						break;
					case 'float':
						if (in_array($compare, ['IN', 'BETWEEN'], true)) {
							$query->value = array_map('floatval', (array) $query->value);
						} else {
							$query->value = (float) $query->value;
						}
						break;
					default:
						if (in_array($compare, ['IN', 'BETWEEN'], true)) {
							$query->value = array_map('strval', (array) $query->value);
						} else {
							$query->value = (string) $query->value;
						}
				}
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
					if (empty(self::$_compares[$compare])) {
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
						if ($array['not'] && !empty(self::$_notCompares[$array['compare']])) {
							$array['not'] = '';
							$array['compare'] = self::$_notCompares[$array['compare']];
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
		if (!isset($this->_data['union'])) {
			$unions = $useTables = [];
			foreach ($this->unions as $union) {
				if (!$union instanceof Param) {
					$union = new Param(['value' => $union]);
				}
				if ($union->value instanceof Cursor) {
					$execute = $union->value->arg('execute');
					$unions[] = 'UNION ' .($union->all ? '' : 'ALL '). rtrim($union->value->execute(false)->select(), " \t\n\r\0\x0B;");
					$union->value->execute($execute);
					$useTables = array_merge($useTables, $union->value->getUseTables());
					continue;
				}

				if ($union->expression) {
					$unions[] = 'UNION ' .($union->all ? '' : 'ALL ') . trim($union->value, " \t\n\r\0\x0B;");
					continue;
				}
				throw new Exception($union, 'Does not support this type of union');
			}
			$this->_data['union'] = implode(' ', $unions);
			$this->_data['unionsUseTables'] = $useTables;
		}

		$this->useTables = array_merge($this->useTables, $this->_data['unionsUseTables']);
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
		$table = $this->DB->key($this->_table(), true);

		$options = [];
		foreach ($this->options as $name => $option) {
			if ($option instanceof Param) {
				$options[$option->name] = $option->name;
			} else {
				$options[$name] = $option;
			}
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




		foreach ($this->columns as $name => $column) {
			if (!$column instanceof Param) {
				$column = new Param(['name' => empty($column['name']) ? $name : $column['name']] + $column);
			}
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
					$isFloatType = in_array($column->type, $floatType, true);
					$isIndexStringType = in_array($column->type, $indexStringType, true);
					$isindexBinaryType = in_array($column->type, $indexBinaryType, true);
					$isStringType = array_key_exists($column->type, $stringType);
					$isBinaryType = array_key_exists($column->type, $binaryType);
					if (in_array($column->type, ['bit', 'date', 'time', 'year', 'datetime', 'timestamp'], true)) {
						// 其他类型
						$value['type'] = $column->type;
						if ($column->length && !in_array($column->type, ['date', 'year'], true)) {
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

			} elseif ($column->type === 'bool') {
				$value['value'] = $this->DB->protocol() === 'mysql' ? (int) (bool) $column->value : (bool) $column->value;
			} elseif ($isIntegerType || $isFloatType) {
				$value['value'] = (int) $column->value;
			} elseif ($isStringType) {

			}  elseif ($isIndexStringType) {
				$value['value'] = $this->DB->value((string) $column->value);
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
				if ($value[$k] || ($value[$k] !== false && in_array($k, ['primary', 'unique', 'key', 'search'], true)) || in_array($k, ['value'], true)) {
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
			if ($value && !empty($options[$name]) && !in_array($name, ['primary', 'unique', 'key', 'search'], true)) {
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

		if ($exists) {
			$ifExists = false;
			foreach ($this->options as $name => $option) {
				if ($option instanceof Param) {
					if ($option->name === 'exists') {
						$ifExists = $option->value;
					}
				} elseif ($name === 'exists') {
					$ifExists = $option;
				}
			}
			if (!$ifExists) {
				$exists = '';
			}
		}
		return $this->_command(strtr($command, [':table' => $table, ':exists' => $exists]), 60);
	}





	public function insert() {
		$table = $this->DB->key($this->_table(), true);

		$options = [];
		foreach ($this->options as $name => $option) {
			if ($option instanceof Param) {
				$options[$option->name] = $option->value;
			} else {
				$options[$name] = $option;
			}
		}




		$documents = $defaultDocument = [];
		foreach ($this->documents as $values) {
			if ($values instanceof Param) {
				throw new Exception($this->documents, 'Documents can not be Param');
			}
			$document = [];
			foreach ($values as $name => $value) {
				if ($value instanceof Param) {
					if ($value->value === NULL) {
						continue;
					}
					if ($value->value instanceof Param) {
						throw new Exception($this->documents, 'Value can not be Param');
					} elseif ($value->value instanceof Cursor) {
						$execute = $value->value->arg('execute');
						$document[$value->name] = rtrim($value->value->execute(false)->select(), " \t\n\r\0\x0B;");
						$value->value->execute($execute);
					} else {
						$document[$value->name] = $value->expression ? $value->value : $this->DB->value($value->value);
					}
					$function = $value->function ? (empty(self::$_functions[$function = strtoupper($value->function)]) ? preg_replace('/[^A-Z]/', '', $function) : $function) : '';
					if ($function) {
						$document[$value->name] = $function . '('. $document[$value->name] .')';
					}
				} elseif ($value !== NULL) {
					$document[$name] = $this->DB->value($value);
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
		return $this->_command(strtr($command, [':ignore' => $this->_ignore(), ':table' => $table, ':column' => $column, ':document' => $documents]));
	}





	public function update() {


		if (!$this->documents) {
			throw new Exception($this->documents, 'Update can not be empty');
		}
		if (count($this->documents) > 1) {
			throw new Exception($this->documents, 'Can not update multiple');
		}


		$document = [];
		$documents = $this->documents;
		$documents = end($documents);
		foreach ($documents as $name => $value) {
			if ($value instanceof Param) {
				if ($value->value === NULL) {
					continue;
				}
				$name = $this->DB->key($value->name);
				if ($value->value instanceof SQLCursor) {
					$execute = $value->value->arg('execute');
					$data = '(' . rtrim($value->value->execute(false)->select(), " \t\n\r\0\x0B;") . ')';
					$value->value->execute($execute);
				} elseif ($value->expression) {
					$data = '('. $value->value .')';
				} else {
					$data = $this->DB->value($value->value);
				}

				$assignment = strtoupper($value->assignment);
				$assignment = empty(self::$_assignments[$assignment]) ? $assignment : self::$_assignments[$assignment];

				// 字段 + 运算符 + 值
				if (in_array($assignment, ['+', '-', '*', '/'], true)) {
					$column = $value->column ? $this->DB->key($value->column) : $name;
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
			} elseif ($value !== NULL) {
				$document[$this->DB->key($name, true)] = $this->DB->value($value);
			}
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
		$command = strtr($command, [':ignore' => $this->_ignore(), ':form' => $this->_from('UPDATE'), ':value' => $value, ':where' => $this->_where(), ':order' => $this->_order(), ':offset' => $this->_offset(), ':limit' => $this->_limit()]);
		return $this->_command($command);
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
			$this->cache[0] && !$this->_lock() && Cache::set($this->_data['select'], json_encode(['cache' => $this->cache] + $this->_data['selectReplaces']), get_class($this->cursor) . $this->DB->database(), $this->cache[0]);

			// 需要去全部行数的
			if ($this->_isCache && $this->_rows()) {
				$this->_isCache = false;
				$this->count();
				$this->_isCache = true;
			}
		}
		return $this->_data['select'];
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
			$this->cache[0] && Cache::set($this->_data['count'], json_encode(['cache' => $this->cache] + $this->_data['countReplaces']), get_class($this->cursor) . $this->DB->database(), $this->cache[0]);
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

	public function deleteCacheCount($refresh = NULL) {
		$this->cache[0] && Cache::delete(json_encode(['cache' => $this->cache, ':group' => $this->_group('COUNT'), ':form' => $this->_from('SELECT'), ':where' => $this->_where(), ':having' => $this->_having(), ':union' => $this->_union(), ':lock' => $this->_lock()]), get_class($this->cursor) . $this->DB->database(), $refresh === NULL ? $this->cache[1] : $refresh);
		$this->_data['count'] = false;
		return $this;
	}


	public function flush() {
		$this->_data = [];
	}
}