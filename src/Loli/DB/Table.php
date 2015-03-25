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
/*	Updated: UTC 2015-03-25 04:25:41
/*
/* ************************************************************************** */
namespace Loli;
class Table{
	// table 表
	protected $table;

	// indexs 索引
	protected $indexs = [];

	// 默认选择字段
	protected $fields = [];

	// 默认查询数组
	protected $querys = [];

	// 默认默认选项
	protected $options = [];

	// 默认插入数组
	protected $insert = [];

	// 自增字段
	protected $insertID;

	// 主要字段
	protected $primary = [];

	// 唯一值 记住是二维数组
	protected $uniques = [];

	// 创建
	protected $create = [];

	// 数据库引擎
	protected $engine = false;

	// 软删除
	protected $softDelete;

	// 缓存数据
	protected $data = [];

	// 缓存时间
	protected $ttl = 0;




	// 是否过滤掉不必要的字段
	protected $intersect = false;

	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}

	protected function table(array $args, $do = 0) {
		return $this->table;
	}




	/**
	*	取得查询字符串
	*
	*	1 参数 查询数组
	*
	*	返回值 字符串
	**/
	public function query(array $query) {
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
}