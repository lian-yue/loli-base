<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-22 09:02:15
/*	Updated: UTC 2015-04-06 12:08:31
/*
/* ************************************************************************** */
namespace Loli;
class DB{
	private $_link;
	private $_protocols = [
		'mysql' => 'MySQLi',
		'maria' => 'MySQLi',
		'mariadb' => 'MySQLi',

		'postgresql' => 'PGSQL',
		'pgsql' => 'PGSQL',
		'pg' => 'PGSQL',

		'sqlserver' => 'MSSQL',
		'mssql' => 'MSSQL',

		'sqlite' => 'SQLite',

		'mongo' => 'Mongo',
		'mongodb' => 'Mongo',

		'oci' => 'OCI',
		'oracle' => 'OCI',

		'odbc' => 'ODBC',
	];


	public function __construct(array $masterServers, array $slaveServers = [], $explain = false) {
		$master = reset($masterServers);
		if (is_int(key($master))) {
			$master = reset($master);
		}
		if (!is_array($master)) {
			$protocol = parse_url($master, PHP_URL_SCHEME);
		} else {
			$protocol = empty($master['protocol']) ? 'mysql' : $master['protocol'];
		}

		$class = __CLASS__.'\\';
		if (class_exists('PDO') && in_array($protocol = strtolower($protocol), \PDO::getAvailableDrivers())) {
			$class .= 'PDO';
		} elseif (isset($this->_protocols[$protocol])) {
			$class .= $this->_protocols[$protocol];
		} else {
			$class .= ucwords($protocol);
		}
		$this->_link = new $class($masterServers, $slaveServers, $explain);
	}
	public function __call($method, $args) {
		return call_user_func_array([$this->_link, $method], $args);
	}

	public function __get($key) {
		return $this->_link->$key;
	}
}