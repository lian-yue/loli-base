<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-27 04:00:39
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
/*	Created: UTC 2015-02-07 12:42:01
/*	Updated: UTC 2015-02-07 12:48:07
/*
/* ************************************************************************** */
namespace Loli;
class Cache{
	public static function __callStatic($method, $args) {
		return self::group('default')->$method(...$args);
	}

	public static function group($group) {
		static $links = [], $configs = [];
		if (empty($configs)) {
			foreach (empty($_SERVER['LOLI']['cache']) ? [[]] : $_SERVER['LOLI']['cache'] as $key => $value) {
				$configs[is_int($key) ? 'default' : $key] = (array) $value;
			}
		}


		if (!$group) {
			$group = 'default';
		}

		if (empty($links[$group])) {
			$config = isset($configs[$group]) ? $configs[$group] : reset($configs);
			$class = empty($config['type']) ? 'Memory' : $config['type'];
			if ($class[0] !== '\\') {
				$class = __NAMESPACE__ . '\Cache\\' . $class;
			}
			$links[$group] = new $class(empty($config['args']) ? [] : (array) $config['args'], $group . (empty($_SERVER['LOLI']['key']) ? '' : $_SERVER['LOLI']['key']));
		}
		return $links[$group];
	}
}