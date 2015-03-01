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
		empty($args['name']) && $this->addLog('MySQLi().select_db()', 'Database name can not be empty', 2);

		// 链接到到mysql
		$link = new \MySQLi($host = empty($args['host']) ? 'localhost' : $args['host'], empty($args['user']) ? 'root' : $args['user'], empty($args['pass']) ? '' : $args['pass'], empty($args['name']) ? 'dbname' : $args['name'], $port = empty($args['port']) ? 3306 : $args['port']);

		// 链接出错
		$link->connect_errno && $this->addLog('MySQLi('. $host .', '. $port .')', $link->connect_error, 2, $link->connect_errno);

		// 选择数据库出错
		$link->error && $this->addLog('MySQLi('. $host .', '. $port .').select_db('. $args['name'] .')', $link->error, 2, $link->errno);

		// 默认设置
		$link->set_charset('utf8');
		$link->query('SET TIME_ZONE = `+0:00`');


		return $link;
	}


	private function _query($query, $slave = true) {
		// 查询不是字符串
		is_string($query) || $this->addLog(json_encode($query), 'Query is not a string', 1);

		// 查询为空
		($query = trim($query)) || $this->addLog('Query', 'Query is empty', 1);


		// 是否用主数据库
		$slave = $slave && $this->autoCommit && !preg_match('/\s*(EXPLAIN)?\s*(SELECT)\s+/i', $query);

		// 链接
		$link = $this->link($slave);

		// 查询
		$q = $link->query($query);
		++self::$querySum;
		if ($q === false) {
			$link->errno && $this->addLog($query, $link->error, 1, $link->errno);
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

	public function ping($slave = null) {
		$link = $this->link($slave === null ? $this->slave : $slave);
		if ($link->ping()) {
			return true;
		}
		$this->addLog('Ping', $link->error, 1);
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
		preg_match('/^(?:[a-z_][0-9a-z_]*\.)?[a-z_][0-9a-z_]*$/i', $table) || $this->addLog($query, 'Table name match', 1);
		return $this->_query($query, false);
	}

	public function drop($table) {
		$query = 'DROP TABLE IF EXISTS `'. $table .'`';
		preg_match('/^(?:[a-z_][0-9a-z_]*\.)?[a-z_][0-9a-z_]*$/i', $table) || $this->addLog($query, 'Table name match', 1);
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

	public function row($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? reset($results) : false;
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
	public function results($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
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
		!$commit && $link->errno && $this->addLog('this.commit()', $link->error, 1, $link->errno);
		return $commit;
	}

	public function rollback() {
		$link = $this->link(false);
		$this->autocommit = true;
		$rollback = $link->rollback();
		!$rollback && $link->errno && $this->addLog('this.rollback()', $link->error, 1, $link->errno);
		return $rollback;
	}
}