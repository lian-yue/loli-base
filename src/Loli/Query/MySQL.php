<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-02-19 07:58:07
/*
/* ************************************************************************** */
namespace Loli\Query;
class_exists('Loli\Query\Base') || exit;


class MySQL extends Base{

	// mysql 引擎
	const ENGINE_INNODB = 'InnoDB';

	const ENGINE_MYISAM = 'MyISAM';

	const ENGINE_MEMORY = 'Memory';

	const ENGINE_ARCHIVE = 'Archive';


	public function create(array $array, $table, $engine = self::ENGINE_INNODB) {
		if (!$array || !($table = $this->key($table))) {
			return false;
		}
		$engine = $engine ? $engine : self::ENGINE_INNODB;

		$key = $unique = $primary = [];

		$q ='CREATE TABLE ';

		$q .= $table . ' (' ."\n";
		foreach ($array as $k => $v) {
			if (!($k = $this->key($k)) || !$v || !is_array($v) || empty($v['type'])) {
				return false;
			}
			$unsigned = !empty($v['unsigned']);
			$q .=  $k . ' ';
			if ($v['type'] == 'bool') {

				// 布尔值
				$q .= 'tinyint(4)';
			} elseif ($v['type'] == 'int') {
				// 整数类型
				$length = empty($v['length']) ? 4 : intval($v['length']);
				if ($length == 1) {
					$q .= 'tinyint(' . ($unsigned ? 3 : 4) . ')';
				} elseif ($length == 2) {
					$q .= 'smallint(' . ($unsigned ? 5 : 6) . ')';
				} elseif ($length == 3) {
					$q .= 'mediumint(' . ($unsigned ? 8 : 9) . ')';
				} elseif ($length == 4) {
					$q .= 'int(' . ($unsigned ? 10 : 11) . ')';
				} else {
					$q .= 'bigint(20)';
				}
			} elseif (in_array($v['type'], ['double', 'float', 'decimal'])) {

				// 浮点类型
				$q .=  $v['type'];
				if (!empty($v['length'])) {
					$length = is_array($v['length']) ? array_slice($v['length'] , 0, 2): explode(',', $v['length'], 2);
					$length = array_map('intval', $length);
					$q .= '('. implode(',', $length) .')';
				}
			} elseif (in_array($v['type'], ['char', 'varchar']) || ($v['type'] == 'text' && ((!empty($vv['length']) && $vv['length'] <= 255) || isset($vv['primary']) || !empty($vv['unique']) || !empty($vv['key'])))) {
				// 能索引的字符串
				$v['type'] == 'text' ? 'varchar' : $v['type'];
				$q .=  $v['type'];
				if (!empty($v['length'])) {
					$q .= '('. intval($v['length']) .')';
				} else {
					$q .= '(255)';
				}
			} elseif(in_array($v['type'], ['date', 'time', 'year', 'datetime', 'timestamp'])) {
				$q .=  $v['type'];
			} elseif ($v['type'] == 'text') {

				// 不能索引的字符串
				$length = empty($v['length']) ? 0 : intval($v['length']);
				if ($length <= 65535) {
					$q .= 'text';
				} elseif ($length <= 16777215) {
					$q .= 'mediumtext';
				} else {
					$q .= 'longtext';
				}
			} else {
				return false;
			}

			if (!empty($v['charset'])) {
				' CHARACTER SET ' . preg_replace('/[^0-9a-z_]/i', '', $v['charset']);
			}

			if ($v['type'] != 'text') {
				// 不能负数
				if ($unsigned && in_array($v['type'], ['double', 'float', 'int'])) {
					$q .= ' unsigned ';
				}

				// 不能为空
				if (!isset($v['null']) || !$v['null']) {
					$q .= ' NOT NULL ';
				}

				if (!empty($v['increment']) && $v['type'] == 'int') {
					$q .= ' AUTO_INCREMENT ';
				} else {
					if (!isset($v['default'])) {
						if (in_array($v['type'], ['int', 'bool', 'double', 'float', 'time', 'year'])) {
							$v['default'] = 0;
						} elseif ($v['type'] == 'date') {
							$v['default'] = '0000-00-00';
						} elseif (in_array($v['type'], ['timestamp', 'datetime'])) {
							$v['default'] = '0000-00-00 00:00:00';
						} else {
							$v['default'] = '';
						}
					}
					$q .= ' DEFAULT ' . $this->escape($v['default']) . ' ';
				}

				// 主要字段
				if (isset($v['primary'])) {
					$primary[$k] = $v['primary'];
				}


				// 约束
				if (!empty($v['unique']) && is_array($v['unique'])) {
					foreach ($v['unique'] as $kk => $vv) {
						$unique[$kk][$vv][] = $k;
					}
				}

				// 索引
				if (!empty($v['key']) && is_array($v['key'])) {
					foreach ($v['key'] as $kk => $vv) {
						$key[$kk][$vv][] = $k;
					}
				}
			} else {
				// 不能为空
				if (!isset($v['null']) || !$v['null']) {
					$q .= ' NOT NULL ';
				}
			}

			$q .= ',' ."\n";
		}

		// 主要字段
		if ($primary) {
			asort($primary, SORT_NUMERIC);
			$q .= 'PRIMARY KEY (' . implode(',',  array_keys($primary)) . '),' ."\n";
		}

		// 约束
		if ($unique) {
			foreach ($unique as $k => $v) {
				if (!$k = $this->key($k)) {
					continue;
				}
				ksort($v, SORT_NUMERIC);
				$arr = [];
				foreach ($v as $vv) {
					foreach ($vv as $vvv) {
						$arr[] = $vvv;
					}
				}
				$q .= 'UNIQUE KEY '. $k .'('. implode(',', $arr)  .'),' ."\n";
			}
		}

		// 索引
		if ($key) {
			foreach ($key as $k => $v) {
				if (!$k = $this->key($k)) {
					continue;
				}
				ksort($v, SORT_NUMERIC);

				$arr = [];
				foreach ($v as $vv) {
					foreach ($vv as $vvv) {
						$arr[] = $vvv;
					}
				}
				$q .= 'KEY '. $k .'('. implode(',', $arr)  .'),' ."\n";
			}
		}

		$q = rtrim($q, ", \n") . "\n" . ')';

		// 数据库引擎
		$q .= ' ENGINE=' . $this->escape($engine) . ' DEFAULT CHARSET=utf8;';
 		return $q ;
	}




