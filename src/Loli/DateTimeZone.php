<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-28 08:10:56
/*
/* ************************************************************************** */
namespace Loli;
use JsonSerializable;
class DateTimeZone extends \DateTimeZone implements JsonSerializable{

	public function getName($translate = false) {
		$name = parent::getName();
		if ($translate) {
			$name = DateTime::translate($name);
		}
		return $name;
	}


	public function __toString() {
		return $this->getName();
	}

	public static function listIdentifiers($what = self::ALL, $country = null, $translate = false) {
		$identifiers = parent::listIdentifiers($what);
		if ($translate) {
			foreach ($identifiers as &$value) {
				$value = DateTime::translate($value);
			}
		}
		return $identifiers;
	}



	public static function listAbbreviations() {
		static $abbreviations = [];
		if (!$abbreviations) {
			$abbreviations = parent::listAbbreviations();
			foreach ($abbreviations as &$abbreviation) {
				foreach ($abbreviation as &$value) {
					$value['timezone_name'] = $value['timezone_id'] ? DateTime::translate($value['timezone_id']) : '';
				}
			}
		}
		return $abbreviations;
	}

	public function jsonSerialize() {
		return ['timezone_type' => $this->timezone_type, 'timezone' => $this->timezone, 'name' => $this->getName(true)];
	}
}