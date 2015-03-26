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
/*	Updated: UTC 2015-03-25 08:53:37
/*
/* ************************************************************************** */
namespace Loli;


class Query{
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
	protected $defaults = [];

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
	protected $cache = [0, 0];



	public function exists() {
		$count = 0;
		foreach ((array) $this->tables([], -1) as $table) {
			if ($this->DB->->exists()) {
				++$count;
			}
		}
		return $count;
	}

	public function truncate() {
		$r = 0;
		foreach ((array) $this->tables([], -1) as $table) {
			if ($this->DB->truncate()) {
				++$r;
			}
		}
		return $r;
	}

	public function drop() {
		$this->slave = false;
		$r = 0;
		foreach ((array) $this->tables([], -1) as $table) {
			if ($this->DB->drop()) {
				++$r;
			}
		}
		return $r;
	}


	public function create() {
		$this->slave = false;
		$r = 0;
		foreach ((array) $this->tables([], -1) as $table) {
			if ($this->create && $this->DB->create()) {
				++$r;
			}
		}
		return $r;
	}

}