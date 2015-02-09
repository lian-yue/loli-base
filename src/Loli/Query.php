<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-12 09:43:36
/*	Updated: UTC 2015-02-09 17:15:17
/*
/* ************************************************************************** */
namespace Loli;
use StdClass;
trait_exists('Loli\Model', true) || exit;
class Query{
	use Model;

	public $ttl = 0;

	// 缓存数据
	public $data = [];

	// table表
	public $table;

	// args索引
	public $args = [];

	// 选择字段
	public $fields = ['*'];

	// 选择字段 手动的 需要 orderby 出现 才会 使用的 优先级高于 fields
	public $as = [];


	// 默认查询数组
	public $query = [];


	// lists 查询数组
	public $lists = [];


	//  关联 joins
	public $joins = [
	//	'关联的key的id' ['this' => $this->key 中的key位置支持 a.b.c, 'type' => 'join类型', 'on' => ['自己id', 别人id], 'auto' => 是否自动加载, 'use' => 是否使用, 'table' => '表' 'args' => '查询数组',  'fields' => '附加值段' 'as' => '排序自动添加' 'query' => '附加查询' '注意' 附加查询如果写了可能会附加到 其他表上面去哦 按照 优先级顺序的 如果没写的话读对象里面的查询会过滤掉全局的];
	];





	// 自增字段 string
	public $insertID;

	// 主要字段
	public $primary = [];

	// 唯一值 记住是二维数组
	public $uniques = [];

	// 默认值
	public $defaults = [];




	// 添加 bool 或者 允许的字段
	public $add = [];

	// 写入 bool 或者 允许的字段
	public $set = [];

	// 更新 bool 或者 允许的字段
	public $update = [];

	// 删除 bool
	public $delete = false;



	// 添加多个 bool 或者 允许的字段
	public $adds = [];

	// 写入多个 bool 或者 允许的字段
	public $sets = [];

	// 更新多个 bool 或者 允许的字段
	public $updates = [];

	// 删除多个 bool
	public $deletes = false;

	// 创建
	public $create = [];

	// 数据库引擎
	public $engine = false;

