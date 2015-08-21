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
class_exists('Loli\Log\Base') || exit;
class Log{
	const LEVEL_ACCESS = Base::LEVEL_ACCESS;
	const LEVEL_NOTICE = Base::LEVEL_NOTICE;
	const LEVEL_WARNING = Base::LEVEL_WARNING;
	const LEVEL_ERROR = Base::LEVEL_ERROR;
	const LEVEL_ALERT = Base::LEVEL_ALERT;
	const LEVEL_DEBUG = Base::LEVEL_DEBUG;

	public static function __callStatic($method, $params) {
		static $link;
		if (empty($link)) {
			$class = __NAMESPACE__ . '\Log\\' . (empty($_SERVER['LOLI']['LOG']['type']) ? 'File' : $_SERVER['LOLI']['LOG']['type']);
			$args = empty($_SERVER['LOLI']['LOG']) ? [] : $_SERVER['LOLI']['LOG'];
			$link = new $class($args);
		}
		return call_user_func_array([$link, $method], $params);
	}
}