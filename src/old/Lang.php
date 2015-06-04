<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-25 13:38:13
/*	Updated: UTC 2015-04-25 05:25:54
/*
/* ************************************************************************** */
namespace Loli;
class Lang{

	// 全部语言
	public static $all = ['en' => 'English'];

	// 默认语言
	public static $default = 'en';

	// 替换的浏览器正则
	public static $replace = [];

	// 当前语言
	public static $current = '';

	// 请求中的参数
	public static $name = 'lang';

	// 浏览器有的语言
	public static $userAll = [];

	// 翻译语言包
	private static $_langs = [];

	// 语言包目录
	private static $_files = [];

	// 载入过的文件
	private static $_loads = [];

	// 载入过的列表
	private static $_lists = [];

	public static function init() {
		if (!empty($_SERVER['LOLI']['LANG'])) {
			foreach ($_SERVER['LOLI']['LANG'] as $k => $v) {
				if (in_array($k, ['all', 'default', 'replace', 'name', 'file'])) {
					$k == 'file' ? '_files' : $k;
					self::$$k = $v;
				}
			}
		}

		self::_userAll();


		if (self::$name) {
			empty($_COOKIE[self::$name]) || is_array($_COOKIE[self::$name]) || self::set($_COOKIE[self::$name]);
			empty($_REQUEST[self::$name]) || is_array($_REQUEST[self::$name]) || self::set($_REQUEST[self::$name]);
		}
	}

	private static function _userAll() {
		self::$userAll = [];

 		// 正则表达提取语言
		if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && preg_match_all("/(([a-z]{2})[a-z_\-]{0,8})/i", $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) {
			foreach ($matches[1] as $k => $v) {
				self::$userAll[] = $v;
				if ($v != $matches[2][$k]) {
					self::$userAll[] = $matches[2][$k];
				}
			}
		}

		// 2. 整理语言
		foreach (self::$userAll as $k => &$lang) {
			if (!$lang = self::format($lang)) {
				unset(self::$userAll[$k]);
			}
		}

		// 默认语言写到最后
		self::$userAll[] = self::$default;

		// 过滤 重复数组 返回交集 重置下标
		self::$userAll = array_values(array_intersect(array_unique(self::$userAll), array_keys(self::$all)));
		self::$current = reset(self::$userAll);
		return self::$userAll;
	}

	/**
	*	格式化语言
	*
	*
	*
	*
	**/
	public static function format($lang) {
		$lang = explode('-', strtr($lang, '_', '-'), 4);

		// 检测语言正确性
		if (!$lang || isset($lang[3]) || ($s0 = strlen($lang[0])) < 2 || $s0 > 4 || (isset($lang[1]) && ($s1 = strlen($lang[1])) != 2 && $s1 != 4) || (isset($lang[2]) && ($s2 = strlen($lang[2])) != 2)) {
			return false;
		}
		$r = false;
		$lang[0] = strtolower($lang[0]);

		// zh  样式
		if (!$r && !isset($lang[1])) {
			$r = $lang[0];
		}

		// zh-CN 样式
		if (!$r && !isset($lang[2]) && $s1 == 2) {
			$r = $lang[0] .'-'. strtoupper($lang[1]);
		}

		// mn-Mong 样式
		if (!$r && !isset($lang[2]) && $s1 == 4) {
			$r =  $lang[0] .'-'. ucfirst($lang[1]);
		}

		// mn-Mong-CN 样式
		if (!$r && isset($lang[2]) && $s1 == 4) {
			$r = $lang[0].'-'. ucfirst($lang[1]) .'-'. strtoupper($lang[2]);
		}

		// 需要替换的
		$r = empty(self::$replace[$r]) || empty(self::$all[self::$replace[$r]]) ? $r : self::$replace[$r];


		// 全部允许语言
		if ($r && !array_key_exists($r, self::$all)) {
			$r = false;
		}

		return $r;
	}


	/**
	*	写入一个新语言
	*
	*	1 参数 语言
	*	2 参数 时候在结束为止写入 默认 false
	*
	*	返回值 true false
	**/
	public static function set($lang = '', $end = false, $cookie = false) {
		if (!$lang = self::format($lang)) {
			return false;
		}
		if ($end) {
			self::$userAll[] = $lang;
		} else {
			array_unshift(self::$userAll, $lang);
		}

		// 过滤 重复数组 返回交集 重置下标
		self::$userAll = array_values(array_intersect(array_unique(self::$userAll), array_keys(self::$all)));
		self::$current = reset(self::$userAll);

		$cookie && self::$name && Router::response()->setCookie(self::$name, self::$current, -1);
		return self::$current;
	}



	/**
	*	翻印语言
	*
	*	1 参数 语言原文
	*	2 参数 语言目录 (文件)
	*
	*	返回值  如果有 就返回翻译后的语言 没就返回原文
	**/

	public static function get($text, $lists = ['default'], $original = true) {
		if (is_array($text)) {
			$replace = [];
			foreach ($text as $k => $v) {
				if ($k) {
					$replace['$'.$k] = $v;
				}
			}
			return strtr(self::get(reset($text), $lists, $original), $replace);
		}
		foreach ((array) $lists as $v) {
			// 如果已经有了直接返回
			if (isset(self::$_langs[self::$current][$v][$text])) {
				return self::$_langs[self::$current][$v][$text];
			}
			// 加载 php 文件
			if (self::$_files && (empty(self::$_lists[self::$current]) || !in_array($v, self::$_lists[self::$current]))) {
				self::$_lists[self::$current][] = $v;
				foreach (self::$_files as $vv) {
					self::load($vv, self::$current, $v);
				}
				if (isset(self::$_langs[self::$current][$v][$text])) {
					return self::$_langs[self::$current][$v][$text];
				}
			}
		}
		return $original ? $text : false;
	}

	// 添加个语言目录
	public static function file($file) {
		self::$_lists = [];
		self::$_files[] = $file;
		return true;
	}



	/**
	*	载入 语言文件
	*
	*	1 参数
	*
	*	返回值 true  false
	**/
	public static function load($file, $lang,  $list = 'default') {
		$file = sprintf($file, $lang, $list);
		if (in_array($file, self::$_loads)) {
			return false;
		}
		self::$_loads[] = $file;
		if (!isset(self::$_langs[$lang][$list])) {
			self::$_langs[$lang][$list] = [];
		}
		if (is_files($file)) {
			self::$_langs[$lang][$list] = ((array) require $file) + self::$_langs[$lang][$list];
		}
		return true;
	}
}
Lang::init();
