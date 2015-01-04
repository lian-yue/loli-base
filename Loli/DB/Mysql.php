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
/*	Updated: UTC 2014-12-31 07:11:21
/*
/* ************************************************************************** */
namespace Loli\DB;
class Mysql extends Base{

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
		$slave = $slave && !preg_match('/\s*(SELECT)/', $query);
		if (!$link = $this->link($slave)){
			return false;
		}

		$q = mysql_query($query, $link);
		++self::$queryNum;

		if ($q === false) {
			// 如果是 false 就不继续执行
			$r = false;
			$this->debug && $this->exitError($query);
		} elseif (preg_match('/^\s*(CREATE|ALTER|TRUNCATE|DROP|SET|START|BEGIN|SERIAL|COMMIT|ROLLBACK|END)[ ;]/i', $query)) {

			// 创建 改变 修改 删除 [表] 设置
			$r = $q;

		} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE) /i', $query)) {

			// 插入 删除 更新 替换 资料 [字段]
			$r = mysql_affected_rows($link);
			if (preg_match('/^\s*(INSERT|REPLACE) /i', $query)) {

				// 插入 替换 [字段]
				$this->insert_id = mysql_insert_id($link);
				++self::$queryNum;

			}
		} else {
			$r = [];
 			while ($row = mysql_fetch_object($q)) {
				$r[] = $row;
				++self::$queryRow;
			}
			$r && mysql_free_result($q);


			if (preg_match('/^\s*SELECT.*(?:\s+SQL_CALC_FOUND_ROWS\s+).*(?=\s+FROM\s+)/is', $query)) {
				$this->found_rows = $this->count('SELECT FOUND_ROWS();', $slave);
			}

			if ($this->debug && preg_match('/^\s*SELECT /i', $query)) {
				$this->query('EXPLAIN ' . $query, $slave);
			}
		}
		return $this->data[$query] = $r;
	}

	public function create($query) {
		return $this->query($query, false);
	}
	public function drop($query) {
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