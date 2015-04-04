<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-01 15:27:44
/*	Updated: UTC 2015-04-03 07:16:08
/*
/* ************************************************************************** */
namespace Loli;
class Storage{
	private static $_link;
	public static function __callStatic($method, $args) {
		if (!isset(self::$_link)) {
			$class = __NAMESPACE__ . '\Storage\\' . (empty($_SERVER['LOLI']['STORAGE']['type']) ? 'Local' : $_SERVER['LOLI']['STORAGE']['type']);
			self::$_link = new $class($_SERVER['LOLI']['STORAGE']);
		}
		return call_user_func_array([self::$_link, $method], $args);
	}
}