	public function get(array $query, $table, $fields = ['*'], $logical = 'AND') {
		if (!$query || !$table) {
			return false;
		}
		$having = [];
		if (!empty($fields)) {
			$a = [];
			foreach ((array) $fields as $k => $v) {
				if (!$v) {
					continue;
				}
				if (!is_string($k)) {
					if (is_string($v)) {
						$a[] = $v;
					}
				} elseif (is_string($v)) {
					$a[] =  $v .' AS ' . $k;
				} else {
					$vv =  $v['column'];
					if (!empty($v['function'])) {
						$function = strtoupper($v['function']);
						$vv = $function . '('. $vv .')';
						if (in_array($function, ['SUM','MIN','MAX','AVG','COUNT'])) {
							$having[] = $k;
						}
					}
					$a[] = $vv .' AS ' . $k;
				}
			}
			$fields = $a ? $a : ['*'];
		} else {
			$fields = ['*'];
		}


		// 唯一值ID
		$groupby = [];
		if (!empty($query['$groupby'])) {
			foreach (is_array($query['$groupby']) ? $query['$groupby'] : explode(',', $query['$groupby']) as $v) {
				if ($v = $this->key($v)) {
					$groupby[] = $v;
				}
			}
		}
		$groupby = implode(',', $groupby);
		$count = !empty($query['$count']) && $query['$count'] === true;

		// 创建查询字符串
		$q = '';

		// 语句信息
		$q .= 'SELECT ';




		// 是否记录查询 数量
		if ($count) {
			$q .= 'COUNT(' . ($groupby ? 'DISTINCT ' . $groupby : '*') . ') AS count';
		} elseif (!empty($query['$foundRows']) && $query['$foundRows'] === true) {
			$q .= ' SQL_CALC_FOUND_ROWS ' . implode(', ', $fields) . ' ';
		} else {
			$q .= ' ' . implode(', ', $fields) . ' ';
		}

		$q .= $this->_from($table);

		$q .= $this->_where($query, $logical, $having);

		if (!$count) {
			$q .= $groupby ? 'GROUP BY '.$groupby.' ' : '';

			$q .= $this->_having($query, $logical, $having);

			$q .= $this->_orderby($query);

			$q .= $this->_limit($query);

			$q .= $this->_offset($query);
		} else {
			$q .= $this->_having($query, $logical, $having);
		}
		++$this->count;
		return $q;
	}





