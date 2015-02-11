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
/*	Updated: UTC 2015-02-11 05:38:19
/*
/* ************************************************************************** */
namespace Loli;
class Session{
	public static function get($key) {
		return Cache::get(Token::get() . $key, __CLASS__);
	}
	public static function add($key, $value, $ttl = 3600) {
		return Cache::add($value, Token::get() . $key, __CLASS__, $ttl);
	}
	public static function set($key, $value, $ttl = 3600) {
		return Cache::set($value, Token::get() . $key, __CLASS__, $ttl);
	}
	public static function delete($key) {
		return Cache::delete(Token::get() . $key, __CLASS__);
	}
}