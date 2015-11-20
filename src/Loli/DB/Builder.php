<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-04-24 02:47:43
/*	Updated: UTC 2015-05-23 10:35:18
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Cursor') || exit;
abstract class Builder{
	protected $cursor;

	// 有修改表的日志  [database=>[表名=>执行时间戳]]
	private static $_logs = [];


	public function __construct(Cursor $cursor) {
		$this->cursor = $cursor;
		if (!$cursor->DB) {
			throw new Exception('', 'Database connection can not be empty');
		}
	}

	public function __get($name) {
		return $this->cursor->$name;
	}


	public function search($value, $write = false) {
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		$value = preg_replace('/[\0000-\002F\003A-\0040\005B-0060\007B-\007F\FF01-\FF0F\FF1A-\FF20\FF3B-\FF40\FF5B-\FF65\FF0E-\FFA0]+/', ' ', mb_strtolower($value));
		$value = array_filter(array_map('trim', explode(' ', $value)));
		if ($write) {
			return $value;
		}
		return ['+' => $value, '-' => []];
	}



	/**
	 * getReadonly  读取是否用只读模式
	 * @return boolean
	 */
	protected function getReadonly() {
		if ($this->readonly === NULL) {
			if (!empty(self::$_logs[$this->DB->database()]) && ($useTables = $this->getUseTables())) {
				foreach (self::$_logs[$this->DB->database()] as $table => $time) {
					if (in_array($table, $useTables, true) && $time > time()) {
						return false;
					}
				}
			}
			return true;
		}
		return $this->readonly;
	}

	/**
	 * setReadonly 设置主从
	 * @param  integer $ttl
	 */
	protected function setReadonly($ttl = 2) {
		foreach ($this->getUseTables() as $table) {
			self::$_logs[$this->DB->database()][$table] = time() + ($ttl < 2 ? 2 : $ttl);
		}
	}


	/**
	 * lastInsertID 最后的 id
	 */
	public function lastInsertID($insertID = false) {
		return $insertID === false ? ($this->insertID ? $this->DB->lastInsertID($insertID) : $this->DB->lastInsertID()) : $this->DB->lastInsertID($insertID);
	}

	/**
	 * beginTransaction 开始事务
	 */
	public function beginTransaction() {
		$this->DB->beginTransaction();
	}

	/**
	 * inTransaction 是否事务
	 */
	public function inTransaction() {
		$this->DB->inTransaction();
	}

	/**
	 * commit 提交
	 */
	public function commit() {
		$this->DB->commit();
	}

	/**
	 * rollBack 滚回
	 */
	public function rollBack() {
		$this->DB->rollBack();
	}
	/**
	 * exists 判断某个表是否存在
	 * @return boolean
	 */
	abstract public function exists();

	/**
	 * create
	 * @return boolean
	 */
	abstract public function create();

	/**
	 * truncate
	 * @return boolean
	 */
	abstract public function truncate();

	/**
	 * drop
	 * @return boolean
	 */
	abstract public function drop();

	/**
	 * insert
	 * @return integer|array
	 */
	abstract public function insert();


	/**
	 * update
	 * @return integer|array
	 */
	abstract public function update();



	/**
	 * delete
	 * @return integer
	 */
	abstract public function delete();



	/**
	 * select
	 * @return array
	 */
	abstract public function select();


	/**
	 * selectRow  选择一行
	 * @return
	 */
	abstract public function selectRow();



	/**
	 * count
	 * @return integer
	 */
	abstract public function count();




	abstract public function flush();


	/**
	 * deleteCacheSelect 删除读取缓存
	 * @param  integer $refresh 延迟刷新时间
	 * @return this
	 */
	abstract public function deleteCacheSelect($refresh = NULL);


	abstract public function deleteCacheSelectRow($refresh = NULL);


	/**
	 * deleteCacheCount 删除数量缓存
	 * @param  integer $refresh 延迟刷新时间
	 * @return this
	 */
	abstract public function deleteCacheCount($refresh = NULL);


	abstract public function getUseTables();
}