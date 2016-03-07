<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-28 08:43:12
/*
/* ************************************************************************** */
namespace Loli;
class Locale{
	protected static $language = 'en';

	protected static $languageReplace = [];

	protected static $languageLists = ['en' => 'English'];

	protected static $languageFile;

	protected static $languageTimezone = [];

	protected static $timezone = 'UTC';

	protected static $groups = [];

	protected static $load = [];

	public static function getLanguageLists() {
		return self::$languageLists;
	}


	public static function getTimezoneLists() {
		$timezoneOption = [];
		foreach (\DateTimeZone::listIdentifiers() as $value) {
			$timezoneOption[$value] = DateTime::translate($value);
		}
		return $timezoneOption;
	}

	public static function getTimezone() {
		return self::$timezone;
	}

	public static function setTimezone($timezone) {
		return self::$timezone = $timezone;
	}

	public static function getLanguage() {
		return self::$language;
	}

	public static function setLanguage($language) {
		if (!is_string($language) || !preg_match('/^([a-z]{2})(?:[_-]([a-z]{3-4}))?(?:[_-]([a-z]{2}))?$/', $language, $matches)) {
			return false;
		}
		$array[] = strtolower($matches[1]);
		if (!empty($matches[2])) {
			$array[] = ucwords($matches[2]);
		}
		if (!empty($matches[3])) {
			$array[] = strtoupper($matches[3]);
		}
		$language =  implode('-', $array);

		// 需要替换的
		if (isset(self::$languageReplace[$language])) {
			$language = self::$languageReplace[$language];
		}
		// 全部允许语言
		if (empty(self::$languageLists[$language])) {
			return false;
		}
		return self::$language = $language;
	}




	public static function translate($text, $groups = ['default'], $original = true) {
		if (is_array($text)) {
			$replace = [];
			foreach ($text as $key => $value) {
				if ($key) {
					$replace['{'.$key . '}'] = $value;
				}
			}
			return strtr(self::translate(reset($text), $groups, $original), $replace);
		}

		foreach ((array) $groups as $group) {
			// 如果已经有了直接返回
			if (isset(self::$groups[self::$language][$group][$text])) {
				return self::$groups[self::$language][$group][$text];
			}

			if (self::$languageFile && !isset(self::$groups[self::$language][$group]) && !in_array($file = strtr(self::$languageFile,  ['{language}'=> self::$language, '{group}' => $group]), self::$load, true)) {
				self::$load[] = $file;
				if (!isset(self::$groups[self::$language][$group])) {
					self::$groups[self::$language][$group] = [];
				}
				if (is_file($file)) {
					self::$groups[self::$language][$group] = ((array) require $file) + self::$groups[self::$language][$group];
					if (isset(self::$groups[self::$language][$group][$text])) {
						return self::$groups[self::$language][$group][$text];
					}
				}
			}
		}
		return $original === true ? $text : $original;
	}

	public static function register() {
		$replaces = [
			'language' => 'language',
			'language_lists' => 'languageLists',
			'language_replace' => 'languageReplace',
			'language_file' => 'languageFile',
			'timezone' => 'timezone'
		];
		foreach (configure('locale', []) as $key => $value) {
			if (isset($replaces[$key])) {
				self::$$replaces[$key]  = $value;
			}
		}
	}
}
Locale::register();
