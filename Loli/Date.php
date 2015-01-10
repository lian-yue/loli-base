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
/*	Updated: UTC 2015-01-09 10:07:06
/*
/* ************************************************************************** */
namespace Loli;
use  DateTime, DateTimeZone;

class Date{

	// 时区
	public static $timezone = '+00:00';

	// 全部时区
	public static $allTimezone = [
		'-12:00',
		'-11:30',
		'-11:00',
		'-10:30',
		'-10:00',
		'-09:30',
		'-09:00',
		'-08:30',
		'-08:00',
		'-07:30',
		'-07:00',
		'-06:30',
		'-06:00',
		'-05:30',
		'-05:00',
		'-04:30',
		'-04:00',
		'-03:30',
		'-03:00',
		'-02:30',
		'-02:00',
		'-01:30',
		'-01:00',
		'-00:30',
		'+00:00',
		'+00:30',
		'+01:00',
		'+01:30',
		'+02:00',
		'+02:30',
		'+03:00',
		'+03:30',
		'+04:00',
		'+04:30',
		'+05:00',
		'+05:30',
		'+05:45',
		'+06:00',
		'+06:30',
		'+07:00',
		'+07:30',
		'+08:00',
		'+08:30',
		'+08:45',
		'+09:00',
		'+09:30',
		'+10:00',
		'+10:30',
		'+11:00',
		'+11:30',
		'+12:00',
		'+12:30',
		'+12:45',
		'+13:00',
		'+13:30',
		'+13:45',
		'+14:00',
	];

	public static $name = '';

	public static function init() {
		if (!empty($_SERVER['LOLI']['DATE'])) {
			foreach ($_SERVER['LOLI']['DATE'] as $k => $v) {
				if (in_array($k, ['timezone', 'allTimezone', 'name'])) {
					self::$$k = $v;
				}
			}
		}


		//self::$allTimezone = array_merge(DateTimeZone::listIdentifiers(), self::$allTimezone);

		// COOKIE
		self::$name && ($cookie = Cookie::get(self::$name)) && self::setTimezone((string)$cookie);

		// GET POST
		self::$name && !empty($_REQUEST[self::$name]) && self::setTimezone((string)$_REQUEST[self::$name]);
	}


	/**
	*	本地化语言包
	*
	*	1 参数原文
	*	2 参数 未翻译是否输出原文默认true
	*
	*	返回值 译文
	**/
	public static function lang($d, $original = true) {
		return Lang::get($d, 'date', $original);
	}


	/**
	*	全部时区
	*
	*
	*
	*
	**/
	public static function allTimezone() {
		$r = [];
		foreach (self::$allTimezone as $v) {
			$r[$v] = self::lang($v);
		}
		return $r;
	}

	/**
	*	写入时区
	*
	*	1 参数 时区
	*	2 参数 是否写入cookie
	*
	*	返回值 false true
	**/
	public static function setTimezone($timezone, $cookie = false) {
		if (is_numeric($timezone)) {
			$arr = explode('.', $timezone);
			$arr[0] = ($arr[0] < 0 ? '-' : '+'). str_pad(intval($arr[0]), 2, '0',STR_PAD_LEFT);
			if (empty($arr[1])) {
				$arr[1] = '00';
				$arr[1] = ('0.' . $arr[1]) * 60;
			}
			$arr[1] = str_pad(('0.' . $arr[1]) * 60, 2, '0',STR_PAD_RIGHT);
			$timezone = implode(':', $arr);
		} elseif (substr($timezone, 0, 1) == ' ') {
			$timezone = '+' . substr($timezone, 1);
		}
		if (!in_array($timezone, self::$allTimezone)) {
			return false;
		}
		self::$timezone = $timezone;
		$cookie && self::$name && Cookie::set(self::$name, $timezone, 86400 * 365);
		return true;
	}

