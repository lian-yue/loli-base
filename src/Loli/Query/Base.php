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
/*	Updated: UTC 2015-02-10 06:22:25
/*
/* ************************************************************************** */
namespace Loli\Query;
use Loli\Search;

abstract class Base{


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
	abstract public function create(array $array, $table, $engine = false);


	/**
	 * 添加
	 * @param [type] $array 一维数组 或二维数组
	 * @param [type] $table 表名称
	 * @return false or string
	 */
	abstract public function add(array $array, $table);
	/**
	 * 写入
	 * @param [type] $array 一维数组 或二维数组
	 * @param [type] $table 表名称
	 * @return false or string
	 */
	abstract public function set(array $array, $table);

	/**
	 * 获得
	 * @param  [type] $query   查询数组
	 * @param  [type] $table   表名称
	 * @param  string $logical 运算符
	 * @return false or string
	 */
	abstract public function get(array $query, $table, $logical = 'AND');

	/**
	 * 更新
	 * @param  [type] $array   修改数组
	 * @param  [type] $query   查询数组
	 * @param  [type] $table   表名称
	 * @param  string $logical 运算符
	 * @return false or string
	 */
	abstract public function update(array $array, array $query, $table, $logical = 'AND');

	/**
	 * 删除
	 * @param  [type] $query   查询数组
	 * @param  [type] $table   表名称
	 * @param  string $logical 运算符
	 * @return false or string
	 */
	abstract public function delete(array $query, $table, $logical = 'AND');


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
	public function parse(array $query, $args = false) {
		$results = [];
		foreach ($query as $key => $value) {

			// 是参数
			if ($value instanceof Param) {
				$results[] = $value;
				continue;
			}

			// 分组的
			if ($key === '$group') {
				$value = (array) $value;
				foreach($value as &$v) {
					if (is_array($args) && !empty($args[$v]['column'])) {
						$results[] = new Option('group', $args[$v]['column'], $args[$v]);
					} else {
						$results[] = new Option('group', $v);
					}
				}
				continue;
			}

			// 排序的
			if ($key === '$order') {
				$order = [];
				foreach ((array) $value as $k => $v) {
					if (is_array($args) && !empty($args[$k]['column'])) {
						$results[] = new Option('order', [$args[$k]['column'] => $v], $args[$k]);
					} else {
						$results[] = new Option('order', [$k => $v]);
					}
				}
				continue;
			}

			// 其他选项
			if ($key && $key{0} == '$') {
				$results[] = new Option(substr($key, 1), $value);
				continue;
			}

			// 查询的
			$option = empty($args[$key]) ? [] : (is_array($args) ? $args[$key] : ['column' => $args[$key]]);
			$results[] = new Param(['column' => empty($args[$key]['column']) ? $key : $args[$key]['column'], 'value' => $value] + $option);
		}
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