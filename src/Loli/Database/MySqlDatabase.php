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
/*	Created: UTC 2014-04-09 07:56:37
/*	Updated: UTC 2015-05-23 11:48:58
/*
/* ************************************************************************** */
namespace Loli\Database;
use Psr\Log\LogLevel;
class MySqlDatabase extends AbstractDatabase{

	protected $protocol = 'mysql';

	public function connect(array $server) {
		if (!is_array($server['hostname'])) {
			$server['hostname'] = explode(', ', $server['hostname']);
		}
		shuffle($server['hostname']);

		if (!empty($server['protocol']) && !in_array($server['protocol'], ['mysql', 'mysqli', 'mariadb'], true)) {
			$this->throwLog(new ConnectException('Does not support this protocol'), LogLevel::ALERT);
		}

		$hostname = explode(':', reset($server['hostname']), 2) + [1 => 3306];


		// 链接到到mysql
		$link = new \MySQLi($hostname[0], $server['username'], $server['password'], $server['database'], $hostname[1]);

		// 链接出错
		if ($link->connect_errno) {
			$this->throwLog(new ConnectException($link->connect_error), LogLevel::ALERT);
		}

		// 选择数据库出错
		if ($link->error) {
			$this->throwLog(new ConnectException($link->error), LogLevel::ALERT);
		}

		$link->set_charset('utf8');
		$link->query("SET TIME_ZONE = `+0:00`");
		return $link;
	}





	public function command($query, $readonly = null, $class = null) {
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

		// 是否是只读
		$readonly = !$this->inTransaction && preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $query) ? $readonly : false;

		// 链接
		$link = $this->link($readonly);


		// 查询
		$result = $link->query($query);
		if ($result === false) {
			if ($link->errno) {
				$this->throwLog(new QueryException($query, $link->error, '', $link->errno), LogLevel::ERROR, ['query' => $query]);
			}
			$results = $result;
		} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $query)) {
			// 插入 删除 更新 替换 资料 [字段]
			$results = $link->affected_rows;
		} elseif (preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $query)) {
			$results = new Results();
			while ($fetch = $result->fetch_assoc()) {
				$results[] = new $class($fetch);
			}
			$results->count() && $result->free_result();
			if ($this->explain && preg_match('/^\s*(SELECT)\s+/i', $query)) {
				$this->command('EXPLAIN ' . $query, $slave);
			}
		} else {
			$results = $result;
		}
		$this->logger && $this->logger->debug(__METHOD__.'('.$query.') ' . (is_scalar($results) ? $results : json_encode($results)));
		return $results;
	}


	public function beginTransaction() {
		if ($this->inTransaction) {
			$this->throwLog(new QueryException(__METHOD__.'()', 'There is already an active transaction'));
		}
		$this->inTransaction = true;
		$link = $this->link(false);
		if (!$link->autoCommit(false) && $link->errno) {
			$this->throwLog(new QueryException(__METHOD__.'()', $link->error, '', $link->errno));
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function commit() {
		if (!$this->inTransaction) {
			$this->throwLog(new QueryException(__METHOD__.'()', 'There is no active transaction'));
		}
		$this->inTransaction = false;
		$link = $this->link(false);
		if (!$link->commit() && $link->errno) {
			$this->throwLog(new QueryException(__METHOD__.'()', $link->error, '', $link->errno));
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function rollBack() {
		if (!$this->inTransaction) {
			$this->throwLog(new QueryException(__METHOD__.'()', 'There is no active transaction'));
		}
		$this->inTransaction = false;
		$link = $this->link(false);
		if (!$link->rollBack() && $link->errno) {
			$this->throwLog(new QueryException(__METHOD__.'()', $link->error, '', $link->errno));
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}


	public function lastInsertId() {
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this->link(false)->insert_id;
	}


	public function ping() {
		$link = $this->link();
		if (!$link->ping()) {
			$this->throwLog(new ConnectException($link->error));
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}


	public function key($key, $throw = false) {
		if (!$key || !is_string($key) || !preg_match('/^(?:([0-9a-z_]+)\.)?([0-9a-z_]+|\*)$/i', $key, $matches) || ($matches[1] && is_numeric($matches[1])) || is_numeric($matches[2])) {
			if ($throw) {
				$this->throwLog(new ConnectException(__METHOD__.'('.$key.')', 'Key name is not formatted correctly'), LogLevel::ERROR, ['key' => $key]);
			}
			return false;
		}
		return ($matches[1] ? '`'. $matches[1]. '`.' : '') . ($matches[2] === '*' ? $matches[2] : '`'. $matches[2] .'`');
	}


	public function value($value) {
		if ($value instanceof \Closure) {
			$value = $value();
		} elseif ($value instanceof \Datetime) {
			static $timezone;
			if (empty($timezone)) {
				$timezone = new \DateTimeZone('GMT');
			}
			$value = clone $value;
			$value = $value->setTimezone($timezone)->format('Y-m-d H:i:s');
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
