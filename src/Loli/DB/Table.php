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
/*	Updated: UTC 2015-05-23 11:57:00
/*
/* ************************************************************************** */
namespace Loli\DB;
abstract class Table extends Cursor{

	// 过滤
	protected $callback = true;

	/**
	 * __construct
	 * @param Base|NULL   $DB
	 * @param array|string $tables
	 * @param array  $indexs 索引
	 */
	public function __construct(array $querys = []) {
		$this->querys($querys);
	}

	/**
	 * callback 设置回调
	 * @param  boolean   $callback
	 * @return this
	 */
	public function callback($callback) {
		$this->callback = $callback;
		return $this;
	}


	protected function read(Row &$value) {

	}

	protected function write($name, Iterator $value = NULL) {

	}

	protected function success($name, Iterator $value = NULL) {

	}

	public function __call($name, array $args) {
		$name = strtolower($name);
		if (!$this->callback) {
			return parent::__call($name, $args);
		}

		switch ($name) {
			case 'insert':
				// 插入
				$this->write($name);
				$result = parent::__call($name, $args);
				$this->success($name);
				break;
			case 'update':
				// 更新
				$select = parent::select();
				$this->write($name, $select);
				$result = parent::__call($name, $args);
				$this->success($name, $select);
				break;
			case 'delete':
				// 删除
				$select = parent::select();
				$result = parent::__call($name, $args);
				$this->success($name, $select);
				break;
			case 'select':
				$result = parent::__call($name, $args);
				foreach($result as &$value) {
					$this->read($value);
				}
			default:
				$result = parent::__call($name, $args);
		}

		return $result;
	}
}