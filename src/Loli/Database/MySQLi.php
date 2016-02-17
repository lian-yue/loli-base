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

class MySQLi extends Base{

	protected $protocol = 'mysql';

	public function connect(array $server) {
		if (!is_array($server['hostname'])) {
			$server['hostname'] = explode(', ', $server['hostname']);
		}
		shuffle($server['hostname']);

		if (!empty($server['protocol']) && !in_array($server['protocol'], ['mysql', 'mysqli', 'mariadb'], true)) {
			throw new ConnectException(__METHOD__.'()', 'Does not support this protocol');
		}

		$hostname = explode(':', reset($server['hostname']), 2) + [1 => 3306];


		// 链接到到mysql
		$link = new \MySQLi($hostname[0], $server['username'], $server['password'], $server['database'], $hostname[1]);

		// 链接出错
		if ($link->connect_errno) {
			throw new ConnectException(__METHOD__.'().MySQLi()', '', $link->connect_error, '', $link->connect_errno);
		}

		// 选择数据库出错
		if ($link->error) {
			throw new ConnectException(__METHOD__.'().MySQLi().select_db()', $link->error, '', $link->errno);
		}

		$link->set_charset('utf8');
		$link->query("SET TIME_ZONE = `+0:00`");
		return $link;
	}





	public function command($query, $readonly = NULL, $class = NULL) {
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

		// 是否是只读
		$readonly = !$this->inTransaction && preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $query) ? $readonly : false;

		// 链接
		$link = $this->link($readonly);


		// 查询
		$result = $link->query($query);
		if ($result === false) {
			if ($link->errno) {
				throw new Exception($result, $link->error, '', $link->errno);
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
		$this->log($query, $results);
		return $results;
	}


	public function beginTransaction() {
		if ($this->inTransaction) {
			throw new Exception(__METHOD__.'()', 'There is already an active transaction');
		}
		$this->statistics[] = 'beginTransaction;';
		$this->inTransaction = true;
		$link = $this->link(false);
		if (!$link->autoCommit(false) && $link->errno) {
			throw new Exception(__METHOD__.'()', $link->error, '', $link->errno);
		}
		return $this;
	}

	public function commit() {
		if (!$this->inTransaction) {
			throw new Exception(__METHOD__.'()', 'There is no active transaction');
		}
		$this->statistics[] = 'commit;';
		$this->inTransaction = false;
		$link = $this->link(false);
		$link->commit();
		if (!$link->commit() && $link->errno) {
			throw new Exception(__METHOD__.'()', $link->error, '', $link->errno);
		}
		return $this;
	}

	public function rollBack() {
		if (!$this->inTransaction) {
			throw new Exception(__METHOD__.'()', 'There is no active transaction');
		}
		$this->statistics[] = 'rollBack;';
		$this->inTransaction = false;
		$link = $this->link(false);
		$rollback = $link->rollBack();
		if (!$link->rollBack() && $link->errno) {
			throw new Exception(__METHOD__.'()', $link->error, '', $link->errno);
		}
		return $this;
	}


	public function lastInsertId() {
		$this->statistics[] = 'lastInsertId;';
		return $this->link(false)->insert_id;
	}


	public function ping() {
		$link = $this->link();
		$this->statistics[] = 'Ping;';
		if (!$link->ping()) {
			throw new ConnectException(__METHOD__.'()', $link->error, '', $link->errno);
		}
		return $this;
	}


	public function key($key, $throw = false) {
		if (!$key || !is_string($key) || !preg_match('/^(?:([0-9a-z_]+)\.)?([0-9a-z_]+|\*)$/i', $key, $matches) || ($matches[1] && is_numeric($matches[1])) || is_numeric($matches[2])) {
			if ($throw) {
				throw new Exception(__METHOD__.'('.$key.')', 'Key name is not formatted correctly');
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
