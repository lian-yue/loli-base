<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-26 15:21:32
/*
/* ************************************************************************** */
namespace Loli;
class Database{
	protected static $protocol = [
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

	public static function __callStatic($method, $args) {
		return self::group('default')->$method(...$args);
	}

	public static function group($group) {
		static $links = [], $configs = [];
		if (empty($configs)) {
			foreach (empty($_SERVER['LOLI']['database']) ? [[]] : $_SERVER['LOLI']['database'] as $key => $value) {
				$configs[is_int($key) ? 'default' : $key] = (array) $value;
			}
		}
		if (!$group) {
			$group = 'default';
		}

		if (empty($links[$group])) {
			$servers = isset($configs[$group]) ? $configs[$group] : reset($configs);
			if (!is_int(key($servers))) {
				$servers = [$servers];
			}

			$server = reset($servers);
			if (empty($server['protocol'])) {
				$server['protocol'] = 'mysql';
			}

			$class = __NAMESPACE__.'\\Database\\';
			if (class_exists('PDO') && in_array($server['protocol'], \PDO::getAvailableDrivers())) {
				$class .= 'PDO';
			} elseif (isset(self::$protocol[$server['protocol']])) {
				$protocol = self::$protocol[$server['protocol']][0];
				$class .= self::$protocol[$server['protocol']][1];
				foreach ($servers as $key => $value) {
					if (!empty($value['protocol']) && $value['protocol'] === $server['protocol']) {
						$servers[$key]['protocol'] = $protocol;
					}
				}
			} else {
				$class .= ucwords($server['protocol']);
			}
			$links[$group] = new $class($servers);
		}
		return $links[$group];
	}
}