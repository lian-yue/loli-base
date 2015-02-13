<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-31 16:02:54
/*	Updated: UTC 2015-02-11 05:39:50
/*
/* ************************************************************************** */
namespace Loli;
class Cookie{
	public static $prefix = '';

	public static $path = '/';

	public static $domain = false;

	public static $secure = false;

	public static $httponly = false;

	public static function get($name) {
			return isset($_COOKIE[self::$prefix . $name]) ? $_COOKIE[self::$prefix . $name] : false;
	}

	public static function delete($name) {
		unset($_COOKIE[self::$prefix .$name]);
		@setcookie(self::$prefix .$name, 'deleted', 1, self::$path, self::$domain, self::$secure, self::$httponly);
		return true;
	}

	public static function add($name, $value, $ttl = 0, $httponly = null, $secure = null) {
			return self::$get($name) && self::set($name, $value, $httponly, $secure);
	}

	public static function set($name, $value, $ttl = 0, $httponly = null, $secure = null) {
		if (is_array($value) || is_object($value)) {
			foreach ($value as $k => $v) {
				self::set($name . '[' . rawurlencode($k) . ']', $value, $ttl, $httponly, $secure);
			}
		} else {
			$_COOKIE[self::$prefix .$name] = $value;
			@setcookie(self::$prefix .$name, $value, $ttl ? time() + $ttl : $ttl, self::$path, self::$domain, $secure == null ? self::$secure : $secure, $httponly == null ? self::$httponly : $httponly);
		}
		return true;
	}
}

if (!empty($_SERVER['LOLI']['COOKIE'])) {
	foreach ($_SERVER['LOLI']['COOKIE'] as $key => $value) {
		if (in_array($key, ['prefix', 'path', 'domain', 'secure', 'httponly'])) {
			Cookie::$$key = $value;
		}
	}
	unset($key, $value);
}
