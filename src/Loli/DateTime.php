<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-28 06:52:48
/*
/* ************************************************************************** */
namespace Loli;
use JsonSerializable;

class DateTime extends \DateTime implements JsonSerializable{
	const TO_STRING = 'Y-m-d H:i:s';

	const SQL = 'Y-m-d H:i:s';

	public function format($format, $translate = false) {
		if (!$translate || in_array(trim($format), ['U', 'Z', 'c', 'r'], true)) {
			return parent::format($format);
		}

		// 本地化格式
		$format = self::translate('format_' . $format, $format);


		// 本地化语言
		$result = ' ' . $format;
		foreach(['D', 'l', 'L', 'S', 'F', 'M', 'a', 'A', 'e'] as $value) {
			if (strpos($format, $value) !== false) {
				$valueFormat = parent::format($value);
				$valueFormat = self::translate($value === 'e' ? $valueFormat : $value . '_' . $valueFormat, $valueFormat);
				$result = preg_replace('/([^\\\])'. $value .'/', '\\1' . preg_replace('/([a-z])/i', '\\\\\1', $valueFormat), $result);
			}
		}
		return parent::format(substr($result, 1));
	}


	/**
	 * timeDiff 返回2个时间的相差
	 * @param  integer         $from 时间
	 * @param  boolean|integer $to   当前时间
	 * @return string
	 */
	public function formatDiff($datetime2 = false, $absolut = false) {
		if ($datetime2 instanceof \DateTimeInterface) {

		} elseif ($datetime2) {
			$datetime2 = new static($datetime2);
		} else {
			static $datetime3, $time;
			if ($datetime3 || $time !== time()) {
				$datetime3 = new static('now');
			}
			$datetime2 = $datetime3;
		}
		$dateInterval = $this->diff($datetime2, $absolut);


		$diff = 0;
		foreach (['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 's' => 'second'] as $key => $value) {
			if ($dateInterval->$key) {
				$diff = $dateInterval->$key;
				break;
			}
		}

		if ($diff === 0) {
			return self::translate('Now');
		}
		$since = self::translate(['{1} ' .$value . ($diff > 1 ? 's' : ''), $diff]);

 		return self::translate(['{1} ' . ($dateInterval->invert ? 'later' : 'ago'), $since]);
	}


	public function __invoke($format = self::W3C, $translate = true) {
		return $this->format($format, $translate);
	}

	public function __toString() {
		return $this->format(self::TO_STRING);
	}


	public function jsonSerialize() {
		return ['date' => $this->__toString(), 'diff' => $this->formatDiff(), 'timezone' => $this->timezone, 'timezone_type' => $this->timezone_type];
	}

	public function getTimeZone() {
		return $this->instanceTimeZone(parent::getTimeZone());
	}

	public static function createFromFormat($format, $time, $timezone = NULL) {
		if ($timezone === null) {
			$datetime = parent::createFromFormat($format, $time);
        } else {
        	$datetime = parent::createFromFormat($format, $time, $timezone);
        }
        if ($datetime) {
        	return self::instance($datetime);
        }

        $errors = static::getLastErrors();
        throw new Exception(implode(PHP_EOL, $errors['errors']));
	}


	public static function translate($text, $original = true) {
		return Language::translate($text, ['datetime'], $original);
	}

	public static function instance(\DateTime $datetime) {
		return new static($datetime->format('Y-m-d H:i:s.u'), $datetime->getTimeZone());
    }

	public static function instanceTimeZone($timezone) {
		if (!$timezone) {
			$timezone = new DateTimeZone(date_default_timezone_get());
		}

		if ($timezone instanceof DateTimeZone) {
			return $timezone;
		}

		if ($timezone instanceof \DateTimeZone) {
			$timezone = $timezone->getName();
		}

		return new DateTimeZone((string) $timezone);
	}
}

