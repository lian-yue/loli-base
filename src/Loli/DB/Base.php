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
/*	Updated: UTC 2015-02-25 14:45:44
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\Log;
abstract class Base{

	// 主数据库
	private $_masters = [];
	private $_master;


	// 从数据库
	private $_slaves = [];
	private $_slave;


	// 是否运行过链接
	protected $link = false;

	// 位置
	protected $explain = false;

	// 是否自动提交
	protected $autoCommit = true;

	// 是否是运行的 slave
	public $slave = true;

	// 创建数据的返回 ID
	public $insertID = 0;

	// 数据查询次数
	public static $querySum = 0;

	// 查询行
	public static $queryRow = 0;

	public function __construct(array $args) {
		if (!empty($args['slave'])) {
			foreach ($args['slave'] as $v) {
				$this->addSlave($v);
			}
			unset($args['slave']);
		}
		$this->explain = !empty($args['explain']);

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
			if ($this->_slave === null) {
				shuffle($this->_slaves);
				$i = 0;
				foreach ($this->_slaves as $args) {
					if (($this->_slave = $this->connect($args)) || $i > 3) {
						$this->isLink = true;
						break;
					}
					++$i;
				}
			}
			if ($this->_slave) {
				return $this->_slave;
			}
		}
		$this->slave = false;
		if ($this->_master === null) {
			shuffle($this->_masters);
			$i = 0;
			foreach ($this->_masters as $args) {
				if (($this->_master = $this->connect($args)) || $i > 3) {
					$this->isLink = true;
					break;
				}
				++$i;
			}
			!$this->_master && $this->addLog('Link', $e->error(), 2);
		}
		return $this->_master;
	}

	public function addSlave($args) {
		$this->_slaves[] = $args;
		return true;
	}
	public function addMaster($args) {
		$this->_masters[] = $args;
		return true;
	}

	public function addLog($query, $value = '', $level = 0) {
		if (class_exists('Loli\Log')) {
			$data = $data ? "\n". (is_array($value) || is_object($value) ? var_export($data, true) : (string) $value)  : '';
			$levels = [0 => Log::LEVEL_QUERY, 1 => Log::LEVEL_ERROR, 2 => Log::LEVEL_ALERT];
			return Log::write($query . $data, $levels[$level]);
		}
		return false;
	}

	abstract public function connect($args);
	abstract public function error();
	abstract public function errno();


	abstract public function tables();

	abstract public function exists($table);
	abstract public function truncate($table);
	abstract public function drop($table);

	abstract public function create($query);
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