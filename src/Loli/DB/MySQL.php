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
/*	Updated: UTC 2015-02-28 11:31:37
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Base') || exit;
class MySQL extends Base{
	public function connect(array $args) {
		// 链接到到mysql
		if (!$link = @mysql_pconnect($server = (empty($args['host']) ? 'localhost' : $args['host']) . ':' . (empty($args['port']) ? 3306 : $args['port']), empty($args['user']) ? 'root' : $args['user'], empty($args['pass']) ? '' : $args['pass'], empty($args['flags']) ? 0 : $args['flags'])) {
			throw new ConnectException('this.mysql_pconnect(' . $server .')', @mysql_error(), @mysql_errno());
		}

		// 链接到数据库
		if (empty($args['name'])) {
			throw new ConnectException('this.mysql_pconnect(' . $server . ').mysql_select_db()', 'Database name can not be empty');
		}
		if (!mysql_select_db($args['name'], $link)) {
			throw new ConnectException('this.mysql_pconnect(' . $server . ').mysql_select_db(' . $args['name'] . ')', mysql_error($link), mysql_errno($link));
		}
		// 默认设置
		mysql_query('SET NAMES \'utf8\'', $link);
		mysql_query('SET TIME_ZONE = `+0:00`', $link);
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
		$q = mysql_query($query, $link);

		// 记录次数
		++self::$querySum;


		// 查询是false
		if ($q === false) {
			// 如果有错误记录错误
			if (mysql_errno($link)) {
				$this->addLog($query);
				throw new Exception($query, mysql_error($link), mysql_errno($link));
			}
			$results = $q;
		} elseif (preg_match('/^\s*(CREATE|ALTER|TRUNCATE|DROP|SET|START|BEGIN|SERIAL|COMMIT|ROLLBACK|END)(\s+|;)/i', $query)) {
			// 创建 改变 修改 删除 [表] 设置
			$results = $q;
		} elseif (preg_match('/^\s*(INSERT|DELETE|UPDATE|REPLACE)\s+/i', $query)) {
			// 插入 删除 更新 替换 资料 [字段]
			$results = mysql_affected_rows($link);
			if (preg_match('/^\s*(INSERT|REPLACE) /i', $query)) {
				// 插入 替换 [字段]
				$this->insertID = mysql_insert_id($link);
				++self::$querySum;
			}
		} else {
			// 查询
			$results = [];
				while ($row = mysql_fetch_object($q)) {
				$results[] = $row;
				++self::$queryRow;
			}
			$results && mysql_free_result($q);

			// 执行  explain
			if ($this->explain && preg_match('/^\s*SELECT\s+/i', $query)) {
				$this->_query('EXPLAIN ' . $query, $slave);
			}
			if (preg_match('/^\s*SELECT\s+(?:(?!\s+FROM\s+).)+SQL_CALC_FOUND_ROWS/i', $query)){
				$this->foundRows = $this->count('SELECT FOUND_ROWS()', $slave);
			}
		}
		$this->addLog($query, $results);
		return $results;
	}

	public function ping($slave = NULL) {
		$link = $this->link($slave === NULL ? $this->slave : $slave);
		if (mysql_ping($link)) {
			return true;
		}
		throw new Exception('this.ping()', mysql_error($link), mysql_errno($link));
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
		$query = 'DROP TABLE `'. $table .'`';
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

	public function group($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}


	public function results($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}
	public function row($query, $slave = true) {
		return ($results = $this->results($query, $slave)) ? reset($results) : false;
	}
	public function aggregate($query, $slave = true) {
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
		$this->autoCommit = false;
	 	return $this->_query('START TRANSACTION;', false);
    }

    public function commit() {
    	$this->autoCommit = true;
        return $this->_query('COMMIT;', false);
    }

    public function rollback() {
    	$this->autoCommit = true;
		return $this->_query('ROLLBACK;', false);
    }
}