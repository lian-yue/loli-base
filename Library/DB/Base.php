<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-09 07:56:37
/*	Updated: UTC 2015-01-26 13:18:13
/*
/* ************************************************************************** */
namespace Loli\DB;

abstract class Base{

	// 主数据库
	private $_masters = [];
	private $_masterLink = null;


	// 从数据库
	private $_slaves = [];
	private $_slaveLink = true;


	// 是否运行过链接
	protected $link = false;

	// 是否是运行的 slave
	public $slave = true;


	// 创建数据的返回 ID
	public $insert_id = 0;

	// 查询带有 found_rows 的数量
	public $found_rows = 0;

	// 是否自动提交
	public $autoCommit = true;

	// 查询日志
	public $data = [];

	// debug
	public $debug = false;


	// 数据查询次数
	public static $querySum = 0;

	// 查询行
	public static $queryRow = 0;

	public function __construct($args) {
		if (!empty($args['slave'])) {
			foreach ($args['slave'] as $v) {
				$this->addSlave($v);
			}
			unset($args['slave']);
		}
		$this->debug = !empty($args['debug']);
		if (!empty($args['master'])) {
			foreach ($args['master'] as $v) {
				$this->addMaster($v);
			}
			unset($args['master']);
		} else {
			$this->addMaster($args);
		}
	}


	public function link($slave = true) {
		$this->link = true;
		if ($slave && $this->_slaves && $this->autoCommit) {
			$this->slave = true;
			if ($this->_slaveLink === null) {
				shuffle($this->_slaves);
				$i = 0;
				foreach ($this->_slaves as $args) {
					if (($this->_slaveLink = $this->connect($args)) || $i > 3) {
						$this->isLink = true;
						break;
					}
					++$i;
				}
			}
			if ($this->_slaveLink) {
				return $this->_slaveLink;
			}
		}
		$this->slave = false;
		if ($this->_masterLink === null) {
			shuffle($this->_masters);
			$i = 0;
			foreach ($this->_masters as $args) {
				if (($this->_masterLink = $this->connect($args)) || $i > 3) {
					$this->isLink = true;
					break;
				}
				++$i;
			}
			!$this->_masterLink && $this->debug && $this->exitError('Link');
		}
		return $this->_masterLink;
	}

	public function addSlave($args) {
		$this->_slaves[] = $args;
		return true;
	}
	public function addMaster($args) {
		$this->_masters[] = $args;
		return true;
	}
	public function exitError($query) {
		($erron = $this->errno()) && !trigger_error($this->error(), E_USER_ERROR);
	}

	abstract public function connect($args);
	abstract public function error();
	abstract public function errno();



	abstract public function create($query);
	abstract public function drop($query);


	abstract public function insert($query);
	abstract public function replace($query);
	abstract public function update($query);
	abstract public function delete($query);



	abstract public function results($query, $slave = true);
	abstract public function row($query, $slave = true);
	abstract public function count($query, $slave = true);



	abstract public function start();
	abstract public function commit();
	abstract public function rollback();
}