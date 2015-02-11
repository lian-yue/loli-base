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
/*	Updated: UTC 2015-02-11 07:24:22
/*
/* ************************************************************************** */
namespace Loli;
class Code{

	// 生成 字符串 KEY
	public static $key = '';

	// 解密是否过期
	public static $expire = false;



	/**
	* 加密 key (不可解密的 只能用来判断) 请勿加密重要数据
	*
	* 1 参数 加密的数据    不能是数组 类 和文件资源
	* 2 参数 返回值 长度 默认 20 字节
	*
	* 返回值 0-9 a-z
	**/
	public static function key($key, $len = 20) {
		$key = md5($key . self::$key);
		$r = '';
		while( strlen($r) < $len) {
			$r .= md5($r . $key . $len . self::$key);
		}
		return substr($r, 0, $len);
	}

	/**
	*	加密解密 KEY 算法
	*
	*	1 参数 明文数据
	*	2 参数 KEY1 字符串前面的
	*	3 参数 KEY2 字符串后面的
	*
	*	返回值 经过 移位运算后的数据 array
	**/
	private static function _code($str, $key) {
		// 生成随机字符串
		$key = self::key('code' . $key, 32);
		$r = '';
		$len = strlen($str);
		for ($i = 0; $i < $len; ++$i) {
			$r .= substr($str, $i, 1) ^ substr($key, $i % 31, 1);
		}
		return $r;
	}


	/**
	*	加密数据 (可解密)
	*
	*	1 参数 加密的数据 支持数组等
	*	2 参数 加密的密码 可以不填写  你加密的时候写的什么密码 解密就要写什么密码
	*	3 参数 密码有效期
	*
	*	返回值  0-9 a-z A-Z - _
	**/
	public static function en($str, $key = '', $ttl = 0) {
		// 随机
		$rand = self::rand(6, '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM_-');

		// base64_decode 随机字符串 和数据
		$code = strtr(base64_encode(self::_code(gettype($str) .'|'. ($ttl? $ttl + time() : 0) .'|'. (is_array($str)||is_object($str) ? serialize($str) :$str), $rand . $key)), ['=' => '', '+' => '-', '/' => '_']);

		// 数据完整性
		$test = '';
		foreach (str_split(self::key($rand . $code . $key, 8)) as $v) {
			$test .= rand(0, 4) ? strtoupper($v) : $v;
		}

		return $rand . $code . $test;

	}


	/**
	*	解密数据
	*
	*	1 参数 密文
	*	2 参数 加密时候填写的密码
	*
	*	返回值  你存入的数据
	**/
	public static function de($str, $key = '') {
		self::$expire = false;
		$len = strlen($str);
		if ($len < 14) {
			return false;
		}
		// 从字符串中提取 各种解密需要的数据
		$rand = substr($str, 0, 6);
		$code = substr($str, 6, -8);
		// 验证数据完整性
		if (strtolower(substr($str, -8)) !== self::key($rand . $code . $key, 8)) {
			return false;
		}
		// 解密
		if (count($exp = explode('|', self::_code(base64_decode(strtr($code, ['-' => '+', '_' => '/'])), $rand . $key), 3)) != 3) {
			return false;
		}
		// 检查过期
		if (!is_numeric($exp[1]) || ($exp[1] && $exp[1] < time())) {
			self::$expire = true;
			return false;
		}
		if ($exp[0] == 'integer') {
			return (int) $exp[2];
		}
		if ($exp[0] == 'float' || $exp[0] == 'double') {
			return (float) $exp[2];
		}
		if ($exp[0] == 'array' || $exp[0] == 'object') {
			return ($un = @unserialize($exp[2])) ? $un : false;
		}
		return $exp[2];
	}
}

Code::$key = isset($_SERVER['LOLI']['CODE']['key']) ? $_SERVER['LOLI']['CODE']['key'] : '';