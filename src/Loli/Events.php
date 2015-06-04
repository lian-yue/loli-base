<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-25 13:38:31
/*	Updated: UTC 2015-04-22 03:29:01
/*
/* ************************************************************************** */
namespace Loli;
class Events{
	private static $_events = [];
	private static $_sorts = [];
	private static $_counts = [];

	public static function bind($name, callable $callable, $priority = 10) {
		self::$_events[$name][$priority][self::_id($callable)] = $callable;
		self::$_sorts[$name] = false;
		return true;
	}

	public static function has($name, callable $callable, $priority = 10) {
		if (empty(self::$_events[$name][$priority])) {
			return false;
		}
		return !empty(self::$_events[$name][$priority][self::_id($callable)]);
	}

	public static function count($name) {
		return empty(self::$_counts[$name]) ? 0 : self::$_counts[$name];
	}

	public static function remove($name, callable $callable, $priority = 10) {
		if (empty(self::$_events[$name][$priority][$id = self::_id($callable)])) {
			return false;
		}
		unset(self::$_events[$name][$priority][$id]);
		self::$_sorts[$name] = false;
		return true;
	}

	public static function run($name, array $params = []) {
		if (empty(self::$_counts[$name])) {
			self::$_counts[$name] = 0;
		}
		++self::$_counts[$name];

		if (empty(self::$_events[$name])) {
			return;
		}

		if (empty(self::$_sorts[$name])) {
			ksort(self::$_sorts[$name]);
			self::$_sorts[$name] = true;
		}
		foreach (self::$_events[$name] as $callables) {
			foreach($callables as $callable) {
				if (call_user_func_array($callable, $params) === false) {
					break 2;
				}
			}
		}
	}


	public static function get($name, array $params = []) {
		if (empty(self::$_counts[$name])) {
			self::$_counts[$name] = 0;
		}
		++self::$_counts[$name];

		$params += [0 => NULL];

		if (empty(self::$_events[$name])) {
			return $params[0];
		}

		if (empty(self::$_sorts[$name])) {
			ksort(self::$_sorts[$name]);
			self::$_sorts[$name] = true;
		}
		foreach (self::$_events[$name] as $callables) {
			foreach($callables as $callable) {
				$params[0] = call_user_func_array($callable, $params);
			}
		}
		return $params[0];
	}



	private static  function _id(callable $callable) {
		if (is_string($callable)) {
			return $callable;
		}
		if (is_object($callable)) {
			return spl_object_hash($callable);
		}
		if (is_object($callable[0])) {
			return spl_object_hash($callable[0]) . $callable[1];
		}
		return $callable[0].$callable[1];
	}
}
