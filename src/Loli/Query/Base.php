<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-10-29 15:22:22
/*	Updated: UTC 2015-01-22 13:22:03
/*
/* ************************************************************************** */
namespace Loli\Query;
use Loli\Search;

abstract class Base{

	// 执行次数
	public $count = 0;


	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}


	/**
	 * 创建表
	 * @param  array   $array  字段数组
	 * @param  string  $table  表名称
	 * @param  string  $engine 数据库引擎 可选
	 * @return false or string
	 */
	abstract public function create($array, $table, $engine = false);



	/**
	 * 删除表
	 * @param  string  $table  名称
	 * @return false or string
	 */
	abstract public function drop($table);

	/**
	 * 添加
	 * @param [type] $array 一维数组 或二维数组
	 * @param [type] $table 表名称
	 * @return false or string
	 */
	abstract public function add($array, $table);
	/**
	 * 写入
	 * @param [type] $array 一维数组 或二维数组
	 * @param [type] $table 表名称
	 * @return false or string
	 */
	abstract public function set($array, $table);

	/**
	 * 获得
	 * @param  [type] $query   查询数组
	 * @param  [type] $table   表名称
	 * @param  [type] $fields  选择字段
	 * @param  string $logical 运算符
	 * @return false or string
	 */
	abstract public function get($query, $table, $fields = ['*'], $logical = 'AND');

	/**
	 * 更新
	 * @param  [type] $array   修改数组
	 * @param  [type] $query   查询数组
	 * @param  [type] $table   表名称
	 * @param  string $logical 运算符
	 * @return false or string
	 */
	abstract public function update($array, $query, $table, $logical = 'AND');

	/**
	 * 删除
	 * @param  [type] $query   查询数组
	 * @param  [type] $table   表名称
	 * @param  string $logical 运算符
	 * @return false or string
	 */
	abstract public function delete($query, $table, $logical = 'AND');


	/**
	 * 转义查询
	 * @param  [type]  $value 转义的数据
	 * @return
	 */
	abstract public function escape($value);

	/**
	 * 转义KEY
	 * @param  [type]  $key        值
	 * @return false 或者字符串
	 */
	abstract public function key($value);


	/**
	 * 解析查询
	 * @param  [type]  $query 数组 or 字符串
	 * @param  boolean $args 允许的字段
	 * @return array
	 */
	public function parse($query, $args = false) {
		$query = parse_string($query);
		$r = [];
		foreach ($query as $k => $v) {
			if ($k && $k{0} == '$') {
				// 设置的

				if ($k == '$groupby') {
					$v = is_array($v) ? $v : explode(',', $v);
					if (is_array($args)) {
						foreach($v as $kk => $vv) {
							$v[$kk] = empty($args[$vv]['column']) ? $vv : $args[$vv]['column'];
						}
					}
				}

				if ($k == '$orderby') {
					$order = empty($query['$order']) ? [] : (array) $query['$order'];
					if (is_object($v)) {
						$v = [$v];
					}
					$v = (array) $v;
					foreach ($v as $kk => $vv) {
						if (!is_object($vv)) {
							$vv = (object) ['column' => $vv];
						}
						$vv->column = is_array($vv->column) ? $vv->column : explode(',', $vv->column);
						$vv->desc = isset($vv->desc) ? $vv->desc : (isset($order[$kk]) && strtoupper($order[$kk]) === 'DESC');
						if (is_array($args)) {
							foreach ($vv->column as $kkk => $vvv) {
								$vv->column[$kkk] = empty($args[$vvv]['column']) ? $vvv : $args[$vvv]['column'];
								$vv->function[$kkk] = isset($vv->function[$kkk]) || empty($args[$vvv]['function']) ? '' : $args[$vvv]['function'];
							}
						}
						$v[$kk] = $vv;
					}
				}
				$r[$k] = $v;
				continue;
			}


			// 单对象
			if (is_object($v) && ($v = $this->_parse($v, $k))) {
				$r[] = $v;
				continue;
			}

			// 多对象
			if ((is_array($v) && is_object(reset($v)))) {
				foreach ($v as $vv) {
					if ($vv = $this->_parse($vv, $k)) {
						$r[] = $vv;
					}
				}
			}

			// 普通输入的
			if ((!is_array($args) || isset($args[$k])) && ($v = $this->_parse($v, $k, isset($args[$k]) ? $args[$k] : false))) {
				$r[] = $v;
			}
		}
		return $r;
	}

	private function _parse($query, $key, $args = false) {
		if ($query === null) {
			return false;
		}
		if (!is_object($query)) {
			$query = (object) ['value' => $query];
			if (is_array($args)) {
				foreach ($args as $k => $v) {
					$query->{$k} = $v;
				}
			} elseif ($args) {
				$query->compare = $args;
			}
		}
		if (!isset($query->column)) {
			$query->column = $key;
		}
		if (!isset($query->value) || !$query->column) {
			return false;
		}
		return $query;
	}


	/**
	 * 搜索
	 * @param  array or string $value
	 * @return object class search
	 */
	public function search($value) {
		return new Search($value);
	}
}