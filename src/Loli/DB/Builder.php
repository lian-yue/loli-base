<?php
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
abstract class Builder{
	protected $cursor;

	// 有修改表的日志  [database=>[表名=>执行时间戳]]
	protected $useTables = [];
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

	/**
	 * getWrite  读取使用主从
	 * @return boolean
	 */
	protected function getWrite() {
		if ($this->->write === NULL) {
			$this->write = $this->DB->write;
		}
		if (!$this->write) {
			return false;
		}
		if (!empty(self::$_logs[$this->DB->database()]) && $this->useTables) {
			foreach (self::$_logs[$this->DB->database()] as $table => $time) {
				if (in_array($table, $this->useTables) && $time > time()) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * setWrite 设置主从
	 * @param  integer $ttl
	 */
	protected function setWrite($ttl = 2) {
		if ($this->useTables) {
			foreach ($this->useTables as $table) {
				self::$_logs[$this->DB->database()][$table] = time() + ($ttl < 2 ? 2 : $ttl);
			}
		}
	}

	public function getUseTables() {
		return $this->useTables;
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
	 * count
	 * @return integer
	 */
	abstract public function count();


	/**
	 * deleteCacheSelect 删除读取缓存
	 * @param  integer $refresh 延迟刷新时间
	 * @return this
	 */
	abstract public function deleteCacheSelect($refresh = NULL);


	/**
	 * deleteCacheCount 删除数量缓存
	 * @param  integer $refresh 延迟刷新时间
	 * @return this
	 */
	abstract public function deleteCacheCount($refresh = NULL);

}