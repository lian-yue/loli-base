<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-05-23 11:01:10
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
/*	Created: UTC 2015-02-25 10:50:28
/*	Updated: UTC 2015-05-23 11:01:10
/*
/* ************************************************************************** */
namespace Loli;
use Loli\Log\Base;

class Log{
	const LEVEL_ACCESS = Base::LEVEL_ACCESS;
	const LEVEL_NOTICE = Base::LEVEL_NOTICE;
	const LEVEL_WARNING = Base::LEVEL_WARNING;
	const LEVEL_ERROR = Base::LEVEL_ERROR;
	const LEVEL_ALERT = Base::LEVEL_ALERT;
	const LEVEL_DEBUG = Base::LEVEL_DEBUG;

	public static function __callStatic($method, $args) {
		static $link;
		if (empty($link)) {
			$class = (empty($_SERVER['LOLI']['log']['type']) ? 'File' : $_SERVER['LOLI']['log']['type']);
			if ($class[0] !== '\\') {
				$class = __NAMESPACE__ . '\Log\\' . $class;
			}
			$link = new $class(empty($_SERVER['LOLI']['log']) ? [] : $_SERVER['LOLI']['log']);
		}
		return $link->$method(...$args);
	}
}