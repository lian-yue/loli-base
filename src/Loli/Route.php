<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-04-15 03:40:04
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-04-06 12:01:29
/*	Updated: UTC 2015-04-15 03:40:04
/*
/* ************************************************************************** */
namespace Loli;
use ArrayAccess;
class Route implements ArrayAccess{

	protected $data = [];

	protected static $callback = [
		'request' => 'Loli\\Route::load',
		'response' => 'Loli\\Route::load',
		'localize' => 'Loli\\Route::load',
		'cache' => 'Loli\\Route::load',
		'storage' => 'Loli\\Route::load',
		'DB' => 'Loli\\Route::load',
	];

	public function __construct(Request $request = NULL, Response $response = NULL, array $args = []) {
		if ($request) {
			$this['request'] = $request;
		}

		if ($response) {
			$this['response'] = $response;
		}
		foreach ($args as $name => $value) {
			$this[strtolower($name)] = $value;
		}
	}


	public function __destruct($name) {
		foreach ($this->data as $value) {
			unset($this->data[$name])
		}
	}



	public function offsetExists($name) {
		return isset($this->data[$name]);
	}

	public function offsetGet($name) {
		if (!isset($this->data[$name])) {
			if (empty(self::$callback[$name])) {
				throw new Exception('Data does not exist', 1);
			}
			$this->data[$name] = call_user_func(self::$callback[$name], $this, $name);
		}
		return $this->data[$name];
	}

	public function offsetSet($name , $value) {
		 if ($name === NULL) {
            $this->data[] = $value;
        } else {
            $this->data[$name] = $value;
        }
	}

	public function offsetUnset($name) {
		unset($this->data[$name]);
		return true;
	}





	public static function setCallback($name, $callback) {
		self::$callback[$name] = $callback;
		return true;
	}




	protected static function load($route, $name) {
		switch ($name) {
			case 'request':
				// 请求对象
				$result = new HTTP\Request;
				break;
			case 'response':
				// 响应对象
				$result = new HTTP\Response($this['request']);
				break;
			case 'localize':
				// 本地化模块
				$result = new Localize(empty($_SERVER['LOLI']['LOCALIZE']['language']) ? false : $_SERVER['LOLI']['LOCALIZE']['language'], empty($_SERVER['LOLI']['LOCALIZE']['timezone']) ? false : $_SERVER['LOLI']['LOCALIZE']['timezone']);
				foreach ($this['request']->getAcceptLanguages() as $language) {
					if ($result->setLanguage($language)) {
						break;
					}
				}
				break;
			case 'storage':
				// 储存模块
				$class = __NAMESPACE__ . '\Storage\\' . (empty($_SERVER['LOLI']['STORAGE']['type']) ? 'Local' : $_SERVER['LOLI']['STORAGE']['type']);
				$result = new $class($_SERVER['LOLI']['STORAGE']);
				break;
			case 'cache':
				// 缓存模块
				$class = __NAMESPACE__ . '\Cache\\' . (empty($_SERVER['LOLI']['CACHE']['type']) ? 'File' : $_SERVER['LOLI']['CACHE']['type']);
				$result = new $class(empty($_SERVER['LOLI']['CACHE']['args']) ? [] : $_SERVER['LOLI']['CACHE']['args'], empty($_SERVER['LOLI']['CACHE']['key']) ? '' : $_SERVER['LOLI']['CACHE']['key']);
				break;
			case 'DB':
				// 数据库模块
				static $protocol = [
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

				if (empty($server['protocol'])) {
					$server['protocol'] = 'mysql';
				}
				$class = __NAMESPACE__.'\\DB';
				if (class_exists('PDO') && in_array($servers['protocol'], \PDO::getAvailableDrivers())) {
					$class .= 'PDO';
				} elseif (isset($protocol[$servers['protocol']])) {
					$class .= $protocol[$servers['protocol']];
				} else {
					$class .= ucwords($servers['protocol']);
				}
				$result = new $class($servers, $this['cache']);
				break;
			default:
				$result = false;
		}
		return $result;
	}
}