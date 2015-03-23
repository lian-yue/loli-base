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
/*	Updated: UTC 2015-03-22 14:34:48
/*
/* ************************************************************************** */
namespace Loli;
class Cache{
	private static $_link;
	public static function __callStatic($method, $args) {
		if (!isset(self::$_link)) {
			$class = __NAMESPACE__ . '\Cache\\' . (empty($_SERVER['LOLI']['CACHE']['type']) ? 'File' : $_SERVER['LOLI']['CACHE']['type']);
			self::$_link = new $class(empty($_SERVER['LOLI']['CACHE']['args']) ? [] : $_SERVER['LOLI']['CACHE']['args'], empty($_SERVER['LOLI']['CACHE']['key']) ? '' : $_SERVER['LOLI']['CACHE']['key']);
		}
		return call_user_func_array([self::$_link, $method], $args);
	}
}