	public function insert(array $documents, $table) {
		return $this->_insertAndReplace($documents, $table);
	}

	public function replace(array $documents, $table) {
		return $this->_insertAndReplace($documents, $table, true);
	}

	public function update(array $document, array $query, $table, $logical = 'AND') {
		if (!$from = $this->_from($table, false)) {
			return false;
		}

		// 多个表请求
		$use = '';
		if (is_array($table)) {
			$use = [];
			foreach ($table as $k => $v) {
				if (!isset($v['update']) || $v['update'] !== false) {
					$use[] = $k;
				}
			}
			$use = implode(', ', $use);
		}

		$arrays = [];
		foreach ($this->parse($document) as $param) {
			if ($param instanceof Option || $param->value === NULL || !($column = $this->key($param->column))) {
				continue;
			}
			$compare = strtoupper($param->compare);
			$value = $this->escape($param->value);
			
			// 字段 + 运算符 + 值
			if (in_array($compare, ['+', '-', '+$', '-$'])) {
				$arrays[$column] = $column .' '. $compare{0} .' '. $value;
				continue;
			}

			// 值 + 运算符 + 字段
			if (in_array($compare, ['$+', '$-'])) {
				$arrays[$column] = $value .' '. $compare{1} .' '. $column;
				continue;
			}

			// 字符串连接
			if (in_array($compare, ['CONCAT', '.', '.$', '$.'])) {
				$arrays[$column] = 'CONCAT('. ($compare == '$.' ? $column .', '. $value : $column .', '. $value) .')';
				continue;
			}

			// 替换
			if ($compare == 'REPLACE') {
				$arrays[$column] =  'REPLACE('. $column .', '. $this->escape($param->search) .', '. $value .')';
				continue;
			}
			$arrays[$column] = $value;
		}

		if (!$arrays) {
			return false;
		}
		$document = [];
		foreach ($arrays as $column => $value) {
			$document[] = $column . ' = ' . $value;
		}

		$q = 'UPDATE ';

		$q .= $use;

		$q .= $from;

		$q .= 'SET ';

		$q .= implode(' , ', $document);

		$q .= $this->_where($query, $logical);

		$q .= $this->_orderby($query);

		$q .= $this->_limit($query);

		return $q;
	}


	/**
	* 数据库 删除 (字符串)
	*
	* 1 参数 数组 where 详情请见 下面 where_query 函数
	* 2 参数 数组 ['table'] = 表名称, ['logical'] = 运算符
	*
	* 返回值 数据库查询 字符串
	**/
	public function delete(array $query, $table, $logical = 'AND') {
		if (!$from = $this->_from($table)) {
			return false;
		}

		// 多个表请求
		$use = '';
		if (is_array($table)) {
			$use = [];
			foreach ($table as $k => $v) {
				if (!isset($v['delete']) || $v['delete'] !== false) {
					$use[] = $k;
				}
			}
			$use .= implode(',', $use);
		}

		$q = 'DELETE ';

		$q .= $use;

		$q .= $from;

		$q .= $this->_where($query, $logical);

		$q .= $this->_orderby($query);

		$q .= $this->_limit($query);

		return $q;
	}



	public function escape($value) {
		if (is_array($value) || is_object($value)) {
			$value = json_encode($value);
		}
		if ($value === false) {
			$value = 0;
		} elseif ($value === true) {
			$value = 1;
		} elseif (!is_int($value) && !is_float($value)) {
			$value = addslashes(stripslashes(addslashes($value)));
			$value = '\''. $value .'\'';
		}
		return $value;
	}

	public function key($value, $asterisk = false) {
		if (!$value || !preg_match('/^(?:([a-z_][0-9a-z_]*)\.)?([a-z_][0-9a-z_]*|\*)$/i', trim($value), $matches)) {
			return false;
		}
		if ($matches[2] == '*' && !$asterisk) {
			return false;
		}
		return ($matches[1] ? '`'. $matches[1]. '`.' : '') . ($matches[2] == '*' ? $matches[2] : '`'. $matches[2] .'`');
	}



