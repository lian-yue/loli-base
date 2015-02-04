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
/*	Updated: UTC 2015-02-03 09:18:44
/*
/* ************************************************************************** */
namespace Loli;
class Static_{

	// 全部注册信息
	private static $_all = [];

	private static $_link;

	private static $_linkArgs;

	// 需要运行 php 的 后缀
	public static $php = ['css', 'js', 'htm', 'html', 'php', 'xml', 'txt'];


	// 根目URL
	public static $url = '/';

	// 版本号
	public static $version = 0;



	public static function init() {
		if (!empty($_SERVER['LOLI']['STATIC'])) {
			self::$_linkArgs = $_SERVER['LOLI']['STATIC'];
			foreach ( $_SERVER['LOLI']['STATIC'] as $k => $v) {
				if (in_array($k, ['url', 'version'])) {
					self::$$k = $v;
				}
			}
		}
	}


	/**
	*	写入缓存 资源数据
	*
	*	1 参数 原始目录 or 文件
	*	2 参数 缓存目录 or 文件 路径
	*
	*	返回值 bool
	**/
	public static function add($source, $dest) {
		if (!$source | !$dest || !empty(self::$_all[$source])) {
			return false;
		}
		self::$_all[$source] = ['dest' => $dest];
		return true;
	}

	public static function url($a, $f = true) {
		if (strpos($a, '$version') === false && $f && preg_match('/\.[a-z]+$/i', $a)) {
			$a .= (strpos($a, '?') === false ? '?' : '&') . 'v=$version';
		}
		return self::$url . strtr($a, ['$lang' => Lang::$current, '$version' =>self::$version]);
	}

	/**
	*	移除资源
	*
	*	1 参数 key
	*
	*	返回值 bool
	**/
	public static function remove($key) {
		if (empty(self::$_all[$key])) {
			return false;
		}
		unset(self::$_all[$key]);
		return true;
	}

	/**
	*	执行缓存资源
	*
	*	无参数
	*
	*	返回值 bool
	**/
	public static function flush() {
		$current = Lang::$current;
		foreach (self::$_all as $k => $v) {
			foreach(is_file($k) ? [$v] : self::_dir($k, $v['dest']) as $source => $vv) {
				foreach (Lang::$all as $kkk => $vvv) {
					$dest = strtr($vv['dest'], ['$lang' => $kkk, '$version' =>self::$version]);
					self::_generate($source, $dest);
					if ($dest == $vv['dest']) {
						break;
					}
				}
			}
		}
		Lang::set($current);
	}


	/**
	*	生成某个缓存文件
	*
	*	1 参数 原始文件路径
	*	2 参数 缓存文件路径
	*
	*	返回值 bool
	**/
	private static function _generate($source, $dest) {
		if (!is_file($source)) {
			return false;
		}
		if (!self::$_link) {
			$class = '\Loli\Storage\\' . (empty(self::$_linkArgs['type']) ? 'Local' : self::$_linkArgs['type']);
			self::$_link = new $class(self::$_linkArgs);
		}
		if ($php = preg_match('/\.('. implode('|', self::$php) .')$/i', $dest)) {
			ob_start();
			require $source;
			$contents = ob_get_contents();
			ob_end_clean();
			self::$_link->cput($dest, $contents);
		} else {
			self::$_link->put($dest, $source);
		}
		return true;
	}



	/**
	*	列出 dir 里面所有文件
	*
	*	1 参数 目录
	*
	*	返回值 array
	**/
	private static function _dir($source, $dest) {
		$r = [];
		if (is_dir($source)) {
			$handle = opendir($source);
			// 循环
			while ($path = readdir($handle)) {
				if ($path == '.' || $path == '..') {
					continue;
				}
				if (is_dir($source . '/'. $path)) {
					$r = array_merge($r, self::_dir($source . '/'. $path, $dest . '/'. $path));
					continue;
				}
				$r[$source .'/'. $path] = ['dest' => $dest .'/'. $path];

			}
			closedir ($handle);
		}
		return $r;

	}
}
Static_::init();