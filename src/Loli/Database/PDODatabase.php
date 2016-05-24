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
use PDOException;
use Psr\Log\LogLevel;
class PDODatabase extends AbstractDatabase{

	protected function connect(array $server) {
		if (!is_array($server['hostname'])) {
			$server['hostname'] = explode(', ', $server['hostname']);
		}
		shuffle($server['hostname']);
		// 不支持的驱动器
		if (!in_array($server['protocol'], \PDO::getAvailableDrivers())) {
			$this->throwLog(new ConnectException('Does not support this protocol'), LogLevel::ALERT);
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
						$this->throwLog(new ConnectException('File directory is not writable'), LogLevel::ALERT);
					}
					$message = __METHOD__.'().PDO(sqlite:'.$this->database().')';
					$link = new \PDO('sqlite:' . $server['database'] . ';charset=UTF8', $server['username'], $server['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
					break;
				default:
					$this->throwLog(new ConnectException('Unknown database protocol'), LogLevel::ALERT);
			}
		} catch (PDOException $e) {
			$this->throwLog(new ConnectException($message . ' ' . $e->getMessage()), LogLevel::ALERT);
		}
		return $link;
	}

	protected function ping() {;
		try {
			if (!($status = $this->link()->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) || stripos($status, 'has gone away')) {
				$this->throwLog(new ConnectException($status), LogLevel::ALERT);
			}
		} catch (PDOException $e) {
			$this->throwLog(new ConnectException($e->getMessage()), LogLevel::ALERT);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}


	public function command($query, $readonly = null, $class = null, $type = null) {
		self::className($class);

		// 查询不是字符串
		if (!is_string($query)) {
			$this->throwLog(new QueryException($query, 'Query is not a string'), LogLevel::ERROR, ['query' => $query]);
		}

		// 查询为空
		if (!$query = trim($query)) {
			$this->throwLog(new QueryException($query, 'Query is empty'), LogLevel::ERROR, ['query' => $query]);
		}
		$query = trim($query, ';') . ';';
		try {
			if (preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $query) && in_array($type, [null, 0])) {
				if ($this->inTransaction) {
					$readonly = false;
				}
				$results = new Results($this->link($readonly)->query($query)->fetchAll(\PDO::FETCH_CLASS, $class));
				if ($this->explain && preg_match('/^\s*(SELECT)\s+/i', $query)) {
					$this->command('EXPLAIN ' . $query, $readonly, $type);
				}
			} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $query) && in_array($type, [null, 1], true)) {
				$results = $this->link(false)->exec($query);
			} elseif (in_array($type, [null, 2], true)) {
				$results = $this->link(false)->exec($query);
			} else {
				$results = $this->link(false)->query($query);
			}
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			$this->throwLog(new QueryException($query, $e->getMessage(), $info[0], $info[1]), LogLevel::ERROR, ['query' => $query]);
		}
		$this->logger && $this->logger->debug(__METHOD__.'('.$query.') ' . (is_scalar($results) ? $results : json_encode($results)));
		return $results;
	}


	public function beginTransaction() {
		if ($this->inTransaction) {
			$this->throwLog(new QueryException(__METHOD__.'()', 'There is already an active transaction'));
		}
		try {
			$this->inTransaction = true;
			$this->link(false)->beginTransaction();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			$this->throwLog(new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]));
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function commit() {
		if (!$this->inTransaction) {
			$this->throwLog(new QueryException(__METHOD__.'()', 'There is no active transaction'));
		}
		try {
			$this->inTransaction = false;
			$this->link(false)->commit();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			$this->throwLog(new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]));
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function rollBack() {
		if (!$this->inTransaction) {
			$this->throwLog(new QueryException(__METHOD__.'()', 'There is no active transaction'));
		}
		try {
			$this->inTransaction = false;
			$this->link(false)->rollBack();
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			$this->throwLog(new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]));
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}



	public function lastInsertId(...$args) {
		try {
			$result = $this->link(false)->lastInsertId(...$args);
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['IM001', 0];
			$this->throwLog(new QueryException(__METHOD__.'()', $e->getMessage(), $info[0], $info[1]));
		}
		$this->logger && $this->logger->debug(__METHOD__.'() ' . $result);
		return $result;
	}


	public function key($key, $throw = false) {
		if (!$key || !is_string($key) || !preg_match('/^(?:([0-9a-z_]+)\.)?([0-9a-z_]+|\*)$/i', $key, $matches) || ($matches[1] && is_numeric($matches[1])) || is_numeric($matches[2])) {
			if ($throw) {
				$this->throwLog(new ConnectException(__METHOD__.'('.$key.')', 'Key name is not formatted correctly'), LogLevel::ERROR, ['key' => $key]);
			}
			return false;
		}
		if ($this->protocol() === 'mysql') {
			return ($matches[1] ? '`'. $matches[1]. '`.' : '') . ($matches[2] === '*' ? $matches[2] : '`'. $matches[2] .'`');
		}
		return ($matches[1] ? '\''. $matches[1]. '\'.' : '') . ($matches[2] === '*' ? $matches[2] : '\''. $matches[2] .'\'');
	}



	public function value($value) {
		if ($value instanceof \Closure) {
			$value = $value();
		} elseif ($value instanceof \Datetime) {
			static $timezone;
			if (empty($timezone)) {
				$timezone = new \DateTimeZone('UTC');
			}
			$value = clone $value;
			$value = $value->setTimezone($timezone)->format('Y-m-d H:i:s');
		}
        if (is_array($value)) {
			$value = $value ? json_encode($value) : '';
		} elseif (is_object($value)) {
			if (method_exists($value, '__toString')) {
				$value = $value->__toString();
			} else {
				$value = json_encode($value);
                if ($value === '[]') {
                    $value = '';
                }
			}
		}
		if ($value === null) {
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
