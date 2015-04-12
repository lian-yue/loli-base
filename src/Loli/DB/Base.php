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
/*	Updated: UTC 2015-04-10 13:03:34
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\Log;
abstract class Base{



	// 主服务器
	private $_masterServers;

	// 主连接
	private $_masterLink;


	// 上次ping时间
	protected $_masterPingTime;



 	// 从服务器
	private $_slaveServers;

	// 从连接
	private $_slaveLink;

	// 上次ping时间
	protected $_slavePingTime;



	// ping 间隔时间  0 ＝ 不尝试 5 ＝ 5秒一次
	protected $pingInterval = 5;

	// 位置 debug 用的
	protected $explain = false;

	// 连接协议
	protected $protocol;

	// 链接到的表 or 链接的id
	protected $database;

	// 是否是事务
	protected $inTransaction = false;

	// cursor 方法名
	protected $cursor = 'SQLCursor';

	public static $querySum = 0;
	public static $queryROW = 0;

	// 是否是运行的 slave
	public $slave = true;

	/**
	 * __construct
	 * @param array   $masterServers 主服务器
	 * @param array   $slaveServers  从服务器
	 * @param boolean $explain       是否explain
	 */
	public function __construct(array $masterServers, array $slaveServers = [], $explain = false) {
		foreach ($masterServers as $servers) {
			$this->_masterServers[] = $this->parseServers($servers);
		}
		foreach ($slaveServers as $servers) {
			$this->_slaveServers[] = $this->parseServers($servers);
		}
		$this->explain = $explain;
	}

	/**
	 * __get
	 * @param  string $name
	 */
	public function __get($name) {
		return $this->cursor($name);
	}

	/**
	 * link
	 * @param  boolean $slave 使用链接类型
	 * @return
	 */
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
						$this->_slaveLink = $this->connect($servers);
						$this->_slavePingTime = time();
						break;
					} catch (\Exception $e) {
						if (!$this->explain) {
							throw $e;
						}
						$this->_slaveLink = false;
					}
					++$i;
				}
			}


			// 自动ping
			if ($this->_slaveLink && $this->pingInterval > 0 && ($this->_slavePingTime + $this->pingInterval) < time()) {
				$this->_slavePingTime = time();
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
					$this->_masterLink = $this->connect($servers);
					$this->_masterPingTime = time();
					break;
				} catch (\Exception $e) {
					if (!$this->explain) {
						throw $e;
					}
					$this->_masterLink = false;
				}
				++$i;
			}
		}

		if (!$this->_masterLink) {
			throw new ConnectException('this.link()', 'Master link is unavailable');
		}

		// 自动 ping
		if ($this->pingInterval > 0 && ($this->_masterPingTime + $this->pingInterval) < time()) {
			$this->_masterPingTime = time();
			$this->ping();
		}
		return $this->_masterLink;
	}

	/**
	 * parseServers 解析服务器信息
	 * @param  array|string $servers 服务器数组
	 * @return array
	 */
	protected function parseServers($servers) {
		$servers = array_filter(is_array($servers) ? $servers : array_map('trim', explode(',', $servers)));
		if ($servers && !is_int(key($servers))) {
			$servers = [$servers];
		}
		$results = [];
		foreach ($servers as $value) {
			if (!$value) {
				continue;
			}
			if (!is_array($value)) {
				$parse = parse_url($value);
				$value = [];
				foreach (['scheme' => 'protocol', 'host' => 'hostname', 'user' => 'username', 'pass' => 'password', 'path' => 'database'] as $k => $v) {
					if (isset($parse[$k])) {
						$value[$v] = $parse[$k];
					}
				}
			}
			if (empty($value['protocol'])) {
				throw new ConnectException('this.parseServers()', 'The database server protocol can not be empty');
			}
			if (empty($value['database'])) {
				throw new ConnectException('this.parseServers()', 'Database is not selected');
			}
			if (!strpos($value['database'], '.') && !strpos($value['database'], '/') && !strpos($value['database'], '\\')) {
				$value['database'] = ltrim($value['database'], '/');
			}
			$value += ['hostname' => 'localhost', 'username' => 'root', 'password' => NULL];
			$results[] = $value;
		}
		if (!$results) {
			throw new ConnectException('this.parseServers()', 'The database server is empty');
		}
		return $results;
	}

	/**
	 * cursor 查询游标
	 * @param  array|string $tables 表名可以是数组
	 * @return class cursor
	 */
	public function cursor($tables = NULL) {
		$class = __NAMESPACE__ . '\\' . $this->cursor;
		return new $class($this, $tables);
	}

	/**
	 * log 写入日志
	 * @param  string|array|object $query 查询语句
	 * @param  string|array|object $value 结果数据
	 * @return this
	 */
	public function log($query, $value) {
		$query = is_array($query) || is_object($query) ? var_export($query, true) : $query;
		$value = is_array($value) || is_object($value) ? var_export($value, true) : $value;
		Log::debug($query ."\n\n". $value);
		return $this;
	}


	/**
	 * protocol 返回链接的协议
	 * @return  string
	 */
	public function protocol() {
		if ($this->protocol === NULL) {
			$this->protocol = reset($this->_masterServers)[0]['protocol'];
		}
		return $this->protocol;
	}

	/**
	 * database 返回链接的数据库 or 文件名 or ID
	 * @param  boolean $name 是否只显示名称
	 * @return string
	 */
	public function database() {
		if ($this->database === NULL) {
			$this->database = reset($this->_masterServers)[0]['database'];
		}
		return basename($this->database);
	}

	/**
	 * inTransaction 是否在执行事务
	 * @return boolean
	 */
	public function inTransaction() {
		return $this->inTransaction;
	}

	/**
	 * connect 链接到服务器
	 * @param  array  $servers 服务器信息
	 * @return 链接资源 or 对象
	 */
	abstract protected function connect(array $servers);


	/**
	 * ping
	 * @param  boolean $slave
	 * @return this
	 */
	abstract protected function ping($slave = NULL);

	/**
	 * command
	 * @param  string|array|object    $command
	 * @param  boolean                $slave
	 * @return array|boolean|integer
	 */
	abstract public function command($command, $slave = NULL);

	/**
	 * beginTransaction 开始事务
	 * @return this
	 */
	abstract public function beginTransaction();

	/**
	 * commit 提交事务
	 * @return this
	 */
	abstract public function commit();

	/**
	 * rollBack 滚回事务
	 * @return this
	 */
	abstract public function rollBack();

	/**
	 * lastInsertID
	 * @return integer
	 */
	abstract public function lastInsertID();

	/**
	 * key 转义键名
	 * @param  string $key
	 * @return string|boolean
	 */
	abstract public function key($key);

	/**
	 * value 转义键值
	 * @param  * $value
	 * @return *
	 */
	abstract public function value($value);

}