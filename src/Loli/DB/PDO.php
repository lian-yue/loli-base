<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-05 09:48:17
/*	Updated: UTC 2015-03-21 13:04:53
/*
/* ************************************************************************** */
namespace Loli\DB;
use PDOException;
class_exists('Loli\DB\Base') || exit;
class PDO extends Base{

	public function connect(array $servers) {
		$server = $servers[array_rand($servers)];

		// sqlite 需要当前 文件目录的写入权限
		if (!in_array($server['protocol'], \PDO::getAvailableDrivers())) {
			throw new ConnectException('Does not support this protocol');
		}
		$hostname = explode(':', $server['hostname']);
		try {
			if ($server['protocol'] == 'mysql') {
				$dsnQuery = 'this.PDO(' . $server['protocol']. ':host='. $hostname[0] . (empty($hostname[1]) ? '' : ';port=' . $hostname[1]) . ')';
				$link = new \PDO($server['protocol']. ':host='. $hostname[0] . (empty($hostname[1]) ? '' : ';port=' . $hostname[1]) .';dbname='.  $server['database'] . ';charset=UTF8', $server['username'], $server['password'], [\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_AUTOCOMMIT => true, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
				$link->exec('SET TIME_ZONE = `+0:00`');
			} elseif ($server['protocol'] == 'sqlite') {
				$link = new \PDO($server['protocol'] .':' . $server['database'] . ';charset=UTF8', $server['username'], $server['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
				$dsnQuery = 'this.PDO(' . $server['protocol'] . ':database)';
			}
		} catch (PDOException $e) {
			throw new ConnectException($dsnQuery, $e->getMessage());
		}
		return $link;
	}


	public function command($query, $slave = NULL, $type = NULL) {
		// 查询不是字符串
		if (!is_string($query)) {
			throw new Exception(json_encode($query), 'Query is not a string');
		}

		// 查询为空
		if (!$query = trim($query)) {
			throw new Exception('Query', 'Query is empty');
		}
		++self::$querySum;
		$query = trim($query, ';') . ';';
		try {
			if (preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $query) && in_array($type, [NULL, 0])) {
				$results = $this->link($slave)->query($query)->fetchAll(\PDO::FETCH_CLASS);
				if ($this->explain && preg_match('/^\s*(SELECT)\s+/i', $query)) {
					$this->command('EXPLAIN ' . $query, $slave, $type);
				}
				self::$queryRow += count($results);
			} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $query) && in_array($type, [NULL, 1], true)) {
				$results = $this->link(false)->exec($query);
			} elseif (in_array($type, [NULL, 2], true)) {
				$results = $this->link(false)->query($query)->execute();
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
			throw new Exception('this.beginTransaction()', 'There is already an active transaction');
		}
		++self::$querySum;
		try {
			$this->inTransaction = true;
			$this->link(false)->beginTransaction();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception('this.beginTransaction()', $e->getMessage(), $info[0], $info[1]);
		}
		return $this;
	}

	public function commit() {
		if (!$this->inTransaction) {
			throw new Exception('this.commit()', 'There is no active transaction');
		}
		++self::$querySum;
		try {
			$this->inTransaction = false;
			$this->link(false)->commit();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception('this.commit()', $e->getMessage(), $info[0], $info[1]);
		}
		return $this;
	}

	public function rollBack() {
		if (!$this->inTransaction) {
			throw new Exception('this.rollBack()', 'There is no active transaction');
		}
		++self::$querySum;
		try {
			$this->inTransaction = false;
			$this->link(false)->rollBack();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception('this.rollBack()', $e->getMessage(), $info[0], $info[1]);
		}
		return $this;
	}


	public function lastInsertID() {
		++self::$querySum;
		try {
			return call_user_func_array([$this->link(false), 'lastInsertId'], func_get_args());
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['IM001', 0];
			throw new Exception('this.lastInsertID()', $e->getMessage(), $info[0], $info[1]);
		}
	}

	public function ping($slave = NULL) {
		++self::$querySum;
		try {
			if (!($status = $this->link($slave)->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) || stripos($status, 'has gone away')) {
				throw new ConnectException('this.ping()', $status);
			}
		} catch (PDOException $e) {
			throw new ConnectException('this.ping()', $e->getMessage());
		}
		return $this;
	}

	public function key($key, $throw = false) {
		if (!$key || !is_string($key) || !preg_match('/^(?:([0-9a-z_]+)\.)?([0-9a-z_]+|\*)$/i', $key, $matches) || ($matches[1] && is_numeric($matches[1])) || is_numeric($matches[2])) {
			if ($throw) {
				throw new Exception('this.key('.$key.')', 'Key name is not formatted correctly');
			}
			return false;
		}
		if (($protocol = $this->protocol()) == 'mysql') {
			return ($matches[1] ? '`'. $matches[1]. '`.' : '') . ($matches[2] == '*' ? $matches[2] : '`'. $matches[2] .'`');
		}
		return ($matches[1] ? '\''. $matches[1]. '\'.' : '') . ($matches[2] == '*' ? $matches[2] : '\''. $matches[2] .'\'');
	}

	public function value($value) {
		if (is_array($value) || is_object($value)) {
			$value = json_encode($value);
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