	/**
	*	格式化时间
	*
	*	1 参数 时间戳
	*	2 参数 格式
	*	3 参数 是否使用语言
	*	4 参数 自定义时区
	*
	**/
	public static function format($format, $time = false, $lang = true, $timezone = false) {
		static $date;
		// 时区
		if ($timezone && !in_array($timezone, self::$allTimezone)) {
			return false;
		}
		$timezone = $timezone ? $timezone : self::$timezone;

		// 时间 时区 对象
		if (empty($date) || $date->getTimezone()->getName() !== $timezone) {
			if (in_array($timezone, DateTimeZone::listIdentifiers())) {
				$date = new DateTime(null, new DateTimeZone($timezone));
			} else {
				$date = new DateTime($timezone);
			}
		}

		// 当前时间戳
		if ($time === false) {
			$time = time();
		}

		// 返回时间戳
		if ($format == 'U') {
			return $time;
		}

		// 写入时间戳
		$date->setTimestamp($time);

		// 无视语言包
		if (!$lang || in_array($format, ['Z', 'c', 'r'])) {
			return $date->format($format);
		}

		// 本地化格式
		if ($tmep = self::lang('format_' . $format, false)) {
			$format = $tmep;
		}

		// 本地化语言
		$r = ' ' . $format;
		$replace = ['D', 'l', 'L', 'S', 'F', 'M', 'a', 'A', 'e'];
		foreach($replace as $v) {
			if (strpos($format, $v) !== false) {
				$d = $date->format($v);
				if ($tmep = self::lang($v == 'e'? $d : $v . '_' . $d, false)) {
					$d = $tmep;
				}
				$r = preg_replace('/([^\\\])'. $v .'/', '\\1' . preg_replace('/([a-z])/i', '\\\\\1', $d), $r);
			}
		}

		return $date->format(substr($r, 1));
	}


	/**
	*	2个时间的差距
	*
	*	1 参数 时间戳
	*	2 参数 格式
	*	3 参数 是否显示差距时间
	*
	**/
	public static function human($from, $to = false) {
		$to = $to === false ? time() : $to;
		$diff = max($to, $from) - min($to, $from);

		// 刚刚
		if ($diff == 0) {
			return self::lang('Now');
		}

		if ($diff < 60) {
			// 秒
			$since = self::lang([$diff == 1 ? '$1 second'  : '$1 seconds', $diff]);
		} elseif ($diff <= 3600 && ($min = round($diff / 60)) < 60) {
			// 钟
			$since = self::lang([$min == 1 ? '$1 min'  : '$1 mins', $min]);
		} elseif (($diff <= 86400) && ($hour = round($diff / 3600)) < 24) {
			// 时
			$since = self::lang([$hour == 1 ? '$1 hour'  : '$1 hours', $hour]);
		} elseif ($diff <= 2592000 && ($min = round($diff / 86400)) < 30) {
			// 天
			$since = self::lang([$min == 1 ? '$1 day'  : '$1 days', $min]);
		} elseif ($diff <= 31536000 && ($min = round($diff / 2592000)) < 12) {
			// 月
			$since = self::lang([$min == 1 ? '$1 month'  : '$1 months', $min]);
		} else {
			// 年
			$year = round($diff / 31536000);
			$since = self::lang([ $year == 1 ? '$1 year'  : '$1 years', $year]);
		}
		return self::lang(['$1 ' . ($from > $to ? 'later' : 'ago'), $since]);
	}


	/**
	*	计算时间的长度
	*
	*	1 参数 长度
	*	2 参数 最高位数
	*	3 参数 最低多少填补
	*
	*	返回值  00:01  这样的
	**/
	public static function length($time, $n = 3, $i = 2) {

		// 转换成数组
		$a = [60, 60, 24, 30, 12, 0];
		$ii = $floor = 0;
		foreach ($a as $k => $v) {
			++$ii;
			$vv = $floor ? floor($time / $floor) : $time;
			$floor = $floor ? $v * $floor : $v;
			$arr[] = $v && $ii < $n ? $vv % $v : $vv;
			if ($ii >= $n) {
				break;
			}
		}
		// 填补
		for ($ii = 0; $ii < $i; ++$ii) {
			$arr[$ii] = zeroise($arr[$ii], 2);
		}

		// 格式化
		$a = [':', ':', ' ', '-', '-', '-'];
		$r = '';
		foreach (array_reverse($arr, true) as $k => $v) {
			if ($v || $r) {
				$r  .= $a[$k] . $v;
			}
		}
		return ltrim($r ,' :-');
	}
}