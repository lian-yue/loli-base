<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-17 14:51:45
/*	Updated: UTC 2015-01-19 06:58:28
/*
/* ************************************************************************** */
namespace Loli;

class Token{
	// 当前 token
	private static $_token = false;

	public static $name = '';

	public static function set($token, $cookie = false) {
		if (!is_string($token) || strlen($token) != 32 || Code::key(__CLASS__ . substr($token, 0, 16), 16) != substr($token, 16)) {
			return false;
		}
		$cookie && self::$name && Cookie::set(self::$name, $token, 86400 * 365, true);
		return self::$_token = $token;
	}

	public static function get($key = false) {
		if (!self::$_token) {
			if (self::$name && !self::set(Cookie::get(self::$name))) {
				$token = uniqid();
				$token .= mb_rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
				$token .= Code::key(__CLASS__ . $token, 16);
				self::set($token, true);
			}
		}
		return $key ? self::$_token : substr(self::$_token, 0, 16);
	}
}


Token::$name = isset($_SERVER['LOLI']['TOKEN']['name']) ? $_SERVER['LOLI']['TOKEN']['name'] : '';