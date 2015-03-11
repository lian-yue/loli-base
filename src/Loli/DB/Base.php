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
/*	Updated: UTC 2015-03-11 09:22:20
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\Log;
abstract class Base{

	/*
	// 主数据库
	private $_masters = [];

	// 主数据库链接
	private $_masterLink;

	// 上次ping时间
	protected $masterPingTime;




	// 从数据库
	private $_slaves = [];

	// 从数据库链接
	private $_slaveLink;

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
	public $insertID = false;

	// 查询数量的 rows
	public $foundRows = false;

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
			$this->addMaster($args['master']);
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
			if ($this->_slaveLink === NULL) {
				shuffle($this->_slaves);
				$i = 0;
				foreach ($this->_slaves as $args) {
					if ($i > 3) {
						break;
					}
					if ($this->explain) {
						$this->_slaveLink = $this->connect($args);
					} else {
						try {
							$this->_slaveLink = $this->connect($args);
							break;
						} catch (\Exception $e) {
						}
					}
					++$i;
				}
				$this->slavePingTime = time();
			}

			// 自动ping
			if ($this->_slaveLink && $this->pingInterval > 0 && ($this->slavePingTime + $this->pingInterval) < time()) {
				$this->slavePingTime = time();
				$this->ping();
			}

			// 从数据库有 返回
			if ($this->_slaveLink) {
				return $this->_slaveLink;
			}
		}



		// 主数据库
		$this->slave = false;

		// 链接主数据库
		if ($this->_masterLink === NULL) {
			shuffle($this->_masters);
			$i = 0;
			foreach ($this->_masters as $args) {
				if ($i > 3) {
					break;
				}
				if ($this->explain) {
					$this->_masterLink = $this->connect($args);
				} else {
					try {
						$this->_masterLink = $this->connect($args);
						break;
					} catch (\Exception $e) {
					}
				}
				++$i;
			}
			$this->masterPingTime = time();
		}

		if (!$this->_masterLink) {
			throw new ConnectException('this.link()', 'Master link is unavailable');
		}

		// 自动 ping
		if ($this->pingInterval > 0 && ($this->masterPingTime + $this->pingInterval) < time()) {
			$this->masterPingTime = time();
			$this->ping();
		}
		return $this->_masterLink;
	}

	public function addSlave(array $args) {
		$this->_slaves[] = $args;
		return true;
	}

	public function addMaster(array $args) {
		$this->_masters[] = $args;
		return true;
	}

	// 记录日志
	public function addLog($query, $data = NULL) {
		$data = $data !== NULL ? "\n". (is_array($data) || is_object($data) ? var_export($data, true) : (is_bool($data) ? ($data ? 'True' : 'False') : (string) $data))  : '';
		if (class_exists('Loli\Log')) {
			Log::write($query . $data, Log::LEVEL_QUERY);
		}
	}

	abstract public function connect(array $args);


	abstract public function ping($slave = NULL);
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
	abstract public function aggregate($query, $slave = true);
	abstract public function count($query, $slave = true);
	abstract public function group($query, $slave = true);
	*/

	// 绑定个列名到变量
	//abstract public function bindColumn($column, &$paramm, $type = NULL, $maxLength = NULL, $driverData = NULL);
	//abstract public function bindParam($param, &$variable, $type = NULL, $length, $driverOptions);
	//abstract public function bindValue($param, $value, $type = NULL, $length, $driverOptions);


	//abstract public function execute(array $params);




	// 主服务器
	private $_masterServers;

	// 主连接
	private $_masterLink;


	// 上次ping时间
	protected $masterPingTime;



 	// 从服务器
	private $_slaveServers;

	// 从连接
	private $_slaveLink;

	// 上次ping时间
	protected $slavePingTime;



	// ping 间隔时间  0 ＝ 不尝试 5 ＝ 5秒一次
	protected $pingInterval = 5;

	// 位置 debug 用的
	protected $explain = false;

	// 连接协议
	protected $protocol;

	// 是否是事务
	protected $inTransaction = false;



	// 是否是运行的 slave
	public $slave = true;



	public function __construct(array $masterServers, array $slaveServers = [], $explain = false) {
		$this->_masterServers = $masterServers;
		$this->_slaveServers = $slaveServers;
		$this->explain = $explain;
	}


	public function link($slave = NULL) {
		if ($slave !== NULL) {
			$this->slave = $slave;
		}

		// 从数据库
		if ($this->slave && $this->_slaveServers && !$this->inTransaction) {


			// 链接从数据库
			if ($this->_slaveLink === NULL) {
				$this->_slaveLink = false;
				shuffle($this->_slaveServers);
				$i = 0;
				foreach($this->_slaveServers as $servers) {
					if ($i > 3) {
						break;
					}
					try {
						$this->_slaveLink = $this->connect($this->parseServers($servers));
						break;
					} catch (\Exception $e) {
						if (!$this->explain) {
							throw $e;
						}
						$this->_slaveLink = false;
					}
					++$i;
				}
				$this->slavePingTime = time();
			}


			// 自动ping
			if ($this->_slaveLink && $this->pingInterval > 0 && ($this->slavePingTime + $this->pingInterval) < time()) {
				$this->slavePingTime = time();
				$this->ping();
			}

			// 从数据库有 返回
			if ($this->_slaveLink) {
				return $this->_slaveLink;
			}
		}





		// 主数据库
		$this->_master = false;

		// 链接主数据库
		if ($this->_masterLink === NULL) {
			$this->_masterLink = false;
			shuffle($this->_masterServers);
			$i = 0;
			foreach ($this->_masterServers as $servers) {
				if ($i > 3) {
					break;
				}
				try {
					$this->_masterLink = $this->connect($this->parseServers($servers));
					break;
				} catch (\Exception $e) {
					if (!$this->explain) {
						throw $e;
					}
					$this->_masterLink = false;
				}
				++$i;
			}
			$this->masterPingTime = time();
		}

		if (!$this->_masterLink) {
			throw new ConnectException('this.link()', 'Master link is unavailable');
		}

		// 自动 ping
		if ($this->pingInterval > 0 && ($this->masterPingTime + $this->pingInterval) < time()) {
			$this->masterPingTime = time();
			$this->ping();
		}
		return $this->_masterLink;
	}

	public function parseServers($servers) {
		$servers = (array) $servers;
		if (!$servers) {
			throw new ConnectException('this.parseServers()', 'Master link is unavailable');
		}
		if (is_int(key($servers))) {

		}
	}


	public function protocol() {
		if ($this->protocol === NULL) {
			$servers = $this->parseServers(reset($this->_masterServers));
			$this->protocol = reset($servers)['protocol'];
		}
		return $this->protocol;
	}

	public function statement($statement, $tables, $slave = false) {
		if ($tables instanceof Statement) {
			$tables->__construct($this, $statement, $slave);
			return $tables;
		}
		$class = ___CLASS__ . 'Statement';
		return new $class($this, $statement, $tables, $slave);
	}

	public function inTransaction() {
		return $this->inTransaction;
	}

	abstract public function command($command, $slave = NULL);
	abstract public function ping($slave = NULL);
	abstract public function connect(array $servers);
	abstract public function beginTransaction();
	abstract public function commit();
	abstract public function rollBack();
	abstract public function lastInsertID();
	abstract public function tables();
	abstract public function key($key);
	abstract public function value($value);


	public function exists($tables) {
		return $this->statement(__METHOD__, $tables);
	}
	public function create($tables, array $values, array $options = []) {
		return $this->statement(__METHOD__, $tables)->values($values)->options($options);
	}
	public function truncate($tables, array $options = []) {
		return $this->statement(__METHOD__, $tables)->options($options);
	}
	public function drop($tables, array $options = []) {
		return $this->statement(__METHOD__, $tables)->options($options);
	}
	public function select($tables, array $querys = [], array $options = [], $slave = NULL) {
		return $this->statement(__METHOD__, $tables, $slave)->querys($querys)->options($options);
	}
	public function insert($tables, array $documents = [], array $options = []) {
		return $this->statement(__METHOD__, $tables)->documents($documents)->options($options);
	}
	public function update($tables, array $document = [], array $queyrs = [], array $options = []) {
		return $this->statement(__METHOD__, $tables)->document($document)->queyrs($queyrs)->options($options);
	}
	public function delete($tables, array $querys = [], array $options = []) {
		return $this->statement(__METHOD__, $tables)->queyrs($queyrs)->options($options);
	}
}