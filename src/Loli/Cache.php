<?php
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
	private static $_link;
	public static function __callstatic($method, $args) {
		if (!isset(self::$_link)) {
			$class = __NAMESPACE__ . '\Cache\\' . (empty($_SERVER['LOLI']['CACHE']['mode']) ? 'File' : $_SERVER['LOLI']['CACHE']['mode']);
			self::$_link = new $class($_SERVER['LOLI']['CACHE']['args'], empty($_SERVER['LOLI']['CACHE']['key']) ? '' : $_SERVER['LOLI']['CACHE']['key']);
		}
		return call_user_func_array([self::$_link, $method], $args);
	}
}