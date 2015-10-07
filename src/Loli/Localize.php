<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-20 10:44:42
/*
/* ************************************************************************** */
namespace Loli;
use  DateTime, DateTimeZone;
class Localize{
	protected $language = 'en';

	protected static $allLanguage = [];

	protected static $replaceLanguage = [];

	protected static $languageGroups = [];

	protected static $languageFile = [];

	protected static $loadLanguage = [];





	protected $timezone = '+00:00';

	protected static $allTimezone = [];

	protected $dateTime;


	public function __construct($language = false, $timezone = false) {
		if (!self::$allLanguage) {
			if (empty($_SERVER['LOLI']['LOCALIZE']['allLanguage'])) {
				self::$allLanguage = ['en' => 'English'];
			} else {
				self::$allLanguage = $_SERVER['LOLI']['LOCALIZE']['allLanguage'];
			}
			if (!empty($_SERVER['LOLI']['LOCALIZE']['replaceLanguage'])) {
				self::$replaceLanguage = $_SERVER['LOLI']['LOCALIZE']['replaceLanguage'];
			}
			if (!empty($_SERVER['LOLI']['LOCALIZE']['file'])) {
				self::$languageFile = $_SERVER['LOLI']['LOCALIZE']['file'];
			}
		}

		if (!self::$allTimezone) {
			if (empty($_SERVER['LOLI']['LOCALIZE']['allTimezone'])) {
				self::$allTimezone = array_merge(DateTimeZone::listIdentifiers(), [ '-12:00', '-11:30', '-11:00', '-10:30', '-10:00', '-09:30', '-09:00', '-08:30', '-08:00', '-07:30', '-07:00', '-06:30', '-06:00', '-05:30', '-05:00', '-04:30', '-04:00', '-03:30', '-03:00', '-02:30', '-02:00', '-01:30', '-01:00', '-00:30', '+00:00', '+00:30', '+01:00', '+01:30', '+02:00', '+02:30', '+03:00', '+03:30', '+04:00', '+04:30', '+05:00', '+05:30', '+05:45', '+06:00', '+06:30', '+07:00', '+07:30', '+08:00', '+08:30', '+08:45', '+09:00', '+09:30', '+10:00', '+10:30', '+11:00', '+11:30', '+12:00', '+12:30', '+12:45', '+13:00', '+13:30', '+13:45', '+14:00']);
			} else {
				self::$allTimezone = $_SERVER['LOLI']['LOCALIZE']['allTimezone'];
			}
		}

		$this->language = key(self::$allLanguage);
		$this->timezone = current(self::$allTimezone);

		$language && $this->setLanguage($language);
		$timezone && $this->setTimezone($timezone);
	}




	public function getLanguage() {
		return $this->language;
	}

	public function setLanguage($language) {
		if (!is_string($language) || !preg_match('/^([a-z]{2})(?:[_-]([a-z]{3-4}))?(?:[_-]([a-z]{2}))?$/', $language, $matches)) {
			return false;
		}
		$array[] = strtolower($matches[1]);
		if ($matches[2]) {
			$array[] = ucwords($matches[2]);
		}
		if ($matches[3]) {
			$array[] = strtoupper($matches[3]);
		}
		$language =  implode('-', $array);

		// 需要替换的
		if (isset(self::$replaceLanguage[$language])) {
			$language = self::$replaceLanguage[$language];
		}
		// 全部允许语言
		if (empty(self::$allLanguage[$language])) {
			return false;
		}
		$this->language = $language;

		return $language;
	}




	public function allLanguage() {
		$all = [];
		foreach (self::$allLanguage as $key => $value) {
			$all[$key] = $this->translate($value, ['language']);
		}
		return $all;
	}






	public function getTimezone() {
		return $this->timezone;
	}


	public function setTimezone($timezone) {
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
		$this->timezone = $timezone;

		return $this->timezone;
	}



	public function allTimezone() {
		$all = [];
		foreach (self::$allTimezone as $key => $value) {
			$all[$key] = $this->dateTimeTranslate($value);
		}
		return $all;
	}






	public function translate($text, $groups = ['default'], $original = true) {
		if (is_array($text)) {
			$replace = [];
			foreach ($text as $key => $value) {
				if ($key) {
					$replace['$'.$key] = $value;
				}
			}
			return strtr($this->translate(reset($text), $groups, $original), $replace);
		}


		foreach ((array) $groups as $group) {
			// 如果已经有了直接返回
			if (isset(self::$languageGroups[$this->language][$group][$text])) {
				return self::$languageGroups[$this->language][$group][$text];
			}

			if (self::$languageFile && !isset(self::$languageGroups[$this->language][$group]) && !in_array($file = sprintf(self::$languageFile, $this->language, $group), self::$loadLanguage, true)) {
				self::$loadLanguage[] = $file;
				if (!isset(self::$languageGroups[$this->language][$group])) {
					self::$languageGroups[$this->language][$group] = [];
				}
				if (is_file($file)) {
					self::$languageGroups[$this->language][$group] = ((array) require $file) + self::$languageGroups[$this->language][$group];
					if (isset(self::$languageGroups[$this->language][$group][$text])) {
						return self::$languageGroups[$this->language][$group][$text];
					}
				}
			}
		}
		return $original ? $text : false;
	}







