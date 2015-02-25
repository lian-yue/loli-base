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
/*	Updated: UTC 2015-02-25 13:38:40
/*
/* ************************************************************************** */
namespace Loli;
class Filter{
	private static $_filters = [];
	private static $_sorts = [];
	private static $_counts = [];

	public static function add($name, callable $call, $priority = 10) {
		self::$_filters[$name][$priority][self::_id($call)] = $call;
		self::$_sorts[$name] = false;
		return true;
	}

	public static function has($name, callable $call, $priority = 10) {
		if (empty(self::$_filters[$name][$priority])) {
			return false;
		}
		return !empty(self::$_filters[$name][$priority][self::_id($call)]);
	}

	public static function count($name) {
		return empty(self::$_counts[$name]) ? 0 : self::$_counts[$name];
	}

	public static function remove($name, callable $call, $priority = 10) {
		if (empty(self::$_filters[$name][$priority][$id = self::_id($call)])) {
			return false;
		}
		unset(self::$_filters[$name][$priority][$id]);
		self::$_sorts[$name] = false;
		return true;
	}

	public static function run($name, array $params) {
		if (empty(self::$_counts[$name])) {
			self::$_counts[$name] = 0;
		}
		++self::$_counts[$name];

		if (empty(self::$_filters[$name])) {
			return;
		}

		if (empty(self::$_sorts[$name])) {
			ksort(self::$_sorts[$name]);
			self::$_sorts[$name] = true;
		}
		foreach (self::$_filters[$name] as $calls) {
			foreach($calls as $call) {
				call_user_func_array($call, $params);
			}
		}
	}


	public static function get($name, array $params) {
		if (empty(self::$_counts[$name])) {
			self::$_counts[$name] = 0;
		}
		++self::$_counts[$name];

		$params += [0 => null];

		if (empty(self::$_filters[$name])) {
			return $params[0];
		}

		if (empty(self::$_sorts[$name])) {
			ksort(self::$_sorts[$name]);
			self::$_sorts[$name] = true;
		}
		foreach (self::$_filters[$name] as $calls) {
			foreach($calls as $call) {
				$params[0] = call_user_func_array($call, $params);
			}
		}
		return $params[0];
	}



	private static  function _id(callable $call) {
		if (is_string($call)) {
			return $call;
		}
		if (is_object($call)) {
			return spl_object_hash($call);
		}
		if (is_object($call[0])) {
			return spl_object_hash($call[0]) . $call[1];
		}
		return $call[0].$call[1];
	}
}
