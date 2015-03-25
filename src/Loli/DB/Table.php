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
/*	Updated: UTC 2015-03-25 05:23:22
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
}