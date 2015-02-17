<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-10 05:18:47
/*	Updated: UTC 2015-02-16 10:08:38
/*
/* ************************************************************************** */
namespace Loli;
class Session{
	private static $_token;
	public static function get($key) {
		return Cache::get(self::$_token . $key, __CLASS__);
	}
	public static function add($key, $value, $ttl = 3600) {
		return Cache::add($value, self::$_token . $key, __CLASS__, $ttl);
	}
	public static function set($key, $value, $ttl = 3600) {
		return Cache::set($value, self::$_token . $key, __CLASS__, $ttl);
	}
	public static function delete($key) {
		var_dump(expression)
		return Cache::delete(self::$_token . $key, __CLASS__);
	}
}
