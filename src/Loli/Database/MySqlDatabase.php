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
use Closure;
class MySqlDatabase extends AbstractDatabase{

	protected $protocol = 'mysql';

	public function connect(array $server) {
		if (!is_array($server['hostname'])) {
			$server['hostname'] = explode(', ', $server['hostname']);
		}
		shuffle($server['hostname']);

		if (!empty($server['protocol']) && !in_array($server['protocol'], ['mysql', 'mysqli', 'mariadb'], true)) {
			$message =__METHOD__.'() Does not support this protocol';
			$this->logger && $this->logger->alert($message);
			throw new ConnectException($message);
		}

		$hostname = explode(':', reset($server['hostname']), 2) + [1 => 3306];


		// 链接到到mysql
		$link = new \MySQLi($hostname[0], $server['username'], $server['password'], $server['database'], $hostname[1]);

		// 链接出错
		if ($link->connect_errno) {
			$message =__METHOD__.'().MySQLi() ' .  $link->connect_error;
			$this->logger && $this->logger->alert($message);
			throw new ConnectException($message);
		}

		// 选择数据库出错
		if ($link->error) {
			$message =__METHOD__.'().MySQLi().select_db() ' . $link->error;
			$this->logger && $this->logger->alert($message);
			throw new ConnectException($message);
		}

		$link->set_charset('utf8');
		$link->query("SET TIME_ZONE = `+0:00`");
		return $link;
	}





	public function command($query, $readonly = NULL, $class = NULL) {
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

		// 是否是只读
		$readonly = !$this->inTransaction && preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $query) ? $readonly : false;

		// 链接
		$link = $this->link($readonly);


		// 查询
		$result = $link->query($query);
		if ($result === false) {
			if ($link->errno) {
				$this->logger && $this->logger->error(__METHOD__.'('. $query .') ' . $link->error);
				throw new QueryException($result, $link->error, '', $link->errno);
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
			$this->logger && $this->logger->error(__METHOD__.'() There is already an active transaction');
			throw new QueryException(__METHOD__.'()', 'There is already an active transaction');
		}
		$this->inTransaction = true;
		$link = $this->link(false);
		if (!$link->autoCommit(false) && $link->errno) {
			$this->logger && $this->logger->error(__METHOD__.'() ' . $link->error);
			throw new QueryException(__METHOD__.'()', $link->error, '', $link->errno);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function commit() {
		if (!$this->inTransaction) {
			$this->logger && $this->logger->error(__METHOD__.'() There is no active transaction');
			throw new QueryException(__METHOD__.'()', 'There is no active transaction');
		}
		$this->inTransaction = false;
		$link = $this->link(false);
		if (!$link->commit() && $link->errno) {
			$this->logger && $this->logger->error(__METHOD__.'() ' . $link->error);
			throw new QueryException(__METHOD__.'()', $link->error, '', $link->errno);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}

	public function rollBack() {
		if (!$this->inTransaction) {
			$this->logger && $this->logger->error(__METHOD__.'() There is no active transaction');
			throw new QueryException(__METHOD__.'()', 'There is no active transaction');
		}
		$this->inTransaction = false;
		$link = $this->link(false);
		if (!$link->rollBack() && $link->errno) {
			$this->logger && $this->logger->error(__METHOD__.'() ' . $link->error);
			throw new QueryException(__METHOD__.'()', $link->error, '', $link->errno);
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
			 $message = __METHOD__.'()' . $link->error;
			$this->logger && $this->logger->error($message);
			throw new ConnectException($message);
		}
		$this->logger && $this->logger->debug(__METHOD__.'()');
		return $this;
	}


	public function key($key, $throw = false) {
		if (!$key || !is_string($key) || !preg_match('/^(?:([0-9a-z_]+)\.)?([0-9a-z_]+|\*)$/i', $key, $matches) || ($matches[1] && is_numeric($matches[1])) || is_numeric($matches[2])) {
			if ($throw) {
				$this->logger && $this->logger->error(__METHOD__.'('.$key.') Key name is not formatted correctly');
				throw new QueryException(__METHOD__.'('.$key.')', 'Key name is not formatted correctly');
			}
			return false;
		}
		return ($matches[1] ? '`'. $matches[1]. '`.' : '') . ($matches[2] === '*' ? $matches[2] : '`'. $matches[2] .'`');
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