	private function _insertAndReplace(array $documents, $table, $set = false) {
		if (!$table = $this->key($table)) {
			return false;
		}


		$arrays = [];
		foreach ($documents as $key => $document) {
			$array = [];
			// 单个的
			if (is_int($key) || !is_array($document)) {
				foreach ($this->parse($documents) as $param) {
					if ($param instanceof Option || $param->value === NULL) {
						continue;
					}
					$array[$param->column] = $this->escape($param->value);
				}
				if (!$array) {
					return false;
				}
				$arrays = [$array]; 
				break;
			}

			// 多个的
			foreach ($this->parse($document) as $param) {
				if ($param instanceof Option || $param->value === NULL) {
					continue;
				}
				$array[$param->column] = $this->escape($param->value);
			}
			
			if (!$array) {
				return false;
			}
			ksort($array);
			
			// 判断多个的健名 是否一致
			if (isset($keys)) {
				if (array_keys($array) !== $keys) {
					return false;
				}
			} else {
				$keys = array_keys($array);
			}
			$arrays[] = $array;
		}


		$head = [];
		foreach (end($arrays) as $column => $value) {
			if (!$column = $this->key($column)) {
				return false;
			}
			$head[] = $column;
		}

		$body = [];
		foreach ($arrays as $array) {
			$body[] = '('. implode(',', $array) . ')';
		}


		$q = ($set ? 'REPLACE' : 'INSERT');

		$q .= ' INTO ';

		$q .= $table;

		$q .= ' (' . implode(',',  $head) . ') ';

		$q .= ' VALUES ' . implode(', ', $body);

		return $q;
	}



	/**
	*	from 选取
	*
	*	1 参数 选项数组
	*
	*	返回值true 或者 false
	**/
	private function _from($table, $from = true) {
		

		$arrays = [$from ? 'FROM' : ''];

		// 表名
		if (is_array($table)) {
			foreach ($table as $key => $value) {
				if (empty($value['name'])) {
					return false;
				}
				if (!$name = $this->key($value['name'])) {
					return false;
				}

				if ($i && empty($value['on'])) {
					$type = empty($value['type']) ? 'INNER JOIN' : preg_replace('/[^A-Z ]/', '', strtoupper($value['type']));
					$arrays[] = strpos($type, ' JOIN') ? $type : $type . ' JOIN';

					// on 的
					if (!empty($value['on'])) {

						if (is_array($value['on'])) {
							$tmp = '';
							$ii = 0;
							foreach ($value['on'] as $vv) {
								if ($ii) {
									$tmp .= ($ii % 2) == 0 ? ' AND ' : ' = ';
								}
								if (!$vv = $this->key($vv)) {
									return false;
								}
								$tmp .= $vv;
								$ii++;
							}
							$value['on'] = $tmp;
						} else {
							$arrays[] = $value['on'];
						}
					}
				}
			}
		} else {
			if (!$table = $this->key($table)) {
				return false;
			}
			$arrays[] = $table;
		}








		// 多个表
		if (is_array($table)) {
			$arrays = [];
			$i = 0;
			foreach ($table as $key => $value) {
				// 无效的 table
				if (!$table || !is_array($table) || empty($table['name']) || !($k = $this->key($k))) {
					return false;
				}

			}
		}

		// 多个表
		if (is_array($table)) {
			$q = $from ? ' FROM ' : ' ';
			$i = 0;
			foreach ($t as $k => &$table) {
				// 无效的 table
				if (!$table || !is_array($table) || empty($table['name']) || !($k = $this->key($k))) {
					return false;
				}

				// 关联方方式 第一个 无效
				if ($i) {
					$table['type'] = empty($table['type']) ? 'INNER JOIN' : (trim($table['type']) == ',' ? ',' : preg_replace('/[^ A-Z]/', '', strtoupper($table['type'])));

					// on 的
					if (!empty($table['on']) && is_array($table['on'])) {
						$tmp = '';
						$ii = 0;
						foreach ($table['on'] as $vv) {
							if ($ii) {
								$tmp .= ($ii % 2) == 0 ? ' AND ' : ' = ';
							}
							if (!$vv = $this->key($vv)) {
								return false;
							}
							$tmp .= $vv;
							$ii++;
						}
						$table['on'] = $tmp;
					}
				} else {
					$table['type'] = $table['on'] = NULL;
				}

				if (!empty($table['type'])) {
					$q .= ' '. $table['type'] . ' ';
				}

				$q .= ' '.$table['name'].' AS '.$k.' ';
				if (!empty($table['on'])) {
					$q .= ' ON ' . $table['on'] . ' ';
				}
				$i++;
			}

		} else {
			$q = $from ? ' FROM ' . $t . ' ' : ' ' . $t . ' ';
		}

		++$this->count;
		return $q;
	}


