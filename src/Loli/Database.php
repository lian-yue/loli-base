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
class Database extends Group{
	protected static $name = 'database';

	protected static $protocol = [
		'mysql' => ['mysql', 'MySqlDatabase'],
		'maria' => ['mysql', 'MySqlDatabase'],
		'mariadb' => ['mysql', 'MySqlDatabase'],

		'postgresql' => ['pgsql', 'PostgreSqlDatabase'],
		'pgsql' => ['pgsql', 'PostgreSqlDatabase'],
		'pg' => ['pgsql', 'PostgreSqlDatabase'],

		'sqlserver' => ['mssql', 'MsSqlDatabase'],
		'mssql' => ['mssql', 'MsSqlDatabase'],

		'sqlite' => ['sqlite', 'SQLiteDatabase'],

		'mongo' => ['mongo', 'MongoDatabase'],
		'mongodb' => ['mongo', 'MongoDatabase'],

		// 'oci' => ['oci', 'OCI'],
		// 'oracle' => ['oci', 'OCI'],
		//
		// 'odbc' => ['odbc', 'ODBC'],
	];

	protected static function link($group, array $config, $exists) {
		if (!$exists && $group !== 'default') {
			return static::group('default');
		}
		if (!$config) {
			$config = [[]];
		}
		$server = reset($config);
		if (empty($server['protocol'])) {
			$server['protocol'] = 'mysql';
		}



		$class = __NAMESPACE__.'\\Database\\';
		if (class_exists('PDO') && in_array($server['protocol'], \PDO::getAvailableDrivers(), true)) {
			$class .= 'PDODatabase';
		} elseif (isset(self::$protocol[$server['protocol']])) {
			$protocol = self::$protocol[$server['protocol']][0];
			$class .= self::$protocol[$server['protocol']][1];
			foreach ($config as $key => $value) {
				if (!empty($value['protocol']) && $value['protocol'] === $server['protocol']) {
					$config[$key]['protocol'] = $protocol;
				}
			}
		} else {
			$class .= ucwords($server['protocol']) . 'Database';
		}
		$result = new $class($config);
		$result->setLogger(Log::group(static::$name));
		return $result;
	}
}
