<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-27 04:48:08
/*
/* ************************************************************************** */
namespace Loli\Database;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;


abstract class AbstractDatabase implements LoggerAwareInterface{
	use LoggerAwareTrait;

	// 主连接
	private $writeLink;

	// 上次ping时间
	private $writePingTime;


	// 从连接
	private $readLink;

	// 上次ping时间
	private $readPingTime;




	// ping 间隔时间  0 ＝ 不尝试 5 ＝ 5秒一次
	protected $pingInterval = 5;

	// 位置 debug 用的
	protected $explain = false;





	// 服务器组
	protected $servers = [];

	// 服务器默认参数
	protected $default = [];


	// 服务器信息
	protected $protocol;

	// 服务器信息
	protected $database;


	// 是否是事务
	protected $inTransaction = false;


	// 只读模式
	protected $readonly = false;


	/**
	 * __construct
	 * @param array   $servers 服务器
	 * @param array   $default 默认参数
	 */
	public function __construct(array $servers) {
		if (!is_int(key($servers))) {
			$servers = [$servers];
		}

		$this->default = reset($servers) + ['protocol' => '', 'hostname' => ['localhost'], 'username' => 'root', 'password' => '', 'readonly' => false];
		if (!$this->default['protocol'] && !$this->protocol) {
			throw new ConnectException(__METHOD__.'() Link protocol is empty');
		}
		if (!$this->default['database']) {
			throw new ConnectException(__METHOD__.'() Database is not selected');
		}
		if (!$this->protocol) {
			$this->protocol = $this->default['protocol'];
		}
		$this->database = $this->default['database'];
		$this->explain = !empty($this->default['explain']);
		$this->servers = $servers;

		shuffle($this->servers);
	}

	/**
	 * __get
	 * @param  string $name
	 */
	public function __get($name) {
		return $this->table($name);
	}
	public function getLogger() {
		return $this->logger;
	}
	/**
	 * link
	 * @param  null|boolean $readonly 使用链接类型
	 * @return
	 */
	public function link($readonly = null) {
		if ($readonly !== null) {
			$this->readonly = $readonly;
		}


		// 读取的服务器
		if ($this->readonly && !$this->inTransaction) {
			// 连接到读取服务器
			if ($this->readLink === null) {
				$this->readLink = false;
				$i = 0;
				foreach($this->servers as $server) {
					if ($i > 3) {
						break;
					}
					$server += $this->default;

					// 不是只读的
					if (!$server['readonly']) {
						continue;
					}

					try {
						$this->readLink = $this->connect($server);
						$this->readPingTime = time();
						break;
					} catch (\Exception $e) {
						if ($this->explain) {
							throw $e;
						}
						$this->readLink = false;
					}
					++$i;
				}
			}

			// 读取数据库返回
			if ($this->readLink) {
				// 自动 ping
				if ($this->pingInterval > 0 && ($this->readPingTime + $this->pingInterval) < time()) {
					$this->readPingTime = time();
					$this->ping();
				}
				return $this->readLink;
			}
		}



		// 写入的服务器
		if ($this->writeLink === null) {
			$this->writeLink = false;
			$i = 0;
			foreach($this->servers as $server) {
				if ($i > 3) {
					break;
				}
				$server += $this->default;

				// 只读的
				if ($server['readonly']) {
					continue;
				}

				try {
					$this->writeLink =  $this->connect($server);
					$this->writePingTime = time();
					break;
				} catch (\Exception $e) {
					if ($this->explain) {
						throw $e;
					}
					$this->writeLink = false;
				}
				++$i;
			}
		}

		if (!$this->writeLink) {
			throw new ConnectException(__METHOD__.'()', 'Database link is unavailable');
		}

		// 自动 ping
		if ($this->pingInterval > 0 && ($this->writePingTime + $this->pingInterval) < time()) {
			$this->writePingTime = time();
			$this->ping();
		}
		return $this->writeLink;
	}


	/**
	 * cursor 查询游标
	 * @param  array|string $tables 表名可以是数组
	 * @return Cursor
	 */
	public function cursor() {
		return (new Cursor)->database($this);
	}

	public function tables(...$args) {
		return $this->cursor()->tables(...$args);
	}

	public function table(...$args) {
		return $this->cursor()->table(...$args);
	}


	/**
	 * protocol 返回链接的协议
	 * @return  string
	 */
	public function protocol() {
		return $this->protocol;
	}

	/**
	 * database 返回链接的数据库 or 文件名 or ID
	 * @return string
	 */
	public function database() {
		return basename($this->database);
	}

	/**
	 * database 返回链接的数据库 or 文件名 or ID
	 * @return string
	 */
	public function readonly($readonly = null) {
		if ($readonly !== null) {
			$this->readonly = (bool) $readonly;
		}
		return $this->readonly;
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
	 * @param  string|array            $hostname  连接的服务器或 文件地址
	 * @return mixed
	 */
	abstract protected function connect(array $server);


	/**
	 * ping
	 * @return this
	 */
	abstract protected function ping();

	/**
	 * command
	 * @param  string|array|object    $command
	 * @param  null|boolean           $readonly
	 * @return Cursor|boolean|integer
	 */
	abstract public function command($command, $readonly = null, $class = null);

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
	 * lastInsertId
	 * @return integer
	 */
	abstract public function lastInsertId();

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


	public static function className(&$class) {
		if (!$class) {
			$class = Document::class;
		} elseif (is_object($class)) {
			$class = get_class($class);
		}
	}
}
