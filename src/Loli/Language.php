<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-28 08:43:12
/*
/* ************************************************************************** */
namespace Loli;
class Language{
	protected static $name = 'en';

	protected static $all = ['en' => 'English'];

	protected static $replace = [];

	protected static $groups = [];

	protected static $file = [];

	protected static $load = [];


	public static function name() {
		return self::$name;
	}

	public static function set($name) {
		if (!is_string($name) || !preg_match('/^([a-z]{2})(?:[_-]([a-z]{3-4}))?(?:[_-]([a-z]{2}))?$/', $name, $matches)) {
			return false;
		}
		$array[] = strtolower($matches[1]);
		if ($matches[2]) {
			$array[] = ucwords($matches[2]);
		}
		if ($matches[3]) {
			$array[] = strtoupper($matches[3]);
		}
		$name =  implode('-', $array);

		// 需要替换的
		if (isset(self::$replace[$name])) {
			$name = self::$replace[$name];
		}
		// 全部允许语言
		if (empty(self::$all[$name])) {
			return false;
		}
		self::$name = $name;
		return $name;
	}




	public static function all() {
		return self::$all;
	}

	public static function translate($text, $groups = ['default'], $original = true) {
		if (is_array($text)) {
			$replace = [];
			foreach ($text as $key => $value) {
				if ($key) {
					$replace['{'.$key . '}'] = $value;
				}
			}
			return strtr(self::translate(reset($text), $groups, $original), $replace);
		}


		foreach ((array) $groups as $group) {
			// 如果已经有了直接返回
			if (isset(self::$groups[self::$name][$group][$text])) {
				return self::$groups[self::$name][$group][$text];
			}

			if (self::$file && !isset(self::$groups[self::$name][$group]) && !in_array($file = sprintf(self::$file, self::$name, $group), self::$load, true)) {
				self::$load[] = $file;
				if (!isset(self::$groups[self::$name][$group])) {
					self::$groups[self::$name][$group] = [];
				}
				if (is_file($file)) {
					self::$groups[self::$name][$group] = ((array) require $file) + self::$groups[self::$name][$group];
					if (isset(self::$groups[self::$name][$group][$text])) {
						return self::$groups[self::$name][$group][$text];
					}
				}
			}
		}
		return $original === true ? $text : $original;
	}




	protected static function load($file, $name,  $group) {
		if (in_array($file = sprintf($file, $name, $group), self::$load, true)) {
			return false;
		}
		self::$load[] = $file;
		if (!isset(self::$groups[$name][$group])) {
			self::$groups[$name][$group] = [];
		}
		if (is_file($file)) {
			self::$load[$lang][$group] = ((array) require $file) + self::$load[$name][$group];
		}
		return true;
	}

	public static function init() {
		if (!empty($_SERVER['LOLI']['language'])) {
			foreach ($_SERVER['LOLI']['language'] as $key => $value) {
				if (in_array($key, ['name', 'all', 'replace', 'file'], true)) {
					self::$$key  = $value;
				}
			}
		}
	}
}
Language::init();