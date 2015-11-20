<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-06-14 08:09:14
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
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-06-14 08:09:14
/*
/* ************************************************************************** */
namespace Loli\Crypt;
class Code{

	// 生成 字符串 KEY
	public static $key = '';

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
	 * rand 随机字符串
	 * @param  [type]  $length [description]
	 * @param  boolean $string [description]
	 * @return [type]          [description]
	 */
	public static function rand($length, $string = false) {
		if (!$string) {
			$string = '0123456789abcdefghijklmnopqrstuvwxyz';
		}
		$strlen = mb_strlen($string) - 1;
		$r = '';
		for ($i = 0; $i < $length; ++$i) {
			$r .= mb_substr($string, mt_rand(0, $strlen), 1);
		}
		return $r;
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
	private static function _code($string, $key) {
		// 生成随机字符串
		$key = self::key('code' . $key, 32);
		$r = '';
		$len = strlen($string);
		for ($i = 0; $i < $len; ++$i) {
			$r .= substr($string, $i, 1) ^ substr($key, $i % 31, 1);
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
	public static function encode($value, $password = '', $ttl = 0) {

		// 随机
		$rand = self::rand(6, '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM_-');

		$type = 9;
		foreach (['is_null', 'is_bool', 'is_int', 'is_float', 'is_string'] as $key => $function) {
			if ($function($value)) {
				$type = $key;
				break;
			}
		}

		// base64_encode 随机字符串 和数据
		$code = str_replace(['=', '+', '/'], ['', '-', '_'], base64_encode(self::_code($type .chr(0). ($ttl? $ttl + time() : 0) .chr(0). ($type === 9 ? serialize($value) :$value), $rand . $password)));


		// 数据完整性
		$test = '';
		foreach (str_split(self::key($rand . $code . $password, 8)) as $v) {
			$test .= mt_rand(0, 4) ? strtoupper($v) : $v;
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
	public static function decode($string, $password = '', &$expire = false) {
		$expire = false;
		if (!is_string($string) || strlen($string) < 14) {
			return false;
		}
		// 从字符串中提取 各种解密需要的数据
		$rand = substr($string, 0, 6);
		$code = substr($string, 6, -8);

		// 验证数据完整性
		if (strtolower(substr($string, -8)) !== self::key($rand . $code . $password, 8)) {
			return false;
		}

		// 解密
		if (count($arrays = explode(chr(0), self::_code(base64_decode(strtr($code, '-_', '+/')), $rand . $password), 3)) != 3) {
			return false;
		}
		list($type, $time, $value) = $arrays;

		// type
		if (!is_numeric($type)) {
			return false;
		}

		// 检查过期
		if (!is_numeric($time) || ($time && $time < time())) {
			$expire = true;
			return false;
		}

		switch ($type) {
			case 0:
				return NULL;
				break;
			case 1:
				return (bool) $value;
				break;
			case 2:
				return (int) $value;
				break;
			case 3:
				return (float) $value;
				break;
			case 4:
				return (string) $value;
				break;
			default:
				return ($un = @unserialize($value)) ? $un : false;
		}
	}
}

Code::$key = isset($_SERVER['LOLI']['CRYPT']['key']) ? $_SERVER['LOLI']['CRYPT']['key'] : '';