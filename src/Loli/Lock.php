<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-11 15:55:37
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-09 20:41:33
/*	Updated: UTC 2015-01-19 07:05:16
/*
/* ************************************************************************** */
namespace Loli;
class Lock{

	private static $_all = [];

	public static $dir = './';


	public function add($key, $wait = false) {
		if (!empty(self::$_all[$key])) {
			return false;
		}

		// 打开文件
		self::$_all[$key]['file'] = self::$dir .'/'. md5($key);
		self::$_all[$key]['data'] = fopen(self::$_all[$key]['file'], 'w+');

		// 锁定文件
		$is = flock(self::$_all[$key]['data'], $wait ? LOCK_EX : LOCK_EX|LOCK_NB);

		if (!$is) {
			fclose(self::$_all[$key]['data']);
			unset(self::$_all[$key]);
			return false;
		}
		return true;
	}


	public function remove($key) {
		if (empty(self::$_all[$key])) {
			return false;
		}
		flock(self::$_all[$key]['data'], LOCK_UN);
		fclose(self::$_all[$key]['data']);
		@unlink(self::$_all[$key]['file']);
		unset(self::$_all[$key]);
		return true;
	}
}

Lock::$dir = empty($_SERVER['LOLI']['lock']['dir']) ? './' : $_SERVER['LOLI']['lock']['dir'];