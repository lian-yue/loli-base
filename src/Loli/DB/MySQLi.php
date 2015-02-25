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
/*	Updated: UTC 2015-02-25 14:44:42
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Base') || exit;
class MySQLi extends Base{

	public function error() {
		if (!$this->link) {
			return false;
		}
		if (($link = $this->link($this->slave)) && $link->connect_error) {
			return $link->connect_error;
		}
		return $link ? $link->error : @mysqli_error();
	}


	public function errno() {
		if (!$this->link) {
			return false;
		}
		if (($link = $this->link($this->slave)) && $link->connect_errno) {
			return $link->connect_errno;
		}
		return $link ? $link->errno : @mysqli_errno();
	}


	public function connect($args) {
		if (!($link = new \MySQLi(empty($args['host']) ? 'localhost' : $args['host'], empty($args['user']) ? 'root' : $args['user'], empty($args['pass']) ? '' : $args['pass'], empty($args['name']) ? 'dbname' : $args['name'], empty($args['port']) ? 3306 : $args['port'])) || $link->connect_errno) {
			return false;
		}
		$link->set_charset('utf8');
		$link->query("SET TIME_ZONE = `+0:00`");
		return $link;
	}


	private function query($query, $slave = true) {
		if (!is_string($query) || !($query = trim($query))) {
			return false;
		}

		$slave = $slave && !preg_match('/\s*(EXPLAIN)?\s*(SELECT)\s+/i', $query);
		if (!$link = $this->link($slave)) {
			return false;
		}

		$q = $link->query($query);
		++self::$querySum;
		if ($q === false) {
			$this->addLog($query, $this->error(), 1);
			return false;
		}

		if (preg_match('/^\s*(CREATE|ALTER|TRUNCATE|DROP|SET|START|BEGIN|SERIAL|COMMIT|ROLLBACK|END)(\s+|\;)/i', $query)) {
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
				$this->query('EXPLAIN ' . $query, $slave);
			}
		}
		$this->addLog($query, $results);
		return $results;
	}

	public function tables() {
		if (($arrays = $this->query('SHOW TABLES', false)) === false) {
			return false;
		}
		$tables = [];
		foreach ($arrays as $object) {
			foreach ($object as $table) {
				$tables[] = $table;
			}
		}
		return $tables;
	}

	public function exists($table) {
		if (!$link = $this->link(false)) {
			return false;
		}
		if (($query = $this->query('SHOW TABLES LIKE \''. addcslashes($link->real_escape_string($table), '%_') .'\'', false)) === false) {
			return false;
		}
		return $query ? 1 : 0;
	}

	public function truncate($table) {
		if (!preg_match('/^(?:[a-z_][0-9a-z_]*\.)?[a-z_][0-9a-z_]*$/i', $table)) {
			return false;
		}
		return $this->query('TRUNCATE TABLE `'. $table .'`', false);
	}

	public function drop($table) {
		if (!preg_match('/^(?:[a-z_][0-9a-z_]*\.)?[a-z_][0-9a-z_]*$/i', $table)) {
			return false;
		}
		return $this->query('DROP TABLE IF EXISTS `'. $table .'`', false);
	}


	public function create($query) {
		return $this->query($query, false);
	}

	public function insert($query) {
		return $this->query($query, false);
	}

	public function replace($query) {
		return $this->query($query, false);
	}

	public function update($query) {
		return $this->query($query, false);
	}

	public function delete($query) {
		return $this->query($query, false);
	}

	public function row($query, $slave = true) {
		return ($results = $this->query($query, $slave)) ? reset($results) : false;
	}

	public function count($query, $slave = true) {
		if (!$arrays = $this->query($query, $slave)) {
			return 0;
		}
		$count = 0;
		foreach ($arrays as $array) {
			$count += array_sum((array) $array);
		}
		return $count;
	}
	public function results($query, $call = false, $slave = true) {
		return ($results = $this->query($query, $slave)) ? $results : [];
	}


	public function start() {
		if (!$link = $this->link(false)) {
			return false;
		}
		$this->autocommit = false;
		return $link->autocommit(false);
	}

	public function commit() {
		if (!$link = $this->link(false)) {
			return false;
		}
		$this->autocommit = true;
		$commit = $link->commit();
		$link->autocommit(true);
		!$commit && $this->errno() && $this->addLog('COMMIT',. $this->error(), 1);
		return $commit;
	}

	public function rollback() {
		if (!$link = $this->link(false)) {
			return false;
		}
		$this->autocommit = true;
		$rollback = $link->rollback();
		$link->autocommit(true);
		!$rollback && $this->errno() && $this->addLog('ROLLBACK', $this->error(), 1);
		return $rollback;
	}
}