	private function _limit($query) {
		if (empty($query['$limit'])) {
			return false;
		}
		return ' LIMIT ' . intval($query['$limit']) . ' ';
	}


	private function _offset($query) {
		if (empty($query['$offset'])) {
			return false;
		}
		return ' OFFSET ' . intval($query['$offset']) . ' ';
	}


	/**
	*	排序方式
	*
	*
	**/
	private function _orderby(array $query) {
		if (empty($query['$orderby'])) {
			return false;
		}
		$query = $this->parse(array_intersect_key($query, ['$orderby' => '', '$order' => '']));

		if (empty($query['$orderby'])) {
			return false;
		}

		// 排序
		$a = [];
		foreach ($query['$orderby'] as $k => $v) {
			foreach ($v->column as $kk => $vv) {
				if (!$vv = $this->key($vv)) {
					unset($v->column[$kk]);
					continue;
				}
				$v->column[$kk] = empty($v->function[$kk]) ? $vv : $v->function[$kk] .'(' . $vv .')';
			}
			if (!$v->column) {
				continue;
			}

			// 自定义排序
			if (isset($v->field)) {
				$v->field = array_map([$this, 'escape'], array_unnull((array) $v->field));
			} else {
				$v->field = [];
			}
			$a[] = ($v->field? 'FIELD(' . reset($v->column) . ',' . explode(',', $v->field) . ')' : implode(', ', $v->column)) . ' ' . ($v->desc ? 'DESC' : 'ASC');
		}
		return $a ? ' ORDER BY ' . implode(', ', $a) . ' ' : '';
	}





	private function _whereHavingEscape($value, $escape = true) {
		return $escape ? $this->escape($value) : $value;
	}

