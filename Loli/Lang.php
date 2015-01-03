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
/*	Updated: UTC 2015-01-01 07:08:33
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model, Loli\Cookie;

class Lang extends Model{

	// 全部语言
	public $all = ['en' => '->'];

	// 默认语言
	public $default = 'en';

	// 替换的浏览器正则
	public $replace = [];

	// 当前语言
	public $current = '';

	// 请求中的参数
	public $name = '';

	// 浏览器有的语言
	private $_user = [];

	// 翻译语言包
	private $_lang = [];

	// 语言包目录
	private $_file = [];

	// 载入过的文件
	private $_load = [];

	// 载入过的列表
	private $_list = [];


	public function __construct() {
		if (!empty($_SERVER['LOLI']['LANG'])) {
			foreach ($_SERVER['LOLI']['LANG'] as $k => $v) {
				if (in_array($k, ['all', 'default', 'replace', 'name', 'file'])) {
					$k == 'file' ? '_file' : $k;
					$this->$k = $v;
				}
			}
		}

		$this->_user();

		// COOKIE
		$this->name && ($cookie = Cookie::get($this->name)) && $this->set((string)$cookie);

		// GET POST
		$this->name && !empty($_REQUEST[$this->name]) && $this->set((string)$_REQUEST[$this->name]);
	}

	public function __invoke() {
		return call_user_func_array([$this, '_'], func_get_args());
	}


	private function _user() {
		$this->_user = [];

 		// 正则表达提取语言
		if (preg_match_all("/(([a-z]{2})[a-z_\-]{0,8})/i", empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? '' : $_SERVER["HTTP_ACCEPT_LANGUAGE"], $arr)) {
			foreach ($arr[1] as $k => $v) {
				$this->_user[] = $v;
				if ($v != $arr[2][$k]) {
					$this->_user[] = $arr[2][$k];
				}
			}
		}

		// 2. 整理语言
		$lang_all = [];
		foreach ($this->_user as $k => &$lang) {
			if (!$lang = $this->format($lang)) {
				unset($this->_user[$k]);
			}
		}

		// 默认语言写到最后
		$this->_user[] = $this->default;

		// 过滤 重复数组 返回交集 重置下标
		$this->_user = array_values(array_intersect(array_unique($this->_user), array_keys($this->all)));
		$this->current = reset($this->_user);
		return $this->_user;
	}

	/**
	*	格式化语言
	*
	*
	*
	*
	**/
	public function format($lang) {
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
		$r = empty($this->replace[$r]) || empty($this->all[$this->replace[$r]]) ? $r : $this->replace[$r];


		// 全部允许语言
		if ($r && !array_key_exists($r, $this->all)) {
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
	public function set($lang = '', $end = false, $cookie = false) {
		if (!$lang = $this->format($lang)) {
			return false;
		}
		if ($end) {
			$this->_user[] = $lang;
		} else {
			array_unshift($this->_user, $lang);
		}

		// 过滤 重复数组 返回交集 重置下标
		$this->_user = array_values(array_intersect(array_unique($this->_user), array_keys($this->all)));
		$this->current = reset($this->_user);

		$cookie && $this->name && Cookie::set($this->name, $this->current, 86400 * 365);
		return $this->current;
	}



	/**
	*	翻印语言
	*
	*	1 参数 语言原文
	*	2 参数 语言目录 (文件)
	*
	*	返回值  如果有 就返回翻译后的语言 没就返回原文
	**/

	public function __($text, $lists = ['default'], $original = true) {
		if (is_array($text)) {
			$replace = [];
			foreach ($text as $k => $v) {
				if ($k) {
					$replace['$'.$k] = $v;
				}
			}
			return strtr($this->__(reset($text), $lists, $original), $replace);
		}
		foreach ((array) $lists as $v) {
			// 如果已经有了直接返回
			if (isset($this->_lang[$this->current][$v][$text])) {
				return $this->_lang[$this->current][$v][$text];
			}
			// 加载 php 文件
			if ($this->_file && (empty($this->_list[$this->current]) || !in_array($v, $this->_list[$this->current]))) {
				$this->_list[$this->current][] = $v;
				foreach ($this->_file as $vv) {
					$this->load($vv, $this->current, $v);
				}
				if (isset($this->_lang[$this->current][$v][$text])) {
					return $this->_lang[$this->current][$v][$text];
				}
			}
		}
		return $original ? $text : false;
	}

	// 添加个语言目录
	public function file($file) {
		$this->_list = [];
		$this->_file[] = $file;
		return true;
	}



	/**
	*	载入 语言文件
	*
	*	1 参数
	*
	*	返回值 true  false
	**/
	public function load($file, $lang,  $list = 'default') {
		$file = sprintf($file, $lang, $list);
		if (in_array($file, $this->_load)) {
			return false;
		}
		$this->_load[] = $file;
		if (!isset($this->_lang[$lang][$list])) {
			$this->_lang[$lang][$list] = [];
		}
		if (is_file($file)) {
			$this->_lang[$lang][$list] = ((array) require $file) + $this->_lang[$lang][$list];
		}
		return true;
	}
}
