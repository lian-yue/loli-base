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
/*	Updated: UTC 2015-02-28 11:29:55
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Base') || exit;
class MySQLi extends Base{
	public function connect(array $args) {

		// 数据库名为空
		if (empty($args['name'])) {
			throw new ConnectException('this.MySQLi().select_db()', 'Database name can not be empt');
		}

		// 链接到到mysql
		$link = new \MySQLi($host = empty($args['host']) ? 'localhost' : $args['host'], empty($args['user']) ? 'root' : $args['user'], empty($args['pass']) ? '' : $args['pass'], empty($args['name']) ? 'dbname' : $args['name'], $port = empty($args['port']) ? 3306 : $args['port']);

		// 链接出错
		if ($link->connect_errno) {
			throw new ConnectException('this.MySQLi('. $host .', '. $port .')', $link->connect_error, $link->connect_errno);
		}
		// 选择数据库出错
		if ($link->error) {
			throw new ConnectException('this.MySQLi('. $host .', '. $port .').select_db('. $args['name'] .')', $link->error, $link->errno);
		}

		// 默认设置
		$link->set_charset('utf8');
		$link->query('SET TIME_ZONE = `+0:00`');


		return $link;
	}


	private function _query($query, $slave = true) {
		// 查询不是字符串
		if (is_string($query)) {
			throw new Exception(json_encode($query), 'Query is not a string');
		}

		// 查询为空
		if (!$query = trim($query)) {
			throw new Exception('Query', 'Query is empty');
		}
		// 是否用主数据库
		$slave = $slave && $this->autoCommit && !preg_match('/\s*(EXPLAIN)?\s*(SELECT)\s+/i', $query);

		// 链接
		$link = $this->link($slave);

		// 查询
		$q = $link->query($query);
		++self::$querySum;
		if ($q === false) {
			if ($link->errno) {
				$this->addLog($query);
				throw new Exception($query, $link->error, $link->errno);
			}
			$results = $q;
		} elseif (preg_match('/^\s*(CREATE|ALTER|TRUNCATE|DROP|SET|START|BEGIN|SERIAL|COMMIT|ROLLBACK|END)(\s+|\;)/i', $query)) {
			// 创建 改变 修改 删除 [表] 设置
			$results = $q;
		} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $query)) {
			// 插入 删除 更新 替换 资料 [字段]
			$results = $link->affected_rows;
			if (preg_match('/^\s*(INSERT|REPLACE) /i', $query)) {
				// 插入 替换 [字段]
				$this->insertID = $link->insertID;
				++self::$querySum;
			}
		} else {
			$results = [];
			while ($row = $q->fetch_object()) {
				$results[] = $row;
				++self::$queryRow;
			}
			$results && $q->free_result();

			// 执行  explain
			if ($this->explain && preg_match('/^\s*(SELECT)\s+/i', $query)) {
				$this->_query('EXPLAIN ' . $query, $slave);
			}
		}
		$this->addLog($query, $results);
		return $results;
	}

	public function ping($slave = NULL) {
		$link = $this->link($slave === NULL ? $this->slave : $slave);
		if ($link->ping()) {
			return true;
		}
		throw new Exception('this.ping()', $link->error, $link->errno);
	}
	public function tables() {
		$tables = [];
		foreach ($this->_query('SHOW TABLES', false) as $object) {
			foreach ($object as $table) {
				$tables[] = $table;
			}
		}
		return $tables;
	}

	public function exists($table) {
		return $this->_query('SHOW TABLES LIKE \''. addcslashes(mysql_real_escape_string($table, $link), '%_') .'\'', false) ? $table : false;
	}

	public function truncate($table) {
		$query = 'TRUNCATE TABLE `'. $table .'`';
		if (!preg_match('/^(?:[a-z_][0-9a-z_]*\.)?[a-z_][0-9a-z_]*$/i', $table)) {
			throw new Exception($query, 'Table name match');
		}
		return $this->_query($query, false);
	}

	public function drop($table) {
		$query = 'DROP TABLE IF EXISTS `'. $table .'`';
		if (!preg_match('/^(?:[a-z_][0-9a-z_]*\.)?[a-z_][0-9a-z_]*$/i', $table)) {
			throw new Exception($query, 'Table name match');
		}
		return $this->_query($query, false);
	}


	public function create($query) {
		return $this->_query($query, false);
	}

	public function insert($query) {
		return $this->_query($query, false);
	}

	public function replace($query) {
		return $this->_query($query, false);
	}

	public function update($query) {
		return $this->_query($query, false);
	}

	public function delete($query) {
		return $this->_query($query, false);
	}




	public function select($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}
	public function distinct($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}
	public function aggregate($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}
	public function group($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}

	public function count($query, $slave = true) {
		if (!$results = $this->_query($query, $slave)) {
			return 0;
		}
		$count = 0;
		foreach ($results as $object) {
			$count += array_sum((array) $object);
		}
		return $count;
	}

	public function startTransaction() {
		$this->autocommit = false;
		$this->link(false)->autoCommit(false);
		return true;
	}

	public function commit() {
		$link = $this->link(false);
		$this->autocommit = true;
		$commit = $link->commit();
		if (!$commit && $link->errno) {
			throw new Exception('this.commit()', $link->error, $link->errno);
		}
		return $commit;
	}

	public function rollback() {
		$link = $this->link(false);
		$this->autocommit = true;
		$rollback = $link->rollback();
		if (!$rollback && $link->errno) {
			throw new Exception('this.rollback()', $link->error, $link->errno);
		}
		return $rollback;
	}
}