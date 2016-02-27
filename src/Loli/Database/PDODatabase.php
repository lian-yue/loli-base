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
use Closure;
use PDOException;

class PDODatabase extends AbstractDatabase{

	protected function connect(array $server) {
		if (!is_array($server['hostname'])) {
			$server['hostname'] = explode(', ', $server['hostname']);
		}
		shuffle($server['hostname']);
		// 不支持的驱动器
		if (!in_array($server['protocol'], \PDO::getAvailableDrivers())) {
			$message = __METHOD__ . '().PDO() Does not support this protocol';
			$this->logger && $this->logger->alert($message);
			throw new ConnectException($message);
		}
		$hostname = explode(':', reset($server['hostname']), 2);
		try {
			switch ($server['protocol']) {
				case 'mysql':
					$message = __METHOD__.'().PDO(mysql:host='. $hostname[0] . (empty($hostname[1]) ? '' : ';port=' . $hostname[1]) . ')';
					$link = new \PDO('mysql:host='. $hostname[0] . (empty($hostname[1]) ? '' : ';port=' . $hostname[1]) .';dbname='.  $server['database'] . ';charset=UTF8', $server['username'], $server['password'], [\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_AUTOCOMMIT => true, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
					$link->exec('SET TIME_ZONE = `+0:00`');
					break;
				case 'sqlite':
					// sqlite 需要当前 文件目录的写入权限
					if (!is_writable(dirname($server['database']))) {
						$message = __METHOD__ . '().PDO() File directory is not writable';
						$this->logger && $this->logger->alert($message);
						throw new ConnectException($message);
					}
					$message = __METHOD__.'().PDO(sqlite:'.$this->database().')';
					$link = new \PDO('sqlite:' . $server['database'] . ';charset=UTF8', $server['username'], $server['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
					break;
				default:
					throw new ConnectException(__METHOD__.'().PDO() Unknown database protocol');
			}
		} catch (PDOException $e) {
			$message .= ' ' . $e->getMessage();
			$this->logger && $this->logger->alert($message);
			throw new ConnectException($message);
		}
		return $link;
	}

	protected function ping() {;
		try {
			if (!($status = $this->link()->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) || stripos($status, 'has gone away')) {
				$message = __METHOD__.'() ' . $status;
				$this->logger && $this->logger->error($message);
				throw new ConnectException($message);
			}
		} catch (PDOException $e) {
			$message = __METHOD__.'() ' . $e->getMessage();
			$this->logger && $this->logger->error($message);
			throw new ConnectException($message);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}


	public function command($query, $readonly = NULL, $class = NULL, $type = NULL) {
		self::className($class);

		// 查询不是字符串
		if (!is_string($query)) {
			$this->logger && $this->logger->error(__METHOD__ . '('. json_encode($query) .') Query is not a string');
			throw new QueryException($query, 'Query is not a string');
		}

		// 查询为空
		if (!$query = trim($query)) {
			$this->logger && $this->logger->error(__METHOD__.'() Query is empty');
			throw new QueryException(__METHOD__.'()', 'Query is empty');
		}
		$query = trim($query, ';') . ';';
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
			$this->logger && $this->logger->error(__METHOD__.'('.$query.')' . $e->getMessage());
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new QueryException($query, $e->getMessage(), $info[0], $info[1]);
		}
		$this->logger && $this->logger->debug(__METHOD__.'('.$query.') ' . (is_scalar($results) ? $results : json_encode($results)));
		return $results;
	}


	public function beginTransaction() {
		if ($this->inTransaction) {
			$this->logger && $this->logger->error(__METHOD__.'() There is already an active transaction');
			throw new QueryException(__METHOD__.'()', 'There is already an active transaction');
		}
		try {
			$this->inTransaction = true;
			$this->link(false)->beginTransaction();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			$this->logger && $this->logger->error(__METHOD__.'() ' . $e->getMessage());
			throw new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function commit() {
		if (!$this->inTransaction) {
			$this->logger && $this->logger->error(__METHOD__.'() There is no active transaction');
			throw new QueryException(__METHOD__.'()', 'There is no active transaction');
		}
		try {
			$this->inTransaction = false;
			$this->link(false)->commit();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			$this->logger && $this->logger->error(__METHOD__.'() ' . $e->getMessage());
			throw new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function rollBack() {
		if (!$this->inTransaction) {
			$this->logger && $this->logger->error(__METHOD__.'() There is no active transaction');
			throw new QueryException(__METHOD__.'()', 'There is no active transaction');
		}
		try {
			$this->inTransaction = false;
			$this->link(false)->rollBack();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			$this->logger && $this->logger->error(__METHOD__.'() ' . $e->getMessage());
			throw new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}



	public function lastInsertId(...$args) {
		try {
			$result = $this->link(false)->lastInsertId(...$args);
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['IM001', 0];
			$this->logger && $this->logger->error(__METHOD__.'() ' . $e->getMessage());
			throw new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]);
		}
		$this->logger && $this->logger->debug(__METHOD__.'() ' . $result);
		return $result;
	}


	public function key($key, $throw = false) {
		if (!$key || !is_string($key) || !preg_match('/^(?:([0-9a-z_]+)\.)?([0-9a-z_]+|\*)$/i', $key, $matches) || ($matches[1] && is_numeric($matches[1])) || is_numeric($matches[2])) {
			if ($throw) {
				$this->logger && $this->logger->error(__METHOD__.'('.$key.') Key name is not formatted correctly');
				throw new QueryException(__METHOD__.'('.$key.')', 'Key name is not formatted correctly');
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
