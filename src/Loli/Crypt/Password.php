<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-11-06 03:09:08
/*
/* ************************************************************************** */
namespace Loli\Crypt;
class Password{

	const PASSWORD = 'password';


	private static function _password(&$password) {
		if (strlen($password) !== 65 || $password{32} !== "\x01") {
			$password = md5(self::PASSWORD . $password) ."\x01". md5($password . self::PASSWORD);
		}
	}

	public static function hash($password) {
		self::_password($password);
		if (function_exists('password_hash')) {
			return password_hash($password, PASSWORD_BCRYPT);
		}
		return crypt($password, '$2y$10$'. uniqid(mt_rand(), true) .'$');
	}

	public static function verify($password, $hash) {
		self::_password($password);
		if (!$hash || !is_string($hash)) {
			return false;
		}
		if (function_exists('password_verify')) {
			return password_verify($password, $hash);
		}
		return hash_equals($password, $hash);
	}
}