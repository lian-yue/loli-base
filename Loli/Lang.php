<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-01-03 12:20:32
/*
/* ************************************************************************** */
namespace Loli;
class Lang{

	// 全部语言
	public static $all = ['en' => '->'];

	// 默认语言
	public static $default = 'en';

	// 替换的浏览器正则
	public static $replace = [];

	// 当前语言
	public static $current = '';

	// 请求中的参数
	public static $name = '';

	// 浏览器有的语言
	public static $user = [];

	// 翻译语言包
	private static $_lang = [];

	// 语言包目录
	private static $_file = [];

	// 载入过的文件
	private static $_load = [];

	// 载入过的列表
	private static $_list = [];


	public static function init() {
		if (!empty($_SERVER['LOLI']['LANG'])) {
			foreach ($_SERVER['LOLI']['LANG'] as $k => $v) {
				if (in_array($k, ['all', 'default', 'replace', 'name', 'file'])) {
					$k == 'file' ? '_file' : $k;
					self::$$k = $v;
				}
			}
		}

		self::_user();

		// COOKIE
		self::$name && ($cookie = Cookie::get(self::$name)) && self::set((string)$cookie);

		// GET POST
		self::$name && !empty($_REQUEST[self::$name]) && self::set((string)$_REQUEST[self::$name]);
	}

	private static function _user() {
		self::$user = [];

 		// 正则表达提取语言
		if (preg_match_all("/(([a-z]{2})[a-z_\-]{0,8})/i", empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? '' : $_SERVER["HTTP_ACCEPT_LANGUAGE"], $arr)) {
			foreach ($arr[1] as $k => $v) {
				self::$user[] = $v;
				if ($v != $arr[2][$k]) {
					self::$user[] = $arr[2][$k];
				}
			}
		}

		// 2. 整理语言
		$lang_all = [];
		foreach (self::$user as $k => &$lang) {
			if (!$lang = self::format($lang)) {
				unset(self::$user[$k]);
			}
		}

		// 默认语言写到最后
		self::$user[] = self::default;

		// 过滤 重复数组 返回交集 重置下标
		self::$user = array_values(array_intersect(array_unique(self::$user), array_keys(self::all)));
		self::$current = reset(self::$user);
		return self::$user;
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
		$r = empty(self::replace[$r]) || empty(self::all[self::replace[$r]]) ? $r : self::replace[$r];


		// 全部允许语言
		if ($r && !array_key_exists($r, self::all)) {
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
			self::$user[] = $lang;
		} else {
			array_unshift(self::$user, $lang);
		}

		// 过滤 重复数组 返回交集 重置下标
		self::$user = array_values(array_intersect(array_unique(self::$user), array_keys(self::all)));
		self::$current = reset(self::$user);

		$cookie && self::$name && Cookie::set(self::$name, self::$current, 86400 * 365);
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
			if (isset(self::$_lang[self::$current][$v][$text])) {
				return self::$_lang[self::$current][$v][$text];
			}
			// 加载 php 文件
			if (self::$_file && (empty(self::$_list[self::$current]) || !in_array($v, self::$_list[self::$current]))) {
				self::$_list[self::$current][] = $v;
				foreach (self::$_file as $vv) {
					self::load($vv, self::$current, $v);
				}
				if (isset(self::$_lang[self::$current][$v][$text])) {
					return self::$_lang[self::$current][$v][$text];
				}
			}
		}
		return $original ? $text : false;
	}

	// 添加个语言目录
	public static function file($file) {
		self::$_list = [];
		self::$_file[] = $file;
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
		if (in_array($file, self::$_load)) {
			return false;
		}
		self::$_load[] = $file;
		if (!isset(self::$_lang[$lang][$list])) {
			self::$_lang[$lang][$list] = [];
		}
		if (is_file($file)) {
			self::$_lang[$lang][$list] = ((array) require $file) + self::$_lang[$lang][$list];
		}
		return true;
	}
}
Lang::init();