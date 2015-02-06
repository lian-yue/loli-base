<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-06 06:23:05
/*	Updated: UTC 2015-02-06 06:52:42
/*
/* ************************************************************************** */
namespace Loli;


class Console{

	public static $is = false;

	public static function init() {
		self::$is = php_sapi_name() == 'cli';
	}


	public static function add() {
		if (self::$is) {
			return false;
		}
		return self::set();
	}

	public static function set() {
		self::$is = true;
		return true;
	}

	/**
	 *  运行ajax
	 * @param  array $data 传入数组
	 * @return exit 结束掉
	 */
	public static function get($data) {
		headers_sent() || header('X-Console: true', false);
		if(is_array($data) || is_object($data)) {
			return print_r($data, false)."\n";
		}
		return $data ."\n";
	}
}
Console::init();