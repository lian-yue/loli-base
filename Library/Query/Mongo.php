<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-11-05 05:03:53
/*	Updated: UTC 2015-01-20 05:53:18
/*
/* ************************************************************************** */
namespace Loli\Query;
class_exists('Loli\Query\Base') || exit;
class Mongo extends Base{
	private $_logical = [
		'AND' => '$and',
		'OR' => '$or',
		'XOR' => '$nor',
	];


	private $_compare = [
		'=' => '$eq',
		'!=' => '$ne',
		'<>' => '$ne',
		'>' => '$gt',
		'>=' => '$gte',
		'=>' => '$gte',
		'<' => '$lt',
		'<=' => '$lte',
		'=<' => '$lte',
		'IN' => '$in',
		'REGEXP' => '$regex',
	];


	public function create($a, $table, $engine = false) {
		if (!$table = $this->key($table)) {
			return false;
		}
		$key = $unique = $primary = [];
		foreach ($a as $k => $v) {
			if (!($k = $this->key($k)) || !$v || !is_array($v)) {
				return false;
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
		}


		$r['collection'] = $table;
		$r['indexes'] = [];
		$r['command'] = 'create';



		// 主要字段
		if ($primary) {
			asort($primary, SORT_NUMERIC);
			foreach ($primary as $k => $v) {
				$primary[$k] = 1;
			}
			if (count($primary) != 1 || empty($primary['_id'])) {
				$r['indexes'][] = ['key' => $primary, 'name' => 'primary', 'unique' => true, 'background' => true];
			}
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
						$arr[$vvv] = 1;
					}
				}
				$r['indexes'][] = ['key' => $arr, 'name' => $k, 'unique' => true, 'background' => true];
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
						$arr[$vvv] = 1;
					}
				}
				$r['indexes'][] = ['key' => $arr, 'name' => $k, 'background' => true];
			}
		}

		return $r;
	}

	public function drop($table) {
		if (!$table = $this->key($table)) {
			return false;
		}
		return ['collection' => $table, 'command' => 'drop'];
	}




	public function add($array, $table) {
		return $this->_addSet($array, $table, false);
	}



	public function set($array, $table) {
		return $this->_addSet($array, $table, true);
	}



	public function get($query, $table, $fields = ['*'], $logical = 'AND') {
		if (!is_array($query) || !($args['collection'] = $this->key($table))) {
			return false;
		}
		if (($args['query'] = $this->_query($query)) === false) {
			return false;
		}
		// 表头 不支持 重命名
		$args['fields'] = $function = [];
		if (!in_array('*', $fields)) {
			foreach ($fields as $k => $v) {
				if (is_string($v)) {
					$args['fields'][$v] = true;
				} elseif (!isset($v['function']) && isset($v['column'])) {
					$args['fields'][$v['column']] = true;
				} elseif (isset($v['function']) && isset($v['column'])) {
					$function[$k] = $v;
				}
			}
		}


		// 表分组
		$groupby = [];
		foreach ( empty($query['$groupby']) ? [] : (is_array($query['$groupby']) ? $query['$groupby'] : explode(',', $query['$groupby'])) as $v) {
			if ($v = $this->key($v)) {
				$groupby[] = $v;
			}
		}
		$groupby = array_unique($groupby);

		// 开始排序什么的
		if (!empty($query['$offset']) &&  ($skip = intval($query['$offset']))) {
			$args['skip'] = $skip;
		}
		if ($limit = $this->_limit($query)) {
			$args['limit'] = $limit;
		}
		if ($sort = $this->_sort($query)) {
			$args['sort'] = $sort;
		}
		if (isset($query['$found_rows']) && $query['$found_rows'] === true) {
			$args['found_rows'] = true;
		}

		// 分组的
		if ($groupby && empty($args['limit']) && empty($args['skip']) && !in_array('_id', $groupby) && !array_diff($groupby, array_keys($args['fields']))) {
			$args['command'] = 'group';
			$args['finalize'] = $args['key'] = $args['$reduce'] = $args['initial'] = [];
			foreach ($groupby as $v) {
				$args['key'][$v] = 1;
			}
			foreach ($function as $k => $v) {
				$v['function'] = strtoupper($v['function']);
				if (!$k = $this->key($kk = is_int($k) ? $v['column'] : $k)) {
					if ($kk != '*' || $v['function'] != 'COUNT') {
						continue;
					}
					$k = 'COUNT(*)';
				}
				if ($v['column'] != '*' && !($v['column'] = $this->key($v['column']))) {
					continue;
				}
				$args['initial'][$k] = 0;
				if ($v['function'] == 'COUNT') {
					// 数量
					$args['$reduce'][] = $v['column'] == '*' ? 'prev[\''.$k.'\']++;' : 'if (obj[\''. $v['column'] .'\'] != undefined){ prev[\''.$v['column'].'\']++; }';
				} elseif ($v['function'] == 'SUM') {
					// 相加
					$args['$reduce'][] = 'prev[\''.$k.'\'] += obj[\''. $v['column'] .'\'];';
				} elseif ($v['function'] == 'MAX') {
					// 最大
					$args['$reduce'][] = 'if (prev[\''.$k.'\'] < obj[\''.$v['column'].'\']) { prev[\''.$k.'\'] = obj[\''.$v['column'].'\']; }';
				} elseif ($v['function'] == 'MIN') {
					// 最小
					$args['$reduce'][] = 'if (prev[\''.$k.'\'] > obj[\''.$v['column'].'\']) { prev[\''.$k.'\'] = obj[\''.$v['column'].'\']; }';
				} elseif ($v['function']  == 'AVG') {
					// 平均
					$args['initial'][$k] = ['sum' => 0, 'count' => 0];
					$args['$reduce'][] = 'prev[\''.$k.'\'][\'sum\'] += obj[\''. $v['column'] .'\']; prev[\''. $k .'\'][\'count\']++;';
					$args['finalize'][] = 'prev[\''.$k.'\'] = prev[\''.$k.'\'][\'sum\'] / prev[\''.$k.'\'][\'count\'];';
				} else {
					return false;
				}
			}
			$args['ns'] = $args['collection'];
			$args['cond'] = $args['query'];
			$args['$reduce'] = $args['$reduce'] ? 'function(obj, prev){'. implode("\n", $args['$reduce']) .'}' : '';
			$args['finalize'] = $args['finalize'] ? 'function(prev){'. implode("\n", $args['finalize']) .'}' : '';
			return array_intersect_key($args, ['command' => '', 'ns' => '', 'key' => '', 'initial' => '', '$reduce' => '', '$keyf' => '', 'cond' => '', 'finalize' => '', 'sort' => '']);
		}

		// 带有函数并且 没 统计所有数量的
		/*if (($function || ($groupby && !in_array('_id', $groupby))) && empty($args['found_rows']) ) {


			$pipeline = [];
			if (!empty($args['fields'])) {
				$pipeline['$project'] = $args['fields'];
			}
			if (!empty($args['query'])) {
				$pipeline['$match'] = $args['query'];
			}
			if (!empty($args['sort'])) {
				$pipeline['$sort'] = $args['sort'];
			}
			if (!empty($args['skip'])) {
				$pipeline['$skip'] = $args['skip'];
			}
			if (!empty($args['limit'])) {
				$pipeline['$limit'] = $args['limit'];
			}

			foreach ($groupby as $v) {
				$pipeline['$group']['_id'][$v] = '$'. $v;
			}
			foreach ($function as $k => $v) {
				$v['function'] = strtolower($v['function']);
				if (!$k = $this->key($kk = is_int($k) ? $v['column'] : $k)) {
					if ($kk != '*' || $v['function'] != 'COUNT') {
						continue;
					}
					$k = 'COUNT(*)';
				}
				if ($v['column'] != '*' && !($v['column'] = $this->key($v['column']))) {
					continue;
				}
				$pipeline['$group'][$k] = ['$'. $v['function'] => $v['column'] == '*' ? '_id':  $v['column']];
			}

			$args['aggregate'] = $args['collection'];
			$args['pipeline'] = $pipeline;

			return ['command' => 'aggregate'] + array_intersect_key($args, ['aggregate' => '', 'pipeline' => '']);
		}
		return ['command' => 'find'] + $args;*/
	}







	public function update($array, $query, $table, $logical = 'AND') {
		if (!is_array($array) || !is_array($query) || !($table = $this->key($table))) {
			return false;
		}

		$document = [];
		foreach ($array as $k => $v) {
			if ($v === null) {
				continue;
			}
			if (!is_object($v) || empty($v->{'$object'}) || $v->{'$object'} !== true) {
				$v = (object)['value' => $v];
			}
			$v->column = $this->key(empty($v->column) ? $k : $v->column);
			if (!$v->column) {
				continue;
			}
			$v->compare = empty($v->compare) ? '' : strtoupper($v->compare);

			// 字段 + 运算符 + 值
			if (in_array($v->compare, ['+', '-', '+$', '-$'])) {
				$document['$inc'][$v->column] = $v->compare{0} == '-' ? 0 - $v->value : $v->value;
				continue;
			}

			// 移除字段
			if ($v->compare === '$unset') {
				$document['$unset'][$v->column] =  '';
				continue;
			}

			// 其他的
			if ($v->compare && $v->compare{0} == '$') {
				$document['$unset'][$v->column] = $v->value;
			}

			$document['$set'][$v->column] = $v->value;
		}
		if (!$document) {
			return false;
		}
		return ['collection' => $table, 'updates' => [['q' => $this->_query($query, $logical), 'u' => $document,'multi' => $this->_limit($query) != 1]], 'command' => 'update'];
	}



	public function delete($query, $table, $logical = 'AND') {
		if (!$table = $this->key($table)) {
			return false;
		}
		return ['collection' => $table, 'deletes' => [['q' => $this->_query($query, $logical)]], 'command' => 'delete'];
	}


	public function escape($value) {
		if (!is_object($value)) {
			return $value;
		}
		if (isset($value->{'$object'})) {
			unset($value->{'$object'});
		}
		return $value;
	}

	public function key($value) {
		if (!$value || !is_string($value) || is_numeric($value) || trim($value) != $value || strpos($value, '$') !== false) {
			return false;
		}
		if ($value{0} == '.' || substr($value, -1, 1) == '.') {
			return false;
		}
		return $value;
	}

	private function _limit($query) {
		if (empty($query['$limit'])) {
			return false;
		}
		return intval($query['$limit']);
	}


	private function _addSet($a, $t, $set = false) {
		if (!($t = $this->key($t)) || !is_array($a)) {
			return false;
		}

		$documents = [];
		foreach ($a as $k => $v) {
			// 单个的
			if (!is_numeric($k) || !is_array($v)) {
				if ($documents || !($a = array_unnull($a))) {
					return false;
				}
				ksort($a);
				foreach ($a as $kk => $vv) {
					if (!$kk = $this->key($kk)) {
						return false;
					}
					$documents[0][$kk] = $vv;
				}
				break;
			}

			// 多个的
			ksort($v);
			if (!$v = array_unnull($v)) {
				return false;
			}
			foreach ($v as $kk => $vv) {
				if (!$kk = $this->key($kk)) {
					return false;
				}
				$documents[$k][$kk] = $vv;
			}
		}

		return ['collection' => $t, 'documents' => $documents, 'command' => $set ? 'replace' : 'insert'];
	}



	private function _query($query = [], $logical = 'AND', $having = []) {
		if (!$query) {
			return [];
		}

		// 逻辑运算符
		$logical = trim($logical);
		$logical = in_array(strtolower($logical), $this->_logical) ? strtolower($logical) : (empty($this->_logical[$logical = strtoupper($logical)]) ? reset($this->_logical) : $this->_logical[$logical]);


		$_false = false;
		$arrays = [];
		foreach ($this->parse($query) as $k => $v) {
			if ($k && $k{0} == '$') {
				continue;
			}
			$v = clone $v;

			if (!isset($v->value)) {
				continue;
			}

			// 过滤数字 key
			if ($v->column && !($v->column = $this->key($v->column))) {
				$_false = true;
				break;
			}

			// not 操作符
			$v->not = !empty($v->not);

			// 运算符
			$v->compare = empty($v->compare) ? (isset($v->compare) && $v->compare === false ? false : '') : ($v->compare{0} == '$' ? '$' . preg_replace('/[^a-zA-Z]/', '', $v->compare) : (empty($this->_compare[$compare = trim(strtoupper($v->compare))]) ? '$' . preg_replace('/[^a-zA-Z]/', '', $compare == trim($v->compare) ? strtolower($compare) : $v->compare) : $this->_compare[$compare]));

			// 是否转义
			$v->escape = !isset($v->escape) || $v->escape !== false;

			// 自动 IN 运算符
			if (!$v->compare && $v->compare !== false && is_array($v->value)) {
				$v->compare = '$in';
			}

         	 // IN 运算符
			if (in_array($v->compare, ['$in', '$nin'])) {
				if (!is_array($v->value) || count($v->value = array_unique(array_unnull($v->value))) == 1) {
					$v->compare = '';
					$v->value = is_array($v->value) ? end($v->value) : $v->value;
				} elseif (!$v->value) {
					$_false = true;
					break 2;
				}
				if ($v->not) {
					$v->not = false;
					$v->compare = $v->compare == '$nin' ? '$in' : '$nin';
				}
			}


			// BETWEEN 运算符
			if ($v->compare == '$between') {
				if (!is_array($v->value) || (count($v->value = array_values($v->value)) == 2 && $v->value[1] === $v->value[0])) {
					$v->compare = '';
					$v->value = is_array($v->value) ? end($v->value) : $v->value;
				} elseif (!$v->value || isset($v->value[2])) {
					$_false = true;
					break 2;
				}
			}


			// 搜索
			if ($v->compare == '$search') {
				$search = $this->search($v->value);
				if ($search = $search->get()) {
					foreach ($search as $kk => $vv) {
						foreach ($vv as $vvv) {
							$vvv = preg_quote($vvv, '/');
							$arrays[90][] = (object) ['column' => $v->column, 'compare' => '$regex', 'value' => '/' . ($kk == '-' ? '^((?!'. $vvv .').)*$' : $vvv) . '/is'];
						}
					}
				}
				continue;
			}


			// like
			if ($v->compare == '$like') {
				$v->compare = '$regex';
			 	if ($v->value) {
					if ($start = ($v->value{0} =='%')) {
						$v->value = ltrim($v->value, '%');
					}
					if ($end = (substr($v->value, -1, 1) =='%' && substr($v->value, -2, 1) != '\\')) {
						$v->value = rtrim($v->value, '%');
					}
					$v->value = preg_quote($v->value, '/');
					$v->value = preg_replace('/([^\\\\]|^)(%)/', '$1.*',$v->value);
					$v->value = preg_replace('/([^\\\\]|^)(_)/', '$1.',$v->value);
					$v->value = preg_replace('/(\\\\)(_|%)/', '$2',$v->value);
					if ($v->not) {
						if (!$start && !$end) {
							// not 操作符 必须开始到结尾的 暂时没
							$v->value = '';
						} elseif (!$start) {
							// 开始不能有
							$v->value = '^((?!'. $v->value .').)+';
						} elseif (!$end) {
							// 结尾不能有
							$v->value = '(.(?<!'.$v->value.'))+$';
						} else {
							// 中间不能有
							$v->value = '^((?!'. $v->value .').)*$';
						}
					} else {
						if ($start) {
							$v->value = '^' . $v->value;
						}
						if ($end) {
							$v->value = $v->value . '$';
						}
					}
					$v->value = '/'.$v->value.'/is';
				}
				$v->not = false;
				continue;
			}

			$v->compare = $v->compare ? $v->compare : '$eq';
			if (isset($v->priority)) {
				$arrays[$v->priority][] = $v;
			} elseif ($v->compare == '$regex') {
				$arrays[90][] = $v;
			} elseif ($v->compare == '$where') {
				$arrays[80][] = $v;
			} elseif ($v->compare == '$text') {
				$arrays[70][] = $v;
			} elseif ($v->column == '_id') {
				$arrays[0][] = $v;
			} else {
				$arrays[10][] = $v;
			}
		}


		if ($_false) {
			return ['_'=>['$in' => []]];
		}

		ksort($arrays);

		$r = [];
		foreach ($arrays as $objects) {
			foreach ($objects as $v) {
				if ($v->compare == '$call') {
					$value = $this->where($v->value, empty($v->logical) ? '$or' : $v->logical, $having);
					if ($v->not) {
						$value = ['$not' => $value];
					}
					$r[] = $value;
					continue;
				}



				$v->value = $v->escape ? $this->escape($v->value) : $v->escape;
				$options = false;

				if ($v->compare == '$between') {
					$between = [];
					if (isset($v->value[0])) {
						$between['$gte'] = $v->value[0];
					} elseif($v->not && isset($v->value[1])){
						$between['$gt'] = $v->value[1];
					}
					if (isset($v->value[1])) {
						$between['$lte'] = $v->value[1];
					} elseif ($v->not && isset($v->value[0])){
						$between['$lt'] = $v->value[0];
					}
					$r[] = [$v->column =>$between];
					continue;
				}


				if ($v->compare == '$regex') {
					if (preg_match('/^\/(.*)\/(\w*)$/is', $v->value, $matches)) {
						$v->value = strtr($matches[1], ['\/' => '/']);
						$options = $matches[2];
					}
				}


				$value = $v->value;
				if ($v->compare) {
					$value = [$v->compare=> $value];
					if ($v->compare == '$where') {
						$r[] = $value;
						continue;
					}
					if ($options !== false) {
						$value['$options'] = $options;
					}
				}
				if ($v->not) {
					$value = ['$not' => $value];
				}
				if ($v->column) {
					$value = [$v->column=> $value];
				}
				$r[] = $value;
			}
		}
		return $r? [$logical => $r] : [];
	}

	/**
	*	排序方式
	*
	*
	**/
	private function _sort($query) {
		if (empty($query['$orderby'])) {
			return false;
		}
		$query = $this->parse(array_intersect_key($query, ['$orderby' => '', '$order' => '']));
		if (empty($query['$orderby'])) {
			return false;
		}

		// 排序
		$sort = [];
		foreach ($query['$orderby'] as $k => $v) {
			foreach ($v->column as $kk => $vv) {
				if (!$vv = $this->key($vv)) {
					unset($v->column[$kk]);
					continue;
				}
				$sort[$vv] = $v->desc ? -1 : 1;
			}
		}
		return $sort;
	}
}