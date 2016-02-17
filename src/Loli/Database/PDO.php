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
/*	Created: UTC 2015-03-05 09:48:17
/*	Updated: UTC 2015-05-23 11:49:14
/*
/* ************************************************************************** */
namespace Loli\Database;
use PDOException, Closure;

class PDO extends Base{

	protected function connect(array $server) {
		if (!is_array($server['hostname'])) {
			$server['hostname'] = explode(', ', $server['hostname']);
		}
		shuffle($server['hostname']);
		// 不支持的驱动器
		if (!in_array($server['protocol'], \PDO::getAvailableDrivers())) {
			throw new ConnectException(__METHOD__.'().PDO()', 'Does not support this protocol');
		}
		$hostname = explode(':', reset($server['hostname']), 2);
		try {
			switch ($server['protocol']) {
				case 'mysql':
					$dsnQuery = __METHOD__.'().PDO(mysql:host='. $hostname[0] . (empty($hostname[1]) ? '' : ';port=' . $hostname[1]) . ')';
					$link = new \PDO('mysql:host='. $hostname[0] . (empty($hostname[1]) ? '' : ';port=' . $hostname[1]) .';dbname='.  $server['database'] . ';charset=UTF8', $server['username'], $server['password'], [\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_AUTOCOMMIT => true, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
					$link->exec('SET TIME_ZONE = `+0:00`');
					break;
				case 'sqlite':
					// sqlite 需要当前 文件目录的写入权限
					if (!is_writable(dirname($server['database']))) {
						throw new ConnectException(__METHOD__.'().PDO()', 'File directory is not writable');
					}
					$link = new \PDO('sqlite:' . $server['database'] . ';charset=UTF8', $server['username'], $server['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
					$dsnQuery = __METHOD__.'().PDO(sqlite:'.$this->database().')';
					break;
				default:
					throw new ConnectException(__METHOD__.'().PDO()', 'Unknown database protocol');
			}
		} catch (PDOException $e) {
			throw new ConnectException($dsnQuery, $e->getMessage());
		}
		return $link;
	}

	protected function ping() {
		$this->statistics[] = 'Ping';
		try {
			if (!($status = $this->link()->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) || stripos($status, 'has gone away')) {
				throw new ConnectException(__METHOD__.'()', $status);
			}
		} catch (PDOException $e) {
			throw new ConnectException(__METHOD__.'()', $e->getMessage());
		}
		return $this;
	}


	public function command($query, $readonly = NULL, $class = NULL, $type = NULL) {
		self::className($class);

		// 查询不是字符串
		if (!is_string($query)) {
			throw new Exception($query, 'Query is not a string');
		}

		// 查询为空
		if (!$query = trim($query)) {
			throw new Exception(__METHOD__.'()', 'Query is empty');
		}
		$query = trim($query, ';') . ';';
		$this->statistics[] = $query;
		try {
			if (preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $query) && in_array($type, [NULL, 0])) {
				$results = new Results($this->link($readonly)->query($query)->fetchAll(\PDO::FETCH_CLASS, $class));
				if ($this->explain && preg_match('/^\s*(SELECT)\s+/i', $query)) {
					$this->command('EXPLAIN ' . $query, $readonly, $type);
				}
			} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $query) && in_array($type, [NULL, 1], true)) {
				$results = $this->link(false)->exec($query);
			} elseif (in_array($type, [NULL, 2], true)) {
				$results = $this->link(false)->exec($query);
			} else {
				$results = $this->link(false)->query($query);
			}
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception($query, $e->getMessage(), $info[0], $info[1]);
		}

		$this->log($query, $results);
		return $results;
	}


	public function beginTransaction() {
		if ($this->inTransaction) {
			throw new Exception(__METHOD__.'()', 'There is already an active transaction');
		}
		$this->statistics[] = 'beginTransaction;';
		try {
			$this->inTransaction = true;
			$this->link(false)->beginTransaction();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
		return $this;
	}

	public function commit() {
		if (!$this->inTransaction) {
			throw new Exception(__METHOD__.'()', 'There is no active transaction');
		}
		$this->statistics[] = 'commit;';
		try {
			$this->inTransaction = false;
			$this->link(false)->commit();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
		return $this;
	}

	public function rollBack() {
		if (!$this->inTransaction) {
			throw new Exception(__METHOD__.'()', 'There is no active transaction');
		}
		$this->statistics[] = 'rollBack;';
		try {
			$this->inTransaction = false;
			$this->link(false)->rollBack();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
		return $this;
	}



	public function lastInsertId(...$args) {
		$this->statistics[] = 'lastInsertId;';
		try {
			return $this->link(false)->lastInsertId(...$args);
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['IM001', 0];
			throw new Exception(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
	}


	public function key($key, $throw = false) {
		if (!$key || !is_string($key) || !preg_match('/^(?:([0-9a-z_]+)\.)?([0-9a-z_]+|\*)$/i', $key, $matches) || ($matches[1] && is_numeric($matches[1])) || is_numeric($matches[2])) {
			if ($throw) {
				throw new Exception(__METHOD__.'('.$key.')', 'Key name is not formatted correctly');
			}
			return false;
		}
		if ($this->protocol() === 'mysql') {
			return ($matches[1] ? '`'. $matches[1]. '`.' : '') . ($matches[2] === '*' ? $matches[2] : '`'. $matches[2] .'`');
		}
		return ($matches[1] ? '\''. $matches[1]. '\'.' : '') . ($matches[2] === '*' ? $matches[2] : '\''. $matches[2] .'\'');
	}



	public function value($value) {
		if ($value instanceof Closure) {
			$value = $value();
		}
		if (is_array($value)) {
			$value = json_encode($value);
		} elseif (is_object($value)) {
			if (method_exists($value, '__toString')) {
				$value = $value->__toString();
			} else {
				$value = json_encode($value);
			}
		}
		if ($value === NULL) {
			return 'NULL';
		} elseif ($value === false) {
			$value = 0;
		} elseif ($value === true) {
			$value = 1;
		} elseif (!is_int($value) && !is_float($value)) {
			$value = addslashes(stripslashes(addslashes($value)));
			$value = '\''. $value .'\'';
		}
		return $value;
	}
}
