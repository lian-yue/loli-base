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
/*	Updated: UTC 2015-03-17 15:49:45
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


	public function command($params, $slave = NULL, $type = NULL) {
		// 查询不是字符串
		if (!is_string($params)) {
			throw new Exception(json_encode($params), 'Query is not a string', '42000');
		}

		// 查询为空
		if (!$params = trim($params)) {
			throw new Exception('Query', 'Query is empty', '42000');
		}
		$params = trim($params, ';') . ';';
		try {
			// 返回游标的
			if (preg_match('/^\s*(EXPLAIN|SELECT|SHOW)\s+/i', $params) && in_array($type, [NULL, 0])) {
				return $this->link($slave)->query($params)->fetchAll(\PDO::FETCH_CLASS);
			} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $params) && in_array($type, [NULL, 1], true)) {
				return $this->link(false)->exec($params);
			} elseif (in_array($type, [NULL, 2], true)) {
				return $this->link(false)->query($params)->execute();
			} else {
				return $this->link(false)->query($params);
			}
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['42000', 0];
			throw new Exception($params, $e->getMessage(), $info[0], $info[1]);
		}
	}


	public function beginTransaction() {
		if ($this->inTransaction) {
			throw new Exception('this.beginTransaction()', 'There is already an active transaction');
		}
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
		try {
			return call_user_func_array([$this->link(false), 'lastInsertId'], func_get_args());
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['IM001', 0];
			throw new Exception('this.lastInsertID()', $e->getMessage(), $info[0], $info[1]);
		}
	}

	public function ping($slave = NULL) {
		try {
			if (!($status = $this->link($slave)->getAttribute(\PDO::ATTR_CONNECTION_STATUS)) || stripos($status, 'has gone away')) {
				throw new Exception('this.ping()', $status);
			}
		} catch (PDOException $e) {
			throw new ConnectException('this.ping()', $e->getMessage(), $e->errorCode());
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


	/*public function exists($table) {
		try {
			if ($this->protocol() == 'mysql') {
				$cursor = $this->link(false)->prepare('SHOW TABLES LIKE :table;');
				$cursor->execute([':table'=> addcslashes($table, '%_')]);
				return $cursor->fetchAll() ? true : false;
			}

			if ($this->protocol() == 'sqlite') {
				$cursor = $this->link(false)->prepare('SELECT * FROM sqlite_master WHERE type=\'table\' AND name=:table;');
				$cursor->execute([':table'=> $table]);
				return $cursor->fetchAll() ? true : false;
			}
		} catch (PDOException $e) {
			$info = $e->errorInfo ? $e->errorInfo : ['', 0];
			throw new Exception('this.exists(\''. $table .'\')', $e->getMessage(), $info[0], $info[1]);
		}
	}

	public function create($table, $params) {
		$this->command(preg_replace('/\:table/', $this->key($table), $params, 1), false);
		return $this;
	}



	public function truncate($table) {
		if ($this->protocol() == 'sqlite') {
			$query = 'DELETE FROM '. $this->key($table, true) .';';
		} else {
			$query = 'TRUNCATE TABLE '. $this->key($table, true) .';';
		}
		$this->command($query, false);
		return $this;
	}

	public function drop($table) {
		$this->command('DROP TABLE '.$this->key($table, true).';', false);
		return $this;
	}


	public function select($table, $params = [], $slave = NULL) {

	}
	public function insert($table, $params) {

	}
	public function update($table, $params) {

	}
	public function delete($table, $params) {

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
	}*/
}