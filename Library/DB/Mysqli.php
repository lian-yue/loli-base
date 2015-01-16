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
/*	Updated: UTC 2015-01-16 08:02:46
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Base') || exit;
class Mysqli extends Base{

	public function error() {
		if (!$this->link) {
			return false;
		}
		if (($link = $this->link($this->slave)) && $link->connect_error) {
			return $link->connect_error;
		}
		return $link ? $link->error : mysqli_error();
	}


	public function errno() {
		if (!$this->link) {
			return false;
		}
		if (($link = $this->link($this->slave)) && $link->connect_errno) {
			return $link->connect_errno;
		}
		return $link ? $link->errno : mysqli_errno();
	}


	public function connect($args) {
		if (!($link = new \mysqli(empty($args['host']) ? 'localhost' : $args['host'], empty($args['user']) ? 'root' : $args['user'], empty($args['pass']) ? '' : $args['pass'], empty($args['name']) ? 'dbname' : $args['name'], empty($args['port']) ? 3306 : $args['port'])) || $link->connect_errno) {
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

		$slave = $slave && !preg_match('/\s*(SELECT)/', $query);
		if (!$link = $this->link($slave)) {
			return false;
		}

		$q = $link->query($query);
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
			$r = $link->affected_rows;
			if (preg_match('/^\s*(INSERT|REPLACE) /i', $query)) {

				// 插入 替换 [字段]
				$this->insert_id = $link->insert_id;
				++self::$queryNum;

			}
		} else {
			$r = [];
 			while ($row = $q->fetch_object()) {
				$r[] = $row;
				++self::$queryRow;
			}
			$r && $q->free_result();


			if (preg_match('/^\s*SELECT.*(?:\s+SQL_CALC_FOUND_ROWS\s+).*(?=\s+FROM\s+)/is', $query)) {
				$this->found_rows = $this->count('SELECT FOUND_ROWS();', $slave);
			}

			if ($this->debug && preg_match('/^\s*(SELECT) /i', $query)) {
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
		if (!$link = $this->link(false)){
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
    	$r = $link->commit();
    	$link->autocommit(true);
    	if (!$r && $this->errno() && $this->debug) {
    		$this->exitError('COMMIT');
    	}
        return $r;
    }
    public function rollback() {
    	if (!$link = $this->link(false)) {
    		return false;
    	}
    	$this->autocommit = true;
		$r = $link->rollback();
		$link->autocommit(true);
    	if (!$r && $this->errno() && $this->debug) {
    		$this->exitError('ROLLBACK');
    	}
		return $r;
    }
}