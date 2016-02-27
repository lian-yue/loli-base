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

	private static function token() {
		return Route::request()->token()->get();
	}

	public static function getItem($key) {
		return Cache::group(self::GROUP)->getItem(self::token() . $key);
	}

	public static function deleteItem($key) {
		return Cache::group(self::GROUP)->deleteItem(self::token() . $key);
	}

	public static function save(...$args) {
		return Cache::group(self::GROUP)->save(...$args);
	}
}