	/**
	 * format 格式化时间
	 * @param  string          $format    http://php.net/manual/function.date.php
	 * @param  boolean|integer $time      time
	 * @param  boolean         $translate 是否翻译
	 * @param  boolean|string  $timezone  自定义时区
	 * @return string
	 */
	public function dateFormat($format, $time = 0, $translate = true, $timezone = false) {
		// 时区
		if ($timezone && !in_array($timezone, self::$allTimezone)) {
			return false;
		}
		if (!$timezone) {
			$timezone = $this->timezone;
		}
		// 时间 时区 对象
		if (empty($this->dateTime) || $this->dateTime->getTimezone()->getName() !== $timezone) {
			if (in_array($timezone, DateTimeZone::listIdentifiers())) {
				$this->dateTime = new DateTime(null, new DateTimeZone($timezone));
			} else {
				$this->dateTime = new DateTime($timezone);
			}
		}

		// 当前时间戳
		if ($time === false) {
			$time = time();
		} elseif (!is_numeric($time)) {
			$time = strtotime($time);
		}


		// 返回时间戳
		if ($format === 'U') {
			return $time;
		}

		// 写入时间戳
		$this->dateTime->setTimestamp($time);

		// 无视语言包
		if (!$translate || in_array($format, ['Z', 'c', 'r'], true)) {
			return $this->dateTime->format($format);
		}

		// 本地化格式
		if ($format2 = $this->dateTimeTranslate('format_' . $format, false)) {
			$format = $format2;
		}

		// 本地化语言
		$result = ' ' . $format;
		foreach(['D', 'l', 'L', 'S', 'F', 'M', 'a', 'A', 'e'] as $value) {
			if (strpos($format, $value) !== false) {
				$valueFormat = $this->dateTime->format($value);
				if ($valueFormat2 = $this->dateTimeTranslate($value === 'e' ? $valueFormat : $value . '_' . $valueFormat, false)) {
					$valueFormat = $valueFormat2;
				}
				$result = preg_replace('/([^\\\])'. $value .'/', '\\1' . preg_replace('/([a-z])/i', '\\\\\1', $valueFormat), $result);
			}
		}

		return $this->dateTime->format(substr($result, 1));
	}


	/**
	 * timeDiff 返回2个时间的相差
	 * @param  integer         $from 时间
	 * @param  boolean|integer $to   当前时间
	 * @return string
	 */
	public function timeDiff($from, $to = false) {
		$to = $to === false ? time() : $to;
		$diff = max($to, $from) - min($to, $from);

		// 刚刚
		if ($diff == 0) {
			return $this->dateTimeTranslate('Now');
		}

		if ($diff < 60) {
			// 秒
			$since = $this->dateTimeTranslate([$diff == 1 ? '$1 second'  : '$1 seconds', $diff]);
		} elseif ($diff <= 3600 && ($min = round($diff / 60)) < 60) {
			// 钟
			$since = $this->dateTimeTranslate([$min == 1 ? '$1 min'  : '$1 mins', $min]);
		} elseif (($diff <= 86400) && ($hour = round($diff / 3600)) < 24) {
			// 时
			$since = $this->dateTimeTranslate([$hour == 1 ? '$1 hour'  : '$1 hours', $hour]);
		} elseif ($diff <= 2592000 && ($min = round($diff / 86400)) < 30) {
			// 天
			$since = $this->dateTimeTranslate([$min == 1 ? '$1 day'  : '$1 days', $min]);
		} elseif ($diff <= 31536000 && ($min = round($diff / 2592000)) < 12) {
			// 月
			$since = $this->dateTimeTranslate([$min == 1 ? '$1 month'  : '$1 months', $min]);
		} else {
			// 年
			$year = round($diff / 31536000);
			$since = $this->dateTimeTranslate([ $year == 1 ? '$1 year'  : '$1 years', $year]);
		}

		return $this->dateTimeTranslate(['$1 ' . ($from > $to ? 'later' : 'ago'), $since]);
	}


	/**
	 * timeLength 返回时间的长度
	 * @param  integer $time 时间戳
	 * @param  integer $n    最大组
	 * @param  integer $i    最小组
	 * @return string
	 */
	public function timeLength($time, $n = 3, $i = 2) {
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



	public function numberFormat($number) {
		return number_format($number);
	}



	protected function dateTimeTranslate($from, $to = false) {
		return $this->translate($text, ['datetime'], $original);
	}



	protected static function loadLanguage($file, $language,  $group) {
		if (in_array($file = sprintf($file, $language, $group), self::$loadLanguage, true)) {
			return false;
		}
		self::$loadLanguage[] = $file;
		if (!isset(self::$languageGroups[$language][$group])) {
			self::$languageGroups[$language][$group] = [];
		}
		if (is_file($file)) {
			self::$loadLanguage[$lang][$group] = ((array) require $file) + self::$loadLanguage[$language][$group];
		}
		return true;
	}
}