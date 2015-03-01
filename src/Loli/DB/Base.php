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
/*	Updated: UTC 2015-02-28 11:24:28
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\Log;
abstract class Base{

	// 主数据库
	private $_masters = [];

	// 主数据库链接
	private $_masterLink;

	// 上次ping时间
	protected $masterPingTime;




	// 从数据库
	private $_slaves = [];

	// 从数据库链接
	private $_slavelink;

	// 上次ping时间
	protected $slavePingTime;




	// ping 间隔时间  0 ＝ 不尝试 5 ＝ 5秒一次
	protected $pingInterval = 5;

	// 是否运行过链接
	protected $link = false;

	// 位置 debug用的
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
		$this->explain = !empty($args['explain']);

		// 从数据库
		if (!empty($args['slaves'])) {
			foreach ($args['slaves'] as $slave) {
				$this->addSlave($slave);
			}
			unset($args['slaves']);
		} elseif (!empty($args['slave'])) {
			$this->addSlave($args['slave']);
			unset($args['slave']);
		}

		// 主数据库
		if (!empty($args['masters'])) {
			foreach ($args['masters'] as $master) {
				$this->addMaster($master);
			}
			unset($args['masters']);
		} elseif (!empty($args['master'])) {
			$this->addSlave($args['master']);
			unset($args['master']);
		} else {
			$this->addMaster($args);
		}
	}


	public function link($slave = true) {
		// 是否运行过链接
		$this->link = true;

		// 从数据库
		if ($slave && $this->_slaves && $this->autoCommit) {
			$this->slave = true;

			// 链接从数据库
			if ($this->_slavelink === null) {
				shuffle($this->_slaves);
				$i = 0;
				foreach ($this->_slaves as $args) {
					if ($i > 3) {
						break;
					}
					if ($this->explain) {
						$this->_slavelink = $this->connect($args);
					} else {
						try {
							$this->_slavelink = $this->connect($args);
							break;
						} catch (\Exception $e) {
						}
					}
					++$i;
				}
				$this->slavePingTime = time();
			}

			// 自动ping
			if ($this->_slavelink && $this->pingInterval > 0 && ($this->slavePingTime + $this->pingInterval) < time()) {
				$this->slavePingTime = time();
				$this->ping();
			}

			// 从数据库有 返回
			if ($this->_slavelink) {
				return $this->_slavelink;
			}
		}



		// 主数据库
		$this->slave = false;

		// 链接主数据库
		if ($this->_masterink === null) {
			shuffle($this->_masters);
			$i = 0;
			foreach ($this->_masters as $args) {
				if ($i > 3) {
					break;
				}
				if ($this->explain) {
					$this->_masterink = $this->connect($args);
				} else {
					try {
						$this->_masterink = $this->connect($args);
						break;
					} catch (\Exception $e) {
					}
				}
				++$i;
			}
			$this->masterPingTime = time();

			$this->_masterink || $this->addLog('Link', 'Master link is unavailable', 2);
		}

		// 自动 ping
		if ($this->pingInterval > 0 && ($this->masterPingTime + $this->pingInterval) < time()) {
			$this->masterPingTime = time();
			$this->ping();
		}
		return $this->_masterink;
	}

	public function addSlave(array $args) {
		$this->_slaves[] = $args;
		return true;
	}
	public function addMaster(array $args) {
		$this->_masters[] = $args;
		return true;
	}

	public function addLog($query, $data = '', $level = 0, $code = 0, $file = __FILE__ , $line = __LINE__) {
		$data = $data ? "\n". (is_array($data) || is_object($data) ? var_export($data, true) : (string) $data)  : '';

		// 记录日志
		if (class_exists('Loli\Log')) {
			$levels = [0 => Log::LEVEL_QUERY, 1 => Log::LEVEL_ERROR, 2 => Log::LEVEL_ALERT];
			Log::write($query . $data, $levels[$level]);
		}

		// 连接错误
		if ($level == 2) {
			throw new ConnectException($query, $data, $code, $file, $line);
		}

		// 查询错误
		if ($level == 1) {
			throw new Exception($query, $data, $code, $file, $line);
		}
	}

	abstract public function connect(array $args);


	abstract public function ping($slave === null);
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


	abstract public function startTransaction();
	abstract public function commit();
	abstract public function rollback();
}