	/**
	* 数据库 遍历
	*
	* 1 参数 查询数组
	* 2 参数 运算符
	* 3 参数 查询类型 WHERE HAVING 或者 空
	* 4 参数 查询类型 having 的字段
	*
	* compare 有 BETWEEN, IN, = ,< , >, != .........
	*
	* value 如果是 数组 没 type 就 type = IN
	*
	* 返回值 数据库查询 字符串
	**/
	private function _whereHaving(array $query, $logical = 'AND', $type = 'WHERE', $having = []) {
		if ($type) {
			$type = strtoupper($type);
			$type == 'HAVING' ? 'HAVING' : 'WHERE';
		}

		// 逻辑运算符
		$logical = strtoupper($logical);
		$logical = in_array($logical, ['AND', 'OR', 'XOR']) ? $logical : 'AND';

 		// 整理数据
		$_false = false;

		$objects = [];
		foreach ($this->parse($query) as $k => $v) {
			if ($k && $k{0} == '$') {
				continue;
			}
			$v = clone $v;

			// 过滤 key
			if (!$v->column = $this->key($v->column)) {
				$_false = true;
				break;
			}

			// 过滤类型
			if ($type) {
				$v->having = (isset($v->having) && $v->having === true) || in_array($v->column, $having);

				// WHERE 模式过滤 HAVING
				if ($type == 'WHERE' && $v->having) {
					continue;
				}
				// HAVING 模式过滤 WHERE
				if ($type == 'HAVING' && !$v->having) {
					continue;
				}
			}

			// not 操作符
			$v->not = empty($v->not) ? '' : 'NOT';

			// 是否转义
			$v->escape = !isset($v->escape) || $v->escape !== false;

			// 运算符
			$v->compare = empty($v->compare) ? (isset($v->compare) && $v->compare === false ? false : '') : strtoupper($v->compare);

			// 自动 IN 运算符
			if (!$v->compare && $v->compare !== false && is_array($v->value)) {
				$v->compare = 'IN';
			}

			// IN 运算符
			if ($v->compare == 'IN') {
				if (!is_array($v->value) || count($v->value = array_unique(array_unnull($v->value))) == 1) {
					$v->compare = '';
					$v->value = is_array($v->value) ? end($v->value) : $v->value;
				} elseif (!$v->value) {
					$_false = true;
					break 2;
				}
			}

			// BETWEEN 运算符
			if ($v->compare == 'BETWEEN') {
				if (!is_array($v->value) || (count($v->value = array_values($v->value)) == 2 && $v->value[1] === $v->value[0])) {
					$v->compare = '';
					$v->value = is_array($v->value) ? end($v->value) : $v->value;
				} elseif (!$v->value || isset($v->value[2])) {
					$_false = true;
					break 2;
				}
			}

			// 搜索
			if ($v->compare == 'SEARCH') {
				$search = $this->search($v->value);
				if ($search = $search->get()) {
					foreach ($search as $kk => $vv) {
						foreach ($vv as $vvv) {
							$objects[] = (object) ['column' => $v->column, 'not' =>  $kk == '-' ? 'NOT' : '', 'compare' => 'LIKE', 'value' => '%' . addcslashes($vvv, '_%\\') . '%', 'escape' => true];
						}
					}
				}
				continue;
			}


			$v->compare = $v->compare ? $v->compare :($v->compare === false ? '' : '=');
			$objects[] = $v;
		}




		if ($_false) {
			return ' '. $type .' 1 = 2 ';
		}



		$arrays = [];
		foreach ($objects as $v) {
			// 回调
			if ($v->compare == 'CALL') {
				$arrays[] = $v->not .' (' . $this->_whereHaving($v->value, empty($v->logical) ? 'OR' : $v->logical, '', $having) . ')';
				continue;
			}


			// BETWEEN 运算符
			if ($v->compare == 'BETWEEN') {
				if (!isset($v->value[1])) {
					$arrays[] = $v->column .' ' . ($v->not ? '<' : '>=') . ' ' . $this->_whereHavingEscape($v->value[0], $v->escape);
				} elseif (!isset($v->value[0])) {
					$arrays[] = $v->column .' ' . ($v->not ? '>' : '<=') . ' ' . $this->_whereHavingEscape($v->value[1], $v->escape);
				} else {
					if (($v0 = $this->_whereHavingEscape($v->value[0], $v->escape)) === ($v1 = $this->_whereHavingEscape($v->value[1], $v->escape)))  {
						$arrays[] = $v->column . ' '. $v->not .' = ' . $v0;
					} else {
						$arrays[] = $v->column . ' '. $v->not .' '. $v->compare . ' ' . $v0 . ' AND ' . $v1;
					}
				}
				continue;
			}


			// IN 运算符
			if ($v->compare == 'IN') {
				$in = [];
				foreach ($v->value as $vvv) {
					$in[] = $this->_whereHavingEscape($vvv, $v->escape);
				}
				$arrays[] = $v->column . ' '. $v->not . ' ' . $v->compare . ' ('. implode(',', $in) .') ';
				continue;
			}

			// REGEX 运算符
			if ($v->compare == 'REGEX' || $v->compare == 'REGEXP') {
				$v->binary = false;
				if (preg_match('/^\/(.*)\/(\w*)$/is', $v->value, $matches)) {
					$v->value = strtr($matches[1], ['\/' => '/']);
					$v->binary = strpos($matches[2], 'i') === false ? 'BINARY' : '';
				}
				$arrays[] = $v->column .' '. $v->not .' REGEXP '. $v->binary .' ' . $this->_whereHavingEscape($v->value, $v->escape);
				continue;
			}


			// 全文索引
			if ($v->compare == 'MATCH' || $v->compare == 'TEXT') {
				$v->mode = empty($v->mode) ? '' : strtoupper(' ' . $v->mode);
				$arrays[] = 'MATCH('. $v->column .') AGAINST('. $this->_whereHavingEscape(is_array($v->value) ? implode(' +', $v->value) : $v->value, $v->escape) . $v->mode .')';
				continue;
			}

			// 其他
			$arrays[] = $v->column .' '. $v->not .' '. $v->compare .' ' . $this->_whereHavingEscape($v->value, $v->escape);
		}

		if (!$arrays) {
			return ' ';
		}
		return ' ' . $type . ' ' . implode(' '. $logical .' ', $arrays) . ' ';
	}



	private function _having(array $query, $logical = 'AND', array $having = []) {
		return $this->_whereHaving($query, $logical, 'HAVING', $having);
	}


	private function _where(array $query, $logical = 'AND', array $having = []) {
		return $this->_whereHaving($query, $logical, 'WHERE', $having);
	}
}