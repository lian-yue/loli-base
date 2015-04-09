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
/*	Updated: UTC 2015-04-07 14:10:32
/*
/* ************************************************************************** */
namespace Loli;
use  DateTime, DateTimeZone;

class Date{

	// 时区
	protected static $timezone = '+00:00';

	// 全部时区
	protected static $allTimezone = [
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

	/**
	 *  init
	 */
	public static function init() {
		if (!empty($_SERVER['LOLI']['DATE'])) {
			foreach ($_SERVER['LOLI']['DATE'] as $key => $value) {
				if ($value !== NULL && isset(self::$$key)) {
					self::$$key = $value;
				}
			}
		}
		//self::$allTimezone = array_merge(DateTimeZone::listIdentifiers(), self::$allTimezone);
	}


	/**
	 * translate
	 * @param  string  $text     需要翻译的语言
	 * @param  boolean $original 是否输出原始语言
	 * @return string
	 */
	public static function translate($text, $original = true) {
		return Lang::translate($text, ['dates'], $original);
	}



	/**
	 * allTimezone 全部时区
	 * @return array
	 */
	public static function allTimezone() {
		$allTimezone = [];
		foreach (self::$allTimezone as $timezone) {
			$allTimezone[$timezone] = self::translate($timezone);
		}
		return $allTimezone;
	}

	/**
	 * getTimezone 获得当前时区
	 * @return string
	 */
	public function getTimezone() {
		return self::$timezone;
	}

	/**
	 * setTimezone 设置时区
	 * @param string $timezone 时区
	 */
	public static function setTimezone($timezone) {
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
		return true;
	}

	/**
	 * format 格式化时间
	 * @param  string          $format    http://php.net/manual/function.date.php
	 * @param  boolean|integer $time      time
	 * @param  boolean         $translate 是否翻译
	 * @param  boolean|string  $timezone  自定义时区
	 * @return string
	 */
	public static function format($format, $time = 0, $translate = true, $timezone = false) {
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
		if (!$translate || in_array($format, ['Z', 'c', 'r'])) {
			return $date->format($format);
		}

		// 本地化格式
		if ($tmep = self::translate('format_' . $format, false)) {
			$format = $tmep;
		}

		// 本地化语言
		$r = ' ' . $format;
		$replace = ['D', 'l', 'L', 'S', 'F', 'M', 'a', 'A', 'e'];
		foreach($replace as $v) {
			if (strpos($format, $v) !== false) {
				$d = $date->format($v);
				if ($tmep = self::translate($v == 'e'? $d : $v . '_' . $d, false)) {
					$d = $tmep;
				}
				$r = preg_replace('/([^\\\])'. $v .'/', '\\1' . preg_replace('/([a-z])/i', '\\\\\1', $d), $r);
			}
		}

		return $date->format(substr($r, 1));
	}

	/**
	 * human 返回2个时间的相差
	 * @param  integer         $from 时间
	 * @param  boolean|integer $to   当前时间
	 * @return string
	 */
	public static function human($from, $to = false) {
		$to = $to === false ? time() : $to;
		$diff = max($to, $from) - min($to, $from);

		// 刚刚
		if ($diff == 0) {
			return self::translate('Now');
		}

		if ($diff < 60) {
			// 秒
			$since = self::translate([$diff == 1 ? '$1 second'  : '$1 seconds', $diff]);
		} elseif ($diff <= 3600 && ($min = round($diff / 60)) < 60) {
			// 钟
			$since = self::translate([$min == 1 ? '$1 min'  : '$1 mins', $min]);
		} elseif (($diff <= 86400) && ($hour = round($diff / 3600)) < 24) {
			// 时
			$since = self::translate([$hour == 1 ? '$1 hour'  : '$1 hours', $hour]);
		} elseif ($diff <= 2592000 && ($min = round($diff / 86400)) < 30) {
			// 天
			$since = self::translate([$min == 1 ? '$1 day'  : '$1 days', $min]);
		} elseif ($diff <= 31536000 && ($min = round($diff / 2592000)) < 12) {
			// 月
			$since = self::translate([$min == 1 ? '$1 month'  : '$1 months', $min]);
		} else {
			// 年
			$year = round($diff / 31536000);
			$since = self::translate([ $year == 1 ? '$1 year'  : '$1 years', $year]);
		}
		return self::translate(['$1 ' . ($from > $to ? 'later' : 'ago'), $since]);
	}


	/**
	 * length 返回时间的长度
	 * @param  integer $time 时间戳
	 * @param  integer $n    最大组
	 * @param  integer $i    最小组
	 * @return string
	 */
	public static function length($time, $n = 3, $i = 2) {
		static $lengths = [60, 60, 24, 30, 12, 0], $splits = [':', ':', ' ', '-', '-', '-'];

		// 转换成数组
		$ii = $floor = 0;
		foreach ($lengths as $length) {
			++$ii;
			$value = $floor ? floor($time / $floor) : $time;
			$floor = $floor ? $length * $floor : $length;
			$array[] = $length && $ii < $n ? $value % $length : $value;
			if ($ii >= $n) {
				break;
			}
		}
		// 填补
		for ($ii = 0; $ii < $i; ++$ii) {
			$array[$ii] = sprintf("%02d", $array[$ii]);
		}

		// 格式化
		$result = '';
		foreach (array_reverse($array, true) as $key => $value) {
			if ($value || $result) {
				$result  .= $splits[$key] . $value;
			}
		}
		return ltrim($result ,' :-');
	}
}
Date::init();