	// 是否使用从数据库
	public $slave = true;

	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}

	public function table($args = [], $do = 0) {
		return $this->table;
	}
	/**
	*	取得查询字符串
	*
	*	1 参数 查询数组
	*
	*	返回值 字符串
	**/
	public function str($query) {
		$query += $this->query;


		if (!$this->joins) {
			$tableW = $query;
			$query = $this->Query->parse($query, $this->args);
			$fields = (array) $this->fields;
			foreach (['$orderby', '$groupby'] as $key) {
				if (empty($query[$key])) {
					continue;
				}
				foreach ($query[$key] as $k => $v) {
					foreach($v->column as $vv) {
						if ( empty($fields[$vv]) && !empty($this->as[$vv])) {
							$fields[$vv] = $this->as[$vv];
						}
					}
				}
			}
			return $this->Query->get($query, $this->table($tableW), $fields);
		}



		$query = array_unnull($query);
		$by = [];
		foreach (['$orderby', '$groupby'] as $key) {
			if (empty($query[$key]) || is_object($query[$key])) {
				continue;
			}
			foreach ((array) $query[$key] as $v) {
				if (is_object($v)) {
					$by[$v->column] = true;
				} elseif (is_string($v)) {
					$by[$v] = true;
				}
			}
		}

		// 多层次支持
		foreach($this->joins as $k => $v) {
			if (empty($v['this'])) {
				$this->joins[$k]['this'] = (object) [
					'table' => '',
					'type' => [],
					'on' => [],
					'args' => [],
					'fields' => [],
					'as' => [],
					'query' => [],
				];
			} elseif (!is_object($v['this'])) {
				$t = (array) $v['this'];
				$a = $this;
				while ($key = array_shift($t)) {
					$a = $a->$key;
				}
				$this->joins[$k]['this'] = $a;
			}
		}
		$joins = ['_this' => ['this' => $this]] + $this->joins;


		// 取字段
		$arrays = $useKeys = [];
		foreach ($joins as $id => $j) {
			$args = isset($j['args']) ? $j['args'] : $j['this']->args;
			$as = isset($j['as']) ? $j['as'] : $j['this']->as;
			$fields = (array) (isset($j['fields']) ? $j['fields'] : $j['this']->fields);

			$fields2 = [];
			foreach ($fields as $field => $value) {
				if (is_int($field)) {
					$fields2[$field] = $value;
					unset($fields[$field]);
				}
			}
			$arrays[$id]['args'] = array_diff_key($args, $useKeys);
			$arrays[$id]['fields'] = array_diff_key($fields, $useKeys);
			$arrays[$id]['as'] = array_diff_key($as, $useKeys);
			$arrays[$id]['fields2'] = $fields2;

			$useKeys += $arrays[$id]['args'] + $arrays[$id]['fields'] + $arrays[$id]['as'];
		}
		unset($useKeys);

		// 取使用
		$use = [];
		foreach($arrays as $id => $a) {
			$j = $joins[$id];
			if (empty($j['auto']) && $id != '_this' && !array_intersect_key($query, $a['args']) && !array_intersect_key($a['args'] + $a['fields'] + $a['as'], $by)) {
				continue;
			}

			// 父级
			$parent = $id;
			do {
				$while = !empty($joins[$parent]['parent']) && $joins[$parent]['parent'] != $id && !empty($joins[$joins[$parent]['parent']]) && (!isset($j['use']) || $j['use']) && !in_array($joins[$parent]['parent'], $ids);
				if ($while) {
					$use[] = $parent = $joins[$parent]['parent'];
				}
			} while($while);
			$use[] = $id;
		}


		// 查询
		$args = $table = $fields = [];
		foreach ($use as $id) {
			$j = $joins[$id];
			$a = $arrays[$id];


			// 表
			$table[$id]['type'] = empty($j['type']) ? '' : $j['type'];
			$table[$id]['name'] = empty($j['table']) ? $j['this']->table(array_intersect_key($query, $a['args']) + (isset($j['query']) ? $j['query'] : $j['this']->query)) : $j['table'];

			// 关联
			if ($id != '_this') {
				$table[$id]['on'] = [];
				if (!empty($j['on'])) {
					foreach (array_values($j['on']) as $k => $v) {
						$table[$id]['on'][] = strpos($v,'.') || strpos($v,'\'') !== false || strpos($v,'"') !== false ? $v : ($k % 2 ? $id . '.' . $v : ((empty($j['parent']) ? '_this' : $j['parent']) . '.' . $v));
					}
				}
			}



			// 表头
			foreach ($a['fields'] + $a['fields2'] + array_intersect_key($a['as'], $by) as $k => $v) {
				if ($v == '*') {
					if ($id == '_this') {
						$fields[] = '_this.*';
					}
					continue;
				}
				if (is_int($k)) {
					$fields[] = strpos($v, '.') ? $v : $id . '.' . $v;
				} elseif (is_string($v)) {
					$fields[$k] = strpos($v, '.') ? $v : $id . '.' . $v;
				} elseif (is_array($v)) {
					foreach ($v as $kk => $vv) {
						$fields[$k][$kk] = in_array($kk, ['column', 'true', 'false']) && is_string($vv) ? $id . '.' . $vv : $vv;
					}
				}
			}


			// 字段
			foreach ($a['args'] as $k => $v) {
				if (!isset($args[$k])) {
					$v = is_array($v) ? $v : ['column' => $k, 'compare' => $v];
					$v['column'] = $id . '.' . (empty($v['column']) ? $k : $v['column']);
					$args[$k] = $v;
				}
			}

			// 附加字段
			$query += isset($j['query']) ? $j['query'] : array_intersect_key($j['this']->query, $a['args']);
		}

		return $this->Query->get($this->Query->parse($query, $args), $table, $fields, 'AND');
	}

	/**
	*	获取 多行
	*
	*	1 参数 查询数组
	*	2 参数 查询选项
	*
	*	返回值 true false
	**/
	public function results($query) {
		$query['$count'] = null;
		$r = [];
		foreach($this->DB->results($this->str($query), $this->slave) as $v) {
			$r[] = $this->r($v);
		}
		return $r;
	}

	/**
	*	获取 用户多行
	*
	*	1 参数 查询数组
	*	2 参数 查询选项
	*
	*	返回值 true false
	**/
	public function row($query) {
		$query['$count'] = null;
		$query['$limit'] = 1;
		if ($r = $this->DB->row($this->str($query), $this->slave)) {
			return $this->r($r);
		}
		return false;
	}


	/**
	* 	数量
	*
	*	1 参数 查询数组
	*	2 参数 查询选项
	*
	*	返回值 数量和
	*/
	public function count($query) {
		$query['$count'] = true;
		return $this->DB->count($this->str($query), $this->slave);
	}


	public function get($a) {
		if (!$a || !$this->primary || ($num = func_num_args()) < ($count = count($this->primary))) {
			return false;
		}
		$a = [];
		$i = 0;
		foreach ($this->primary as $v) {
			if (is_array($a[$v] = func_get_arg($i)) || is_object($a[$v])) {
				return false;
			}
			++$i;
		}
		switch($count) {
			case 1:
				if (!empty($this->data[$a[$this->primary[0]]])) {
					return $this->data[$a[$this->primary[0]]];
				}
				break;
			case 2:
				if (!empty($this->data[$a[$this->primary[0]]][$a[$this->primary[1]]])) {
					return $this->data[$a[$this->primary[0]]][$a[$this->primary[1]]];
				}
				break;
			case 3:
				if (!empty($this->data[$a[$this->primary[0]]][$a[$this->primary[1]]][$a[$this->primary[2]]])) {
					return $this->data[$a[$this->primary[0]]][$a[$this->primary[1]]][$a[$this->primary[2]]];
				}
				break;
			case 4:
				if (!empty($this->data[$a[$this->primary[0]]][$a[$this->primary[1]]][$a[$this->primary[2]]][$a[$this->primary[3]]])) {
					return $this->data[$a[$this->primary[0]]][$a[$this->primary[1]]][$a[$this->primary[2]]][$a[$this->primary[3]]];
				}
				break;
		}

		if ($this->ttl && ($r = Cache::get($count == 1? reset($a) : json_encode($a), get_class($this)))) {
			return $this->r($r, false);
		}
		return $this->row($a);
	}


	/**
	*
	*
	*
	*
	*
	**/
	public function lists() {
		if (!$this->lists) {
			return [];
		}
		if (!isset($this->lists['$results']) || !is_array($this->lists['$results'])) {
			// 数量
			$this->lists['$count'] = !isset($this->lists['$count']) || $this->lists['$count'] === true;

			// 内容
			$this->lists['$results'] = $this->results($this->lists);

			// 数量
			if ($this->lists['$count']) {
				$this->lists['$count'] = $this->count($this->lists);
			} else {
				$this->lists['$count'] = count($this->lists['$results']);
			}
		}


		/*//if ($this->lists['$count'] && !empty($this->lists['$limit'])) {


		//}

		// 数量
		if ($this->lists['$count'] && !empty($this->lists['$limit'])) {
			$this->Page->offset = empty($this->lists['$offset']) ? 0 : $this->lists['$offset'];
			$this->Page->limit = $this->lists['$limit'];
			// 总共数量
			$this->Page->count = $this->lists['$count'];
			if (!empty($this->lists['$more']) && ($row = end($this->lists['$results']))) {
				foreach ($this->lists['$more'] as $k => $v) {
					if (is_int($k)) {
						$more[$v] = $row->$v;
					} else {
						$more[$k] = $row->$v;
					}
				}
				$this->Page->more = $more;
			} else {
				$this->Page->more = [];
			}
		}*/

 		// 内容
		/*$r = [];
		foreach($this->lists['$results'] as $k => $v) {
			$r[$k] = clone $v;
		}*/
		return ['results' => $r, 'count' => $this->lists['$count']];
	}




	/**
	*	添加
	*
	*	1 参数 添加数组
	*
	*	返回值 bool
	**/
	public function add($args) {
		// 过滤数组
		if (!($a = $this->defaults($args, ['add'], true)) || !($table = $this->table($a + $args, 1))) {
			return false;
		}

		$this->slave = false;

		// 主要字段检测
		if ($this->primary) {
			$q = [];
			foreach ($this->primary as $k) {
				if (!$get = isset($a[$k])) {
					break;
				}
				$q[$k] = $a[$k];
			}
			if ($get && call_user_func_array([$this, 'get'], $q)) {
				return false;
			}
		}

		// 唯一值检测
		if ($this->uniques) {
			foreach ($this->uniques as $uniques) {
				$qq = [];
				foreach($uniques as $k) {
					if (!$get = isset($a[$k])) {
						break;
					}
					$qq[$k] = $a[$k];
				}
				if ($get && $this->DB->row($this->Query->get(['$limit' => 1] + $qq, $table), false)) {
					return false;
				}
			}
		}

		// 过滤 w
		if (!$a = $this->w($a, false, $args)) {
			return false;
		}

		// 添加进去
		if (!$r = $this->DB->insert($this->Query->add($a, $table), false)) {
			return false;
		}

		// 有 insertID
		if ($this->insertID) {
			$a[$this->insertID] = $r = empty($a[$this->insertID]) ? $this->DB->insertID : $a[$this->insertID];
		}

		// 完成回调
		$this->c((object) $a, false, $a + $args);
		return $r;
	}


	public function set($args) {
		// 过滤数组
		if (!($a = $this->defaults($args, ['set'], true)) || !($table = $this->table($a + $args, 1))) {
			return false;
		}

		$this->slave = false;
		// 旧值
		$old = false;
		if ($this->primary) {
			$q = [];
			foreach ($this->primary as $k) {
				if (!$get = isset($a[$k])) {
					break;
				}
				$q[$k] = $a[$k];
			}

			if ($get && ($old = $this->DB->row($this->Query->get(['$limit' => 1] + $q, $table)))) {
				$a = array_intersect_key($a + (array) $old, (array) $old);
			}
		}

		// 过滤 w
		if (!$a = $this->w($a, $old, $args)) {
			return false;
		}

		// 写入
		if (($r = $this->DB->replace($this->Query->set($a, $table), false)) === false) {
			return false;
		}

		// 有 insertID
		if ($this->insertID) {
			$r = $a[$this->insertID] = $this->DB->insertID;
		}

		// 完成回调
		$this->c((object) ($a + ($old ? (array) $old : [])), $old, $a + $args);

		return $r;
	}


	public function update($args, $b) {
		if (!$b || !$this->primary || count(array_filter(func_get_args())) <= count($this->primary)) {
			return false;
		}
		$this->slave = false;

		if (!$a = $this->defaults($args, ['update'])) {
			return false;
		}

		$q = [];
		foreach ($this->primary as $i => $k) {
			if (is_array($q[$k] = func_get_arg($i+1)) || is_object($q[$k])) {
				return false;
			}
		}

		if (!$table = $this->table($q + $a + $args, 1)) {
			return false;
		}

		if (!$old =  $this->DB->row($this->Query->get(['$limit' => 1] + $q, $table))) {
			return false;
		}


		$w = [];
		foreach ($this->primary as $v) {
			$w[$v] = $old->{$v};
		}


		// 唯一值检测
		if ($this->uniques) {
			foreach ($this->uniques as $uniques) {
				$q = [];
				foreach($uniques as $k) {
					if (isset($a[$k])) {
						$v = $a[$k];
						if ($v !== $old->{$k}) {
							$q[$k] = $v;
						}
					}
				}
				if ($q) {
					foreach($uniques as $k) {
						$q[$k] = isset($q[$k]) ? $q[$k] : $old->{$k};
					}
					if ($this->DB->row($this->Query->get(['$limit' => 1] + $q, $table), false)) {
						return false;
					}
				}
			}
		}


		if (!$a = $this->w($a, $old, $args)) {
			return false;
		}

		$r = $this->DB->update($this->Query->update($a, ['$limit' => 1] + $w, $table), false);

		$a = $w + $a;

		// 完成回调
		$this->c((object) ($a + (array) $old), $old, $a + $args);

		return $r;
	}


	public function delete($a) {
		if (!$a || !$this->delete || !$this->primary || count(array_filter(func_get_args())) < count($this->primary)) {
			return false;
		}
		$this->slave = false;

		$q = [];
		foreach ($this->primary as $i => $k) {
			if (is_array($q[$k] = func_get_arg($i)) || is_object($q[$k])) {
				return false;
			}
		}

		if (!$table = $this->table($q, 1)) {
			return false;
		}

		if (!$old = $this->DB->row($this->Query->get(['$limit' => 1] + $q, $table))) {
			return false;
		}

		$w = [];
		foreach ($this->primary as $v) {
			$w[$v] = $old->{$v};
		}
		if (!$r = $this->DB->delete($this->Query->delete(['$limit' => 1] + $w, $table), false)) {
			return $r;
		}

		// 完成回调
		$this->c(false, $old, false);
		return $r;
	}


	/**
	*	添加多个
	*
	*	1 二维数组
	*
	*	返回值 false 或者 添加的数量  带有自增ID的能使用
	**/
	public function adds($args) {
		$a = [];
		$table = false;
		foreach ($args as $k => $v) {
			if (!$a[$k] = $this->defaults($v, ['adds','add'], true)) {
				return false;
			}
			if (!$t = $this->table($a[$k] + $v, 1)) {
				return false;
			}
			if ($table && $t != $table) {
				return false;
			}
			$table = $t;
		}

		$this->slave = false;
		// 主要 字段检测 和唯值检测
		$uniques = $this->uniques ? $this->uniques : [];
		if ($this->primary) {
			$uniques = array_merge([$this->primary], $uniques);
		}
		if ($uniques) {

			// 取得查询数组
			$q = [];
			$i = 0;
			foreach ($a as $k => $v) {
				foreach($uniques as $vv) {
					if (count($vv) == 1) {
						$kkk = reset($vv);
						if (isset($v[$kkk])) {
							$q[$kkk][] = $v[$kkk];
						}
					} else {
						$q[$i] = new StdClass;
						$q[$i]->compare = 'CALL';
						foreach ($vv as $kkk) {
							if (!isset($v[$kkk])) {
								unset($q[$i]);
								break;
							}
							$q[$i]->value[$kkk] = $v[$kkk];
						}
						++$i;
					}
				}
			}

			// 检测重复
			if ($q) {
				foreach($this->DB->results($this->Query->get($q, $table, '*', 'OR'), false) as $v) {
					foreach($a as $kk => $vv) {
						foreach($uniques as $vvv) {
							$exists = true;
							foreach ($vvv as $vvvv) {
								if (!isset($vv[$vvvv]) || $vv[$vvvv] !== $v->{$vvvv}) {
									$exists = false;
									break;
								}
							}
							if ($exists) {
								return false;
							}
						}
					}

				}
				if  (!$a) {
					return false;
				}
			}
		}

		// 过滤
		foreach ($a as $k => $v) {
			if (!$a[$k] = $this->w($v, false, $args[$k])) {
				unset($a[$k]);
			}
		}

		// 添加进去
		if (!$a || !($r = $this->DB->insert($this->Query->add(array_values($a), $table), false))) {
			return false;
		}

		// 完成回调
		if (!$this->insertID) {
			foreach ($a as $k => $v) {
				$this->c((object) $v, false, $v + $args[$k]);
			}
		}
		return $r;
	}

	/**
	*	写入多个
	*
	*	1 二维数组
	*
	*	返回值 false 或者 影响的数量  带有自增ID的能使用
	**/
	public function sets($args) {
		$a = [];
		$table = false;
		foreach ($args as $k => $v) {
			if (!$a[$k] = $this->defaults($v, ['sets', 'set'], true)) {
				return false;
			}
			if (!$t = $this->table($a[$k] + $v, 1)) {
				return false;
			}
			if ($table && $t != $table) {
				return false;
			}
			$table = $t;
		}

		$this->slave = false;
		// 旧数据取得
		$old = [];
		if ($this->primary) {
			$q = [];
			$i = 0;
			foreach ($a as $k => $v) {
				if (count($this->primary) == 1) {
					$kk = reset($this->primary);
					if (isset($v[$kk])) {
						$q[$kk][] = $v[$kk];
					}
				} else {
					$q[$i] = new StdClass;
					$q[$i]->compare = 'CALL';
					foreach ($this->primary as $kk) {
						if (!isset($v[$kk])) {
							unset($q[$i]);
							break;
						}
						$q[$i]->value[$kk] = $v[$kk];
					}
					++$i;
				}
			}

			// 取得存在的
			if ($q) {
				foreach($this->DB->results($this->Query->get($q, $table, '*', 'OR'), false) as $v) {
					foreach($a as $kk => $vv) {
						$exists = true;
						foreach ($this->primary as $vvv) {
							if (!isset($vv[$vvv]) || $vv[$vvv] !== $v->{$vvv}) {
								$exists = false;
								break;
							}
						}
						if ($exists) {
							$old[$kk] = $v;
							break;
						}
					}
				}
			}
		}


		// 过滤
		foreach ($a as $k => $v) {
			if (!$a[$k] = $this->w($v, isset($old[$k]) ? $old[$k] : false, $args[$k])) {
				unset($a[$k]);
			}
		}

		// 修改
		if (!$a || ($r = $this->DB->replace($this->Query->set(array_values($a), $table), false)) === false) {
			return false;
		}

		// 完成回调
		if (!$this->insertID) {
			foreach ($a as $k => $v) {
				$this->c((object) ($v + (isset($old[$k]) ? (array) $old[$k] : [])), isset($old[$k]) ? $old[$k] : false, $v + $args[$k]);
			}
		}

		return $r;
	}


	public function updates($args, $b, $c = true) {
		if (!$b  || !is_array($b)) {
			return false;
		}
		if (!$a = $this->defaults($args, ['updates', 'update'])) {
			return false;
		}
		if (!$table = $this->table($b + $a + $args, 1)) {
			return false;
		}

		// 查询数组
		if (!$w = $this->Query->parse($b, $this->args)) {
			return false;
		}

		if ($this->primary && $c) {
			foreach ($w as $v) {
				if ($break = in_array($v->column, $this->primary)) {
					break;
				}
			}
			if (!$break) {
				return false;
			}
		}

		$this->slave = false;

		$old = [];
		if (!$w || ($c && !($old = $this->DB->results($this->Query->get($w, $this->table), false)))) {
			return false;
		}


		// 主要 字段检测 和唯值检测
		$uniques = array_merge([$this->primary],  $this->uniques ? $this->uniques : []);
		foreach ($uniques as $v) {
			$for = false;
			foreach ($v as $vv) {
				if (isset($a[$vv])) {
					$for = true;
				}
			}
			if ($for) {
				foreach ($old as $vv) {
					$exists = true;
					foreach ($v as $vvv) {
						if (isset($a[$vvv]) && $a[$vvv] !== $vv->{$vvv}) {
							$exists = false;
							break;
						}
					}
					if ($exists) {
						return false;
					}
				}
			}
		}

		// 写入过滤
		foreach ($old as $v) {
			if (!$a = $this->w($a, $v, $args)) {
				return false;
			}
		}

		// 更新
		$r = $this->DB->update($this->Query->update($a, $w, $table), false);

		// 完成回调
		foreach ($old as $v) {
			$new = $a;
			foreach ($this->primary as $vv) {
				$new[$vv] = $v->{$vv};
			}
			$this->c((object) ($new + ((array) $v)), $v, $new + $args);
		}
		return $r;
	}



	public function deletes($a, $c = true) {
		if (!$a || !$this->deletes || !is_array($a)) {
			return false;
		}

		if (!$table = $this->table($a, 1)) {
			return false;
		}

		// 查询数组
		if (!$w = $this->Query->parse($a, $this->args)) {
			return false;
		}
		if ($this->primary && $c) {
			foreach ($w as $v) {
				if ($break = in_array($v->column, $this->primary)) {
					break;
				}
			}
			if (!$break) {
				return false;
			}
		}

		$this->slave = false;

		//  读取 和 删除
		$old = [];
		if (!$w || ($c && !($old = $this->DB->results($this->Query->get($w, $table), false))) || !($r = $this->DB->delete($this->Query->delete($w, $table), false))) {
			return 0;
		}

		// 完成回调
		foreach($old as $v) {
			$this->c(false, $v, false);
		}
		return $r;
	}

	public function exists() {
		$this->slave = false;
		$r = 0;
		foreach ((array) $this->table([], -1) as $table) {
			if ($this->DB->exists($table)) {
				++$r;
			}
		}
		return $r;
	}

	public function truncate() {
		$this->slave = false;
		$r = 0;
		foreach ((array) $this->table([], -1) as $table) {
			if ($this->DB->truncate($table)) {
				++$r;
			}
		}
		return $r;
	}

	public function drop() {
		$this->slave = false;
		$r = 0;
		foreach ((array) $this->table([], -1) as $table) {
			if ($this->DB->drop($table)) {
				++$r;
			}
		}
		return $r;
	}


	public function create() {
		$this->slave = false;
		$r = 0;
		foreach ((array) $this->table([], -1) as $table) {
			if ($this->create && $this->DB->create($this->Query->create($this->create, $table, $this->engine), false)) {
				++$r;
			}
		}
		return $r;
	}



	// 整理数据类型
	public function defaults($args, $key = [], $merge = false) {
		if (!$args || !is_array($args) || empty($this->{$key[0]})) {
			return false;
		}
		if ($this->defaults) {
			$a = [];
			$defaults = $this->defaults;
			foreach ($defaults as $k => $v) {
				if (isset($args[$k])) {
					$type = gettype($v);
					if ($type == 'boolean') {
						$a[$k] = (bool) $args[$k];
					} elseif ($type == 'float' || $type == 'double') {
						$a[$k] = (double) $args[$k];
					} elseif ($type == 'integer') {
						$a[$k] = (int) $args[$k];
					} elseif ($type == 'array') {
						$a[$k] = $args[$k] ? (array) $args[$k] : [];
					} elseif ($type == 'object') {
						$a[$k] = (object) $args[$k];
					} elseif ($type == 'string') {
						$a[$k] = (string) $args[$k];
					} else {
						$a[$k] = $args[$k];
					}
				}
				if ($v === null) {
					unset($defaults[$k]);
				}
			}
		} else {
			$a = $args;
		}
		if (!$a) {
			return false;
		}
		if ($merge && $this->defaults) {
			$a += $defaults;
		}
		foreach($key as $v) {
			if (is_array($this->{$v}) && $this->{$v}) {
				if (!$a = array_intersect_key($a, array_flip($this->{$v}))) {
					return false;
				}
				break;
			}
		}
		return $a;
	}

	/**
	*	读取
	*
	*	回调
	**/
	protected function r($r, $c = true) {
		$count = count($this->primary);
		if ($count == 1) {
			if (is_array($r->{$this->primary[0]}) || is_object($r->{$this->primary[0]})) {
				return $r;
			}
			$this->data[$r->{$this->primary[0]}] = $r;
		} elseif ($count == 2) {
			$this->data[$r->{$this->primary[0]}][$r->{$this->primary[1]}] = $r;
		} elseif ($count == 3) {
			$this->data[$r->{$this->primary[0]}][$r->{$this->primary[1]}][$r->{$this->primary[2]}] = $r;
		} elseif ($count == 4) {
			$this->data[$r->{$this->primary[0]}][$r->{$this->primary[1]}][$r->{$this->primary[2]}][$r->{$this->primary[3]}] = $r;
		}

		if ($c && $this->ttl) {
			$params = [];
			foreach ($this->primary as $v) {
				$params[$v] = $r->$v;
			}
			$this->slave ? Cache::add($r, $count == 1 ? reset($params) : json_encode($params), get_class($this), $this->ttl) : Cache::set($r, $count == 1 ? reset($params) : json_encode($params), get_class($this), $this->ttl);
		}
		return $r;
	}


	/**
	*	写入
	*
	*	回调
	**/
	protected function w($w, $old, $args) {
		return $w;
	}

	/**
	*	完成
	*
	*	回调
	**/
	protected function c($new, $old, $args) {
		$a = $new ? $new : $old;
		$count = count($this->primary);
		switch($count) {
			case 1:
				unset($this->data[$a->{$this->primary[0]}]);
				break;
			case 2:
				unset($this->data[$a->{$this->primary[0]}][$a->{$this->primary[1]}]);
				break;
			case 3:
				unset($this->data[$a->{$this->primary[0]}][$a->{$this->primary[1]}][$a->{$this->primary[2]}]);
				break;
			case 4:
				unset($this->data[$a->{$this->primary[0]}][$a->{$this->primary[1]}][$a->{$this->primary[2]}][$a->{$this->primary[3]}]);
				break;
			default:
				$this->data = [];
		}
		$params = [];
		foreach ($this->primary as $v) {
			$params[$v] = $a->$v;
		}
		if ($params) {
			$this->ttl && Cache::delete($count == 1 ? reset($params) : json_encode($params), get_class($this));
			call_user_func_array([$this, 'get'], $params);
		}
	}
}