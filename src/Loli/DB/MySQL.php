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
/*	Updated: UTC 2015-02-25 14:42:50
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Base') || exit;
class MySQL extends Base{

	public function error() {
		if (!$this->link) {
			return false;
		}
		return ($link = $this->link($this->slave)) ? mysql_error($link) : @mysql_error();
	}


	public function errno() {
		if (!$this->link) {
			return false;
		}
		return ($link = $this->link($this->slave)) ? mysql_errno($link) : @mysql_errno();
	}


	public function connect($args) {
		if (!$link = @mysql_pconnect((empty($args['host']) ? 'localhost' : $args['host']) . ':' . (empty($args['port']) ? 3306 : $args['port']), empty($args['user']) ? 'root' : $args['user'], empty($args['pass']) ? '' : $args['pass'], empty($args['flags']) ? 0 : $args['flags'])) {
			return false;
		}
		if (!mysql_select_db(empty($args['name']) ? 'dbname' : $args['name'], $link)) {
			return false;
		}
		mysql_query('SET NAMES \'utf8\'', $link);
		mysql_query('SET TIME_ZONE = `+0:00`', $link);
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

		$q = mysql_query($query, $link);
		++self::$querySum;

		if ($q === false) {
			$this->addLog($query, $this->error(), 1);
			return false;
		}


		// 创建 改变 修改 删除 [表] 设置
		if (preg_match('/^\s*(CREATE|ALTER|TRUNCATE|DROP|SET|START|BEGIN|SERIAL|COMMIT|ROLLBACK|END)(\s+|;)/i', $query)) {
			// 创建 改变 修改 删除 [表] 设置
			$results = $q;
		} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $query)) {
			// 插入 删除 更新 替换 资料 [字段]
			$results = mysql_affected_rows($link);
			if (preg_match('/^\s*(INSERT|REPLACE) /i', $query)) {
				// 插入 替换 [字段]
				$this->insertID = mysql_insertID($link);
				++self::$querySum;
			}
		} else {
			$results = [];
				while ($row = mysql_fetch_object($q)) {
				$results[] = $row;
				++self::$queryRow;
			}
			$results && mysql_free_result($q);

			// 执行  explain
			if ($this->explain && preg_match('/^\s*SELECT\s+/i', $query)) {
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
		if (($query = $this->query('SHOW TABLES LIKE \''. addcslashes(mysql_real_escape_string($table, $link), '%_') .'\'', false)) === false) {
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
		return ($r = $this->query($query, $slave)) ? reset($r) : false;
	}

	public function count($query, $slave = true) {
		if (!$arr = $this->query($query, $slave)) {
			return 0;
		}
		$count = 0;
		foreach ($arr as $v) {
			$count += array_sum((array) $v);
		}
		return $count;
	}
	public function results($query, $call = false, $slave = true) {
		return ($r = $this->query($query, $slave)) ? $r : [];
	}


	public function start() {
		$this->autoCommit = false;
	 	return $this->query('START TRANSACTION;', false);
    }

    public function commit() {
    	$this->autoCommit = true;
        return $this->query('COMMIT;', false);
    }

    public function rollback() {
    	$this->autoCommit = true;
		return $this->query('ROLLBACK;', false);
    }
}