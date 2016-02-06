<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-25 03:45:17
/*
/* ************************************************************************** */
namespace Loli;
class Session{
	const GROUP = 'session';

	private static function _token() {
		return Route::request()->getToken();
	}

	public static function get($key) {
		return Cache::group(self::GROUP)->get(self::_token() . $key);
	}

	public static function add($value, $key, $ttl = 1800) {
		return Cache::group(self::GROUP)->add($value, self::_token() . $key, $ttl);
	}

	public static function set($value, $key, $ttl = 1800) {
		return Cache::group(self::GROUP)->set($value, self::_token() . $key, $ttl);
	}

	public static function incr($n, $key) {
		return Cache::group(self::GROUP)->incr($value, self::_token() . $key);
	}

	public static function decr($n, $key) {
		return Cache::group(self::GROUP)->decr($value, self::_token() . $key);
	}

	public static function delete($key, $ttl = 0) {
		return Cache::group(self::GROUP)->delete(self::_token() . $key, $ttl);
	}

	public static function ttl($key) {
		return Cache::group(self::GROUP)->ttl(self::_token() . $key);
	}
}