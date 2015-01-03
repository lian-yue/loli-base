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
/*	Updated: UTC 2014-12-31 09:52:31
/*
/* ************************************************************************** */
namespace Loli;
use StdClass;
class Query extends Model{

	public $data = [];

	// table表
	public $table;

	// args索引
	public $args = [];

	// 选择字段
	public $fields = ['*'];

	// 选择字段 手动的 需要 orderby 出现 才会 使用的
	public $as = [];

	// 默认 唯一值
	public $groupby = [];

	// 默认 排序字段
	public $orderby = [];

	// 默认 排序 正序倒序
	public $order = [];

	// 默认 查询数量
	public $limit;

	// lists 查询数组
	public $lists = [];


	//  关联 joins
	public $joins = [
	//	'关联的key的id' ['this' => $this->key 中的key位置支持 a.b.c, 'type' => 'join类型', 'on' => ['自己id', 别人id], 'auto' => 是否自动加载, 'escape' => 是否转义, 'where' => 嵌套运行的附加的,   .... 然后这是覆盖上面 除了 lists 和 data 的变量覆盖];
	];





	// 自增字段 string
	public $insert_id;

	// 主要字段
	public $primary = [];

	// 唯一值 记住是二维数组
	public $unique = [];

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
	public $engine = '';

	// 是否使用从数据库
	public $slave = true;


	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}

	/**
	*	取得查询字符串
	*
	*	1 参数 查询数组
	*
	*	返回值 字符串
	**/
	public function str($w) {
		foreach (['limit', 'groupby', 'orderby', 'order'] as $v) {
			if (!isset($w['$'.$v]) && !empty($this->$v)) {
				$w['$'.$v] = $this->$v;
			}
		}

		if (!$this->joins) {
			$w = $this->Query->parse($w, $this->args);
			$fields = (array) $this->fields;
			if (!empty($w['$orderby'])) {
				foreach ($w['$orderby'] as $k => $v) {
					foreach($v->column as $vv) {
						if ( empty($fields[$vv]) && !empty($this->as[$vv])) {
							$fields[$vv] = $this->as[$vv];
						}
					}
				}
			}
			return $this->Query->get($w, $this->table, $fields);
		}



		$w = array_unnull($w);
		$orderbyFlip = array_flip(empty($w['$orderby'])? [] : (array) $w['$orderby']);


		// 多层次支持
		foreach($this->joins as $k => $v) {
			if (empty($v['this'])) {
				$this->joins[$k]['this'] = (object) [
					'auto' => false,
					'table' => '',
					'escape' => false,
					'args' => [],
					'fields' => [],
					'as' => [],
					'groupby' => [],
					'orderby' => [],
					'order' => [],
					'limit' => null,
					'type' => [],
					'on' => [],
				];
			} elseif (is_string($v['this'])) {
				$t = explode('.', $v['this']);
				$a = $this;
				while ($key = array_shift($t)) {
					$a = $a->$key;
				}
				$this->joins[$k]['this'] = $a;
			}
		}
		$join = ['_this' => ['this' => $this]] + $this->joins;

		// 需要使用的
		$use = ['_this'];
		foreach ($join as $k => $v) {
			if (in_array($k, $use)) {
				continue;
			}
			if (empty($v['auto'])) {
				$a = isset($v['args']) ? $v['args'] : array_diff_key($v['this']->args, empty($v['parent']) ? $this->args : (empty($join[$v['parent']]) ?  [] : (empty($join[$v['parent']]['args']) ? $join[$v['parent']]['this']->args : $join[$v['parent']]['args'])));
				$as = isset($v['as']) ? $v['as'] : $v['this']->as;
				$fields = (array)(isset($v['fields']) ? $v['fields'] : $v['this']->fields);
				foreach ($fields as $kk => $vv) {
					if (is_int($v)) {
						unset($fields[$kk]);
					}
				}
				if (empty($v['auto']) && !array_intersect_key($a, $w) && !array_intersect_key($a + $as + $fields, $orderbyFlip)) {
					continue;
				}
			}

			// 父级
			do {
				$while = !empty($v['parent']) && !in_array($v['parent'], $use) && !empty($join[$v['parent']]);
				if ($while) {
					$use[] = $v['parent'];
				}
			} while($while);

			$use[] = $k;
		}

		// 表和字段
		$args = $table = $fields = [];
		foreach ($use as $id) {
			$j = $join[$id];
			$escape = isset($j['escape']) && $j['escape'] === true;

			// 表
			$table[$id]['escape'] = $escape;
			$table[$id]['type'] = empty($j['type']) ? '' : $j['type'];
			if ($escape) {
				// 需要运行的
				if ($j['this']->args) {
					$ww = [];
					foreach ($j['this']->args as $kk => $vv) {
						if (isset($w[$kk])) {
							$ww[$kk] = $w[$kk];
						}
					}
				} else {
					$ww = $w;
				}
				foreach (['limit', 'groupby', 'orderby', 'order'] as $vv) {
					$ww['$'.$vv] = empty($j[$kk]) ? '' : $j[$kk];
				}
				if (!empty($j['where'])) {
					$ww = $j['where'] + $ww;
				}
				$table[$id]['name'] = $j['this']->str($ww);
			} else {
				$table[$id]['name'] = empty($j['table']) ? $j['this']->table : $j['table'];
			}

			if ($id != '_this') {
				$table[$id]['on'] = [];
				if (!empty($j['on'])) {
					foreach (array_values($j['on']) as $kk => $vv) {
						$table[$id]['on'][] = strpos($vv,'.') || strpos($vv,'\'') !== false || strpos($vv,'"') !== false ? $vv : ($kk % 2 ? $id . '.' . $vv : ((empty($j['parent']) ? '_this' : $j['parent']) . '.' . $vv));
					}
				}
			}


			$f = isset($j['fields'])? (array) $j['fields'] : ($escape ? [] : (array)$j['this']->fields);

			// AS 排序允许
			$f += array_intersect_key((isset($j['as'])?$j['as'] : ($escape ? [] : $j['this']->as)), $orderbyFlip);

			// 表头
			foreach ($f as $kk => $vv) {
				if ($vv == '*') {
					if ($id == '_this') {
						$fields[] = '_this.*';
					}
					continue;
				}
				if (is_int($kk)) {
					$fields[] = strpos($vv, '.') ? $vv : $id . '.' . $vv;
				} elseif (is_string($vv)) {
					$fields[$kk] = strpos($vv, '.') ? $vv : $id . '.' . $vv;
				} elseif (is_array($vv)) {
					foreach ($vv as $kkk => $vvv) {
						$fields[$kk][$kkk] = in_array($kkk, ['column', 'true', 'false']) && is_string($vvv) ? $id . '.' . $vvv : $vvv;
					}
				}
			}

			// 字段
			foreach ( isset($j['args']) ? $j['args'] : ($escape ? [] : $j['this']->args) as $kk => $vv ) {
				if (!isset($args[$kk])) {
					$vv = is_array($vv) ? $vv : ['column' => $kk, 'compare' => $vv];
					$vv['column'] = $id . '.' . (empty($vv['column']) ? $kk : $vv['column']);
					$args[$kk] = $vv;
				}
			}
		}
		return $this->Query->get($this->Query->parse($w, $args), $table, $fields, 'AND');
	}

	/**
	*	获取 多行
	*
	*	1 参数 查询数组
	*	2 参数 查询选项
	*
	*	返回值 true false
	**/
	public function result($w) {
		$w['$count'] = null;
		$r = [];
		foreach($this->DB->result($this->str($w), $this->slave) as $v) {
			$r[] = $this->_r($v);
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
	public function row($w) {
		$w['$count'] = null;
		$w['$limit'] = 1;
		if ($r = $this->DB->row($this->str($w), $this->slave)) {
			return $this->_r($r);
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
	public function count($w) {
		$w['$count'] = true;
		return $this->DB->count($this->str($w), $this->slave);
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
		if (!isset($this->lists['$result']) || !is_array($this->lists['$result'])) {
			// 数量
			$this->lists['$found_rows'] = !isset($this->lists['$count']) || $this->lists['$count'] === true;

			// 内容
			$this->lists['$result'] = $this->result($this->lists);

			// 数量
			if ($this->lists['$found_rows']) {
				$this->lists['$count'] = $this->DB->found_rows;
			} else {
				$this->lists['$count'] = count($this->lists['$count']);
			}
			$this->lists['$found_rows'] = false;
		}


		// 数量
		if ($this->lists['$count'] && !empty($this->lists['$limit'])) {
			$this->Page->offset = empty($this->lists['$offset']) ? 0 : $this->lists['$offset'];
			$this->Page->limit = $this->lists['$limit'];
			// 总共数量
			$this->Page->count = $this->lists['$count'];
			if (!empty($this->lists['$more']) && ($row = end($this->lists['$result']))) {
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
		}

 		// 内容
		$r = [];
		foreach($this->lists['$result'] as $k => $v) {
			$r[$k] = clone $v;
		}
		return $r;
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
		if (!$a = $this->defaults($args, ['add'], true)) {
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
		if ($this->unique) {
			foreach ($this->unique as $unique) {
				$q = [];
				foreach($unique as $k) {
					if (!$get = isset($a[$k])) {
						break;
					}
					$q[$k] = $a[$k];
				}
				if ($get && $this->DB->row($this->Query->get(['$limit' => 1] + $q, $this->table), false)) {
					return false;
				}
			}
		}


		// 过滤 w
		if (!$a = $this->_w($a, false, $args)) {
			return false;
		}

		// 添加进去
		if (!$r = $this->DB->insert($this->Query->add($a, $this->table), false)) {
			return false;
		}

		// 有 insert_id
		if ($this->insert_id) {
			$a[$this->insert_id] = $r = empty($a[$this->insert_id]) ? $this->DB->insert_id : $a[$this->insert_id];
		}

		// 完成回调
		$this->_c((object) $a, false, $a + $args);
		return $r;
	}


	public function set($args) {
		// 过滤数组
		if (!$a = $this->defaults($args, ['set'], true)) {
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

			if ($get && ($old = $this->DB->row($this->Query->get(['$limit' => 1] + $q, $this->table)))) {
				$a = array_intersect_key($a + (array) $old, (array) $old);
			}
		}

		// 过滤 w
		if (!$a = $this->_w($a, $old, $args)) {
			return false;
		}

		// 写入
		if (($r = $this->DB->replace($this->Query->set($a, $this->table), false)) === false) {
			return false;
		}

		// 有 insert_id
		if ($this->insert_id) {
			$r = $a[$this->insert_id] = $this->DB->insert_id;
		}

		// 完成回调
		$this->_c((object) ($a + ($old ? (array) $old : [])), $old, $a + $args);

		return $r;
	}


	public function update($args, $b) {
		if (!$b || !$this->primary || count(array_filter(func_get_args())) <= count($this->primary)) {
			return false;
		}
		$this->slave = false;

		$q = [];
		foreach ($this->primary as $i => $k) {
			if (is_array($q[$k] = func_get_arg($i+1)) || is_object($q[$k])) {
				return false;
			}
		}

		if (!$old =  $this->DB->row($this->Query->get(['$limit' => 1] + $q, $this->table))) {
			return false;
		}

		// 过滤数组
		if (!$a = $this->defaults($args, ['update'])) {
			return false;
		}

		$w = [];
		foreach ($this->primary as $v) {
			$w[$v] = $old->{$v};
		}


		// 唯一值检测
		if ($this->unique) {
			foreach ($this->unique as $unique) {
				$q = [];
				foreach($unique as $k) {
					if (isset($a[$k])) {
						$v = $a[$k];
						if ($v !== $old->{$k}) {
							$q[$k] = $v;
						}
					}
				}
				if ($q) {
					foreach($unique as $k) {
						$q[$k] = isset($q[$k]) ? $q[$k] : $old->{$k};
					}
					if ($this->DB->row($this->Query->get(['$limit' => 1] + $q, $this->table), false)) {
						return false;
					}
				}
			}
		}


		if (!$a = $this->_w($a, $old, $args)) {
			return false;
		}


		$r = $this->DB->update($this->Query->update($a, ['$limit' => 1] + $w, $this->table), false);

		$a = $w + $a;

		// 完成回调
		$this->_c((object) ($a + (array) $old), $old, $a + $args);

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
		if (!$old = $this->DB->row($this->Query->get(['$limit' => 1] + $q, $this->table))) {
			return false;
		}

		$w = [];
		foreach ($this->primary as $v) {
			$w[$v] = $old->{$v};
		}
		if (!$r = $this->DB->delete($this->Query->delete(['$limit' => 1] + $w, $this->table), false)) {
			return $r;
		}

		// 完成回调
		$this->_c(false, $old, false);
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
		foreach ($args as $k => $v) {
			if (!$a[$k] = $this->defaults($v, ['adds','add'], true)){
				return false;
			}
		}
		$this->slave = false;

		// 主要 字段检测 和唯值检测
		$unique = $this->unique ? $this->unique : [];
		if ($this->primary) {
			$unique = array_merge([$this->primary], $unique);
		}
		if ($unique) {

			// 取得查询数组
			$q = [];
			$i = 0;
			foreach ($a as $k => $v) {
				foreach($unique as $vv) {
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
				foreach($this->DB->result($this->Query->get($q, $this->table, '*', 'OR'), false) as $v) {
					foreach($a as $kk => $vv) {
						foreach($unique as $vvv) {
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
			if (!$a[$k] = $this->_w($v, false, $args[$k])) {
				unset($a[$k]);
			}
		}

		// 添加进去
		if (!$a || !($r = $this->DB->insert($this->Query->add(array_values($a), $this->table), false))) {
			return false;
		}

		// 完成回调
		if (!$this->insert_id) {
			foreach ($a as $k => $v) {
				$this->_c((object) $v, false, $v + $args[$k]);
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
		foreach ($args as $k => $v) {
			if (!$a[$k] = $this->defaults($v, ['sets', 'set'], true)) {
				return false;
			}
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
				foreach($this->DB->result($this->Query->get($q, $this->table, '*', 'OR'), false) as $v) {
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
			if (!$a[$k] = $this->_w($v, isset($old[$k]) ? $old[$k] : false, $args[$k])) {
				unset($a[$k]);
			}
		}

		// 修改
		if (!$a || ($r = $this->DB->replace($this->Query->set(array_values($a), $this->table), false)) === false) {
			return false;
		}

		// 完成回调
		if (!$this->insert_id) {
			foreach ($a as $k => $v) {
				$this->_c((object) ($v + (isset($old[$k]) ? (array) $old[$k] : [])), isset($old[$k]) ? $old[$k] : false, $v + $args[$k]);
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
		if (!$w || ($c && !($old = $this->DB->result($this->Query->get($w, $this->table), false)))) {
			return false;
		}


		// 主要 字段检测 和唯值检测
		$unique = array_merge([$this->primary],  $this->unique ? $this->unique : []);
		foreach ($unique as $v) {
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
			if (!$a = $this->_w($a, $v, $args)) {
				return false;
			}
		}

		// 更新
		$r = $this->DB->update($this->Query->update($a, $w, $this->table), false);

		// 完成回调
		foreach ($old as $v) {
			$new = $a;
			foreach ($this->primary as $vv) {
				$new[$vv] = $v->{$vv};
			}
			$this->_c((object) ($new + ((array) $v)), $v, $new + $args);
		}
		return $r;
	}



	public function deletes($a, $c = true) {
		if (!$a || !$this->deletes || !is_array($a)) {
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
		if (!$w || ($c && !($old = $this->DB->result($this->Query->get($w, $this->table), false))) || !($r = $this->DB->delete($this->Query->delete($w, $this->table), false))) {
			return 0;
		}

		// 完成回调
		foreach($old as $v) {
			$this->_c(false, $v, false);
		}
		return $r;
	}



	public function create() {
		$this->slave = false;
		return $this->create && $this->DB->create($this->Query->create($this->create, $this->table, $this->engine), false);
	}

	public function drop() {
		$this->slave = false;
		return $this->DB->drop($this->Query->drop($this->table), false);
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
	protected function _r($r) {
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
		return $r;
	}


	/**
	*	写入
	*
	*	回调
	**/
	protected function _w($w, $old, $args) {
		return $w;
	}

	/**
	*	完成
	*
	*	回调
	**/
	protected function _c($new, $old, $args) {
		$a = $new ? $new : $old;

		switch(count($this->primary)) {
			case 1:
				$this->data[$a->{$this->primary[0]}] = null;
				break;
			case 2:
				$this->data[$a->{$this->primary[0]}][$a->{$this->primary[1]}] = null;
				break;
			case 3:
				$this->data[$a->{$this->primary[0]}][$a->{$this->primary[1]}][$a->{$this->primary[2]}] = null;
				break;
			case 4:
				$this->data[$a->{$this->primary[0]}][$a->{$this->primary[1]}][$a->{$this->primary[2]}][$a->{$this->primary[3]}] = null;
				break;
			default:
				$this->data = [];
		}
	}
}