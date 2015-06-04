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
/*	Updated: UTC 2015-05-23 11:55:55
/*
/* ************************************************************************** */
namespace Loli;
class DB{
	private $_link;
	private $_protocols = [
		'mysql' => ['mysql', 'MySQLi'],
		'maria' => ['mysql', 'MySQLi'],
		'mariadb' => ['mysql', 'MySQLi'],

		'postgresql' => ['pgsql', 'PGSQL'],
		'pgsql' => ['pgsql', 'PGSQL'],
		'pg' => ['pgsql', 'PGSQL'],

		'sqlserver' => ['mssql', 'MSSQL'],
		'mssql' => ['mssql', 'MSSQL'],

		'sqlite' => ['sqlite', 'SQLite'],

		'mongo' => ['mongo', 'Mongo'],
		'mongodb' => ['mongo', 'Mongo'],

		'oci' => ['oci', 'OCI'],
		'oracle' => ['oci', 'OCI'],

		'odbc' => ['odbc', 'ODBC'],
	];


	public function __construct(array $servers) {
		if (empty($server['protocol'])) {
			$server['protocol'] = 'mysql';
		}
		$class = __CLASS__.'\\';
		if (class_exists('PDO') && in_array($servers['protocol'], \PDO::getAvailableDrivers())) {
			$class .= 'PDO';
		} elseif (isset($this->_protocols[$servers['protocol']])) {
			$class .= $this->_protocols[$servers['protocol']];
		} else {
			$class .= ucwords($servers['protocol']);
		}
		$this->_link = new $class($servers);
	}

	public function __call($method, $args) {
		return call_user_func_array([$this->_link, $method], $args);
	}

	public function __get($key) {
		return $this->_link->$key;
	}
}